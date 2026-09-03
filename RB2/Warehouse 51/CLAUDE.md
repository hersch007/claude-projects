# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Warehouse 51 — a dark conspiracy catalog/research game built in **Godot 4.7** for Desktop. Core loop: receive artifact → read dossier → drag to correct shelf row → research it → feed the AI Overseer → watch the phase escalate.

## Running the Game

Open the project in the Godot 4.7 editor and press **F5** (or the Play button). There is no build script — all development happens inside the Godot editor. The root scene is `scenes/main.tscn`.

## Architecture

All scene logic is built **programmatically in GDScript** — there are no `.tscn` child-nodes worth inspecting; `main.gd` constructs the entire UI at runtime via `_build_*` methods.

### Autoloads (global singletons)

| Node path | Script | Role |
|---|---|---|
| `/root/AIOverseer` | `scripts/ai_overseer.gd` | Knowledge/suspicion/phase state machine; emits signals on phase change |
| `/root/ResearchManager` | `scripts/research_manager.gd` | Item registry + research progression; calls `AIOverseer.feed_item()` on completion |

`main.gd` resolves both via `get_node("/root/…")` in `_ready()`.

### Data layer

Items are `Resource` subclasses (`ItemData`) stored as `.tres` files under `data/items/`. Add a new item by creating a `.tres` there — `main.gd` auto-loads all `.tres` files from that directory at startup via `DirAccess`. Procedurally-generated filler items are also appended in `_populate_queue()`.

### Key design invariants

- **Category → shelf row mapping** is determined by `ROW_CATEGORIES` in `main.gd`. An item's `category` field must exactly match one of those strings, or it defaults to `"Intelligence"` row 0.
- **AI feed path**: `ResearchManager.advance_research()` → `_handle_fully_researched()` → `AIOverseer.feed_item()`. Suspicion gain scales with `ai_threat_level`; `is_ai_sensitive` adds a 1.75× multiplier; completing a series adds a further 1.5× and fires `series_completed`.
- **Phase thresholds** live in `AIOverseer.PHASE_THRESHOLDS` (Observing 20, Assisting 50, Controlling 80, Hostile 120) and are checked against `total_knowledge`, not suspicion.
- **Mis-filing** is tracked in `_wrong_filed` (a dictionary in `main.gd`); it changes item card SVG colours to a red-tinted wood tone and raises suspicion by 8.0 immediately.
- **Visual type** of shelf cards (blueprint vs folder vs default crate) is inferred from title keywords at spawn time — no enum field on `ItemData`.

### CanvasLayer stack

| Layer | Contents |
|---|---|
| -1 | `warehouse_bg.gd` — scrollable procedural background |
| 5 | Item cards (shelf) |
| 7 | Row-drop overlays (shown while dragging) |
| 9 | Tray artifact + row label strip |
| 10 | HUD (top bar + notification strip) |
| 11 | Dossier panel |

### Adding new items

Create `data/items/<id>.tres` as a `ItemData` resource. Required fields: `id`, `title`, `category` (must match a `ROW_CATEGORIES` entry), `series` (empty string if standalone). Optional but meaningful: `ai_feed_value`, `ai_threat_level`, `is_ai_sensitive`, `is_series_key_item`.

## Palette (main.gd constants)

```
C_PANEL   = dark walnut      C_TEXT = aged parchment
C_BORDER  = warm oak         C_DIM  = dim aged ink
C_GREEN   = #00E87E          C_HEADER = warm parchment header
C_AMBER   = #FF9E1A          C_RED    = #F22140
```
