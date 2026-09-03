/**
 * ai.js — Computer opponent logic for American Mahjong
 *
 * Strategy tiers:
 *   1. Aim toward a specific hand pattern (best fit from current tiles)
 *   2. Keep tiles that contribute to sets (kongs > pungs > pairs > singletons)
 *   3. Discard lowest-value isolated tiles first
 */
// ai.js — uses global MJ defined by tiles.js
'use strict';

MJ.AI = {

  // ─── Main entry points ────────────────────────────────────────────────────

  /** Called after AI player draws. Returns the tile to discard. */
  chooseDiscard(game, seat) {
    const hand = game.hands[seat];
    if (hand.length === 0) return null;

    // Score each tile: lower = better to keep, higher = better to discard
    const scores = hand.map(tile => this._discardScore(hand, tile));
    let worst = -Infinity;
    let worstTile = hand[0];

    for (let i = 0; i < hand.length; i++) {
      if (scores[i] > worst) {
        worst     = scores[i];
        worstTile = hand[i];
      }
    }
    return worstTile;
  },

  /** Called during Charleston. Returns array of 3 tiles to pass. */
  chooseCharleston(game, seat) {
    const hand = game.hands[seat];
    const scores = hand.map(tile => this._discardScore(hand, tile));
    // Sort by descending score (most discardable first)
    const ranked = hand.map((t, i) => ({ tile: t, score: scores[i] }))
                       .sort((a, b) => b.score - a.score);
    return ranked.slice(0, 3).map(x => x.tile);
  },

  /** Decide whether to call a discard (and which type). Returns call info or null. */
  decideCall(game, seat, tile) {
    const hand   = game.hands[seat];
    const testHand = [...hand, tile];

    // Always declare Mahjong
    if (testHand.length === MJ.RULES.HAND_SIZE) {
      const win = MJ.checkWinningHand(testHand);
      if (win) return { type: 'mahjong', tiles: [] };
    }

    // Check Kong/Pung only if it meaningfully advances a hand
    const exact  = hand.filter(t => t.matches(tile));
    const jokers = hand.filter(t => t.isJoker);

    const canKong  = exact.length + jokers.length >= 3;
    const canPung  = exact.length + jokers.length >= 2;

    // Check if calling would leave a hand one discard from winning
    if (canKong) {
      const handAfter = this._simulateMeld(hand, tile, 'kong');
      if (handAfter && MJ.handsDistance(handAfter) <= 2) return { type: 'kong', tiles: exact.slice(0,3) };
    }
    if (canPung) {
      const handAfter = this._simulateMeld(hand, tile, 'pung');
      if (handAfter && MJ.handsDistance(handAfter) <= 2) return { type: 'pung', tiles: exact.slice(0,2) };
    }

    return null;
  },

  // ─── Helpers ──────────────────────────────────────────────────────────────

  /**
   * Score a tile for discard: higher = more likely to discard.
   * Factors: isolation, suit concentration, value toward best hand target.
   */
  _discardScore(hand, tile) {
    if (tile.isJoker)  return -100; // never discard jokers
    if (tile.isFlower) return  50;  // flowers are already in flower rack, shouldn't be here

    let score = 0;

    // Isolated tiles (not part of any group) get high discard score
    const sameKey  = hand.filter(t => t.matches(tile)).length;
    const adjacent = hand.filter(t =>
      t.isSuit && tile.isSuit && t.suit === tile.suit &&
      Math.abs(t.value - tile.value) <= 2
    ).length;

    // Reward tiles with pairs/pungs
    if (sameKey >= 3) score -= 30; // kong — very valuable, keep
    if (sameKey === 2) score -= 15; // pair
    if (sameKey === 1) score += 10; // singleton

    // Reward tiles near other tiles of same suit (sequence potential)
    if (tile.isSuit) {
      score -= adjacent * 4;
      // Middle tiles (4-6) are more flexible
      if (tile.value >= 3 && tile.value <= 7) score -= 3;
    }

    // Isolated honor tiles are less useful in American mj unless collecting them
    if (tile.isHonor) {
      const sameHonor = hand.filter(t => t.matches(tile)).length;
      if (sameHonor < 2) score += 15;
    }

    // Slight randomness to avoid completely predictable play
    score += (Math.random() * 4) - 2;

    return score;
  },

  /** Simulate forming a meld; return the resulting hand tiles after discard */
  _simulateMeld(hand, calledTile, type) {
    const size  = type === 'kong' ? 4 : 3;
    const exact = hand.filter(t => t.matches(calledTile));
    const jokers = hand.filter(t => t.isJoker);
    const fromHand = exact.slice(0, size - 1);
    const jokersNeeded = Math.max(0, (size - 1) - fromHand.length);

    if (jokersNeeded > jokers.length) return null;

    const used = new Set([...fromHand.map(t => t.id), ...jokers.slice(0, jokersNeeded).map(t => t.id)]);
    return hand.filter(t => !used.has(t.id));
  },

  /** Run the AI turn: decide what to discard and do it. */
  runTurn(game, seat) {
    const tile = this.chooseDiscard(game, seat);
    if (!tile) return;

    // Check for self-draw win first
    if (game.hands[seat].length === MJ.RULES.HAND_SIZE) {
      const win = MJ.checkWinningHand(game.hands[seat]);
      if (win) {
        game.declareMahjong(seat);
        return;
      }
    }

    // Discard
    const idx = game.hands[seat].findIndex(t => t.id === tile.id);
    if (idx >= 0) {
      const discarded = game.hands[seat].splice(idx, 1)[0];
      game._discard(seat, discarded);
    }
  },

  /** Evaluate whether AI should call on a discard in the call window. */
  evaluateCallWindow(game) {
    // Go through candidates in priority order: mahjong > kong > pung
    const byPriority = ['mahjong', 'quint', 'kong', 'pung'];
    const sorted = game.callCandidates.slice().sort((a, b) =>
      byPriority.indexOf(a.type) - byPriority.indexOf(b.type)
    );

    for (const candidate of sorted) {
      if (candidate.seat === MJ.HUMAN_SEAT) continue; // human decides themselves
      const decision = this.decideCall(game, candidate.seat, game.lastDiscard);
      if (decision) {
        const hand  = game.hands[candidate.seat];
        const exact = hand.filter(t => t.matches(game.lastDiscard));
        const jokers = hand.filter(t => t.isJoker);
        const needed = decision.type === 'kong' ? 3 : 2;
        const tileIds = [
          ...exact.slice(0, Math.min(exact.length, needed - 1)).map(t => t.id),
          ...jokers.slice(0, Math.max(0, needed - 1 - exact.length)).map(t => t.id)
        ];
        return { seat: candidate.seat, type: decision.type, tileIds };
      }
    }
    return null; // no AI wants to call — pass
  }
};
