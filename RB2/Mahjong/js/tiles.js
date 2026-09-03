/**
 * tiles.js — Tile definitions, deck builder, and display helpers
 * American Mahjong: 152-tile set
 */
// MJ is the global namespace shared across all script files.
// Using var so it becomes window.MJ without redeclaration errors.
var MJ = window.MJ || {}; // eslint-disable-line no-var
window.MJ = MJ;

// ─── Constants ────────────────────────────────────────────────────────────────

MJ.SUITS    = ['crak', 'bam', 'dot'];
MJ.WINDS    = ['east', 'south', 'west', 'north'];
MJ.DRAGONS  = ['red', 'green', 'white']; // white = Soap
MJ.WIND_SHORT  = { east: 'E', south: 'S', west: 'W', north: 'N' };
MJ.DRAGON_SHORT = { red: 'R', green: 'G', white: 'Soap' };

// ─── Tile class ───────────────────────────────────────────────────────────────

class Tile {
  /**
   * @param {string} suit  crak|bam|dot|wind|dragon|flower|joker
   * @param {string|number|null} value
   * @param {number} id  unique per-deck id
   */
  constructor(suit, value, id) {
    this.suit  = suit;
    this.value = value;
    this.id    = id;
  }

  get key()       { return `${this.suit}:${this.value}`; }
  get isJoker()   { return this.suit === 'joker'; }
  get isFlower()  { return this.suit === 'flower'; }
  get isSuit()    { return MJ.SUITS.includes(this.suit); }
  get isWind()    { return this.suit === 'wind'; }
  get isDragon()  { return this.suit === 'dragon'; }
  get isHonor()   { return this.isWind || this.isDragon; }
  get isNumbered(){ return this.isSuit; }

  /** True when two tiles are the same kind (ignoring id) */
  matches(other) {
    return this.suit === other.suit && this.value === other.value;
  }

  /** Human-readable label */
  label() {
    if (this.isJoker)  return 'Joker';
    if (this.isFlower) {
      const names = ['Plum','Orchid','Chrysanthemum','Bamboo','Spring','Summer','Autumn','Winter'];
      return names[(this.value - 1)] || `Flower ${this.value}`;
    }
    if (this.isWind)   return `${_cap(this.value)} Wind`;
    if (this.isDragon) return `${_cap(this.value)} Dragon`;
    return `${this.value} ${_cap(this.suit)}`;
  }

  clone() { return new Tile(this.suit, this.value, this.id); }
}

MJ.Tile = Tile;

function _cap(s) { return String(s).charAt(0).toUpperCase() + String(s).slice(1); }

// ─── Deck builder ─────────────────────────────────────────────────────────────

let _idGen = 0;
function _tile(suit, value) { return new Tile(suit, value, _idGen++); }

/**
 * Build a complete 152-tile American Mahjong deck.
 * Suits: 108 | Winds: 16 | Dragons: 12 | Flowers: 8 | Jokers: 8
 */
MJ.buildDeck = function () {
  _idGen = 0;
  const tiles = [];

  // 3 suits × 9 × 4 = 108
  for (const suit of MJ.SUITS)
    for (let v = 1; v <= 9; v++)
      for (let i = 0; i < 4; i++) tiles.push(_tile(suit, v));

  // 4 winds × 4 = 16
  for (const w of MJ.WINDS)
    for (let i = 0; i < 4; i++) tiles.push(_tile('wind', w));

  // 3 dragons × 4 = 12
  for (const d of MJ.DRAGONS)
    for (let i = 0; i < 4; i++) tiles.push(_tile('dragon', d));

  // 8 flowers (4 flowers + 4 seasons)
  for (let v = 1; v <= 8; v++) tiles.push(_tile('flower', v));

  // 8 jokers
  for (let i = 0; i < 8; i++) tiles.push(_tile('joker', null));

  return tiles; // 152
};

MJ.shuffle = function (arr) {
  const a = arr.slice();
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
};

// ─── Display helpers ──────────────────────────────────────────────────────────

/**
 * Returns { topText, mainSymbol, bottomText, colorClass, bgClass }
 * used by ui.js to render a tile element.
 */
MJ.tileDisplay = function (tile) {
  if (tile.isJoker) {
    return { topText: '★', mainSymbol: 'JKR', bottomText: '★', colorClass: 'tile-joker', bgClass: '' };
  }
  if (tile.isFlower) {
    const syms = ['🌸','🌺','🌼','🎋','🌱','☀','🍂','❄'];
    const names = ['Plum','Orchid','Mum','Bam','Spring','Sum','Aut','Win'];
    return {
      topText: String(tile.value),
      mainSymbol: syms[tile.value - 1] || '✿',
      bottomText: names[tile.value - 1] || '',
      colorClass: 'tile-flower',
      bgClass: ''
    };
  }
  if (tile.isWind) {
    const kanji = { east:'東', south:'南', west:'西', north:'北' };
    const cls   = { east:'tile-east', south:'tile-south', west:'tile-west', north:'tile-north' };
    return {
      topText: MJ.WIND_SHORT[tile.value],
      mainSymbol: kanji[tile.value],
      bottomText: MJ.WIND_SHORT[tile.value],
      colorClass: cls[tile.value],
      bgClass: ''
    };
  }
  if (tile.isDragon) {
    const info = {
      red:   { sym: '中', short: 'R',    cls: 'tile-red-dragon'   },
      green: { sym: '發', short: 'G',    cls: 'tile-green-dragon' },
      white: { sym: '白', short: 'Soap', cls: 'tile-white-dragon' }
    }[tile.value];
    return { topText: info.short, mainSymbol: info.sym, bottomText: info.short, colorClass: info.cls, bgClass: '' };
  }
  // Numbered suit
  const suitInfo = {
    crak: { kanji: '萬', cls: 'tile-crak' },
    bam:  { kanji: '竹', cls: 'tile-bam'  },
    dot:  { kanji: '●', cls: 'tile-dot'  }
  }[tile.suit];
  return {
    topText: String(tile.value),
    mainSymbol: suitInfo.kanji,
    bottomText: String(tile.value),
    colorClass: suitInfo.cls,
    bgClass: ''
  };
};

/**
 * Sort tiles in a sensible rack order:
 * Craks 1-9 → Bams 1-9 → Dots 1-9 → Winds ESWN → Dragons RGW → Flowers → Jokers
 */
MJ.sortTiles = function (tiles) {
  const order = { crak: 0, bam: 1, dot: 2, wind: 3, dragon: 4, flower: 5, joker: 6 };
  const windOrder  = { east: 0, south: 1, west: 2, north: 3 };
  const dragonOrder = { red: 0, green: 1, white: 2 };

  return tiles.slice().sort((a, b) => {
    const so = order[a.suit] - order[b.suit];
    if (so !== 0) return so;
    if (a.isSuit)   return a.value - b.value;
    if (a.isWind)   return windOrder[a.value] - windOrder[b.value];
    if (a.isDragon) return dragonOrder[a.value] - dragonOrder[b.value];
    if (a.isFlower) return a.value - b.value;
    return 0;
  });
};

/**
 * Count occurrences of each tile key in an array.
 * Returns Map<key, count>
 */
MJ.countTiles = function (tiles) {
  const map = new Map();
  for (const t of tiles) {
    map.set(t.key, (map.get(t.key) || 0) + 1);
  }
  return map;
};

/**
 * Group tiles by key, ignoring jokers.
 * Returns Map<key, Tile[]>
 */
MJ.groupTiles = function (tiles) {
  const map = new Map();
  for (const t of tiles) {
    if (t.isJoker) continue;
    const k = t.key;
    if (!map.has(k)) map.set(k, []);
    map.get(k).push(t);
  }
  return map;
};
