# Warehouse 51

**Platform:** Godot 4.7 — Desktop  
**Genre:** Dark conspiracy catalog/research game (Arcane Library feel, AI Oversight setting)

## Core Loop
Catalog → Research → Feed the AI → Survive the consequences.

Every fully-researched item feeds the AI Oversight system. Complete a series and it gets a major meal. Watch the phase shift. Decide whether to keep going.

## AI Phases
| Phase | Knowledge Threshold | Behavior |
|---|---|---|
| Dormant | 0 | Silent |
| Observing | 20 | Watching |
| Assisting | 50 | Helpful, suspicious |
| Controlling | 80 | Pressure mounts |
| Hostile | 120 | Active threat |

## Project Structure
```
scenes/main.tscn        — root scene
scripts/
  item_data.gd          — Resource class for all catalog items
  ai_overseer.gd        — Knowledge/suspicion/phase state machine
  research_manager.gd   — Research progression, feeds AIOverseer on completion
  main.gd               — Orchestrator + programmatic UI
data/items/*.tres       — Item resources (add new items here)
```

## Current Items
- **Project Blue Book** (series, 3 vols) — declassified/classified USAF records
- **Neural Interface Program** (series, 2 items, AI-sensitive) — reverse-engineered tech
- **Roswell Retrieval** (series, 1 of many) — debris fragment
