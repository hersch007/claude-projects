/**
 * drag-drop.js — Mouse & touch drag-and-drop for the human player's rack
 *
 * Supports:
 *   • Reordering tiles within the rack (drag left/right)
 *   • Discarding a tile by dragging to the discard zone
 *   • Touch support via pointer events
 */
// drag-drop.js — uses global MJ defined by tiles.js
'use strict';

MJ.DragDrop = {
  _dragging:     null,  // { tileId, element, startX, startY, ghost }
  _sourceIndex:  -1,
  _enabled:      false,

  init() {
    // Bind global pointer events
    document.addEventListener('pointermove',  e => this._onMove(e),   { passive: false });
    document.addEventListener('pointerup',    e => this._onUp(e));
    document.addEventListener('pointercancel',e => this._cancel());
  },

  enable()  { this._enabled = true;  },
  disable() { this._enabled = false; },

  /** Attach drag start listener to a tile element */
  attachTile(el, tileId) {
    el.setAttribute('data-tile-id', tileId);
    el.addEventListener('pointerdown', e => this._onDown(e, tileId));
  },

  _onDown(e, tileId) {
    if (!this._enabled) return;
    if (e.button !== undefined && e.button !== 0) return; // left button only

    e.preventDefault();
    const el  = e.currentTarget;
    el.setPointerCapture(e.pointerId);
    const rect = el.getBoundingClientRect();

    // Build ghost element
    const ghost = el.cloneNode(true);
    ghost.classList.add('tile-ghost');
    ghost.style.position = 'fixed';
    ghost.style.left     = rect.left + 'px';
    ghost.style.top      = rect.top  + 'px';
    ghost.style.width    = rect.width  + 'px';
    ghost.style.height   = rect.height + 'px';
    ghost.style.zIndex   = '9999';
    ghost.style.opacity  = '0.85';
    ghost.style.pointerEvents = 'none';
    ghost.style.transform = 'rotate(-3deg) scale(1.08)';
    ghost.style.transition = 'none';
    document.body.appendChild(ghost);

    el.classList.add('tile-dragging');

    this._dragging = {
      tileId,
      element: el,
      ghost,
      startX:  e.clientX,
      startY:  e.clientY,
      offsetX: e.clientX - rect.left,
      offsetY: e.clientY - rect.top,
    };

    const rack = el.closest('.rack');
    if (rack) {
      this._sourceIndex = [...rack.children].indexOf(el);
    }
  },

  _onMove(e) {
    if (!this._dragging) return;
    e.preventDefault();

    const { ghost, offsetX, offsetY } = this._dragging;
    ghost.style.left = (e.clientX - offsetX) + 'px';
    ghost.style.top  = (e.clientY - offsetY) + 'px';

    // Highlight discard zone if hovering over it
    const discardZone = document.getElementById('discard-pile');
    if (discardZone) {
      const dr = discardZone.getBoundingClientRect();
      const over = e.clientX >= dr.left && e.clientX <= dr.right &&
                   e.clientY >= dr.top  && e.clientY <= dr.bottom;
      discardZone.classList.toggle('drop-target-active', over);
    }
  },

  _onUp(e) {
    if (!this._dragging) return;
    const { tileId, element, ghost } = this._dragging;

    ghost.remove();
    element.classList.remove('tile-dragging');

    // Check if dropped on discard zone
    const discardZone = document.getElementById('discard-pile');
    if (discardZone) {
      const dr = discardZone.getBoundingClientRect();
      const onZone = e.clientX >= dr.left && e.clientX <= dr.right &&
                     e.clientY >= dr.top  && e.clientY <= dr.bottom;
      discardZone.classList.remove('drop-target-active');

      if (onZone) {
        this._dragging = null;
        window.dispatchEvent(new CustomEvent('mjDiscard', { detail: { tileId } }));
        return;
      }
    }

    // Check if dropped onto another tile in the rack (reorder)
    const target = document.elementFromPoint(e.clientX, e.clientY);
    const targetTile = target?.closest('.tile[data-tile-id]');
    if (targetTile && targetTile !== element) {
      const targetId = parseInt(targetTile.getAttribute('data-tile-id'), 10);
      window.dispatchEvent(new CustomEvent('mjReorder', { detail: { fromId: tileId, toId: targetId } }));
    }

    this._dragging = null;
  },

  _cancel() {
    if (!this._dragging) return;
    this._dragging.ghost.remove();
    this._dragging.element.classList.remove('tile-dragging');
    document.getElementById('discard-zone')?.classList.remove('drop-target-active');
    this._dragging = null;
  }
};
