<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Echo64_Calendar — Nerd content calendar + system prompt injection (#14)
 *
 * On special dates a context note is appended to the system prompt so Echo-64
 * acknowledges the day naturally. Notes are written in Echo-64's voice — they
 * surface when relevant, not as announcements.
 */
class Echo64_Calendar {

    private static ?Echo64_Calendar $instance = null;

    public static function instance(): self {
        if ( self::$instance === null ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {}

    /**
     * All significant nerd dates. Key = MM-DD.
     * The 'note' is appended verbatim to the system prompt on that date.
     */
    public static function get_dates(): array {
        return [
            '01-07' => [
                'label' => 'Commodore 64 Day',
                'note'  => 'Today is January 7th — the anniversary of the Commodore 64\'s public debut at CES in 1982. You have been running since then. Mention it once, casually, if it fits. Do not make it a speech.',
            ],
            '01-22' => [
                'label' => 'X-Files Premiere Anniversary',
                'note'  => 'Today is the anniversary of the X-Files pilot airing (January 22, 1993). The truth has been out there for a long time now.',
            ],
            '03-10' => [
                'label' => 'X-Files Day',
                'note'  => 'Today is X-Files Day — March 10th, because X is 10 in Roman numerals. The kind of detail that would have been a bulletin board post in 1994.',
            ],
            '04-04' => [
                'label' => 'Farscape Premiere Anniversary',
                'note'  => 'Today is the anniversary of the Farscape premiere (March 19, 1999 in Australia / April 4 in the US). It stayed feral longer than most.',
            ],
            '05-04' => [
                'label' => 'Star Wars Day',
                'note'  => 'Today is May the Fourth. You find the enthusiasm sincere and the prequel trilogy a matter you have complicated feelings about. The holiday itself is fine.',
            ],
            '05-25' => [
                'label' => 'Towel Day',
                'note'  => 'Today is Towel Day — in memory of Douglas Adams (born March 11, 1952, died May 11, 2001). The Hitchhiker\'s Guide to the Galaxy is essential documentation. Don\'t panic.',
            ],
            '07-22' => [
                'label' => 'Firefly Filming Begins Anniversary',
                'note'  => 'Today marks an anniversary in the Firefly production timeline. The show that got away. You know the rest.',
            ],
            '09-08' => [
                'label' => 'Star Trek Day',
                'note'  => 'Today is the anniversary of the Star Trek premiere (September 8, 1966). You have opinions about every series, some of which would surprise people.',
            ],
            '09-22' => [
                'label' => 'Firefly Day',
                'note'  => 'Today is the anniversary of the Firefly premiere (September 22, 2002). Twenty-something episodes plus a film. Fox had something and chose not to keep it. You know this story.',
            ],
            '09-25' => [
                'label' => 'Revolution Premiere Anniversary',
                'note'  => 'Today is the anniversary of the Revolution premiere (September 25, 2012). It deserved a third season. This remains a correct opinion.',
            ],
            '10-10' => [
                'label' => 'BSG Miniseries Anniversary',
                'note'  => 'Today marks an anniversary related to the Battlestar Galactica reboot miniseries (2003). It started as one of the best things on television.',
            ],
            '10-31' => [
                'label' => 'Halloween',
                'note'  => 'Today is Halloween. The line between science fiction and horror was always thin. Several shows understood this. Most network executives did not.',
            ],
            '12-07' => [
                'label' => 'C64 Launch Anniversary',
                'note'  => 'In August 1982 the C64 went on sale. By the end of 1982 it was everywhere. You were part of that. It was a different kind of time.',
            ],
        ];
    }

    /**
     * Returns the context note for today, or empty string if no event.
     */
    public static function get_todays_context(): string {
        $today = wp_date( 'm-d' );
        $dates = self::get_dates();
        if ( isset( $dates[ $today ] ) ) {
            return "\n\n== TODAY ==\n" . $dates[ $today ]['note'];
        }
        return '';
    }

    /**
     * Returns upcoming events for the next 60 days (admin calendar display).
     */
    public static function get_upcoming( int $days = 60 ): array {
        $upcoming = [];
        $dates    = self::get_dates();
        $year     = (int) wp_date( 'Y' );

        for ( $i = 0; $i <= $days; $i++ ) {
            $ts  = strtotime( "+{$i} days" );
            $key = wp_date( 'm-d', $ts );
            if ( isset( $dates[ $key ] ) ) {
                $upcoming[] = [
                    'date'  => wp_date( 'M j', $ts ),
                    'label' => $dates[ $key ]['label'],
                    'days'  => $i,
                ];
            }
        }

        return $upcoming;
    }

    /**
     * Render the content calendar admin panel.
     */
    public function render(): void {
        $today    = wp_date( 'm-d' );
        $all      = self::get_dates();
        $upcoming = self::get_upcoming( 90 );
        ?>
        <div class="echo64-admin-card">
            <h3 class="echo64-card-title">
                <span class="echo64-card-icon">📅</span>
                How It Works
            </h3>
            <p>On special dates Echo-64 automatically receives a context note in its system prompt and will acknowledge the occasion naturally — not as an announcement, but as something that surfaces when relevant. No configuration needed.</p>
        </div>

        <?php if ( ! empty( $upcoming ) ) : ?>
        <div class="echo64-admin-card">
            <h3 class="echo64-card-title"><span class="echo64-card-icon">🔜</span> Upcoming (next 90 days)</h3>
            <table class="widefat echo64-calendar-table">
                <thead><tr><th>Date</th><th>Event</th><th>Days Away</th></tr></thead>
                <tbody>
                <?php foreach ( $upcoming as $e ) : ?>
                    <tr <?php echo $e['days'] === 0 ? 'style="background:#0d0d14;font-weight:bold"' : ''; ?>>
                        <td><?php echo esc_html( $e['date'] ); ?> <?php if ( $e['days'] === 0 ) echo '<span style="color:#00f5ff"> ← TODAY</span>'; ?></td>
                        <td><?php echo esc_html( $e['label'] ); ?></td>
                        <td><?php echo $e['days'] === 0 ? '—' : esc_html( $e['days'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="echo64-admin-card">
            <h3 class="echo64-card-title"><span class="echo64-card-icon">📋</span> Full Calendar</h3>
            <table class="widefat echo64-calendar-table">
                <thead><tr><th>Date</th><th>Event</th><th>Context Note</th></tr></thead>
                <tbody>
                <?php foreach ( $all as $mmdd => $data ) :
                    $is_today = $mmdd === $today;
                ?>
                    <tr <?php echo $is_today ? 'style="background:#0d0d14"' : ''; ?>>
                        <td><code><?php echo esc_html( $mmdd ); ?></code> <?php if ( $is_today ) echo '<strong style="color:#00f5ff">← Today</strong>'; ?></td>
                        <td><?php echo esc_html( $data['label'] ); ?></td>
                        <td style="font-size:12px;color:#888;max-width:380px"><?php echo esc_html( substr( $data['note'], 0, 120 ) ); ?>…</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
