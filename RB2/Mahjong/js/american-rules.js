/**
 * american-rules.js — NMJL-style hand patterns and rule enforcement
 *
 * NOTE: The actual NMJL card changes every year. These hands are
 * representative of typical NMJL categories and structures for 2024-style play.
 * Purchase the current official card at nationalmahjonggleague.org
 */
// american-rules.js — uses global MJ defined by tiles.js
'use strict';

// ─── Rule constants ────────────────────────────────────────────────────────────

MJ.RULES = {
  HAND_SIZE: 14,          // tiles in a complete hand
  DRAW_SIZE: 13,          // tiles dealt initially
  CHARLESTON_PASSES: 3,   // rounds of Charleston
  JOKERS_IN_DECK: 8,
  // Jokers CANNOT be used in pairs or singles (unless a hand explicitly allows it)
  JOKER_CAN_BE_PAIR: false,
  JOKER_CAN_BE_SINGLE: false,
  // You can steal a joker from an exposed set by replacing it with the tile it represents
  JOKER_STEALING: true,
};

// ─── Tile matching utilities ───────────────────────────────────────────────────

/**
 * Try to fill a required set of tiles from available tiles + jokers.
 * required: Array of tile keys e.g. ['crak:1','crak:1','crak:1']
 * available: Map<key, count>
 * jokersLeft: number of jokers available
 * Returns { ok, jokersUsed } or null
 */
function fillSet(required, available, jokersLeft) {
  let jokers = jokersLeft;
  const used = new Map(available); // copy

  for (const key of required) {
    const have = used.get(key) || 0;
    if (have > 0) {
      used.set(key, have - 1);
    } else if (jokers > 0) {
      jokers--;
    } else {
      return null;
    }
  }
  return { ok: true, jokersUsed: jokersLeft - jokers, remaining: used, jokersLeft: jokers };
}

/**
 * Build a tile key string from suit + value
 */
function k(suit, value) { return `${suit}:${value}`; }

// Wind keys
const K_EAST  = k('wind','east');
const K_SOUTH = k('wind','south');
const K_WEST  = k('wind','west');
const K_NORTH = k('wind','north');
const K_RED   = k('dragon','red');
const K_GREEN = k('dragon','green');
const K_WHITE = k('dragon','white');
const K_FLOWER = 'flower'; // any flower

/**
 * Count flower tiles in the tile list (they match any flower key)
 */
function countFlowers(tiles) {
  return tiles.filter(t => t.isFlower).length;
}

/**
 * Check if tiles form a valid set of n identical tiles (allowing jokers).
 * setSize: 2=pair, 3=pung, 4=kong, 5=quint
 */
function checkSet(tiles, key, setSize, allowJoker = true) {
  const exact = tiles.filter(t => t.key === key).length;
  const jokers = allowJoker ? tiles.filter(t => t.isJoker).length : 0;
  return exact + jokers >= setSize;
}

// ─── Hand Pattern Definitions ─────────────────────────────────────────────────
//
// Each pattern has:
//   id:          unique string
//   category:    display group
//   name:        short name
//   description: full notation (card-style)
//   value:       point value
//   check(tiles): returns true if the 14 tiles match the pattern
//
// tiles passed to check() is an Array<Tile> of exactly 14 tiles.
// Jokers within the array are wild (subject to restrictions).

MJ.HAND_PATTERNS = [];

function addHand(def) { MJ.HAND_PATTERNS.push(def); }

// ─── Helper: resolve tiles to a count map + joker count ───────────────────────
function resolve(tiles) {
  const counts = new Map();
  let jokers = 0;
  let flowers = 0;
  for (const t of tiles) {
    if (t.isJoker)  { jokers++;  continue; }
    if (t.isFlower) { flowers++; continue; }
    counts.set(t.key, (counts.get(t.key) || 0) + 1);
  }
  return { counts, jokers, flowers };
}

/**
 * Try to match tiles against a list of required sets.
 * sets: Array of { keys: string[], isPair: bool, isSingle: bool }
 * Returns true if possible.
 */
function matchSets(tiles, sets) {
  const { counts, jokers, flowers } = resolve(tiles);
  let jLeft = jokers;
  let fLeft = flowers;
  const cur = new Map(counts);

  for (const set of sets) {
    if (set.isFlower) {
      if (fLeft < set.keys.length) return false;
      fLeft -= set.keys.length;
      continue;
    }
    const isPairOrSingle = set.isPair || set.isSingle;
    const result = fillSet(set.keys, cur, isPairOrSingle ? 0 : jLeft);
    if (!result) return false;
    // Update state
    for (const [k2, v] of result.remaining) cur.set(k2, v);
    if (!isPairOrSingle) jLeft = result.jokersLeft;
  }
  // All tiles should be accounted for
  const totalUsed = [...cur.values()].reduce((a, b) => a + b, 0);
  return totalUsed === 0 && jLeft === 0 && fLeft === 0;
}

// ─── CATEGORY 1: Consecutive Runs ─────────────────────────────────────────────

for (const suit of MJ.SUITS) {
  addHand({
    id: `consec_123456789_${suit}`,
    category: 'Consecutive Run',
    name: `123-456-789 ${suit}`,
    description: `FF 123 456 789 (all ${suit}s)`,
    value: 25,
    check(tiles) {
      return matchSets(tiles, [
        { keys: [K_FLOWER, K_FLOWER], isFlower: true },
        { keys: [k(suit,1), k(suit,2), k(suit,3)] },
        { keys: [k(suit,4), k(suit,5), k(suit,6)] },
        { keys: [k(suit,7), k(suit,8), k(suit,9)] },
        { keys: [K_GREEN, K_GREEN], isPair: true },
      ]);
    }
  });
}

for (const suit of MJ.SUITS) {
  addHand({
    id: `consec_111222333444_${suit}`,
    category: 'Consecutive Run',
    name: `1111 2222 3333 ${suit}`,
    description: `1111 2222 3333 DD (${suit}s)`,
    value: 30,
    check(tiles) {
      return matchSets(tiles, [
        { keys: [k(suit,1),k(suit,1),k(suit,1),k(suit,1)] },
        { keys: [k(suit,2),k(suit,2),k(suit,2),k(suit,2)] },
        { keys: [k(suit,3),k(suit,3),k(suit,3),k(suit,3)] },
        { keys: [K_RED, K_GREEN], isPair: false },
      ]);
    }
  });
}

// Mixed suit consecutive
addHand({
  id: 'consec_mixed_123',
  category: 'Consecutive Run',
  name: '123 in 3 suits',
  description: 'FF 123C 123B 123D DD',
  value: 30,
  check(tiles) {
    return matchSets(tiles, [
      { keys: [K_FLOWER, K_FLOWER], isFlower: true },
      { keys: [k('crak',1), k('crak',2), k('crak',3)] },
      { keys: [k('bam',1),  k('bam',2),  k('bam',3)]  },
      { keys: [k('dot',1),  k('dot',2),  k('dot',3)]  },
      { keys: [K_RED, K_GREEN], isPair: true },
    ]);
  }
});

// ─── CATEGORY 2: Like Numbers ──────────────────────────────────────────────────

for (let n = 1; n <= 9; n++) {
  addHand({
    id: `like_num_${n}_kong_kong_kong_pair`,
    category: 'Like Numbers',
    name: `${n}s everywhere`,
    description: `${n}${n}${n}${n}C ${n}${n}${n}${n}B ${n}${n}${n}${n}D ${n}${n}`,
    value: 35,
    check(tiles) {
      // 3 kongs + 1 pair of same number, different suits
      // pair can be any suit or dragons
      for (const pairSuit of [...MJ.SUITS, 'wind', 'dragon']) {
        for (const pairVal of (pairSuit === 'wind' ? MJ.WINDS : pairSuit === 'dragon' ? MJ.DRAGONS : [n])) {
          const ok = matchSets(tiles, [
            { keys: [k('crak',n),k('crak',n),k('crak',n),k('crak',n)] },
            { keys: [k('bam',n), k('bam',n), k('bam',n), k('bam',n)]  },
            { keys: [k('dot',n), k('dot',n), k('dot',n), k('dot',n)]  },
            { keys: [k(pairSuit, pairVal), k(pairSuit, pairVal)], isPair: true },
          ]);
          if (ok) return true;
        }
      }
      return false;
    }
  });
}

// ─── CATEGORY 3: 369 ──────────────────────────────────────────────────────────

addHand({
  id: '369_C',
  category: '3-6-9',
  name: '3s 6s 9s Craks + pair',
  description: '3333 6666 9999 DD (Craks)',
  value: 30,
  check(tiles) {
    return (
      matchSets(tiles, [
        { keys: [k('crak',3),k('crak',3),k('crak',3),k('crak',3)] },
        { keys: [k('crak',6),k('crak',6),k('crak',6),k('crak',6)] },
        { keys: [k('crak',9),k('crak',9),k('crak',9),k('crak',9)] },
        { keys: [K_RED, K_RED], isPair: true },
      ]) ||
      matchSets(tiles, [
        { keys: [k('crak',3),k('crak',3),k('crak',3),k('crak',3)] },
        { keys: [k('crak',6),k('crak',6),k('crak',6),k('crak',6)] },
        { keys: [k('crak',9),k('crak',9),k('crak',9),k('crak',9)] },
        { keys: [K_GREEN, K_GREEN], isPair: true },
      ])
    );
  }
});

addHand({
  id: '369_mixed',
  category: '3-6-9',
  name: '3-6-9 mixed suits',
  description: 'FF 333 666 999 333 (mixed suits)',
  value: 25,
  check(tiles) {
    for (const s1 of MJ.SUITS) for (const s2 of MJ.SUITS) for (const s3 of MJ.SUITS) {
      if (s1 === s2 && s2 === s3) continue;
      if (matchSets(tiles, [
        { keys: [K_FLOWER, K_FLOWER], isFlower: true },
        { keys: [k(s1,3), k(s1,3), k(s1,3)] },
        { keys: [k(s2,6), k(s2,6), k(s2,6)] },
        { keys: [k(s3,9), k(s3,9), k(s3,9)] },
        { keys: [K_RED, K_GREEN], isPair: false },
      ])) return true;
    }
    return false;
  }
});

// ─── CATEGORY 4: Winds & Dragons ──────────────────────────────────────────────

addHand({
  id: 'winds_news',
  category: 'Winds & Dragons',
  name: 'NEWS NEWS NEWS NEWS',
  description: 'NNNN EEEE WWWW SSSS',
  value: 50,
  check(tiles) {
    return matchSets(tiles, [
      { keys: [K_NORTH, K_NORTH, K_NORTH, K_NORTH] },
      { keys: [K_EAST,  K_EAST,  K_EAST,  K_EAST]  },
      { keys: [K_WEST,  K_WEST,  K_WEST,  K_WEST]  },
      { keys: [K_SOUTH, K_SOUTH, K_SOUTH, K_SOUTH] },
    ]);
  }
});

addHand({
  id: 'winds_pairs',
  category: 'Winds & Dragons',
  name: 'Wind pairs + Dragon kongs',
  description: 'NN EE WW SS RRRR GGGG',
  value: 35,
  check(tiles) {
    return matchSets(tiles, [
      { keys: [K_NORTH, K_NORTH], isPair: true },
      { keys: [K_EAST,  K_EAST],  isPair: true },
      { keys: [K_WEST,  K_WEST],  isPair: true },
      { keys: [K_SOUTH, K_SOUTH], isPair: true },
      { keys: [K_RED,   K_RED,   K_RED,   K_RED]   },
      { keys: [K_GREEN, K_GREEN, K_GREEN, K_GREEN] },
    ]);
  }
});

addHand({
  id: 'dragons_soup',
  category: 'Winds & Dragons',
  name: 'Dragon soup',
  description: 'FF RRRR GGGG SSSS NN',
  value: 30,
  check(tiles) {
    return matchSets(tiles, [
      { keys: [K_FLOWER, K_FLOWER], isFlower: true },
      { keys: [K_RED,   K_RED,   K_RED,   K_RED]   },
      { keys: [K_GREEN, K_GREEN, K_GREEN, K_GREEN] },
      { keys: [K_WHITE, K_WHITE, K_WHITE, K_WHITE] },
      { keys: [K_NORTH, K_NORTH], isPair: true },
    ]);
  }
});

addHand({
  id: 'winds_dragons_mix',
  category: 'Winds & Dragons',
  name: 'Mixed winds & dragons',
  description: 'EEEE SSSS RRRR GG',
  value: 30,
  check(tiles) {
    for (const [w1, w2] of [[K_EAST, K_SOUTH],[K_EAST, K_WEST],[K_EAST,K_NORTH],
                             [K_SOUTH,K_WEST],[K_SOUTH,K_NORTH],[K_WEST,K_NORTH]]) {
      for (const [d1, dpair] of [[K_RED, K_GREEN],[K_RED,K_WHITE],[K_GREEN,K_WHITE]]) {
        if (matchSets(tiles, [
          { keys: [w1, w1, w1, w1] },
          { keys: [w2, w2, w2, w2] },
          { keys: [d1, d1, d1, d1] },
          { keys: [dpair, dpair], isPair: true },
        ])) return true;
      }
    }
    return false;
  }
});

// ─── CATEGORY 5: Singles & Pairs (NO JOKERS) ──────────────────────────────────

addHand({
  id: 'seven_pairs',
  category: 'Singles & Pairs',
  name: 'Seven Pairs',
  description: 'Any 7 different pairs — NO JOKERS',
  value: 25,
  check(tiles) {
    if (tiles.some(t => t.isJoker)) return false; // jokers NOT allowed
    const counts = MJ.countTiles(tiles);
    const pairs = [...counts.values()].filter(c => c === 2).length;
    const totalNonFlower = tiles.filter(t => !t.isFlower).length;
    return pairs === 7 && totalNonFlower === 14;
  }
});

addHand({
  id: 'thirteen_terminals',
  category: 'Singles & Pairs',
  name: '13 Orphans',
  description: '1C 9C 1B 9B 1D 9D E S W N R G Soap + 1 duplicate — NO JOKERS',
  value: 40,
  check(tiles) {
    if (tiles.some(t => t.isJoker)) return false;
    const required = [
      k('crak',1), k('crak',9), k('bam',1), k('bam',9),
      k('dot',1),  k('dot',9),
      K_EAST, K_SOUTH, K_WEST, K_NORTH, K_RED, K_GREEN, K_WHITE
    ];
    const counts = MJ.countTiles(tiles);
    let duplicates = 0;
    for (const req of required) {
      const have = counts.get(req) || 0;
      if (have === 0) return false;
      if (have >= 2)  duplicates++;
    }
    return duplicates === 1 && tiles.length === 14;
  }
});

// ─── CATEGORY 6: Quints ────────────────────────────────────────────────────────
// A quint is 5 of a kind (4 tiles + 1 joker). Requires jokers.

for (const suit of MJ.SUITS) {
  for (let n = 1; n <= 9; n++) {
    addHand({
      id: `quint_${suit}_${n}`,
      category: 'Quints',
      name: `Quint ${n}s (${suit})`,
      description: `${n}${n}${n}${n}${n} ${n}${n}${n}${n}${n} (${suit}) — needs Jokers`,
      value: 50,
      check(tiles) {
        // Two quints of the same tile + matching set/pair
        const keyN = k(suit, n);
        const exact = tiles.filter(t => t.key === keyN).length;
        const jokers = tiles.filter(t => t.isJoker).length;
        // Need 10 of this tile (5+5), jokers fill gaps; remaining 4 tiles must be a kong
        if (exact + jokers < 10) return false;
        const jokersForQuints = Math.max(0, 10 - exact);
        const jLeft = jokers - jokersForQuints;
        if (jLeft < 0) return false;
        const remaining = tiles.filter(t => t.key !== keyN && !t.isJoker);
        if (remaining.length !== 4) return false;
        // The 4 remaining must be a kong of something
        if (new Set(remaining.map(t => t.key)).size !== 1) return false;
        return true;
      }
    });
    break; // just do one per suit to keep pattern count manageable; remove break for all
  }
}
// Remove the break — create quints for more numbers
MJ.HAND_PATTERNS = MJ.HAND_PATTERNS.filter(h => !h.id.startsWith('quint_'));

// Add a cleaner quint category with representative examples
addHand({
  id: 'quint_any_two',
  category: 'Quints',
  name: 'Two Quints + Kong',
  description: 'NNNNN NNNNN NNNN (same tile, needs Jokers)',
  value: 50,
  check(tiles) {
    const jokers = tiles.filter(t => t.isJoker).length;
    const nonJokers = tiles.filter(t => !t.isJoker);
    const counts = MJ.countTiles(nonJokers);
    for (const [key, cnt] of counts) {
      // Check if this tile can form two quints (10) + kong (4)
      if (cnt + jokers >= 14 && cnt >= 4) { // at minimum 4 real tiles
        const jForQuints = Math.max(0, 10 - cnt);
        if (jForQuints <= jokers && cnt - Math.min(cnt, 10) >= 0) {
          // try 2 quints of this key + 4 of something else
          const jLeft = jokers - jForQuints;
          const othersNeeded = 14 - 10;
          const otherCounts = new Map(counts);
          otherCounts.set(key, cnt - Math.min(cnt, 10));
          for (const [k2, c2] of otherCounts) {
            if (k2 === key) continue;
            if (c2 + jLeft >= othersNeeded) return true;
          }
        }
      }
    }
    return false;
  }
});

// ─── CATEGORY 7: Any Like Numbers (flexible) ─────────────────────────────────

addHand({
  id: 'any_like_pairs',
  category: 'Any Like Numbers',
  name: 'Six pairs same number',
  description: 'NN NN NN NN NN NN DD (same number, any suits)',
  value: 30,
  check(tiles) {
    const jokers = tiles.filter(t => t.isJoker).length;
    const nonJ   = tiles.filter(t => !t.isJoker && !t.isFlower);
    for (let n = 1; n <= 9; n++) {
      const matching = nonJ.filter(t => t.isNumbered && t.value === n);
      if (matching.length + jokers >= 12) {
        const jUsed = Math.max(0, 12 - matching.length);
        const jLeft = jokers - jUsed;
        const dragons = nonJ.filter(t => t.isDragon);
        if (dragons.length + jLeft >= 2) return true;
      }
    }
    return false;
  }
});

// ─── CATEGORY 8: 2468 (Even Numbers) ─────────────────────────────────────────

addHand({
  id: '2468_kongs',
  category: 'Evens',
  name: '2-4-6-8 Kongs',
  description: '2222 4444 6666 88 (one suit + pair)',
  value: 30,
  check(tiles) {
    for (const suit of MJ.SUITS) {
      for (const pSuit of MJ.SUITS) {
        if (matchSets(tiles, [
          { keys: [k(suit,2),k(suit,2),k(suit,2),k(suit,2)] },
          { keys: [k(suit,4),k(suit,4),k(suit,4),k(suit,4)] },
          { keys: [k(suit,6),k(suit,6),k(suit,6),k(suit,6)] },
          { keys: [k(pSuit,8),k(pSuit,8)], isPair: true },
        ])) return true;
      }
    }
    return false;
  }
});

addHand({
  id: '2468_mixed',
  category: 'Evens',
  name: '2-4-6-8 mixed',
  description: 'FF 2222 4444 6666 88 (mixed suits)',
  value: 25,
  check(tiles) {
    for (const s1 of MJ.SUITS) for (const s2 of MJ.SUITS) for (const s3 of MJ.SUITS) for (const s4 of MJ.SUITS) {
      if (s1===s2 && s2===s3) continue;
      if (matchSets(tiles, [
        { keys: [K_FLOWER, K_FLOWER], isFlower: true },
        { keys: [k(s1,2),k(s1,2),k(s1,2),k(s1,2)] },
        { keys: [k(s2,4),k(s2,4),k(s2,4),k(s2,4)] },
        { keys: [k(s3,6),k(s3,6),k(s3,6),k(s3,6)] },
        { keys: [k(s4,8),k(s4,8)], isPair: true },
      ])) return true;
    }
    return false;
  }
});

// ─── CATEGORY 9: Flowers ──────────────────────────────────────────────────────

addHand({
  id: 'all_flowers',
  category: 'Flowers',
  name: 'All Flowers',
  description: 'FFFFFFFF (all 8 flowers) + pairs',
  value: 50,
  check(tiles) {
    const flowers = tiles.filter(t => t.isFlower).length;
    if (flowers < 8) return false;
    const rest = tiles.filter(t => !t.isFlower);
    if (rest.length !== 6) return false;
    // rest must be 3 pairs
    const counts = MJ.countTiles(rest);
    const jokers = rest.filter(t => t.isJoker).length;
    let pairs = 0;
    for (const [, c] of counts) { if (c >= 2) pairs++; }
    return pairs >= 3;
  }
});

// ─── MAIN VALIDATION ENTRY POINT ──────────────────────────────────────────────

/**
 * Check if a 14-tile hand is a winning hand.
 * Returns the matching HandPattern object or null.
 *
 * @param {Tile[]} tiles — exactly 14 tiles including flowers & jokers
 * @returns {object|null}
 */
MJ.checkWinningHand = function (tiles) {
  if (tiles.length !== MJ.RULES.HAND_SIZE) return null;
  for (const pattern of MJ.HAND_PATTERNS) {
    try {
      if (pattern.check(tiles)) return pattern;
    } catch (e) {
      // pattern check threw — skip silently
    }
  }
  return null;
};

/**
 * How close is this hand to being complete?
 * Returns the minimum number of tiles that need to change.
 * Used by AI for hand evaluation.
 *
 * @param {Tile[]} tiles — 13 or 14 tiles
 * @returns {number} 0 = complete, higher = further away
 */
MJ.handsDistance = function (tiles) {
  // Simple heuristic: find the best "partial match" score
  let bestScore = Infinity;

  const counts = MJ.countTiles(tiles.filter(t => !t.isFlower && !t.isJoker));
  const jokers  = tiles.filter(t => t.isJoker).length;
  const flowers = tiles.filter(t => t.isFlower).length;

  // Score based on how many sets can be formed
  let sets = 0;
  let pairs = 0;
  for (const [, cnt] of counts) {
    sets  += Math.floor(cnt / 3);
    pairs += cnt >= 2 ? 1 : 0;
  }
  const score = 14 - (sets * 3 + Math.min(1, pairs) * 2 + jokers * 2);
  bestScore = Math.min(bestScore, Math.max(0, score));

  return bestScore;
};

// ─── Charleston rules ──────────────────────────────────────────────────────────

MJ.CHARLESTON = {
  PASSES: [
    { name: 'First Right',  dir: 'right',  mandatory: true  },
    { name: 'First Across', dir: 'across', mandatory: true  },
    { name: 'First Left',   dir: 'left',   mandatory: true  },
    { name: 'Second Left',  dir: 'left',   mandatory: false },
    { name: 'Second Across',dir: 'across', mandatory: false },
    { name: 'Second Right', dir: 'right',  mandatory: false },
  ],
  TILES_PER_PASS: 3,
};

/**
 * Which player index receives tiles when player p passes in direction dir?
 * Players: 0=South(human), 1=East, 2=North, 3=West
 */
MJ.charlestOnTarget = function (from, dir) {
  const seats = 4;
  switch (dir) {
    case 'right':  return (from + 1) % seats;
    case 'left':   return (from + 3) % seats;
    case 'across': return (from + 2) % seats;
    default: return from;
  }
};

// ─── Joker steal validation ────────────────────────────────────────────────────

/**
 * Can player steal joker from an exposed set by providing the real tile?
 * exposedSet: Tile[]  — the exposed set containing the joker
 * realTile: Tile      — the tile the player wants to swap in
 */
MJ.canStealJoker = function (exposedSet, realTile) {
  if (!MJ.RULES.JOKER_STEALING) return false;
  if (!exposedSet.some(t => t.isJoker)) return false;
  if (realTile.isJoker) return false;
  // The real tile must match the key of a non-joker tile in the set
  const nonJokerKey = exposedSet.find(t => !t.isJoker)?.key;
  return nonJokerKey === realTile.key;
};
