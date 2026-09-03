/**
 * ui.js — DOM rendering for the Mahjong game board
 *
 * Renders: table layout, player racks, discard pile, wall count,
 *          Charleston modal, call buttons, win screen.
 */
// ui.js — uses global MJ defined by tiles.js
'use strict';

MJ.UI = {

  // ─── Tile element builder ──────────────────────────────────────────────────

  /**
   * Create a DOM element for one tile.
   * @param {Tile} tile
   * @param {object} opts
   *   faceDown:  bool — show tile back
   *   small:     bool — smaller size class
   *   selectable:bool — clickable/draggable
   *   selected:  bool — currently selected
   */
  buildTileEl(tile, opts = {}) {
    const el = document.createElement('div');
    el.className = 'tile';
    if (opts.faceDown)   el.classList.add('tile-back');
    if (opts.small)      el.classList.add('tile-sm');
    if (opts.selected)   el.classList.add('tile-selected');
    if (opts.selectable) el.classList.add('tile-selectable');

    el.setAttribute('data-tile-id', tile.id);

    if (opts.faceDown) {
      el.innerHTML = '<div class="tile-back-inner"></div>';
      return el;
    }

    const d = MJ.tileDisplay(tile);
    el.classList.add(d.colorClass);

    el.innerHTML = `
      <span class="tile-corner tile-corner-tl">${d.topText}</span>
      <span class="tile-main-sym">${d.mainSymbol}</span>
      <span class="tile-corner tile-corner-br">${d.bottomText}</span>
    `;
    return el;
  },

  // ─── Rack renderer ────────────────────────────────────────────────────────

  /**
   * Render all tiles in player's hand into a rack element.
   * @param {HTMLElement} rackEl
   * @param {Tile[]} tiles
   * @param {object} opts  { faceDown, selectable, selectedIds, small }
   */
  renderRack(rackEl, tiles, opts = {}) {
    rackEl.innerHTML = '';
    const sorted = opts.faceDown ? tiles : MJ.sortTiles(tiles);
    for (const tile of sorted) {
      const el = this.buildTileEl(tile, {
        faceDown:   opts.faceDown,
        selectable: opts.selectable,
        selected:   opts.selectedIds?.has(tile.id),
        small:      opts.small,
      });
      rackEl.appendChild(el);

      if (opts.selectable) {
        MJ.DragDrop.attachTile(el, tile.id);
        el.addEventListener('click', () => {
          window.dispatchEvent(new CustomEvent('mjTileClick', { detail: { tileId: tile.id } }));
        });
      }
    }
  },

  // ─── Exposed melds ────────────────────────────────────────────────────────

  renderExposed(containerEl, melds) {
    containerEl.innerHTML = '';
    for (const meld of melds) {
      const meldEl = document.createElement('div');
      meldEl.className = 'meld';
      for (const tile of meld) {
        meldEl.appendChild(this.buildTileEl(tile, { small: true }));
      }
      containerEl.appendChild(meldEl);
    }
  },

  // ─── Discard pile ────────────────────────────────────────────────────────

  renderDiscardPile(pileEl, tiles, lastTile) {
    pileEl.innerHTML = '';
    const recent = tiles.slice(-24); // show last 24
    for (const tile of recent) {
      const el = this.buildTileEl(tile, { small: true });
      if (lastTile && tile.id === lastTile.id) el.classList.add('tile-last-discard');
      pileEl.appendChild(el);
    }
  },

  // ─── Wall count ──────────────────────────────────────────────────────────

  renderWallCount(el, count) {
    el.textContent = `Wall: ${count}`;
    el.className   = count < 10 ? 'wall-count wall-low' : 'wall-count';
  },

  // ─── Flowers ─────────────────────────────────────────────────────────────

  renderFlowers(containerEl, flowers) {
    containerEl.innerHTML = '';
    for (const tile of flowers) {
      containerEl.appendChild(this.buildTileEl(tile, { small: true }));
    }
  },

  // ─── Seat label ──────────────────────────────────────────────────────────

  seatLabel(seat) {
    const labels = ['You (South)', 'East', 'North', 'West'];
    return labels[seat] || `Player ${seat}`;
  },

  // ─── Status message ──────────────────────────────────────────────────────

  setStatus(msg, type = 'info') {
    const el = document.getElementById('status-msg');
    if (!el) return;
    el.textContent = msg;
    el.className   = `status-msg status-${type}`;
  },

  // ─── Call buttons ────────────────────────────────────────────────────────

  showCallButtons(tile, candidates) {
    const bar = document.getElementById('call-bar');
    if (!bar) return;
    bar.innerHTML = '';

    const humanCandidate = candidates.find(c => c.seat === MJ.HUMAN_SEAT);
    if (!humanCandidate) { bar.style.display = 'none'; return; }

    bar.style.display = 'flex';

    const label = document.createElement('span');
    label.className = 'call-label';
    label.textContent = `Call ${MJ.UI._tileLabel(tile)}?`;
    bar.appendChild(label);

    const types = [];
    if (humanCandidate.type === 'mahjong') types.push('mahjong');
    if (['quint','kong','pung','mahjong'].includes(humanCandidate.type)) {
      if (humanCandidate.type !== 'mahjong') types.push(humanCandidate.type);
    }
    types.push('pass');

    for (const type of types) {
      const btn = document.createElement('button');
      btn.className = `call-btn call-${type}`;
      btn.textContent = type === 'mahjong' ? '🏆 Mahjong!' :
                        type === 'pung'    ? 'Pung (×3)'   :
                        type === 'kong'    ? 'Kong (×4)'   :
                        type === 'quint'   ? 'Quint (×5)'  : 'Pass';
      btn.addEventListener('click', () => {
        this.hideCallButtons();
        window.dispatchEvent(new CustomEvent('mjCall', { detail: { type, seat: MJ.HUMAN_SEAT } }));
      });
      bar.appendChild(btn);
    }
  },

  hideCallButtons() {
    const bar = document.getElementById('call-bar');
    if (bar) { bar.style.display = 'none'; bar.innerHTML = ''; }
  },

  _tileLabel(tile) {
    return tile ? tile.label() : '';
  },

  // ─── Charleston modal ────────────────────────────────────────────────────

  showCharlestонModal(passInfo, hand, selectedIds, onConfirm) {
    const modal = document.getElementById('charleston-modal');
    if (!modal) return;
    modal.style.display = 'flex';

    document.getElementById('charleston-title').textContent =
      `Charleston — ${passInfo.name}`;
    document.getElementById('charleston-desc').textContent =
      `Select 3 tiles to pass ${passInfo.dir}. ${passInfo.mandatory ? '' : '(Optional — you may stop here)'}`;

    const rackEl = document.getElementById('charleston-rack');
    this.renderRack(rackEl, hand, { selectable: true, selectedIds });

    const countEl = document.getElementById('charleston-count');
    countEl.textContent = `${selectedIds.size}/3 selected`;

    const confirmBtn = document.getElementById('charleston-confirm');
    confirmBtn.disabled = selectedIds.size !== 3;
    confirmBtn.onclick  = () => onConfirm([...selectedIds]);

    if (!passInfo.mandatory) {
      const stopBtn = document.getElementById('charleston-stop');
      stopBtn.style.display = 'inline-block';
      stopBtn.onclick = () => {
        modal.style.display = 'none';
        window.dispatchEvent(new CustomEvent('mjCharlestonStop'));
      };
    } else {
      const stopBtn = document.getElementById('charleston-stop');
      if (stopBtn) stopBtn.style.display = 'none';
    }
  },

  hideCharlestonModal() {
    const modal = document.getElementById('charleston-modal');
    if (modal) modal.style.display = 'none';
  },

  // ─── Win screen ──────────────────────────────────────────────────────────

  showWinScreen(winner, pattern, seats) {
    const overlay = document.getElementById('win-overlay');
    if (!overlay) return;
    overlay.style.display = 'flex';

    const title = document.getElementById('win-title');
    const detail = document.getElementById('win-detail');

    if (winner === null) {
      title.textContent  = 'Wall Exhausted!';
      detail.textContent = 'The wall ran out — no winner. Start a new game.';
    } else {
      const name = MJ.UI.seatLabel(winner);
      title.textContent  = winner === MJ.HUMAN_SEAT ? '🏆 Mahjong! You win!' : `${name} wins!`;
      detail.textContent = pattern
        ? `Winning hand: ${pattern.name} — "${pattern.description}" (${pattern.value}¢)`
        : 'Hand complete!';
    }
  },

  hideWinScreen() {
    const overlay = document.getElementById('win-overlay');
    if (overlay) overlay.style.display = 'none';
  },

  // ─── Full board render ────────────────────────────────────────────────────

  /** Re-render everything based on current game state */
  renderAll(game) {
    const g = game;

    // Wall count
    this.renderWallCount(document.getElementById('wall-count'), g.wall.length);

    // Discard pile
    this.renderDiscardPile(
      document.getElementById('discard-pile'),
      g.discardPile,
      g.lastDiscard
    );

    // Human rack (South = seat 0)
    const humanRack = document.getElementById('human-rack');
    if (humanRack) {
      this.renderRack(humanRack, g.hands[0], {
        selectable: g.phase === 'play' && g.currentSeat === 0,
        selectedIds: MJ.App?.selectedTiles || new Set(),
      });
    }

    // Human exposed
    const humanExposed = document.getElementById('human-exposed');
    if (humanExposed) this.renderExposed(humanExposed, g.exposed[0]);

    // Human flowers
    const humanFlowers = document.getElementById('human-flowers');
    if (humanFlowers) this.renderFlowers(humanFlowers, g.flowers[0]);

    // AI racks (face down)
    for (const seat of [1, 2, 3]) {
      const rackEl = document.getElementById(`rack-${seat}`);
      if (rackEl) this.renderRack(rackEl, g.hands[seat], { faceDown: true, small: true });

      const expEl = document.getElementById(`exposed-${seat}`);
      if (expEl) this.renderExposed(expEl, g.exposed[seat]);

      const countEl = document.getElementById(`count-${seat}`);
      if (countEl) countEl.textContent = `${g.hands[seat].length} tiles`;
    }

    // Turn indicator
    const turnEl = document.getElementById('turn-indicator');
    if (turnEl) {
      turnEl.textContent = g.phase === 'gameover' ? 'Game Over' :
                           g.phase === 'charleston' ? 'Charleston' :
                           `${this.seatLabel(g.currentSeat)}'s turn`;
      turnEl.className = `turn-indicator seat-${g.currentSeat}`;
    }
  }
};
