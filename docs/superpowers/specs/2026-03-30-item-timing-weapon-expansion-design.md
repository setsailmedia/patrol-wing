# Item Timing + Weapon Expansion - Design Spec
**Date:** 2026-03-30
**Scope:** Universal appear/disappear timing on all placeable items, weapon pickup type expansion (specific/random/generic), and removal of default dropTimer on editor-placed pickups.

---

## Overview

Three changes to the level editor and runtime:
1. Any placeable item (obstacle, enemy, pickup, hazard) can have an optional appear delay and duration. Items start invisible/inactive and appear after `appearAfter` seconds, then disappear after `duration` seconds.
2. Weapon pickups in the level editor can be configured as: generic (next unlock), specific weapon, or random from a designer-chosen set.
3. Editor-placed pickups no longer have a `dropTimer` by default. They persist until collected.

---

## 1. Universal Appear/Disappear Timing

### Data Model

Two optional fields on any placed item in the level JSON:

```json
{
  "type": "scout", "x": 500, "y": 300,
  "appearAfter": 10,
  "duration": 30
}
```

- `appearAfter`: seconds after level start before the item appears. Default: 0 (immediate). Range: 0-300.
- `duration`: seconds the item persists after appearing. Default: 0 (permanent -- never disappears). Range: 0-300. 0 means permanent.

### Runtime

At level load, each entity (obstacle, enemy, pickup, hazard) gets runtime fields:
- `_appearAt`: `appearAfter * 1000` (ms countdown)
- `_duration`: `duration * 1000` (ms countdown, 0 = permanent)
- `_visible`: false if `_appearAt > 0`, true otherwise
- `_expired`: false

**Tick logic** (new function `tickItemTimers(dt)`):
- For each entity with `_appearAt > 0`: decrement. When 0: set `_visible = true`, spawn appear particles.
- For each visible entity with `_duration > 0`: decrement. When 0: set `_expired = true`, `_visible = false`, spawn vanish particles.

**Visibility gating:**
- Obstacles: invisible obstacles don't collide (skip in `circleVsObs`, `pushOutObs`)
- Enemies: invisible enemies don't tick AI, don't fire, don't collide. Rendered at alpha 0.
- Pickups: invisible pickups can't be collected. Not rendered.
- Hazards: invisible hazards don't tick effects. Not rendered.

**Expired items:** Removed from their arrays on next tick (or marked inactive).

### Editor Integration

When any item is selected in the editor (via the Adjust tool or gate selection), the properties toolbar shows two optional fields:
- "APPEAR AFTER: Xs" -- click cycles through 0/5/10/15/30/60/120s presets
- "DURATION: Xs" -- click cycles through 0(permanent)/5/10/15/30/60/120s presets

These fields are saved to the level JSON and loaded at runtime.

### Visual Indicators in Editor

Items with `appearAfter > 0` render with a clock icon and the delay value.
Items with `duration > 0` render with a timer icon and the duration value.

---

## 2. Weapon Pickup Expansion

### Current Behavior

Weapon pickups (`type:'weapon'`) always unlock the next sequential weapon. No designer control.

### New Weapon Pickup Modes

Three modes, set via `weaponMode` field on the pickup:

**Generic (default):** `weaponMode:'generic'` -- current behavior, unlocks next weapon in sequence. No change needed.

**Specific:** `weaponMode:'specific', weaponId:'grapple'` -- always drops the named weapon. If already unlocked, acts as ammo refill.

**Random:** `weaponMode:'random', weaponPool:['grapple','grenade','leech']` -- randomly selects one from the pool at pickup time. If selected weapon is already unlocked, picks another from pool. If all unlocked, acts as ammo refill.

### Runtime Changes

In the `case'weapon':` pickup handler, check `pk.weaponMode`:
- If `'specific'`: unlock `pk.weaponId` index (or refill ammo if already unlocked).
- If `'random'`: pick random from `pk.weaponPool`, unlock it (or refill).
- Otherwise (generic): existing behavior.

### Editor Integration

When a weapon pickup is selected in the editor, the toolbar shows:
- **Mode toggle:** GENERIC / SPECIFIC / RANDOM (3 buttons)
- If SPECIFIC: weapon selector (list of all weapon names, click to choose)
- If RANDOM: weapon multi-selector (click weapons to toggle inclusion in pool)

### Level JSON

```json
{"type": "weapon", "x": 500, "y": 300, "hidden": false, "weaponMode": "specific", "weaponId": "grapple"}
{"type": "weapon", "x": 800, "y": 400, "hidden": false, "weaponMode": "random", "weaponPool": ["grapple", "grenade", "leech"]}
```

---

## 3. Pickup Timer Fix

### Problem

Editor-placed pickups currently get `dropTimer:6000` from `spawnPickup()`, causing them to disappear after 6 seconds.

### Fix

In `loadCustomLevel()`, after calling `spawnPickup()` for each pickup, find the just-pushed pickup and set `dropTimer:null` (permanent).

OR: pass a flag to `spawnPickup` to skip the dropTimer. Since `spawnPickup` sets `dropTimer:hidden?null:6000`, the fix is to pass `hidden:true` for editor pickups (but this makes them invisible).

Better fix: after the pickup push in loadCustomLevel, override `dropTimer`:
```javascript
if(pickups.length>idx) pickups[pickups.length-1].dropTimer=null;
```

This makes editor-placed pickups permanent by default. The appear/disappear timing system (section 1) provides the designer-controlled alternative.

---

## Unresolved Questions

None.
