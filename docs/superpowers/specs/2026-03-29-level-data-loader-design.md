# Level Data Format + Loader - Design Spec
**Date:** 2026-03-29
**Scope:** JSON schema for custom levels and packs, runtime loader function, win condition tick logic, custom level select screen, localStorage persistence. Editor UI is a separate spec.

---

## Overview

Players can play custom-designed levels stored in localStorage. Each level defines world size, obstacles, enemies, pickups, hazards, and objective markers. Levels can be grouped into ordered packs. A loader function hydrates the game state from a level's JSON data, replacing the normal randomized generation. Five win conditions are supported.

---

## Level Data Schema

```json
{
  "name": "Ambush Alley",
  "author": "Player1",
  "created": 1711756800000,
  "worldW": 3000,
  "worldH": 2400,
  "winCondition": "killAll",
  "winParams": {},
  "obstacles": [
    {"type": "pillar", "x": 500, "y": 300, "r": 35},
    {"type": "wall", "x": 800, "y": 200, "w": 26, "h": 150}
  ],
  "enemies": [
    {"type": "scout", "x": 1200, "y": 600},
    {"type": "guard", "x": 1800, "y": 900}
  ],
  "pickups": [
    {"type": "battery", "x": 600, "y": 400, "hidden": false},
    {"type": "weapon", "x": 1000, "y": 500, "hidden": true}
  ],
  "hazards": [
    {"type": "zap_pylon", "x": 1400, "y": 700, "angle": 0.5, "gap": 120},
    {"type": "floor_mine", "x": 2000, "y": 1200}
  ],
  "objectives": [
    {"type": "finish", "x": 2800, "y": 1200}
  ],
  "spawnX": 200,
  "spawnY": 1200
}
```

### Win Conditions

| Condition | `winCondition` | `winParams` | `objectives` |
|-----------|---------------|-------------|-------------|
| Kill All | `"killAll"` | `{}` | none |
| Reach Finish | `"reachFinish"` | `{}` | `[{"type":"finish","x","y"}]` |
| Survive | `"survive"` | `{"seconds": 60}` | none |
| Retrieve | `"retrieve"` | `{}` | `[{"type":"item","x","y"}, {"type":"goal","x","y"}]` |
| Collect All | `"collectAll"` | `{}` | `[{"type":"key","x","y"}, ...]` (N keys) |

### Constraints

- World size: min `canvas.width x canvas.height`, max 4500x4500
- Obstacle types: `pillar` (with `r` radius) and `wall` (with `w`, `h`)
- Enemy types: any valid `ETYPES` key
- Pickup types: any valid `PTYPES` key
- Hazard types: `zap_pylon` (with `angle`, `gap`) and `floor_mine`

---

## Pack Schema

```json
{
  "packName": "Gauntlet Run",
  "author": "Player1",
  "created": 1711756800000,
  "levels": [ ...array of level objects... ]
}
```

A standalone level is stored as a pack with one level. All entries in localStorage are packs.

---

## Runtime Loader

### `loadCustomLevel(levelData)`

1. Set `gameMode='custom'`
2. Set `WORLD_W=levelData.worldW`, `WORLD_H=levelData.worldH`
3. Reset all entity arrays (same pattern as `startBattle`): particles, pickups, pBullets, eBullets, mines, seekers, rockets, boomerangs, fractals, hazards, grenades, gravityWells, faradayCages, enemies
4. Reset flash variables: empFlash, weaponFlash, leechFlash, shockwaveFlash, harbingerRef, portalActive
5. Reset miniMe
6. Call `resetPlayer()`, then set `P.x=levelData.spawnX`, `P.y=levelData.spawnY`
7. Set camera: `camX=P.x-canvas.width/2`, `camY=P.y-canvas.height/2`
8. Populate `obstacles[]` directly from `levelData.obstacles` (push objects as-is, no randomization)
9. For each entry in `levelData.enemies`: call `mkEnemy(e.type, e.x, e.y)` and push to `enemies[]`
10. For each entry in `levelData.pickups`: call `spawnPickup(p.x, p.y, p.type, p.hidden)`
11. For each entry in `levelData.hazards`:
    - `zap_pylon`: call `spawnZapPylonPair(h.x, h.y, h.angle, h.gap)`
    - `floor_mine`: call `spawnFloorMine(h.x, h.y)`
12. Store objective data in module-level `customObjectives` for the tick logic
13. Set `gameState='playing'`, `gameStartTime=Date.now()`

### Module-level state

```javascript
let customPack=null;       // {levels:[], currentIdx:0}
let customObjectives=[];   // current level's objectives array
let customWinCondition=''; // 'killAll','reachFinish','survive','retrieve','collectAll'
let customWinParams={};    // e.g. {seconds:60}
let customSurviveMs=0;     // countdown for survive mode
let customKeysCollected=0; // count for collectAll mode
let customItemHeld=false;  // flag for retrieve mode
```

---

## Win Condition Tick Logic

Added to the main playing loop, gated by `gameMode==='custom'`:

```
if killAll:     enemies.length===0 -> win
if reachFinish: dist(P, finishObj) < 50 -> win
if survive:     customSurviveMs -= dt*1000; if <=0 -> win
if retrieve:    on item pickup set customItemHeld=true; if customItemHeld && dist(P, goalObj) < 50 -> win
if collectAll:  on key pickup increment customKeysCollected; if === total keys -> win
```

On win: call `customLevelWin()` which either advances to the next level in the pack or transitions to `'customResult'`.

### Objective rendering

- `finish`: reuse existing `drawFinishLine` pattern (vertical stripe at finish.x)
- `key`: small rotating key icon with glow (similar to pickup rendering)
- `item`: distinct pickup-style icon (diamond shape)
- `goal`: pulsing circle zone (similar to JR Rescue base)
- `survive` timer: displayed in HUD (countdown text)

---

## Pack Sequencing

### `customLevelWin()`

1. If `customPack.currentIdx < customPack.levels.length - 1`:
   - Increment `customPack.currentIdx`
   - Show brief interstitial (reuse `waveClear`-style pause with "LEVEL COMPLETE" text, 2s)
   - Call `loadCustomLevel(customPack.levels[customPack.currentIdx])`
2. If last level:
   - Transition to `gameState='customResult'`

### `customResult` state

- Shows pack name, total score, total time
- "BACK TO MENU" button returns to `'customSelect'`

---

## Menu Integration

### Start screen

New menu item "LEVEL DESIGNER" added to the main menu `getMenuRects()` list. Clicking it sets `gameState='customSelect'`.

### `customSelect` state

- Title: "CUSTOM LEVELS"
- Lists all saved packs from `pw_custom_levels` localStorage
- Each entry: card showing pack name, author, level count, first level's win condition
- Click a pack: set `customPack={levels, currentIdx:0}`, show briefing, then `loadCustomLevel(levels[0])`
- BACK button: returns to `'start'`
- DELETE button per entry (tap/click, no confirmation modal -- just remove and re-render)
- If no saved levels: show "No custom levels yet" placeholder text

### localStorage

- Key: `pw_custom_levels`
- Value: JSON array of pack objects
- Helpers:
  - `_saveCustomLevels(packs)`: `localStorage.setItem('pw_custom_levels', JSON.stringify(packs))`
  - `_loadCustomLevels()`: `JSON.parse(localStorage.getItem('pw_custom_levels')) || []`

---

## Unresolved Questions

None.
