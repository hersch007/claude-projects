<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

final class Echo64_Shows_CPT {

    const POST_TYPE = 'echo64_show';

    private static ?Echo64_Shows_CPT $instance = null;

    private static array $questions = [
        '%s — why was it cancelled and why does it still hurt?',
        'Make the case for %s. Convince me it deserved ten more seasons.',
        'Which network executive cancelled %s and do they still have a job?',
        '%s got cancelled. Tell me everything that went wrong.',
        'Defend %s like your life depends on it.',
        'What would %s look like if it had never been cancelled?',
        'Rank the crimes committed against %s by the network that cancelled it.',
        'Give me the full post-mortem on %s. What killed it?',
        'If %s came back today, would it survive? Be honest.',
        'The people who cancelled %s — where are they now and do they feel bad?',
    ];

    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init',                [ $this, 'register_post_type' ] );
        add_action( 'init',                [ $this, 'maybe_migrate' ], 20 );
        add_action( 'init',                [ $this, 'maybe_upgrade_content' ], 25 );
        add_action( 'init',                [ $this, 'maybe_upgrade_content_v3' ], 26 );
        add_action( 'init',                [ $this, 'maybe_upgrade_tsf_titles' ], 27 );
        add_action( 'init',                [ $this, 'maybe_upgrade_tsf_titles_v2' ], 28 );
        add_action( 'add_meta_boxes',      [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post',           [ $this, 'save_meta' ] );
        add_filter( 'template_include',    [ $this, 'single_template' ] );
        add_action( 'wp_head',             [ $this, 'render_seo_meta' ], 1 );
        add_filter( 'pre_get_document_title', [ $this, 'filter_show_title' ], 999 );
        add_filter( 'document_title_parts',   [ $this, 'filter_document_title' ], 999 );
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns',       [ $this, 'admin_columns' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'render_admin_column' ], 10, 2 );
    }

    public function register_post_type(): void {
        register_post_type( self::POST_TYPE, [
            'labels' => [
                'name'               => 'Cancelled Shows',
                'singular_name'      => 'Cancelled Show',
                'add_new_item'       => 'Add Cancelled Show',
                'edit_item'          => 'Edit Cancelled Show',
                'all_items'          => 'Cancelled Shows',
                'menu_name'          => 'Cancelled Shows',
            ],
            'public'        => true,
            'has_archive'   => false,
            'show_in_rest'  => true,
            'menu_icon'     => 'dashicons-desktop',
            'supports'      => [ 'title', 'editor' ],
            'rewrite'       => [ 'slug' => 'cancelled-shows', 'with_front' => false ],
        ] );
    }

    public static function build_question( string $title ): string {
        $template = self::$questions[ array_rand( self::$questions ) ];
        return sprintf( $template, $title );
    }

    // ── One-time migration of the legacy hardcoded array into real posts ──────

    public function maybe_migrate(): void {
        if ( get_option( 'echo64_shows_migrated' ) === 'yes' ) {
            return;
        }

        $shows = $this->legacy_shows();

        foreach ( $shows as $show ) {
            $existing = get_page_by_title( $show['title'], OBJECT, self::POST_TYPE );
            if ( $existing ) continue;

            $post_id = wp_insert_post( [
                'post_type'    => self::POST_TYPE,
                'post_title'   => $show['title'],
                'post_content' => $show['verdict'],
                'post_status'  => 'publish',
            ] );

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_echo64_network', $show['network'] );
                update_post_meta( $post_id, '_echo64_year',    $show['year'] );
            }
        }

        update_option( 'echo64_shows_migrated', 'yes' );
        flush_rewrite_rules();
    }

    // ── Upgrade: replace one-liner verdicts with full page copy for top shows ──

    public function maybe_upgrade_content(): void {
        if ( get_option( 'echo64_shows_content_v2' ) === 'yes' ) return;

        $upgrades = [
            'Firefly' => "Fourteen episodes. One complete universe — characters, language, mythology, moral philosophy — all of it built and destroyed in less time than most shows take to find their footing. Fox scheduled it against the World Series, aired the episodes out of order, and pulled it before it could build an audience. The executives called it underperforming. Forty years of television history would like a word.\n\nThe fans built a movement loud enough to fund a feature film. Serenity arrived in 2005 and answered some of what was left unfinished. It was not enough. It was never going to be enough. Some cancellations are inconveniences. Firefly was a crime.\n\nJoss Whedon has said he still doesn't know how the story ends. That's the part that's unforgivable — not the cancellation itself, but the waste of something that was already complete in every way that mattered except the one the network controlled.",

            'Freaks and Geeks' => "Eighteen episodes produced. Twelve aired before NBC pulled it. The complete season eventually aired on Fox Family — a network that had nothing to do with making it — just to let the remaining episodes exist somewhere. That is how little NBC understood what it had.\n\nThe cast includes James Franco, Seth Rogen, Jason Segel, Linda Cardellini, and John Francis Daley. The creator is Judd Apatow. The showrunner is Paul Feig. Every name on that list became a major force in Hollywood within ten years of cancellation. NBC passed.\n\nIt is the most honest show about high school ever made. Not aspirational. Not dramatic. Just accurate in the way that made uncomfortable viewing for anyone who remembered what fifteen actually felt like. That's probably why it was cancelled.",

            'Deadwood' => "Three seasons of the finest dialogue ever written for television. David Milch constructed a world where every character — from the protagonist to the extras drinking in the background — spoke with specificity and weight. Then HBO cancelled it on a cliffhanger because the contracts ran out and nobody could get the numbers to work.\n\nThe show finally got a movie in 2019. It was good. It was not enough. The story promised across three seasons and a dozen character arcs deserved more than a two-hour wrap-up twelve years later. Al Swearengen deserved a proper ending. The audience deserved one too.\n\nThe cancellation of Deadwood is the clearest example on television of a network signing off on a story without committing to letting it be told. The obligation runs both ways. HBO forgot that.",

            'Pushing Daisies' => "Bryan Fuller created a show where a pie-maker could bring the dead back to life with a touch — but a second touch would kill them again permanently, and if he held them alive for more than a minute, someone nearby would die in their place. The rules were precise. The world built around them was impeccable. The colour palette alone was worth watching.\n\nThe writers' strike hit mid-season one. By the time production resumed, the audience had scattered and ABC never properly rebuilt it. Season two got nine episodes before cancellation. The story was not finished. It has never been finished.\n\nA musical revival has been rumoured for years. It hasn't happened. In the meantime, Pushing Daisies remains the most visually distinctive show in network television history that never got to complete its story.",

            'My So-Called Life' => "One season. Nineteen episodes. The most honest portrait of adolescence American television had produced at that point — not the after-school special version, not the aspirational version, but the actual experience of being fifteen and not knowing how anything works including yourself.\n\nClaire Danes was extraordinary. The writing didn't condescend. Characters lied, misunderstood each other, acted from confusion rather than clear motivation. It was realistic in a way that felt radical for 1995 and still feels rare now.\n\nABC cancelled it because the ratings were low. The ratings were low because it aired opposite Seinfeld and Home Improvement. That context tends to be left out of the official story. The full sentence matters.",

            'Sense8' => "Eight characters. Eight cities. A production budget large enough to actually film in all of them. Netflix cancelled it after two seasons and the response was loud enough that they funded a two-hour finale — a thing that almost never happens. The finale happened. It was not enough of an ending.\n\nThe Wachowskis built something that couldn't be summarised in a pitch. That is both why it was brilliant and why Netflix eventually said no. Streaming services need to be able to describe what a show is in one sentence for an algorithm. Sense8 resisted that description completely.\n\nAt its best, it was about what people feel when they are genuinely connected to each other. At its worst, it was still better than most things Netflix kept running.",

            'Mindhunter' => "Two seasons of David Fincher working at the peak of his abilities — the architecture of serial killer psychology, 1970s FBI procedural, and character study executed with the patience of someone who knows exactly what they're doing. Netflix called it 'on pause' in 2020. David Fincher has since moved on to other projects. Pause is a polite word for cancelled.\n\nThe show was expensive. The show was slow. The show required the audience to pay attention across multiple episodes before it paid off. None of these are flaws. All of them are reasons streaming services cancel things.\n\nHolden Ford was about to encounter BTK. The show knew that. The audience knew that. Netflix cancelled it anyway. That is the sentence that still doesn't make sense.",

            'The OA' => "The ending of season two is one of the most genuinely strange and committed pieces of television ever made. Netflix cancelled it two weeks later. The cast and crew found out the same way the fans did — through a press release. Brit Marling was writing season three.\n\nThe OA asked the audience to believe in things that were difficult to believe in. Interdimensional travel. Movements that open portals. A protagonist who may or may not be reliable. Most shows hedge. The OA didn't hedge. That was the point and that was probably the problem.\n\nPeople held vigils outside Netflix's offices. That is not a metaphor or an exaggeration. People stood outside a building to mourn a television show. That's what The OA was.",

            'Agent Carter' => "Two seasons. Hayley Atwell gave Peggy Carter more depth and wit than the character had in any of the films she appeared in. The show was smart about period detail, interesting about gender and institutional power, and — crucially — fun in a way the wider MCU increasingly was not.\n\nABC cancelled it to make room for a Marvel show that was cancelled the following year. The replacement had worse ratings and worse reviews. This is the complete sentence and it contains everything you need to know about how network television makes decisions.\n\nThere were plans for a third season set in Los Angeles. They got as far as a setting. The story ended in a place that assumed continuation — which is the worst way for anything to end.",

            'Carnivàle' => "Two seasons into a planned six-season story. Nick Stahl and Clancy Brown building toward a confrontation between light and dark that the show had been constructing with extraordinary patience across thirty episodes. HBO looked at the budget and the ratings and said the story was finished.\n\nIt wasn't finished. It was approximately one third finished. Creator Daniel Knauf had the full arc mapped — six seasons, a complete story about good and evil set during the Great Depression and its aftermath. Two seasons is not a complete story. Two seasons is a very expensive introduction.\n\nThe cancellation of Carnivàle is one of the clearest examples of a network greenlighting a story without committing to letting it be told. The scale of the ambition was visible from the first episode. HBO said yes to the ambition, then cancelled the execution.",
        ];

        foreach ( $upgrades as $title => $content ) {
            $post = get_page_by_title( $title, OBJECT, self::POST_TYPE );
            if ( ! $post ) continue;
            wp_update_post( [
                'ID'           => $post->ID,
                'post_content' => $content,
            ] );
        }

        update_option( 'echo64_shows_content_v2', 'yes' );
    }

    // ── Upgrade v3: full copy for remaining shows ─────────────────────────────

    public function maybe_upgrade_content_v3(): void {
        if ( get_option( 'echo64_shows_content_v3' ) === 'yes' ) return;

        $upgrades = [
            'Farscape' => "A man accidentally shot through a wormhole finds himself on the far side of the galaxy aboard a living ship with a crew of fugitives. The world-building was unlike anything else on television. The puppetry, courtesy of the Jim Henson Company, was used to create characters with more depth than most shows gave their human leads.\n\nThe Sci-Fi Channel cancelled it after the season four cliffhanger — a two-part finale that ended on one of the most brutal unresolved moments in the history of the medium. The fan response was immediate and sustained enough to produce a miniseries, Farscape: The Peacekeeper Wars, which resolved the major arc. It was not the same as the seasons that were promised.\n\nFarscape was proof that science fiction television could be genuinely alien — in tone, in character, in the assumptions it made about its audience. The cancellation proved that proof was not enough.",

            'Dark Angel' => "James Cameron and Charles H. Eglee created a post-pulse America where infrastructure had collapsed and a genetically engineered super-soldier was hiding in Seattle while working as a bicycle messenger. Jessica Alba in the role she should have been defined by. Fox cancelled it after two seasons.\n\nSeason two lost the thread somewhat — the mythology expanded faster than the character work could support it — but the bones of the show were extraordinary and the world deserved more time to breathe. The cancellation came before the story reached anything close to a resolution.\n\nWhat's notable about Dark Angel now is how clearly it predicted the surveillance state, the gig economy, and the class stratification that followed genuine infrastructure collapse. It was set in 2019. It was not entirely wrong.",

            'Terminator: The Sarah Connor Chronicles' => "The second season of The Sarah Connor Chronicles is some of the most ambitious science fiction television ever produced on a network budget. The show understood that time travel stories are about grief as much as paradox, and it constructed a mythology that honoured the films while doing something genuinely new with the material.\n\nFox aired it opposite Monday Night Football and then cancelled it on a cliffhanger that sent John Connor forward in time into a future where his own legend had already been written. The final image of the season is one of the finest in the franchise. It was also the last image the show ever produced.\n\nThe cancellation came the same week Terminator Salvation opened in cinemas. The film made significantly more money and was significantly worse. This is not a coincidence. This is just what happens.",

            'Dollhouse' => "Joss Whedon's most structurally ambitious project: a facility that imprints human beings with temporary personalities and skills and rents them out to wealthy clients. The ethical horror was built into the premise from episode one. Fox gave it two seasons, which was approximately one season more than they understood it.\n\nThe first season found its footing slowly. The second season, once it was clear cancellation was coming, accelerated into something extraordinary — the episode Epitaph One, produced for DVD and not aired, is one of the finest single episodes of science fiction television made in the 2000s.\n\nDollhouse is the show that most clearly demonstrates what Joss Whedon could do when given enough time to reach the dark places the premise was always heading toward. Two seasons was almost enough to get there. Almost.",

            'Stargate Universe' => "After two lighter series in the Stargate franchise, Universe attempted something different: a dark, character-driven drama about a group of people stranded aboard an ancient ship billions of light years from Earth with no way home. The show took the franchise seriously in a way its predecessors hadn't always managed.\n\nThe Syfy Channel cancelled it after two seasons, again on a cliffhanger, again without resolution. The pattern with Syfy and science fiction is consistent enough to be called a policy. The show had just found its rhythm when the decision came.\n\nStargate Universe was the franchise's attempt to grow up. The cancellation suggests the audience for that version of the show was smaller than the audience for the lighter version. That may be true. It doesn't make the loss less real.",

            'Caprica' => "A prequel to Battlestar Galactica set fifty-eight years before the Cylon war, focused on the corporate and family dynamics that led to the creation of artificial life. Less action, more philosophy. The kind of science fiction that asks what it means to be a person rather than what it means to shoot at things.\n\nSyfy cancelled it after one season, airing the remaining episodes out of order months after the initial run had concluded. By the time the finale aired, the show had already been cancelled and the audience had already scattered. The ending was rushed into something that almost worked.\n\nCaprica was doing something genuinely difficult — building mythology slowly, in a world the audience already knew the fate of. That structural challenge required patience from the network. Syfy provided approximately none.",

            'FlashForward' => "Every person on Earth loses consciousness for two minutes and seventeen seconds simultaneously, and in that time, sees a vision of their life six months in the future. The premise was extraordinary. ABC had the bones of a genuine mythology series that could have run for years.\n\nThe execution stumbled. The show front-loaded its mystery without building sufficient character investment, and by the time the mythology became compelling, the ratings had already dropped. ABC cancelled it before the flash forward date arrived, which meant the audience never saw whether the visions came true.\n\nFlashForward is a lesson in how to waste a premise. The source novel by Robert J. Sawyer was more focused and more satisfying. The adaptation had more resources and less clarity about what story it was actually telling.",

            'The Event' => "NBC called it the next Lost. The comparison was a marketing strategy and a death sentence simultaneously. Lost worked because it was strange and patient and willing to withhold answers for seasons at a time. The Event was willing to withhold answers but not willing to be strange, which left it in a middle ground that satisfied neither mystery fans nor general viewers.\n\nThe mythology involved aliens living among us, a government conspiracy, and a protagonist searching for his missing girlfriend. The elements were familiar. The execution was competent. Competent mystery television in the post-Lost landscape was not sufficient.\n\nNBC cancelled it after one season. The ending resolved nothing. The show had positioned itself for a long-form story and never got the time to tell it. The next Lost, it wasn't. But it deserved more than twenty-two episodes to find out what it actually was.",

            'Almost Human' => "A detective and his android partner in near-future Los Angeles. Karl Urban doing some of the best work of his career. Michael Ealy creating a character — the empathetic synthetic human Dorian — that was more interesting than most human leads on television at the time. Fox aired the episodes out of order and then cancelled it.\n\nThe episode ordering mattered. The character development that was built across the season was scrambled by Fox's scheduling decisions, which meant the emotional beats landed incorrectly or not at all. The show that aired was not the show that was made.\n\nAlmost Human is the clearest example of a network actively undermining a show's chances and then citing its performance as the reason for cancellation. The episodes, watched in production order, tell a coherent and compelling story. Fox made sure most people never saw that version.",

            'Revolution' => "A world fifteen years after all electricity stopped working permanently. Governments collapsed. Militias rose. Former suburban families learned to survive. The premise was strong enough that NBC ordered it to series immediately after the pilot, and strong enough that J.J. Abrams was attached as producer.\n\nThe first season leaned heavily on action and mythology. The second season, moved to a different timeslot and struggling for ratings, attempted to course-correct into something darker and more character-driven. By that point the audience had already made its decision. NBC cancelled it after season two on another unresolved cliffhanger.\n\nRevolution had the misfortune of being ambitious science fiction on a network that wanted procedural comfort. The world it built was more interesting than the stories it initially chose to tell in that world. By the time the stories caught up, the cancellation was already coming.",

            'Alcatraz' => "Every prisoner and guard who disappeared from Alcatraz in 1963 has been reappearing in the present day, not having aged a day. The mystery was legitimate. J.J. Abrams was again involved. Rebecca Madsen and Detective Soto made for a compelling investigative team working cases that connected to a larger conspiracy that the show never got to reveal.\n\nFox gave it one season. Thirteen episodes. The mythology was constructed for years of storytelling and Fox provided months. The finale set up the next chapter of the story and there was no next chapter.\n\nAlcatraz is what happens when a network orders a long-form mystery series without committing to the long form. The premise required patience from the audience and from the broadcaster. Fox provided neither.",

            'Terra Nova' => "A family travels back to the Cretaceous period to help establish a colony as humanity's last chance at survival. Dinosaurs. Time travel. Steven Spielberg as executive producer. Fox cancelled it after one season despite the enormous production budget, because the enormous production budget was precisely the problem — the show cost more to make than it could possibly earn back.\n\nThe world that was built across thirteen episodes was genuinely impressive. The mythology was just beginning to develop when the cancellation came. The Sixers, the conspiracy, the strange markings in the rocks — none of it was resolved.\n\nTerra Nova is the rare cancellation that was probably correct from a business perspective and still genuinely disappointing from an audience perspective. The show deserved to exist. It probably shouldn't have cost what it cost to make it.",

            'Constantine' => "Matt Ryan as John Constantine is one of the finest pieces of casting in DC television history. The character — the working-class occult detective with a guilt complex and a cigarette permanently behind his ear — came to life in a way that the 2005 Keanu Reeves film had not managed. NBC gave it thirteen episodes.\n\nThe show was cancelled because the ratings didn't support the budget. It was moved to a different timeslot twice. It was given no time to find its audience before the decision was made. The cruel footnote is that Matt Ryan was eventually brought back as Constantine in the Arrowverse, where he became a fan favourite for years — proof that the character worked, proof that NBC gave up too quickly.\n\nThirteen episodes is not enough to build a mythology. NBC asked the impossible and then cited the results as justification.",

            'Forever' => "A man who cannot die has been alive for two hundred years and is now working as a medical examiner in New York. Ioan Gruffudd in the lead role, doing work precise and funny enough to carry the show's high concept. The procedural frame was standard. The character at the centre of it was not.\n\nABC gave it one season of twenty-two episodes, which is more than most of these shows received and still not enough to resolve the mythology that was being constructed around the character's immortality. The villain introduced in the back half of the season was a compelling mirror — another immortal with different conclusions about what endless life means.\n\nForever was comfortable television with an uncomfortable premise at its centre. The cancellation was quiet and unsurprising and still unnecessary. The character had more story left to tell.",

            'Limitless' => "The CBS television series did something the film it was based on didn't fully manage: it gave its protagonist a moral framework and a consistent supporting cast and let the enhanced-intelligence premise breathe across twenty-two episodes. Jake McDorman as Brian Finch was more interesting than Bradley Cooper's film version, largely because the show gave him time.\n\nCBS cancelled it after one season. The ratings were not poor by most standards — they were poor by CBS standards, which are considerably higher than the rest of television. A show that would have been a success on NBC or ABC was cancelled because it underperformed relative to NCIS.\n\nLimitless is the cleanest example of a show being cancelled not because it was bad but because it was on the wrong network. The context of the cancellation matters as much as the decision itself.",

            'Timeless' => "Cancelled twice. Uncancelled once. The second cancellation came after NBC aired a two-part finale specifically designed to wrap up the story after the renewal looked uncertain — and then renewed it anyway — and then cancelled it again after a second season that ended on another cliffhanger.\n\nThe show followed a historian, a soldier, and a scientist chasing a time-travelling organisation through American history. The historical episode structure was clever. The character work was genuine. The fan response to each cancellation was organised, vocal, and ultimately insufficient.\n\nTimeless earned its audience twice and lost its network's confidence twice. The fans who campaigned for its return are not wrong to feel that the contract was broken. You don't bring a show back to cancel it again without owing the audience something you cannot give them.",

            'Defiance' => "A science fiction series tied to a video game — the same story, the same world, running simultaneously across two different media. The game launched with the show. The game was shut down in 2021. The show was cancelled in 2015 after three seasons on Syfy, on a cliffhanger, because Syfy.\n\nThe world of Defiance was genuinely interesting — Earth reshaped by terraforming technology, multiple alien species coexisting in an uneasy frontier settlement. The politics were more complex than most genre television attempted. The execution was uneven but improving across three seasons.\n\nThe simultaneous cancellation of a show and its companion game meant an entire fictional universe was shut down at once. That's a particular kind of loss — not just a story cut short but an entire world sealed off.",

            'Continuum' => "A future cop accidentally travels back to 2012 while pursuing a group of terrorist time travellers. The show, produced in Canada for Showcase, ran for four seasons — which sounds like a success until you know that the fourth season was six episodes long, granted as a mercy by the network to wrap up the story after cancellation.\n\nShowrunner Simon Barry used those six episodes to deliver something close to a satisfying ending. It's worth acknowledging how rarely that happens and how much it changes the legacy of a cancelled show. Continuum ended. It didn't stop mid-sentence.\n\nThe show's central question — whether corporations or governments are the greater threat to individual freedom — got more relevant as it went on. The answer it arrived at was more nuanced than the question suggested. Six episodes was just enough space to land it.",

            'Dark Matter' => "Six people wake up on a derelict spaceship with no memories of who they are or how they got there. The premise was clean. The execution, from the creators of Stargate, built a crew dynamic that improved steadily across three seasons as the backstories were revealed and the mythology expanded.\n\nSyfy cancelled it after season three's cliffhanger — a pattern so consistent across their science fiction programming that it should be written into the contracts. The season three finale ended on an event of such magnitude that resolution across a fourth season would have required significant storytelling ambition. That story was never told.\n\nDark Matter is the Syfy cancellation that the fans still talk about with the most heat. The crew had become genuinely compelling. The world had depth. The cancellation was a decision made before the story was anywhere near finished.",

            'Lodge 49' => "A former surfer finds a lodge of a dissolving fraternal order in Long Beach, California, and in doing so finds something that might be community, might be magic, or might just be the stories people tell themselves to keep going. The show resisted plot in favour of atmosphere and feeling in a way that was singular in American television.\n\nAMC cancelled it during a pandemic after two seasons. The show had not attracted large audiences because it was not making conventional demands of them. It asked for patience and attention in return for something that was difficult to describe and easy to feel.\n\nLodge 49 is the kind of show that people discover years after cancellation and cannot believe existed on basic cable. It is gentle and strange and deeply human. AMC cancelled it and kept running other things.",

            'The Get Down' => "Baz Luhrmann's love letter to the Bronx in the 1970s — the birth of hip-hop, the collapse of New York City's infrastructure, the survival of creativity in conditions designed to prevent it. The production values were extraordinary. The music was extraordinary. Netflix cancelled it after one part-season.\n\nThe show was expensive and the viewing numbers were not sufficient to justify the cost. The timing was also difficult — the production was troubled, delivery was late, and the relationship between Netflix and the production had frayed by the time the decision was made.\n\nWhat exists of The Get Down is more alive than most of what replaced it on the platform. The cancellation erased a story about a community and a moment in cultural history that deserved more than six episodes and a six-episode extension.",

            'Patriot' => "An intelligence officer is sent to Luxembourg to influence a pipeline deal, under cover as a folk singer, while managing severe PTSD, an unsanctioned assassination, and an office full of people who have no idea who he is. The funniest show about espionage ever made, and possibly the saddest.\n\nAmazon cancelled it quietly after two seasons. No announcement. The show simply disappeared from the schedule. Creator Steve Conrad has said he had a third season planned. The character of John Tavner, played by Michael Dorman, remains one of the most fully realised protagonists in streaming television history.\n\nPatriot found a small audience who loved it completely and a large audience who never found it. Amazon's cancellation deprived the small audience of an ending and confirmed what the large audience never had to care about.",

            'Rubicon' => "An AMC conspiracy thriller about an intelligence analyst who believes he has uncovered evidence of a shadow organisation manipulating world events from inside the intelligence community. The show moved slowly, deliberately, and with absolute confidence in what it was doing. AMC cancelled it after one season because Breaking Bad was coming and Rubicon wasn't Breaking Bad.\n\nThe show required the audience to pay sustained attention across thirteen episodes to a plot that rewarded patience with density and dread. It was the smartest thing on television for exactly one season. The finale resolved some threads and left others open in ways that assumed continuation.\n\nRubicon is the cancelled show that most feels like a document of something real. The paranoia has aged well. The cancellation, in retrospect, was a loss that AMC probably does not think about and the audience probably still does.",

            'Max Headroom' => "Set twenty minutes into the future — a future of maximum television penetration, corporate media monopoly, and an AI construct named Max Headroom who appeared between programmes to comment on what he had seen. ABC aired it in 1987 and 1988. We are now living in it.\n\nThe show was genuinely ahead of its time in the literal sense: the satire was so accurate that the future it described is now the present. Blipverts. Network wars. The commodification of human attention. The show named things that didn't have names yet.\n\nABC cancelled it after two seasons. The network was not interested in the critique it was funding. Max Headroom would have had commentary on that decision. He would have been correct.",

            'Jericho' => "A nuclear attack destroys twenty-three American cities. A small town in Kansas tries to survive what comes after. The show handled the premise with more intelligence and restraint than most post-apocalyptic fiction — focusing on community, resource management, and the political vacuum that catastrophe creates.\n\nCBS cancelled it after season one. The fans sent forty thousand pounds of nuts to CBS in protest — a reference to a line of dialogue from the finale. The campaign worked. CBS renewed it for a shortened second season of seven episodes. Then cancelled it again.\n\nJericho's fan campaign is the model for every subsequent save-our-show effort in the streaming era. It worked once. It didn't work twice. The show ended without resolution, but it ended knowing its audience would have followed it anywhere.",

            'Kings' => "A modern retelling of the biblical story of King David set in an alternate present where the kingdom of Gilboa is a contemporary monarchy. Ian McShane as King Silas Benjamin, doing the finest work of his career. NBC aired it on Saturdays and then cancelled it after one season.\n\nThe show was extraordinary — operatic in scale, precise in dialogue, committed to its allegory in a way that made it genuinely strange and genuinely memorable. The ratings were catastrophic because Saturday nights on NBC are where shows go to not be watched.\n\nKings was a mistake in scheduling that became a casualty of what scheduling produces. Put it on a Tuesday and it runs for years. Put it opposite college football on a Saturday and cite the numbers. That is the full story of why Kings was cancelled.",

            'Journeyman' => "A San Francisco journalist begins involuntarily travelling through time, repeatedly disappearing from his present life to complete missions in the past — missions whose purpose is rarely clear until after they are accomplished. The domestic stakes — a marriage under impossible strain, a career in pieces — were handled with more care than most genre television affords.\n\nNBC gave it thirteen episodes and cancelled it when the writers' strike made renewal uncertain. The show had just found its footing. The mythology was deepening in ways the early episodes had only hinted at.\n\nJourneyman was quiet and human and sad in the way time travel stories should be but rarely are. The cancellation came before the show had the chance to become what the back half of season one suggested it was becoming.",

            'Drive' => "Nathan Fillion in a cross-country illegal street race, each driver coerced into participation by a shadowy organisation holding something they love hostage. Fox aired four episodes and cancelled it. The remaining two produced episodes were eventually aired back-to-back on a Friday night to fulfil contractual obligations.\n\nThe show was Fox cancelling Nathan Fillion twice — once in 2002 with Firefly, once in 2007 with Drive. At some point the pattern stops being coincidence.\n\nDrive had the bones of something genuinely exciting: a mythology-heavy chase narrative with a strong ensemble and a lead actor who understood exactly what the show needed from him. Fox did not give those bones time to become anything more.",

            'The Tick' => "The second live-action television adaptation of Ben Edlund's comic — this time from Amazon, with Peter Serafinowicz as The Tick and Griffin Newman as Arthur, the accountant who reluctantly becomes a superhero. The show was the best superhero comedy since The Incredibles and was cancelled before enough people noticed.\n\nAmazon gave it two seasons. The second season was better than the first. The cancellation came without warning and without explanation, which is standard practice for streaming services and still feels like a breach of something.\n\nThe Tick worked because it understood that the funniest thing about superheroes is that someone has to be the person who did not ask for this and cannot leave. Arthur is that person. The show found genuine emotion inside the absurdism. Amazon cancelled it anyway.",

            'Travelers' => "Consciousness from the future is transmitted into the bodies of present-day people at the moment of their death, and these travellers are tasked with preventing the apocalypse that their future contains. Three seasons of careful, internally consistent science fiction that took its own rules seriously and played fair with its audience.\n\nNetflix cancelled it after season three — a season that had been produced for Canadian broadcast on Showcase and only licensed by Netflix rather than produced by them. The distinction mattered contractually. The cancellation came without warning and without a concluding season.\n\nTravelers is the Netflix cancellation that most clearly demonstrates the problem with licensing versus producing: the platform had no financial obligation to continue a story it didn't create. The audience that found it on Netflix had no way of knowing the distinction until the cancellation explained it.",

            'Blood Drive' => "A grindhouse road race set in a post-peak-oil 1999 where the cars run on human blood and the prize money is enough to retire on. Syfy made this. It is exactly as strange as it sounds and considerably more intelligent than the description suggests.\n\nSyfy cancelled it after one season. The show was too weird, too violent, and too specific in its pleasures to attract a mainstream audience. The audience it did attract loved it without reservation. That audience was not large enough.\n\nBlood Drive is the clearest example of a show that knew exactly what it was and did it completely. There is something to be said for that kind of integrity, even when the integrity produces a single season of television that ends without resolution.",

            'Daybreak' => "Post-apocalyptic high school — the adults all turned into ghoulies and the teenagers have reorganised into factions based on their former cliques. The show was self-aware and strange and faster-paced than most Netflix comedies, with a protagonist who addressed the camera directly and a mythology that got genuinely interesting around episode six.\n\nNetflix cancelled it before episode six of a potential second season existed. One season of ten episodes, ending without resolution. The show had the energy of something that was just figuring out what it wanted to be when the cancellation came.\n\nDaybreak is one of several Netflix shows cancelled after one season that had built something worth continuing. The platform's decision-making criteria for cancellation remain opaque. The results are consistent.",

            'Studio 60 on the Sunset Strip' => "Aaron Sorkin writing about television with the same velocity and intelligence he brought to The West Wing, but about a Saturday Night Live-style sketch comedy show instead of the White House. Matthew Perry and Bradley Whitford as the showrunner and head writer doing their finest work. NBC cancelled it after one season.\n\nThe show was accused of taking itself too seriously. The accusation was probably accurate and almost certainly irrelevant — Sorkin takes things seriously, that's the job, and the results are usually worth it. The workplace dynamics, the creative arguments, the behind-the-scenes politics of live television were handled with genuine specificity.\n\nStudio 60 failed by the standards of The West Wing. By the standards of most other television, it was extraordinary. NBC cancelled it and Sorkin went on to The Newsroom. The loss was real even if the successor was imperfect.",

            'Wonderfalls' => "A Brown University graduate working in a Niagara Falls gift shop begins receiving instructions from animal-shaped objects — figurines, logos, toys — that compel her to take actions whose consequences she cannot predict. Bryan Fuller again. The show was cancelled by Fox after four episodes aired out of order.\n\nThirteen episodes were produced. Four aired. The remaining nine were released on DVD and are considerably better than the four that aired, because the four that aired were not the four that should have aired first. The show's character development was scrambled by the ordering.\n\nWonderfalls is a show most people have never seen that most people who have seen it consider one of the finest single seasons of television produced in the 2000s. Fox made it and then made it impossible to see. That's a particular kind of waste.",

            'Brimstone' => "A dead detective makes a deal with the Devil: recapture 113 escaped souls from Hell and earn his way back to life. Peter Horton as Detective Zeke Stone. John Glover as the Devil, doing the finest devil performance in television history with the possible exception of Mark Pellegrino's Lucifer. Fox cancelled it at Christmas.\n\nThe show ran for thirteen episodes in 1998 and 1999. The mythology was built for a long run. The relationship between Stone and the Devil — adversarial, philosophical, occasionally warm — was the finest thing in it and needed more time to develop.\n\nBrimstone is the cancelled show most likely to be described as ahead of its time. The supernatural procedural format it pioneered became extremely common in the decade after it was cancelled. The show that invented the template didn't get to benefit from its own influence.",

            'Surface' => "Sea creatures of unknown origin appearing along coastlines. A teenage boy who bonds with one of them. A government cover-up. NBC gave it one season of fifteen episodes and cancelled it without resolving the mystery of what the creatures were or what they wanted.\n\nThe show was ambitious in its world-building and uneven in its execution. The mythology was larger than the episodes had space to fully contain. The cancellation came at a point when the story was just beginning to reveal what it had been building toward.\n\nSurface is not the finest show on this list. It is here because it built something genuine and never got to finish it, and that is the only qualification required.",

            'The Middleman' => "A young artist is recruited to work for a secret organisation that fights supernatural and extraterrestrial threats, partnered with a square-jawed hero from a 1950s adventure serial who has somehow survived to the present. Twelve episodes. ABC Family. The most fun twelve episodes of television produced in 2008.\n\nThe show was adapted from a comic series and retained the comic's pace and density — more jokes per minute, more references per scene, more plot per episode than most network television dares. It was cancelled because the ratings were insufficient for the budget.\n\nThe Middleman got a table read of the unproduced season two finale at Comic-Con in 2009, which the cast and original crew performed for an audience that turned out to say goodbye. That's the kind of show it was — the kind people show up to mourn properly.",
        ];

        foreach ( $upgrades as $title => $content ) {
            $post = get_page_by_title( $title, OBJECT, self::POST_TYPE );
            if ( ! $post ) continue;
            wp_update_post( [
                'ID'           => $post->ID,
                'post_content' => $content,
            ] );
        }

        update_option( 'echo64_shows_content_v3', 'yes' );
    }

    private function legacy_shows(): array {
        return [
            [ 'title' => 'Firefly',                              'network' => 'Fox',            'year' => '2002', 'verdict' => 'Fourteen episodes. A complete universe. The executives are still employed.' ],
            [ 'title' => 'Pushing Daisies',                      'network' => 'ABC',            'year' => '2009', 'verdict' => 'A show about death that was more alive than anything else on television.' ],
            [ 'title' => 'Freaks and Geeks',                     'network' => 'NBC',            'year' => '2000', 'verdict' => 'Every cast member became a star. The network still cancelled it.' ],
            [ 'title' => 'Carnivàle',                            'network' => 'HBO',            'year' => '2005', 'verdict' => 'Two seasons into a six-season story. We will never know the ending.' ],
            [ 'title' => 'Deadwood',                             'network' => 'HBO',            'year' => '2006', 'verdict' => 'The best dialogue ever written for television. Ended mid-sentence.' ],
            [ 'title' => 'Farscape',                             'network' => 'Sci-Fi Channel', 'year' => '2003', 'verdict' => 'Cancelled the week after a cliffhanger. The fans screamed loud enough for a miniseries.' ],
            [ 'title' => 'My So-Called Life',                    'network' => 'ABC',            'year' => '1995', 'verdict' => 'One perfect season. Claire Danes was never better. ABC disagreed.' ],
            [ 'title' => 'Dark Angel',                           'network' => 'Fox',            'year' => '2002', 'verdict' => 'James Cameron created it. Fox cancelled it. Fox was wrong.' ],
            [ 'title' => 'Terminator: The Sarah Connor Chronicles', 'network' => 'Fox',         'year' => '2009', 'verdict' => 'Season two was extraordinary. Ended on the best cliffhanger Fox ever cancelled.' ],
            [ 'title' => 'Dollhouse',                            'network' => 'Fox',            'year' => '2010', 'verdict' => 'Joss Whedon\'s most ambitious idea. Fox gave it two seasons to confuse people.' ],
            [ 'title' => 'Sense8',                               'network' => 'Netflix',        'year' => '2018', 'verdict' => 'Eight characters. Eight cities. One of the most expensive cancellations in streaming history.' ],
            [ 'title' => 'The OA',                               'network' => 'Netflix',        'year' => '2019', 'verdict' => 'People cried in the street when this was cancelled. That is not a metaphor.' ],
            [ 'title' => 'Mindhunter',                           'network' => 'Netflix',        'year' => '2020', 'verdict' => 'David Fincher\'s best work since Zodiac. Netflix put it on indefinite hold. Same thing.' ],
            [ 'title' => 'Dark Matter',                          'network' => 'Syfy',           'year' => '2017', 'verdict' => 'Three seasons in, the plot was finally paying off. Syfy cancelled it that week.' ],
            [ 'title' => 'Caprica',                              'network' => 'Syfy',           'year' => '2011', 'verdict' => 'A prequel to Battlestar Galactica that was better than most of its competition.' ],
            [ 'title' => 'FlashForward',                         'network' => 'ABC',            'year' => '2010', 'verdict' => 'Everyone saw six months into the future. ABC saw only one season.' ],
            [ 'title' => 'The Event',                            'network' => 'NBC',            'year' => '2011', 'verdict' => 'They called it the next Lost. NBC cancelled it before anyone could find out.' ],
            [ 'title' => 'Almost Human',                         'network' => 'Fox',            'year' => '2014', 'verdict' => 'Fox aired the episodes out of order then cancelled it.' ],
            [ 'title' => 'Revolution',                           'network' => 'NBC',            'year' => '2014', 'verdict' => 'A world without electricity had more power than NBC gave it credit for.' ],
            [ 'title' => 'Alcatraz',                             'network' => 'Fox',            'year' => '2012', 'verdict' => 'Time-travelling prisoners. One season. Fox ran out of patience before the mystery did.' ],
            [ 'title' => 'Terra Nova',                           'network' => 'Fox',            'year' => '2012', 'verdict' => 'Dinosaurs and time travel and Fox killed it before the second season breathed.' ],
            [ 'title' => 'Constantine',                          'network' => 'NBC',            'year' => '2015', 'verdict' => 'The perfect casting. The perfect tone. Thirteen episodes and NBC called it done.' ],
            [ 'title' => 'Forever',                              'network' => 'ABC',            'year' => '2015', 'verdict' => 'A man who cannot die, cancelled before his story had one.' ],
            [ 'title' => 'Agent Carter',                         'network' => 'ABC',            'year' => '2016', 'verdict' => 'Peggy Carter deserved more than two seasons. ABC disagreed with everyone.' ],
            [ 'title' => 'Limitless',                            'network' => 'CBS',            'year' => '2016', 'verdict' => 'The pill gave you infinite intelligence. CBS gave the show one season.' ],
            [ 'title' => 'Timeless',                             'network' => 'NBC',            'year' => '2018', 'verdict' => 'Cancelled twice. Uncancelled once. Cancelled for good. The timeline did not survive.' ],
            [ 'title' => 'Defiance',                             'network' => 'Syfy',           'year' => '2015', 'verdict' => 'A show tied to a video game. Both were cancelled. Neither deserved it.' ],
            [ 'title' => 'Continuum',                            'network' => 'Showcase',       'year' => '2015', 'verdict' => 'Got a proper ending only because the showrunner begged for six extra episodes.' ],
            [ 'title' => 'Stargate Universe',                    'network' => 'Syfy',           'year' => '2011', 'verdict' => 'The darkest and most ambitious Stargate. Cancelled on a cliffhanger. Obviously.' ],
            [ 'title' => 'Rubicon',                              'network' => 'AMC',            'year' => '2011', 'verdict' => 'The smartest conspiracy show ever made. AMC cancelled it for something louder.' ],
            [ 'title' => 'Lodge 49',                             'network' => 'AMC',            'year' => '2020', 'verdict' => 'A gentle, strange masterpiece about belonging. AMC cancelled it during a pandemic.' ],
            [ 'title' => 'The Get Down',                         'network' => 'Netflix',        'year' => '2017', 'verdict' => 'Baz Luhrmann\'s love letter to the Bronx in the 1970s. One season.' ],
            [ 'title' => 'Patriot',                              'network' => 'Amazon',         'year' => '2018', 'verdict' => 'The funniest show about espionage ever made. Amazon cancelled it quietly.' ],
            [ 'title' => 'Max Headroom',                         'network' => 'ABC',            'year' => '1988', 'verdict' => 'Set twenty minutes into the future. We are living in it now. They cancelled it in 1988.' ],
            [ 'title' => 'Jericho',                              'network' => 'CBS',            'year' => '2008', 'verdict' => 'Fans sent 40,000 pounds of nuts to CBS to save it. Got one more season. Then nothing.' ],
            [ 'title' => 'Kings',                                'network' => 'NBC',            'year' => '2009', 'verdict' => 'A modern retelling of King David with Ian McShane. NBC buried it on Saturdays.' ],
            [ 'title' => 'Journeyman',                           'network' => 'NBC',            'year' => '2008', 'verdict' => 'Time travel done quietly and beautifully. NBC saw the ratings and said no.' ],
            [ 'title' => 'Drive',                                'network' => 'Fox',            'year' => '2007', 'verdict' => 'Nathan Fillion in a cross-country race thriller. Fox cancelled it after four episodes.' ],
            [ 'title' => 'The Tick',                             'network' => 'Amazon',         'year' => '2019', 'verdict' => 'The best superhero comedy since The Incredibles. Amazon cancelled it in year two.' ],
            [ 'title' => 'Travelers',                            'network' => 'Netflix',        'year' => '2018', 'verdict' => 'Three perfect seasons of time travel ethics. Netflix ended it without warning.' ],
            [ 'title' => 'Blood Drive',                          'network' => 'Syfy',           'year' => '2017', 'verdict' => 'A grindhouse road race with cars fuelled by human blood. Too weird to survive.' ],
            [ 'title' => 'Daybreak',                             'network' => 'Netflix',        'year' => '2019', 'verdict' => 'Post-apocalyptic high school. Clever and strange and gone before episode ten.' ],
            [ 'title' => 'Studio 60 on the Sunset Strip',        'network' => 'NBC',            'year' => '2007', 'verdict' => 'Aaron Sorkin at his most Aaron Sorkin. NBC wanted something cheaper.' ],
            [ 'title' => 'Wonderfalls',                          'network' => 'Fox',            'year' => '2004', 'verdict' => 'A gift shop employee gets instructions from animal figurines. Fox aired four episodes.' ],
            [ 'title' => 'Brimstone',                            'network' => 'Fox',            'year' => '1999', 'verdict' => 'A dead cop hunts escaped souls from Hell. Fox cancelled it at Christmas.' ],
            [ 'title' => 'Surface',                              'network' => 'NBC',            'year' => '2006', 'verdict' => 'Sea monsters and government cover-ups. One season. The ocean kept its secrets.' ],
            [ 'title' => 'The Middleman',                        'network' => 'ABC Family',     'year' => '2008', 'verdict' => 'The most fun twelve episodes of television in 2008. Twelve episodes total.' ],
        ];
    }

    // ── Upgrade v4: set TSF _genesis_title for all show posts ─────────────────

    public function maybe_upgrade_tsf_titles(): void {
        if ( get_option( 'echo64_shows_tsf_titles_v1' ) === 'yes' ) return;

        $posts = get_posts( [
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ] );

        foreach ( $posts as $post_id ) {
            $network = get_post_meta( $post_id, '_echo64_network', true );
            $year    = get_post_meta( $post_id, '_echo64_year', true );
            $show    = get_the_title( $post_id );

            if ( $network && $year ) {
                $tsf_title = sprintf( '%s (%s, %s) — Why It Was Cancelled', $show, $network, $year );
            } else {
                $tsf_title = $show . ' — Cancelled Too Soon';
            }

            update_post_meta( $post_id, '_genesis_title', $tsf_title );
        }

        update_option( 'echo64_shows_tsf_titles_v1', 'yes' );
    }

    // ── Upgrade v4b: suppress TSF site-name suffix + fix over-60-char titles ─

    public function maybe_upgrade_tsf_titles_v2(): void {
        if ( get_option( 'echo64_shows_tsf_titles_v2' ) === 'yes' ) return;

        // Suppress TSF site name addition on all show posts so titles stay clean.
        // Shorten the two shows whose titles exceed 60 chars even without the suffix.
        $overrides = [
            'Terminator: The Sarah Connor Chronicles' => 'Sarah Connor Chronicles (Fox, 2009) — Why It Was Cancelled',
            'Studio 60 on the Sunset Strip'           => 'Studio 60 on the Sunset Strip (NBC) — Why It Was Cancelled',
        ];

        $posts = get_posts( [
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ] );

        foreach ( $posts as $post_id ) {
            // Remove site-name addition (TSF reads this meta key to decide).
            update_post_meta( $post_id, '_genesis_title_additions_select', 'remove' );

            // Override title for shows that are still too long without suffix.
            $show = get_the_title( $post_id );
            if ( isset( $overrides[ $show ] ) ) {
                update_post_meta( $post_id, '_genesis_title', $overrides[ $show ] );
            }
        }

        update_option( 'echo64_shows_tsf_titles_v2', 'yes' );
    }

    // ── Admin meta box ──────────────────────────────────────────────────────

    public function add_meta_boxes(): void {
        add_meta_box(
            'echo64_show_details',
            'Show Details',
            [ $this, 'render_meta_box' ],
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    public function render_meta_box( WP_Post $post ): void {
        wp_nonce_field( 'echo64_show_meta', 'echo64_show_meta_nonce' );
        $network = get_post_meta( $post->ID, '_echo64_network', true );
        $year    = get_post_meta( $post->ID, '_echo64_year', true );
        ?>
        <p>
            <label for="echo64_network"><strong>Network</strong></label><br>
            <input type="text" id="echo64_network" name="echo64_network" class="widefat" value="<?php echo esc_attr( $network ); ?>" />
        </p>
        <p>
            <label for="echo64_year"><strong>Year(s)</strong></label><br>
            <input type="text" id="echo64_year" name="echo64_year" class="widefat" value="<?php echo esc_attr( $year ); ?>" />
        </p>
        <p class="description">The editor content above is used as the "verdict" — keep it to 1–2 sentences.</p>
        <?php
    }

    public function save_meta( int $post_id ): void {
        if ( ! isset( $_POST['echo64_show_meta_nonce'] ) || ! wp_verify_nonce( $_POST['echo64_show_meta_nonce'], 'echo64_show_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( get_post_type( $post_id ) !== self::POST_TYPE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        if ( isset( $_POST['echo64_network'] ) ) {
            update_post_meta( $post_id, '_echo64_network', sanitize_text_field( $_POST['echo64_network'] ) );
        }
        if ( isset( $_POST['echo64_year'] ) ) {
            update_post_meta( $post_id, '_echo64_year', sanitize_text_field( $_POST['echo64_year'] ) );
        }
    }

    public function admin_columns( array $columns ): array {
        $columns['echo64_network'] = 'Network';
        $columns['echo64_year']    = 'Year';
        return $columns;
    }

    public function render_admin_column( string $column, int $post_id ): void {
        if ( $column === 'echo64_network' ) {
            echo esc_html( get_post_meta( $post_id, '_echo64_network', true ) );
        }
        if ( $column === 'echo64_year' ) {
            echo esc_html( get_post_meta( $post_id, '_echo64_year', true ) );
        }
    }

    // ── Single show template ───────────────────────────────────────────────

    public function single_template( string $template ): string {
        if ( is_singular( self::POST_TYPE ) ) {
            $custom = ECHO64_PLUGIN_DIR . 'templates/single-show.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        return $template;
    }

    // ── SEO meta for single show pages ─────────────────────────────────────

    public function filter_show_title( string $title ): string {
        if ( ! is_singular( self::POST_TYPE ) ) return $title;
        $post    = get_post();
        $network = $post ? get_post_meta( $post->ID, '_echo64_network', true ) : '';
        $year    = $post ? get_post_meta( $post->ID, '_echo64_year', true ) : '';
        if ( $network && $year ) {
            return sprintf( '%s (%s, %s) — Why It Was Cancelled | Echo-64 · NerdAfterDark', get_the_title(), $network, $year );
        }
        return get_the_title() . ' — Cancelled Too Soon | NerdAfterDark';
    }

    public function render_seo_meta(): void {
        if ( ! is_singular( self::POST_TYPE ) ) return;
        $post = get_post();
        if ( ! $post ) return;

        $network   = get_post_meta( $post->ID, '_echo64_network', true );
        $year      = get_post_meta( $post->ID, '_echo64_year', true );
        $verdict   = wp_strip_all_tags( $post->post_content );
        $title     = $post->post_title;

        $meta = [
            'title'       => sprintf( '%s (%s, %s) — Why It Was Cancelled | Echo-64', $title, $network, $year ),
            'description' => $verdict . ' Argue the case with Echo-64, a Commodore 64 with forty years of grievances.',
            'og_title'    => sprintf( '%s — Cancelled Too Soon', $title ),
            'og_desc'     => $verdict,
            'og_image'    => ECHO64_PLUGIN_URL . 'assets/images/echo-64-avatar.png',
            'og_type'     => 'article',
        ];

        $canonical = esc_url( get_permalink( $post ) );
        ?>
<!-- Echo-64 Show SEO -->
<meta property="og:type"        content="<?php echo esc_attr( $meta['og_type'] ); ?>" />
<meta property="og:url"         content="<?php echo $canonical; ?>" />
<meta property="og:title"       content="<?php echo esc_attr( $meta['og_title'] ); ?>" />
<meta property="og:description" content="<?php echo esc_attr( $meta['og_desc'] ); ?>" />
<meta property="og:image"       content="<?php echo esc_url( $meta['og_image'] ); ?>" />
<meta property="og:site_name"   content="NerdAfterDark" />

<meta name="twitter:card"        content="summary" />
<meta name="twitter:site"        content="@meetecho64" />
<meta name="twitter:title"       content="<?php echo esc_attr( $meta['og_title'] ); ?>" />
<meta name="twitter:description" content="<?php echo esc_attr( $meta['og_desc'] ); ?>" />
<meta name="twitter:image"       content="<?php echo esc_url( $meta['og_image'] ); ?>" />
<?php
    }

    public function filter_document_title( array $title ): array {
        if ( ! is_singular( self::POST_TYPE ) ) return $title;
        $post = get_post();
        if ( ! $post ) return $title;

        $network = get_post_meta( $post->ID, '_echo64_network', true );
        $year    = get_post_meta( $post->ID, '_echo64_year', true );

        $title['title'] = sprintf( '%s (%s, %s) — Why It Was Cancelled', $post->post_title, $network, $year );
        $title['site']  = 'Echo-64 · NerdAfterDark';

        return $title;
    }
}
