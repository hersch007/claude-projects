/**
 * game-logic.js — Game state machine for American Mahjong
 *
 * Phases: 'setup' → 'charleston' → 'play' → 'gameover'
 * Events: dispatched on window as CustomEvent('mjGameEvent', {detail:{type,...}})
 */
// game-logic.js — uses global MJ defined by tiles.js
'use strict';

// ─── Player seats ──────────────────────────────────────────────────────────────
// 0 = South (human), 1 = East, 2 = North, 3 = West (all AI by default)
MJ.SEATS = ['South', 'East', 'North', 'West'];
MJ.HUMAN_SEAT = 0;

// ─── GameState ─────────────────────────────────────────────────────────────────
class GameState {
  constructor() {
    this.reset();
  }

  reset() {
    this.phase          = 'setup';   // 'setup'|'charleston'|'play'|'gameover'
    this.wall           = [];        // remaining draw pile
    this.hands          = [[], [], [], []]; // tiles in each player's hand
    this.exposed        = [[], [], [], []]; // exposed melds [[meld,…], …]
    this.flowers        = [[], [], [], []]; // flowers each player received
    this.discardPile    = [];        // all discards in order
    this.lastDiscard    = null;      // most recent discarded tile
    this.lastDiscardSeat = -1;
    this.currentSeat    = 1;        // East always starts
    this.turnState      = 'draw';   // 'draw'|'discard'|'claimed'|'waiting'
    this.winner         = null;     // null | seat index
    this.winningPattern = null;     // hand pattern object
    this.message        = '';       // status message
    this.callWindow     = false;    // true while others can call last discard
    this.callCandidates = [];       // seats that can call {seat, type, tiles}

    // Charleston state
    this.charleston = {
      passIndex: 0,   // index into MJ.CHARLESTON.PASSES
      pending:   [[], [], [], []], // tiles each player has chosen to pass
      active:    false,
      done:      false,
    };
  }

  emit(type, detail = {}) {
    window.dispatchEvent(new CustomEvent('mjGameEvent', { detail: { type, ...detail } }));
  }

  // ─── Deal ──────────────────────────────────────────────────────────────────
  deal() {
    const deck = MJ.shuffle(MJ.buildDeck());
    this.wall  = deck;

    // Each player draws 13 tiles
    for (let s = 0; s < 4; s++) {
      this.hands[s] = [];
      this.exposed[s] = [];
      this.flowers[s] = [];
      for (let i = 0; i < MJ.RULES.DRAW_SIZE; i++) {
        this._drawTo(s);
      }
    }

    this.phase = 'charleston';
    this.charleston.passIndex = 0;
    this.charleston.pending   = [[], [], [], []];
    this.charleston.active    = true;
    this.charleston.done      = false;

    this.emit('dealt');
    this.emit('charlestonStart', { pass: MJ.CHARLESTON.PASSES[0] });
  }

  /** Draw from wall to player, handling flowers (auto-replace) */
  _drawTo(seat) {
    if (this.wall.length === 0) return null;
    const tile = this.wall.pop();
    if (tile.isFlower) {
      this.flowers[seat].push(tile);
      return this._drawTo(seat); // replace flower with another draw
    }
    this.hands[seat].push(tile);
    return tile;
  }

  // ─── Charleston Phase ─────────────────────────────────────────────────────

  /** Human selects 3 tiles to pass */
  charlestonSelectTiles(seat, tileIds) {
    if (tileIds.length !== MJ.CHARLESTON.TILES_PER_PASS) return false;
    const passInfo = MJ.CHARLESTON.PASSES[this.charleston.passIndex];

    // Validate all tiles belong to player
    const chosen = tileIds.map(id => this.hands[seat].find(t => t.id === id)).filter(Boolean);
    if (chosen.length !== 3) return false;

    this.charleston.pending[seat] = chosen;
    return true;
  }

  /** Execute the current Charleston pass */
  executeCharleston() {
    const passInfo = MJ.CHARLESTON.PASSES[this.charleston.passIndex];

    // AI players choose their 3 tiles to pass
    for (let s = 0; s < 4; s++) {
      if (this.charleston.pending[s].length === 0) {
        this.charleston.pending[s] = MJ.AI.chooseCharleston(this, s);
      }
    }

    // Remove chosen tiles from hands and pass to targets
    const outgoing = this.charleston.pending.map(tiles => tiles.slice());

    for (let s = 0; s < 4; s++) {
      const target = MJ.charlestOnTarget(s, passInfo.dir);
      // Remove from sender
      for (const tile of outgoing[s]) {
        const idx = this.hands[s].findIndex(t => t.id === tile.id);
        if (idx >= 0) this.hands[s].splice(idx, 1);
      }
    }
    for (let s = 0; s < 4; s++) {
      const sender = MJ.charlestOnTarget(s, passInfo.dir === 'right' ? 'left' :
                                           passInfo.dir === 'left'  ? 'right' : 'across');
      // Add incoming tiles
      for (const tile of outgoing[sender]) {
        if (tile.isFlower) {
          this.flowers[s].push(tile);
          this._drawTo(s); // replace flower
        } else {
          this.hands[s].push(tile);
        }
      }
    }

    this.charleston.pending = [[], [], [], []];
    this.charleston.passIndex++;

    if (this.charleston.passIndex >= MJ.CHARLESTON.PASSES.length) {
      this._endCharleston();
    } else {
      const next = MJ.CHARLESTON.PASSES[this.charleston.passIndex];
      this.emit('charlestonPass', { pass: next, passIndex: this.charleston.passIndex });
    }
  }

  _endCharleston() {
    this.charleston.done   = true;
    this.charleston.active = false;
    this.phase = 'play';
    this.currentSeat = 1; // East goes first
    this.turnState   = 'draw';
    this.emit('charlestonDone');
    this.emit('phaseChange', { phase: 'play' });
    // Auto-draw for East (AI) or prompt human
    this._startTurn(this.currentSeat);
  }

  // ─── Play Phase ────────────────────────────────────────────────────────────

  _startTurn(seat) {
    this.turnState = 'draw';
    if (this.wall.length === 0) {
      this._endGame(null); // wall exhausted = draw
      return;
    }
    const tile = this._drawTo(seat);
    this.emit('drew', { seat, tile });

    if (seat === MJ.HUMAN_SEAT) {
      this.turnState = 'discard';
      this._checkSelfDraw(seat);
    } else {
      // AI turn
      this.turnState = 'discard';
      this.emit('aiTurn', { seat });
    }
  }

  _checkSelfDraw(seat) {
    // Check if the player just drew their winning tile
    const win = MJ.checkWinningHand(this.hands[seat]);
    if (win) {
      this.emit('canDeclareWin', { seat, pattern: win });
    }
  }

  /** Human discards a tile by ID */
  humanDiscard(tileId) {
    if (this.currentSeat !== MJ.HUMAN_SEAT) return false;
    if (this.turnState !== 'discard') return false;

    const idx = this.hands[MJ.HUMAN_SEAT].findIndex(t => t.id === tileId);
    if (idx < 0) return false;

    const tile = this.hands[MJ.HUMAN_SEAT].splice(idx, 1)[0];
    this._discard(MJ.HUMAN_SEAT, tile);
    return true;
  }

  _discard(seat, tile) {
    this.discardPile.push(tile);
    this.lastDiscard     = tile;
    this.lastDiscardSeat = seat;
    this.turnState       = 'waiting';
    this.emit('discarded', { seat, tile });

    // Check if any other player can call this tile
    this.callCandidates = this._findCallCandidates(seat, tile);
    if (this.callCandidates.length > 0) {
      this.callWindow = true;
      this.emit('callWindow', { tile, candidates: this.callCandidates });
      // Auto-resolve AI calls after brief delay (handled by main.js)
    } else {
      this._advanceTurn();
    }
  }

  _findCallCandidates(discardSeat, tile) {
    const candidates = [];
    for (let s = 0; s < 4; s++) {
      if (s === discardSeat) continue;
      const callType = this._canCall(s, tile);
      if (callType) candidates.push({ seat: s, type: callType, tile });
    }
    return candidates;
  }

  _canCall(seat, tile) {
    const hand = this.hands[seat];
    const matching = hand.filter(t => t.matches(tile) || t.isJoker);
    const exact    = hand.filter(t => t.matches(tile));
    const jokers   = hand.filter(t => t.isJoker);

    // Check Mahjong (win)
    const testHand = [...hand, tile];
    if (testHand.length === MJ.RULES.HAND_SIZE && MJ.checkWinningHand(testHand)) {
      return 'mahjong';
    }
    // Check Quint (5 of a kind including jokers)
    if (exact.length + jokers.length >= 4) return 'quint';
    // Check Kong (4 of a kind)
    if (exact.length + jokers.length >= 3) return 'kong';
    // Check Pung (3 of a kind)
    if (exact.length + jokers.length >= 2) return 'pung';

    return null;
  }

  /** Called when player makes a call (pung/kong/quint/mahjong) */
  call(seat, type, tileIds) {
    if (!this.callWindow) return false;
    const tile = this.lastDiscard;

    if (type === 'mahjong') {
      // Add the claimed tile and declare win
      this.hands[seat].push(tile);
      const pattern = MJ.checkWinningHand(this.hands[seat]);
      if (pattern) {
        this._endGame(seat, pattern);
        return true;
      }
      // If somehow it doesn't win, undo
      this.hands[seat].pop();
      return false;
    }

    // Form a meld
    const setSize = type === 'quint' ? 5 : type === 'kong' ? 4 : 3;
    const fromHand = tileIds.map(id => this.hands[seat].find(t => t.id === id)).filter(Boolean);
    if (fromHand.length !== setSize - 1) return false; // -1 because claimed tile is included

    // Remove from hand
    for (const t of fromHand) {
      const idx = this.hands[seat].findIndex(x => x.id === t.id);
      this.hands[seat].splice(idx, 1);
    }
    const meld = [...fromHand, tile];
    this.exposed[seat].push(meld);

    this.callWindow = false;
    this.callCandidates = [];
    this.currentSeat = seat;
    this.turnState   = 'discard';

    this.emit('called', { seat, type, meld });

    if (seat === MJ.HUMAN_SEAT) {
      // Wait for human to discard
    } else {
      this.emit('aiTurn', { seat });
    }
    return true;
  }

  /** Skip / pass on calling */
  passCall() {
    this.callWindow     = false;
    this.callCandidates = [];
    this._advanceTurn();
  }

  _advanceTurn() {
    this.currentSeat = (this.currentSeat + 1) % 4;
    this._startTurn(this.currentSeat);
  }

  /** Declare Mahjong (self-draw) */
  declareMahjong(seat) {
    const pattern = MJ.checkWinningHand(this.hands[seat]);
    if (pattern) {
      this._endGame(seat, pattern);
      return true;
    }
    return false;
  }

  _endGame(winnerSeat, pattern = null) {
    this.phase          = 'gameover';
    this.winner         = winnerSeat;
    this.winningPattern = pattern;
    this.emit('gameOver', { winner: winnerSeat, pattern });
  }

  /** Steal a joker from an exposed set */
  stealJoker(thiefSeat, targetSeat, meldIndex, realTileId) {
    const meld     = this.exposed[targetSeat][meldIndex];
    const realTile = this.hands[thiefSeat].find(t => t.id === realTileId);
    if (!meld || !realTile) return false;
    if (!MJ.canStealJoker(meld, realTile)) return false;

    const jokerIdx = meld.findIndex(t => t.isJoker);
    const joker    = meld.splice(jokerIdx, 1)[0];

    // Replace joker in meld with real tile
    const realIdx = this.hands[thiefSeat].findIndex(t => t.id === realTileId);
    this.hands[thiefSeat].splice(realIdx, 1);
    meld.push(realTile);

    // Thief gets the joker into their hand
    this.hands[thiefSeat].push(joker);
    this.emit('jokerStolen', { thiefSeat, targetSeat, meldIndex });
    return true;
  }
}

MJ.GameState = GameState;
MJ.game = new GameState();
