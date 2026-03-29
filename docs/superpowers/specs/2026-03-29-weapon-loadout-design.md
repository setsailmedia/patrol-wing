# Weapon Loadout System -- Design Spec
**Date:** 2026-03-29
**Scope:** Per-craft weapon slot limits, loadout management UI (pause + hangar), localStorage persistence per craft. Replaces the current "all unlocked weapons visible in HUD" model with a slot-based loadout.

---

## Overview

Each craft has a `maxSlots` value (4--10) determining how many weapons can be loaded at once. The player unlocks weapons during gameplay as before, but once the loadout is full, new unlocks go to an "available but not loaded" pool. The player must open a loadout editor (via pause screen or hangar) to swap weapons in and out.

---

## Craft Slot Assignments

New `maxSlots` field on each CRAFTS entry. Inversely correlated with speed.

| Craft | Class | Speed | maxSlots |
|-------|-------|-------|----------|
| Titan | HEAVY | 3.3 | 10 |
| Carrier | COMMAND | 3.8 | 9 |
| Phantom | RECON | 5.2 | 7 |
| Sniper | PRECISION | 6.0 | 6 |
| Specter | STEALTH | 6.2 | 6 |
| Skirmisher | AGILITY | 6.5 | 5 |
| Viper | ASSAULT | 7.8 | 4 |

---

## Data Model

### Player Object

- `P.loadout` -- ordered array of weapon indices currently loaded. Length capped at `CRAFTS[P.craftIdx].maxSlots`. Position in this array = HUD slot = key binding.
- `P.unlockedW` -- stays as-is. A Set of all weapon indices unlocked this session (superset of loadout).
- `P.weaponIdx` -- active weapon index in WEAPONS[]. Must always be a member of `P.loadout`.

### On Weapon Unlock Pickup

1. Add index to `P.unlockedW`.
2. If `P.loadout.length < maxSlots`: auto-append to `P.loadout`, set `P.weaponIdx` to it, flash `WEAPON: <name>`.
3. If `P.loadout` is full: do NOT auto-load. Flash `UNLOCKED <name> -- LOADOUT FULL` (prefix `'UNLOCKED'`).

### Weapon Switching

- Keys 1--9, 0: `P.weaponIdx = P.loadout[keyIndex]` (only if slot exists).
- Q/E/right-click: cycle through `P.loadout` entries only (not all of `P.unlockedW`).
- Weapon bar tap (touch/mouse): same, indexes into `P.loadout`.

### No-Ammo Auto-Switch

The existing `isNoAmmo` logic that auto-switches away from depleted weapons now cycles through `P.loadout` instead of `P.unlockedW`.

---

## Weapon Bar HUD

Currently renders all `WEAPONS.length` (23) slots. Change to render only `P.loadout.length` slots.

- Bar width: `P.loadout.length * (slotW + gap)`, centered on canvas.
- Each slot renders the weapon at `P.loadout[i]`, not `WEAPONS[i]`.
- Slot key label: `i+1` for slots 0--8, `0` for slot 9.
- Active weapon highlight, ammo display, icon glyph -- all unchanged, just indexed through loadout.
- Current weapon name display below bar: unchanged, reads from `WEAPONS[P.weaponIdx]`.

---

## Loadout Editor UI

### Access Points

1. **Pause screen**: new button "MODIFY WEAPONS" (cyan/teal) between Resume and Abort. Transitions to state `'loadoutEdit'`.
2. **Hangar screen**: new button "EDIT LOADOUT" below craft cards. Opens the same editor overlay.

### Editor Layout

- Frozen battlefield (pause) or hangar background behind editor.
- Title: `WEAPONS LOADOUT -- <CRAFT NAME> -- X/Y SLOTS`
- **Loaded zone (top)**: current loadout as weapon cards in order. Empty slots shown as dashed outlines.
- **Available zone (bottom)**: unlocked weapons NOT in loadout. In hangar context (pre-game), shows all 23 weapons since the player is pre-configuring a saved loadout.
- **Weapon card**: icon glyph + weapon name + color accent from WEAPONS entry. Compact horizontal cards.

### Interaction

- Click a **loaded** weapon card: unloads it (moves to available zone). If it was the active weapon, `P.weaponIdx` snaps to `P.loadout[0]` (or the first remaining weapon).
- Click an **available** weapon card: loads it into the next empty slot. If loadout is full, click is ignored (must unload one first).
- Loadout must have at least 1 weapon loaded. Attempting to unload the last weapon is blocked.
- **DONE button**: returns to pause screen or hangar. Saves loadout to localStorage.

### Hangar vs Pause Contexts

| | Hangar | Pause |
|---|---|---|
| Available pool | All 23 weapons | `P.unlockedW` minus `P.loadout` |
| Background | Hangar screen | Frozen battlefield |
| Return state | `'hangar'` | `'paused'` |
| When saved | On DONE | On DONE |

---

## localStorage Persistence

- Key: `pw_loadout_<craftId>` (e.g. `pw_loadout_titan`)
- Value: JSON array of weapon ID strings (not indices). E.g. `["std","spread","grapple","mine"]`
- Stored as IDs so the data survives WEAPONS array reordering.
- On load: resolve IDs back to indices. Silently drop any IDs not found in WEAPONS.
- Saved on: DONE in loadout editor, hangar save.

---

## Game Flow Integration

### New Game Start

1. Load saved loadout from `pw_loadout_<craftId>`.
2. If saved loadout exists with valid entries: `P.loadout = resolvedIndices`, `P.unlockedW = new Set(P.loadout)`, `P.weaponIdx = P.loadout[0]`.
3. If no saved loadout: `P.loadout = [craft.startWeapon]`, `P.unlockedW = new Set([0, craft.startWeapon])`.

### Wave Transitions

`P.loadout` persists across waves unchanged.

### Combat Training

Same loadout system applies. Weapon pickups unlock and auto-load until full.

### Craft Switch in Hangar

When the player selects a different craft, load that craft's saved loadout from localStorage. If switching from a high-slot craft (Titan, 10) to a low-slot craft (Viper, 4), excess weapons are truncated (keep first `maxSlots` entries).

---

## Unresolved Questions

None.
