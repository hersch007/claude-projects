/**
 * main.js — App entry point: wires game events, UI updates, and AI timing
 */
// main.js — uses global MJ defined by tiles.js
'use strict';

MJ.App = {
  selectedTiles:       new Set(),  // tile IDs selected in Charleston modal
  charlestonSelected:  new Set(),  // tile IDs chosen for current Charleston pass
  currentPass:         null,       // current Charleston pass info

  init() {
    MJ.DragDrop.init();
    this._bindGameEvents();
    this._bindUIEvents();
    this._bindButtons();
    MJ.UI.setStatus('Welcome to American Mahjong! Click "New Game" to start.', 'info');
  },

  // ─── Game event handlers ──────────────────────────────────────────────────

  _bindGameEvents() {
    window.addEventListener('mjGameEvent', e => {
      const d = e.detail;
      switch (d.type) {

        case 'dealt':
          MJ.UI.renderAll(MJ.game);
          MJ.UI.setStatus('Tiles dealt! Charleston begins — pass 3 tiles to your right.', 'info');
          break;

        case 'charlestonStart':
        case 'charlestonPass':
          this.currentPass = MJ.CHARLESTON.PASSES[MJ.game.charleston.passIndex];
          this.charlestonSelected.clear();
          MJ.UI.renderAll(MJ.game);
          this._showCharlestonForHuman();
          break;

        case 'charlestonDone':
          MJ.UI.hideCharlestonModal();
          MJ.UI.renderAll(MJ.game);
          MJ.UI.setStatus('Charleston complete! Game begins. East draws first.', 'success');
          // Kick off East's turn (AI)
          this._scheduleAITurn(MJ.game.currentSeat, 800);
          break;

        case 'drew':
          MJ.UI.renderAll(MJ.game);
          if (d.seat === MJ.HUMAN_SEAT) {
            MJ.DragDrop.enable();
            MJ.UI.setStatus('You drew a tile. Click or drag a tile to discard.', 'info');
          }
          break;

        case 'aiTurn':
          this._scheduleAITurn(d.seat, 900);
          break;

        case 'discarded':
          MJ.UI.renderAll(MJ.game);
          if (d.seat === MJ.HUMAN_SEAT) {
            MJ.DragDrop.disable();
            MJ.UI.setStatus(`You discarded: ${d.tile.label()}`, 'info');
          } else {
            MJ.UI.setStatus(`${MJ.UI.seatLabel(d.seat)} discarded: ${d.tile.label()}`, 'info');
          }
          break;

        case 'callWindow':
          MJ.UI.showCallButtons(d.tile, d.candidates);
          // Resolve AI calls after short delay
          setTimeout(() => this._resolveCallWindow(), 1200);
          break;

        case 'called':
          MJ.UI.hideCallButtons();
          MJ.UI.renderAll(MJ.game);
          MJ.UI.setStatus(
            `${MJ.UI.seatLabel(d.seat)} called ${d.type}! ${d.seat === MJ.HUMAN_SEAT ? 'Now discard a tile.' : ''}`,
            d.seat === MJ.HUMAN_SEAT ? 'success' : 'info'
          );
          if (d.seat === MJ.HUMAN_SEAT) {
            MJ.DragDrop.enable();
          } else {
            this._scheduleAITurn(d.seat, 1000);
          }
          break;

        case 'canDeclareWin':
          MJ.UI.setStatus('🏆 You can declare Mahjong! Click the Mahjong button!', 'win');
          document.getElementById('btn-mahjong')?.classList.remove('hidden');
          break;

        case 'gameOver':
          MJ.DragDrop.disable();
          MJ.UI.hideCallButtons();
          MJ.UI.renderAll(MJ.game);
          setTimeout(() => MJ.UI.showWinScreen(d.winner, d.pattern), 600);
          break;

        case 'jokerStolen':
          MJ.UI.renderAll(MJ.game);
          MJ.UI.setStatus('Joker stolen!', 'info');
          break;
      }
    });
  },

  // ─── UI / input event handlers ────────────────────────────────────────────

  _bindUIEvents() {
    // Human discards via drag
    window.addEventListener('mjDiscard', e => {
      if (MJ.game.phase !== 'play') return;
      if (MJ.game.currentSeat !== MJ.HUMAN_SEAT) return;
      MJ.game.humanDiscard(e.detail.tileId);
    });

    // Tile click: select for Charleston or discard
    window.addEventListener('mjTileClick', e => {
      const { tileId } = e.detail;

      if (MJ.game.phase === 'charleston') {
        this._handleCharlestonClick(tileId);
        return;
      }

      if (MJ.game.phase === 'play' && MJ.game.currentSeat === MJ.HUMAN_SEAT
          && MJ.game.turnState === 'discard') {
        // Single click = select; double click = discard
        if (this.selectedTiles.has(tileId)) {
          // Second click on same tile = discard it
          this.selectedTiles.clear();
          MJ.game.humanDiscard(tileId);
        } else {
          this.selectedTiles.clear();
          this.selectedTiles.add(tileId);
          MJ.UI.renderAll(MJ.game);
          MJ.UI.setStatus('Click the tile again or drag to the discard zone to discard.', 'info');
        }
      }
    });

    // Reorder tiles in rack
    window.addEventListener('mjReorder', e => {
      const { fromId, toId } = e.detail;
      const hand = MJ.game.hands[MJ.HUMAN_SEAT];
      const fi   = hand.findIndex(t => t.id === fromId);
      const ti   = hand.findIndex(t => t.id === toId);
      if (fi >= 0 && ti >= 0) {
        [hand[fi], hand[ti]] = [hand[ti], hand[fi]];
        MJ.UI.renderAll(MJ.game);
      }
    });

    // Call events from call buttons
    window.addEventListener('mjCall', e => {
      const { type, seat } = e.detail;
      if (type === 'pass') {
        MJ.game.passCall();
        return;
      }
      if (type === 'mahjong') {
        const tile = MJ.game.lastDiscard;
        MJ.game.call(seat, 'mahjong', []);
        return;
      }
      // For pung/kong/quint: auto-select tiles from hand
      const hand   = MJ.game.hands[seat];
      const tile   = MJ.game.lastDiscard;
      const exact  = hand.filter(t => t.matches(tile));
      const jokers = hand.filter(t => t.isJoker);
      const needed = type === 'kong' ? 3 : type === 'quint' ? 4 : 2;
      const tileIds = [
        ...exact.slice(0, Math.min(exact.length, needed)).map(t => t.id),
        ...jokers.slice(0, Math.max(0, needed - exact.length)).map(t => t.id)
      ];
      MJ.game.call(seat, type, tileIds);
    });

    // Charleston stop button
    window.addEventListener('mjCharlestonStop', () => {
      MJ.game._endCharleston();
    });
  },

  _bindButtons() {
    document.getElementById('btn-new-game')?.addEventListener('click', () => this.startNewGame());
    document.getElementById('btn-sort')?.addEventListener('click', () => {
      const hand = MJ.game.hands[MJ.HUMAN_SEAT];
      MJ.game.hands[MJ.HUMAN_SEAT] = MJ.sortTiles(hand);
      MJ.UI.renderAll(MJ.game);
    });
    document.getElementById('btn-mahjong')?.addEventListener('click', () => {
      if (MJ.game.declareMahjong(MJ.HUMAN_SEAT)) {
        document.getElementById('btn-mahjong')?.classList.add('hidden');
      }
    });
    document.getElementById('btn-play-again')?.addEventListener('click', () => {
      MJ.UI.hideWinScreen();
      this.startNewGame();
    });
  },

  // ─── Charleston interaction ───────────────────────────────────────────────

  _showCharlestonForHuman() {
    const passInfo = this.currentPass;
    if (!passInfo) return;
    MJ.UI.showCharlestонModal(
      passInfo,
      MJ.game.hands[MJ.HUMAN_SEAT],
      this.charlestonSelected,
      (ids) => {
        // Confirmed selection — executeCharleston emits the next event which re-shows modal
        MJ.game.charlestonSelectTiles(MJ.HUMAN_SEAT, ids);
        MJ.game.executeCharleston();
      }
    );
  },

  _handleCharlestonClick(tileId) {
    if (this.charlestonSelected.has(tileId)) {
      this.charlestonSelected.delete(tileId);
    } else if (this.charlestonSelected.size < 3) {
      this.charlestonSelected.add(tileId);
    }
    // Re-render the Charleston modal rack
    const rackEl = document.getElementById('charleston-rack');
    if (rackEl) {
      MJ.UI.renderRack(rackEl, MJ.game.hands[MJ.HUMAN_SEAT], {
        selectable: true,
        selectedIds: this.charlestonSelected,
      });
    }
    const countEl = document.getElementById('charleston-count');
    if (countEl) countEl.textContent = `${this.charlestonSelected.size}/3 selected`;
    const confirmBtn = document.getElementById('charleston-confirm');
    if (confirmBtn) confirmBtn.disabled = this.charlestonSelected.size !== 3;
  },

  // ─── AI turn management ───────────────────────────────────────────────────

  _scheduleAITurn(seat, delay) {
    setTimeout(() => {
      if (MJ.game.phase !== 'play') return;
      if (MJ.game.currentSeat !== seat) return;
      MJ.AI.runTurn(MJ.game, seat);
    }, delay);
  },

  _resolveCallWindow() {
    if (!MJ.game.callWindow) return; // already resolved (human called or passed)
    const aiCall = MJ.AI.evaluateCallWindow(MJ.game);
    if (aiCall) {
      MJ.game.call(aiCall.seat, aiCall.type, aiCall.tileIds);
    } else {
      MJ.game.passCall();
    }
  },

  // ─── New game ─────────────────────────────────────────────────────────────

  startNewGame() {
    MJ.game.reset();
    this.selectedTiles.clear();
    this.charlestonSelected.clear();
    MJ.DragDrop.disable();
    MJ.UI.hideWinScreen();
    MJ.UI.hideCallButtons();
    document.getElementById('btn-mahjong')?.classList.add('hidden');
    MJ.game.deal();
  }
};

// Scripts are at bottom of <body>, DOM is already parsed — init immediately.
MJ.App.init();
