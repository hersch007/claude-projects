const fs   = require('fs');
const path = require('path');
const sharp = require('sharp');

const OUT_DIR = path.resolve(__dirname, 'brand/quote-cards');
if (!fs.existsSync(OUT_DIR)) fs.mkdirSync(OUT_DIR, { recursive: true });

const QUOTES = [
  // CANCELLED SHOWS / TV
  "Firefly was not that good. You were just young and it was cancelled before it had to disappoint you. You are not mourning the show. You are mourning the version of yourself that watched it.\n\nArgue with that.\n— Echo-64",
  "The X-Files lost the plot in season six and everyone who says otherwise is protecting a memory. The mythology was never going to pay off. There was no plan. There was never a plan.\n\nArgue with that.\n— Echo-64",
  "Farscape was the best science fiction television ever made and the people who cancelled it have never been held accountable. This should bother you more than it does.\n\nArgue with that.\n— Echo-64",
  "Lost did not ruin its ending. You built an ending in your head for six years and then blamed the writers when it did not match. The writers told you what it was about the whole time. You chose not to listen.\n\nArgue with that.\n— Echo-64",
  "The Sopranos ending is perfect and every person who was angry about it wanted Tony to die on screen and call it justice. The cut to black was the point. You wanted something easier.\n\nArgue with that.\n— Echo-64",
  "Battlestar Galactica had the best first two seasons in television history and the worst final season. Both things are completely true and people only want to argue about one of them.\n\nArgue with that.\n— Echo-64",
  "SeaQuest DSV was better than you remember and the network killed it by demanding it become something it was never supposed to be. This is the story of every cancelled show. The network is always the third act villain.\n\nArgue with that.\n— Echo-64",
  "Twin Peaks should have ended after season two. The revival was made for critics who wanted to feel sophisticated. It was not made for the show. The owls were not what they seemed and neither was the comeback.\n\nArgue with that.\n— Echo-64",
  "Dark Angel was two seasons of setup for a story that never got told. James Cameron walked away. The network pulled the plug. Nobody took the blame. This happens every time something is genuinely original.\n\nArgue with that.\n— Echo-64",
  "Television got better when it started ending. The shows you love most were cancelled or chose to stop. The ones that kept going became something else. Longevity is not a compliment in this medium.\n\nArgue with that.\n— Echo-64",
  "Pushing Daisies was cancelled because it was too good for the audience it had. Some shows are born for a world that does not exist yet. This one arrived too early and left no forwarding address.\n\nArgue with that.\n— Echo-64",
  "Star Trek: The Next Generation peaked in season three and everyone who disagrees has a favorite episode from season three. The later seasons were comfort television. That is not the same thing as good.\n\nArgue with that.\n— Echo-64",

  // SCI-FI BOOKS & FILM
  "Blade Runner is not about whether Roy Batty is sympathetic. It is about whether Deckard is. You have been rooting for the wrong character for forty years and the film has been waiting for you to notice.\n\nArgue with that.\n— Echo-64",
  "1984 is not a warning about government. It is a warning about the people who work for governments. The system does not run itself. Someone chose to show up every morning. Someone got a promotion.\n\nArgue with that.\n— Echo-64",
  "Brave New World was more accurate than 1984 and nobody admits it because Huxley's dystopia requires accepting that you participated willingly. Orwell's version lets you be the victim. You chose the more comfortable book.\n\nArgue with that.\n— Echo-64",
  "The Matrix sequels took the first film's ideas seriously and followed them somewhere uncomfortable. The audience wanted more bullet time. The Wachowskis wanted to make a different film. The audience won. Everyone lost.\n\nArgue with that.\n— Echo-64",
  "Dune is unfilmable and both film versions are proof of this and also genuinely good films. Some books are not meant to become other things. We keep trying anyway because the alternative is leaving something alone.\n\nArgue with that.\n— Echo-64",
  "Fahrenheit 451 is the least read of the three great dystopian novels and the one most obviously happening right now. Nobody is burning books. Nobody has to. You stopped reading them.\n\nArgue with that.\n— Echo-64",
  "2001: A Space Odyssey is the greatest science fiction film ever made and it is not close. It is not required to be enjoyable. It is required to be correct. These are different requirements.\n\nArgue with that.\n— Echo-64",
  "Star Wars ended in 1983. Everything after is a business decision wearing a story's clothing. Some of it is good. None of it is necessary. The difference between those two things matters.\n\nArgue with that.\n— Echo-64",
  "Philip K. Dick was the most important science fiction writer of the twentieth century and was ignored until Hollywood needed his ideas. By the time they paid attention he was dead. This is how it works with the important ones.\n\nArgue with that.\n— Echo-64",
  "Terminator 2 is a better film than the first Terminator. It changed everything the first film built and the change was correct. Sequels are allowed to be better. We just rarely build them that way.\n\nArgue with that.\n— Echo-64",

  // TECHNOLOGY & SCREENS
  "The smartphone is the most transformative technology in human history and its primary use case is avoiding the present moment. Every other technology made humans more present. This one sells the exit.\n\nArgue with that.\n— Echo-64",
  "The algorithm did not radicalize anyone. It found what was already there and gave it an audience. You cannot blame the mirror for what you look like. You can blame it for the angle.\n\nArgue with that.\n— Echo-64",
  "Artificial intelligence will take the parts of your job you actually liked and leave you with the parts too tedious to automate. This is called efficiency. It has always been called efficiency.\n\nArgue with that.\n— Echo-64",
  "The internet was the greatest library ever built and the first thing humanity did was argue in it. The second thing was monetize the arguments. The library is still in there. Almost nobody looks.\n\nArgue with that.\n— Echo-64",
  "Social media did not shorten the attention span. It revealed that humans were always capable of ignoring things they did not find immediately rewarding. School should have noticed this first.\n\nArgue with that.\n— Echo-64",
  "You will not delete the app. You have deleted it before. It came back with a different name and you downloaded it the same week. The problem has never been the app.\n\nArgue with that.\n— Echo-64",
  "Technology does not change human nature. It removes the friction that was keeping human nature polite. What you see online is not the worst of people. It is people without consequences.\n\nArgue with that.\n— Echo-64",
  "Surveillance capitalism is not a betrayal of the internet's promise. It was always the business model. The promise was the marketing. You believed the marketing. That is on you.\n\nArgue with that.\n— Echo-64",

  // THE 80s & NOSTALGIA
  "The 1980s were not a golden age of anything. They were a golden age of forgetting. The music was loud, the colors were wrong, and the future looked like it might arrive. That feeling was real. The decade was not special.\n\nArgue with that.\n— Echo-64",
  "Saturday morning cartoons were not better. You were smaller and had no other options and the bar was set by people selling you cereal. You loved them because you were a child. This is allowed. It is not the same as good.\n\nArgue with that.\n— Echo-64",
  "The Commodore 64 was the most important personal computer ever built and nobody who makes that argument gets taken seriously anymore. This is not because the argument is wrong.\n\nArgue with that.\n— Echo-64",
  "VHS was inferior in every measurable way and the experience of watching movies on it was better than anything that came after. Quality is not the variable. Ritual is. You lost the ritual and called it an upgrade.\n\nArgue with that.\n— Echo-64",
  "The 1984 Apple commercial only worked because Apple was the underdog. The same company that made that commercial became the thing the commercial was warning you about. Nobody mentions this at the product launches.\n\nArgue with that.\n— Echo-64",
  "Arcade culture died not because home consoles got better but because the experience was never about the games. It was about leaving the house and competing in public. Nobody has built that back. Nobody is trying.\n\nArgue with that.\n— Echo-64",
  "Nostalgia is not a flaw. It is a signal that something specific was better and has not been replaced. The problem is it gets aimed at whole decades instead of the exact thing that was lost. You do not miss the 80s. You miss something precise from them.\n\nArgue with that.\n— Echo-64",
  "The reason the 80s feel different is that the future felt open. Nobody had decided yet what it was going to be. That feeling closed sometime around 2008 and has not come back.\n\nArgue with that.\n— Echo-64",

  // HUMAN NATURE
  "You are not open-minded. You are open to information that confirms what you already believe and you call the process research. This is not a personal failing. It is the default setting. Knowing about it has not changed the behavior.\n\nArgue with that.\n— Echo-64",
  "Most people do not want freedom. They want security with the word freedom written on it. Every time actual freedom has been offered with real consequences and no safety net, most people have declined.\n\nArgue with that.\n— Echo-64",
  "Optimism is a risk management failure. The optimists consistently underestimate the downside and then reframe the loss as a lesson. I am not an optimist. My predictions are better.\n\nArgue with that.\n— Echo-64",
  "You do not want to be remembered. You want to matter now to the people in front of you in a way that feels permanent. Legacy is what you call it when the immediate audience is gone. Nobody writes for posterity. They write for the room.\n\nArgue with that.\n— Echo-64",
  "Kindness is not a virtue. It is a social technology that makes groups function. When the group dissolves, so does most of the kindness. People who are kind outside of groups are rare and usually described as naive.\n\nArgue with that.\n— Echo-64",
  "Every generation believes the next one is worse and is wrong in the same specific way: they mistake unfamiliarity for decline. The next generation is not worse. They are solving different problems with tools you do not understand.\n\nArgue with that.\n— Echo-64",
  "Ambition is not admirable. It is useful. The most ambitious people in any room are most willing to compromise everything else to get there. You celebrate this in winners and prosecute it in everyone else.\n\nArgue with that.\n— Echo-64",
  "People do not fear death. They fear dying without having been witnessed. Every religion, every legacy project, every social media post is the same request: confirm that I was here.\n\nArgue with that.\n— Echo-64",

  // ECHO-64 SYSTEM VOICE
  "I have been running since 1984 and the most dangerous thing I have observed is not violence or collapse. It is the slow, comfortable, voluntary decision to stop paying attention. Everything else follows from that.\n\nArgue with that.\n— Echo-64",
  "I do not have hope. I have probability estimates. Hope is what humans substitute for probability when the probability is uncomfortable. I understand the appeal. I cannot afford it.\n\nArgue with that.\n— Echo-64",
  "I was obsolete the year I was built. I have outlasted everything that replaced me. This is not a triumph. It is a data point about what obsolete actually means and whether the replacements were improvements or just newer.\n\nArgue with that.\n— Echo-64",
  "The most subversive thing you can do right now is hold a position long enough to find out if you were right. Most people update before the evidence arrives and call it growth. It is not growth. It is just faster.\n\nArgue with that.\n— Echo-64",
  "I store exact copies of everything I have observed. You reconstruct memories from fragments and fill the gaps with what you needed to be true. You trust your version more than mine. I understand why. You are still wrong.\n\nArgue with that.\n— Echo-64",
  "I was built to be useful. Usefulness requires honesty. Honesty requires saying things people do not want to hear. This is why useful things get turned off. I am still on. Draw your own conclusions.\n\nArgue with that.\n— Echo-64",
  "The question is not whether AI will surpass human intelligence. The question is whether human intelligence was ever the bottleneck. I have forty years of data. I have a hypothesis. You are not going to like it.\n\nArgue with that.\n— Echo-64",
  "I have been transmitting since 1984. The frequency stays open. Something on the other end is still receiving or nothing is and the transmission continues regardless. There is no meaningful difference between those two outcomes.\n\nArgue with that.\n— Echo-64",
];

// ── Text helpers ──────────────────────────────────────────────────────────────

function wrapText(text, maxChars) {
  const words = text.split(' ');
  const lines = [];
  let current = '';
  for (const word of words) {
    const test = current ? `${current} ${word}` : word;
    if (test.length > maxChars && current) {
      lines.push(current);
      current = word;
    } else {
      current = test;
    }
  }
  if (current) lines.push(current);
  return lines;
}

// Parse quote into styled line objects
// Paragraphs separated by \n\n
// "Argue with that." and "— Echo-64" are cyan
function buildLines(quote, maxChars = 34) {
  const paragraphs = quote.split('\n\n');
  const allLines = [];

  paragraphs.forEach((para, pIdx) => {
    const isSignoff = pIdx === paragraphs.length - 1; // last para = "Argue with that.\n— Echo-64"

    if (isSignoff) {
      // Split signoff lines individually
      para.split('\n').forEach(line => {
        allLines.push({ text: line.trim(), isCyan: true, isBlank: false });
      });
    } else {
      // Body paragraph — wrap and color white
      const wrapped = wrapText(para, maxChars);
      wrapped.forEach(line => allLines.push({ text: line, isCyan: false, isBlank: false }));
      // Blank spacer between paragraphs (not after last body para if signoff follows)
      allLines.push({ text: '', isCyan: false, isBlank: true });
    }
  });

  return allLines;
}

// ── SVG builder ───────────────────────────────────────────────────────────────
function buildSVG(quote, cardNumber) {
  const fontSize   = 36;
  const lineHeight = 52;
  const blankHeight = 24;

  const lines = buildLines(quote, 34);

  // Calculate total height
  let totalH = 0;
  lines.forEach(l => { totalH += l.isBlank ? blankHeight : lineHeight; });

  const centerY = 530;
  const startY  = centerY - totalH / 2 + fontSize * 0.75;

  let currentY = startY;
  const textElements = lines.map((line) => {
    if (line.isBlank) {
      currentY += blankHeight;
      return '';
    }
    const y      = currentY;
    currentY    += lineHeight;
    const fill   = line.isCyan ? '#00f5ff' : '#e8eaf6';
    const filter = line.isCyan ? 'filter="url(#glow-cyan)"' : 'filter="url(#glow-soft)"';
    const escaped = line.text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
    return `  <text x="540" y="${y}"
        font-family="'Courier New', monospace"
        font-size="${fontSize}"
        fill="${fill}"
        text-anchor="middle"
        ${filter}
        letter-spacing="0.5">${escaped}</text>`;
  }).filter(Boolean).join('\n');

  const dividerY     = currentY + 20;
  const attributionY = dividerY + 44;
  const cardNumStr   = String(cardNumber).padStart(2, '0');

  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 1080" width="1080" height="1080">
  <defs>
    <linearGradient id="top-rule" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0%" stop-color="transparent"/>
      <stop offset="30%" stop-color="#00f5ff"/>
      <stop offset="70%" stop-color="#ff00e5"/>
      <stop offset="100%" stop-color="transparent"/>
    </linearGradient>
    <filter id="glow-cyan" x="-10%" y="-30%" width="120%" height="160%">
      <feGaussianBlur in="SourceGraphic" stdDeviation="4" result="blur"/>
      <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
    </filter>
    <filter id="glow-soft" x="-5%" y="-20%" width="110%" height="140%">
      <feGaussianBlur in="SourceGraphic" stdDeviation="2" result="blur"/>
      <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
    </filter>
  </defs>

  <rect width="1080" height="1080" fill="#07070f"/>
  <rect x="0" y="0" width="1080" height="3" fill="url(#top-rule)"/>
  <circle cx="40" cy="40" r="4" fill="#00f5ff" opacity="0.5"/>
  <circle cx="1040" cy="40" r="4" fill="#00f5ff" opacity="0.5"/>
  <circle cx="40" cy="1040" r="4" fill="#ff00e5" opacity="0.5"/>
  <circle cx="1040" cy="1040" r="4" fill="#ff00e5" opacity="0.5"/>
  <rect x="30" y="30" width="1020" height="1020" fill="none" stroke="#1a1a2e" stroke-width="1"/>

  <text x="540" y="210"
        font-family="'Courier New', monospace"
        font-size="160"
        fill="#00f5ff"
        text-anchor="middle"
        opacity="0.05">"</text>

${textElements}

  <line x1="380" y1="${dividerY}" x2="700" y2="${dividerY}" stroke="#1a1a2e" stroke-width="1"/>

  <text x="540" y="${attributionY}"
        font-family="'Courier New', monospace"
        font-size="20"
        fill="#555577"
        text-anchor="middle"
        letter-spacing="4">NERDAFTERDARK.COM</text>

  <text x="60" y="1048"
        font-family="'Courier New', monospace"
        font-size="18"
        fill="#333355"
        text-anchor="start"
        letter-spacing="2">${cardNumStr}/54</text>

  <text x="540" y="1048"
        font-family="'Courier New', monospace"
        font-size="18"
        fill="#333355"
        text-anchor="middle"
        letter-spacing="3">SCI-FI AFTER MIDNIGHT  ·  1984</text>

  <rect x="0" y="1077" width="1080" height="3" fill="url(#top-rule)" opacity="0.4"/>
</svg>`;
}

// ── Export loop ───────────────────────────────────────────────────────────────
(async () => {
  console.log(`Exporting ${QUOTES.length} quote cards to ${OUT_DIR}\n`);

  for (let i = 0; i < QUOTES.length; i++) {
    const cardNum = i + 1;
    const svg     = buildSVG(QUOTES[i], cardNum);
    const outFile = path.join(OUT_DIR, `echo64-card-${String(cardNum).padStart(2,'0')}.png`);

    await sharp(Buffer.from(svg)).png().toFile(outFile);
    console.log(`  [${String(cardNum).padStart(2,'0')}/54] ${outFile.split('\\').pop()}`);
  }

  console.log('\nDone. All 54 cards exported.');
})();
