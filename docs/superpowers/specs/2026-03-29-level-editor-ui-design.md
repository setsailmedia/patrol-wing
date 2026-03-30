# Level Editor UI - Design Spec
**Date:** 2026-03-29
**Scope:** Full-screen grid-based level editor with toolbox sidebar, setup wizard, save/pack flow, copy-to-pack on customSelect, and placeholder UI for leaderboard/awards. Builds on the Level Data Format + Loader (spec 1) which is already implemented.

---

## Overview

Players create custom levels using a full-screen grid editor. A left sidebar toolbox provides placeable items (obstacles, enemies, pickups, hazards, objectives). Levels are saved into packs stored in localStorage. The customSelect screen is enhanced with expandable pack views, per-level actions (play, copy-to, delete), and placeholder panels for future multiplayer features (leaderboards, awards).

---

## Game States

| State | Purpose |
|-------|---------|
| `'levelSetup'` | Wizard dialog: name, world size, win condition |
| `'levelEditor'` | Full-screen grid editor with sidebar toolbox |
| `'levelSavePrompt'` | "Add another level?" dialog after save |

Existing `'customSelect'` state is enhanced with expandable pack/level views.

---

## Editor Flow

```
customSelect -> "CREATE NEW" button
  -> levelSetup (name, world W/H, win condition, win params)
  -> levelEditor (grid + sidebar, place items)
  -> Save button
  -> levelSavePrompt ("Add another level to this pack?")
    -> Yes: levelSetup (fresh level, same pack context)
    -> No: save pack to localStorage, return to customSelect
```

First level in a session prompts for pack name.

---

## Module-Level Editor State

```javascript
let editorPack=null;    // {packName, author, levels:[]}
let editorLevel=null;   // current level JSON object being edited
let editorTool='';      // selected tool, e.g. 'pillar_m', 'scout', 'battery'
let editorCamX=0,editorCamY=0; // camera pan offset
let editorSidebarScroll=0;     // sidebar scroll offset
let editorExpandedCat='';      // which sidebar category is expanded
```

---

## Setup Wizard (`levelSetup`)

Centered dialog over dark background:

- **Level Name**: HTML text input (reuse hidden input pattern like `colorPick`). Default "Untitled Level".
- **World Width**: slider, `canvas.width` to 4500, snapped to 100px, default 2600.
- **World Height**: slider, `canvas.height` to 4500, snapped to 100px, default 1700.
- **Win Condition**: 5 clickable option cards (Kill All, Reach Finish, Survive, Retrieve, Collect All). Default Kill All. Selected card highlighted.
- **Win Params**: appears contextually. Survive: "Seconds" number input (default 60). Others: nothing.
- **START EDITING** button.
- **BACK** button: returns to customSelect.

On START EDITING: creates `editorLevel` from the chosen settings, transitions to `'levelEditor'`. If first level in session, prompts for pack name (small text input overlay).

---

## Editor Grid (`levelEditor`)

### Layout

- **Left sidebar**: 180px wide, dark background, tool categories
- **Grid canvas**: fills remaining screen width and full height
- **Top-right corner**: SAVE button, SETTINGS button (re-opens setup wizard for current level's metadata), BACK button (return to customSelect with confirmation if unsaved)

### Grid Rendering

- 50px cell grid, dark background (`#060c18`), faint grid lines (`rgba(0,80,180,0.12)`)
- World bounds shown as bright border
- Camera pans via arrow keys, WASD, or middle-mouse drag
- Camera clamped to world bounds

### Placing Items

- Left-click at cursor: places the selected tool item snapped to nearest 50px grid point
- Ghost preview of current tool shown at snapped position under cursor (semi-transparent)
- Right-click on a placed item: removes it
- Eraser tool: left-click to remove instead of place

### Item Rendering

Simplified but recognizable versions of in-game visuals:
- **Obstacles**: pillars as grey circles (radius shown), walls as grey rectangles
- **Enemies**: colored circles with type label text, color from ETYPES
- **Pickups**: small colored diamonds with type initial, color from PTYPES
- **Hazards**: zap pylons as yellow paired dots with connecting line, floor mines as red circles
- **Objectives**: finish line as yellow vertical stripe, keys as gold "K", item as orange diamond, goal as green circle
- **Player spawn**: bright cyan crosshair marker (always exactly one)

### Zap Pylon Placement

Zap pylons are pairs. On click: places the center point. Mouse scroll wheel adjusts the `gap` (80-200px range). Shift+scroll adjusts the `angle`. Visual preview updates in real-time before confirming with a second click.

---

## Left Sidebar Toolbox

180px wide, categories with expandable subcategories. Data-driven from existing game maps so new entries auto-appear.

### Categories

**OBSTACLES**
- Pillar Small (r:26)
- Pillar Medium (r:35)
- Pillar Large (r:46)
- Wall H Short (90x26)
- Wall H Long (190x26)
- Wall V Short (26x90)
- Wall V Long (26x190)

**ENEMIES** -- auto-populated from `Object.keys(ETYPES)`
Each entry: colored dot + name. Shows all registered enemy types.

**PICKUPS** -- auto-populated from `Object.keys(PTYPES)` or the existing `DROP_TABLE` types
Each entry: colored icon + name.

**HAZARDS**
- Zap Pylon
- Floor Mine

**OBJECTIVES** -- filtered by current level's win condition
- Player Spawn (always shown)
- Finish Line (reachFinish only)
- Key (collectAll only)
- Item (retrieve only)
- Goal (retrieve only)

**TOOLS**
- Eraser

### Sidebar Behavior

- Category headers clickable to expand/collapse
- Only one category expanded at a time
- Selected tool highlighted with accent border
- Sidebar scrolls if content exceeds screen height (mouse wheel on sidebar area)

---

## Save Flow

### Save Button (top-right of editor)

1. Validate: at least 1 enemy OR 1 objective placed. Player spawn must exist.
2. Bundle `editorLevel` into final JSON (matches level schema from spec 1)
3. If first level in this editing session and no pack name yet: prompt for pack name (text input overlay, default "Custom Pack")
4. Push level to `editorPack.levels`
5. Transition to `'levelSavePrompt'`

### levelSavePrompt State

Centered dialog:
- "Level saved to <pack name>!"
- Level preview summary: name, world size, enemy count, pickup count, win condition
- **ADD ANOTHER LEVEL** button: returns to `'levelSetup'` with fresh level, same `editorPack`
- **DONE** button: saves `editorPack` to localStorage via `_saveCustomLevels`, returns to `'customSelect'`

---

## customSelect Enhancements

### Expandable Pack View

Currently packs render as single cards. Change to: clicking a pack expands it inline to show its individual levels as sub-rows.

Expanded pack layout:
- Pack header row (name, author, level count) -- click again to collapse
- Per-level sub-rows indented, showing: level name, win condition icon, enemy count
- Per-level action buttons: **PLAY** | **COPY TO...** | **EDIT** | **DELETE**

### Per-Level Actions

**PLAY**: loads that single level as a one-level pack and starts it.

**COPY TO...**: opens a small overlay listing all packs + "NEW PACK" option. Clicking a destination copies the level JSON into that pack. Clicking "NEW PACK" prompts for a name, creates a new pack with the copied level.

**EDIT**: loads the level into the editor (`'levelEditor'` state). On save, updates the level in-place in its pack.

**DELETE**: removes the level from the pack. If it was the last level, deletes the pack entirely.

### Leaderboard + Awards Stubs

When a level sub-row is selected/expanded, the bottom portion of the screen shows two placeholder panels:

**LEADERBOARD**
- Bordered box with title "LEADERBOARD"
- Body: "Multiplayer coming soon" in dim text
- Reserved height: ~120px

**AWARDS**
- Bordered box with title "AWARDS"
- Body: "Multiplayer coming soon" in dim text
- Reserved height: ~100px

### Level Schema Additions

Two empty placeholder fields added to each level object at save time:
```json
{
  "leaderboard": [],
  "awards": []
}
```

---

## Unresolved Questions

None.
