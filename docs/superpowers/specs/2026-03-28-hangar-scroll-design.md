# Hangar Horizontal Scroll — Design Spec
**Date:** 2026-03-28
**Scope:** Convert the Aircraft Hangar craft selection from a fixed row to a horizontally scrollable row supporting 7+ craft, showing 4 at a time.

## New State Variable
- `let hangarScroll = 0;` — integer offset (0 to `Math.max(0, CRAFTS.length - HANGAR_VISIBLE)`)
- `const HANGAR_VISIBLE = 4;` — number of craft visible at once

## `_hangarLayout()` Changes
- Remove the `startX = cx - (CRAFTS.length-1)*spacing/2` formula that centers all craft
- Replace with:
  - `const visStart = hangarScroll`
  - `const startX = cx - (HANGAR_VISIBLE-1)*spacing/2` — center only the visible window
- Cards are positioned as `startX + (i - visStart)*spacing` for craft indices `visStart` through `visStart + HANGAR_VISIBLE - 1`
- Add arrow button geometry:
  - `arrowW = 36, arrowH = cardH`
  - `arrowLX = startX - arrowW - 12` (left arrow x)
  - `arrowRX = startX + (HANGAR_VISIBLE-1)*spacing + spacing/2 + 12` (right arrow x)

## Scroll Arrow Buttons
- `◀` left arrow: drawn at `(arrowLX, cardsCY)`, dimmed when `hangarScroll === 0`
- `▶` right arrow: drawn at `(arrowRX, cardsCY)`, dimmed when `hangarScroll >= CRAFTS.length - HANGAR_VISIBLE`
- Style: same `_briefBtn`-like appearance as other game buttons, cyan on hover, dim when disabled

## Scroll Position Indicator
- Row of dots below card row, one dot per craft
- Dot at index `hangarCraft` is filled/bright; others dim
- Dots representing the visible window (indices `hangarScroll` to `hangarScroll + HANGAR_VISIBLE - 1`) have slightly higher opacity

## Auto-Scroll on Selection
When player clicks a card at index `i`:
- If `i < hangarScroll` → `hangarScroll = i`
- If `i >= hangarScroll + HANGAR_VISIBLE` → `hangarScroll = i - HANGAR_VISIBLE + 1`
- Clamp result to valid range `[0, Math.max(0, CRAFTS.length - HANGAR_VISIBLE)]`

## Click Handler Updates
- Arrow left click: `hangarScroll = Math.max(0, hangarScroll - 1)`
- Arrow right click: `hangarScroll = Math.min(CRAFTS.length - HANGAR_VISIBLE, hangarScroll + 1)`
- Card click detection: only process clicks on visible window indices (`hangarScroll` to `hangarScroll + HANGAR_VISIBLE - 1`)

## `getCardCenters()` Update
Must use same `startX + (i - hangarScroll)*spacing` formula for visible cards only. Return positions only for indices `hangarScroll` through `hangarScroll + HANGAR_VISIBLE - 1`.

## `drawHangarScreen()` Live Preview
The live preview at top uses `CRAFTS[hangarCraft]` — no change needed. The preview draw function dispatch (`if craft.id === 'phantom' drawPhantom(...)`) needs new craft IDs added:
- `'sniper'` → `drawEnemyDrone(...)`
- `'carrier'` → `drawEnemyDrone(...)`
- `'skirmisher'` → `drawEnemyDrone(...)`

Stub these with `drawEnemyDrone(...)` for now.

## `hangarScroll` Reset
Reset `hangarScroll` to 0 in all game-start functions:
- `startBattle()`
- `startGame()`
- `startCombatTraining()`
- Any other function that initializes hangar state

This prevents hangar scroll position from persisting into gameplay.

## Unresolved Questions
None.
