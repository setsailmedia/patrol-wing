# Gate Obstacle - Design Spec
**Date:** 2026-03-29
**Scope:** New "gate" obstacle type with 3 unlock methods (guard, key, time), rotation animation, collision handling, and level editor integration.

---

## Overview

Gates are closeable obstacles that block all movement and bullets when closed. They rotate 90 degrees on a fixed hinge to open. Three unlock methods: guard-based (enemies defeated or drawn away), key-based (player holds a matching key), and time-based (countdown timer). Gates integrate into the existing obstacle system and the level editor toolbox.

---

## Gate Data Model

Stored in the `obstacles[]` array alongside pillars and walls:

```javascript
{
  type: 'gate',
  x: 500, y: 300,            // hinge point position
  len: 120,                   // gate length (fixed)
  w: 26,                      // gate width (matches wall width)
  orient: 'h',                // 'h' horizontal, 'v' vertical
  hinge: 'left',              // 'left'/'right' (horizontal) or 'top'/'bottom' (vertical)
  unlockType: 'guard',        // 'guard', 'key', 'time'
  unlockParams: {},           // type-specific params (see below)
  open: false,                // permanently open
  openPct: 0,                 // 0=closed, 1=fully open (smooth animation)
  tempOpen: false,            // guard gates: temporarily open when guards drawn away
}
```

### Physical Dimensions

- Fixed size: 120px long, 26px wide
- Hinge point is one end of the rectangle
- Closed: rectangle extends from hinge in the orient direction
- Open: rectangle rotated 90 degrees around hinge point

### Collision

When closed (`openPct < 1`): blocks player, enemies, pBullets, eBullets. Uses the same collision system as walls but with a rotated rectangle based on current `openPct` angle.

When fully open (`openPct === 1`): the gate sits perpendicular to its closed position, flush against the wall/geometry. Still solid (the physical gate doesn't vanish) but no longer blocks the passageway.

Intermediate states during animation: collision rect follows the current rotation angle `openPct * 90 degrees`.

### Animation

`openPct` lerps toward target (0 or 1) at a rate of ~2.0 per second (500ms full swing). Target is 1 when `open || tempOpen`, 0 otherwise. Collision shape updates each tick to match.

---

## Unlock Mechanics

### Guard-Based (`unlockType: 'guard'`)

```javascript
unlockParams: {
  guardPositions: [[1200,600], [1400,800]],  // matched to enemies at load time
  radius: 200,                                // proximity radius
  guardRefs: []                               // populated at runtime with enemy object refs
}
```

**At level load:** Match `guardPositions` to nearest spawned enemies and store refs in `guardRefs`.

**Each tick:**
- If ALL guards are dead: set `open = true` (permanent).
- If all LIVING guards are outside `radius` from gate center: set `tempOpen = true`. Gate opens.
- If any living guard returns within `radius`: set `tempOpen = false`. Gate closes (500ms animation).

**Visual indicator on closed gate:** Small red badge showing guard count (e.g. "2").

### Key-Based (`unlockType: 'key'`)

```javascript
unlockParams: {
  keyId: 'gate_key_1'     // matches a key objective pickup
}
```

**Mechanic:** The level places a key objective with a matching `keyId`. When the player picks up the key, it's added to `P.gateKeys` (a Set). When the player is within 50px of the gate and `P.gateKeys.has(keyId)`: set `open = true` (permanent).

**`P.gateKeys`:** New Set on the player object, reset each level.

**Visual indicator on closed gate:** Gold lock icon. Key pickup color matches gate accent.

### Time-Based (`unlockType: 'time'`)

```javascript
unlockParams: {
  seconds: 30,          // designer-set countdown
  remaining: 30         // runtime countdown (set at load)
}
```

**Mechanic:** `remaining` decrements each tick. When it reaches 0: set `open = true` (permanent).

**Visual indicator on closed gate:** Countdown timer displayed on gate face (e.g. "0:28").

---

## Rendering

### Closed Gate

A thick rectangle matching wall visual style but with distinct coloring:
- Base color: dark metallic (`#334455`)
- Accent stripe along the gate face, color by unlock type:
  - Guard: red (`#ff4444`)
  - Key: gold (`#ffdd00`)
  - Time: cyan (`#00ccff`)
- Hinge point: small circle/rivet at the pivot end
- Status indicator on gate face:
  - Guard: red badge with count
  - Key: lock icon
  - Time: countdown text

### Open Gate

Same rectangle rotated 90 degrees around hinge. Slightly faded (`globalAlpha 0.6`) to indicate non-blocking passageway. Hinge rivet stays visible.

### Animation

Smooth rotation using `ctx.translate` to hinge point, `ctx.rotate(openPct * Math.PI/2)`, draw rectangle from origin. Direction of rotation determined by hinge side.

---

## Collision Implementation

### `gateCollisionRect(gate)`

Returns the current rotated rectangle as `{x, y, w, h, angle}` based on `gate.openPct`. For collision with circles (player, enemies, bullets), use rotated-rect-vs-circle test:
1. Transform the circle center into the gate's local coordinate space (rotate around hinge by `-angle`)
2. Use standard rect-vs-circle AABB test in local space
3. Push-out vector rotated back to world space

### Integration Points

- `circleVsObs(cx, cy, cr)`: add gate case using `gateCollisionRect`
- `pushOutObs(entity, size)`: add gate push-out case
- `reflectRicoVsObs(b)`: add gate bounce case for rico/grenade bullets
- pBullet/eBullet tick: gates block bullets same as walls (bullets removed on contact with closed gate)

---

## Level Data Schema

Gates stored in the obstacles array in level JSON:

```json
{
  "type": "gate",
  "x": 500, "y": 300,
  "orient": "h",
  "hinge": "left",
  "unlockType": "guard",
  "unlockParams": {"guardPositions": [[1200,600],[1400,800]], "radius": 200}
}
```

```json
{
  "type": "gate",
  "x": 800, "y": 600,
  "orient": "v",
  "hinge": "top",
  "unlockType": "key",
  "unlockParams": {"keyId": "gate_key_1"}
}
```

```json
{
  "type": "gate",
  "x": 1200, "y": 400,
  "orient": "h",
  "hinge": "right",
  "unlockType": "time",
  "unlockParams": {"seconds": 30}
}
```

### loadCustomLevel() Changes

When loading obstacles, if `o.type==='gate'`: push the gate object with runtime fields (`open:false, openPct:0, tempOpen:false`). For guard gates, match `guardPositions` to spawned enemies by nearest distance and populate `guardRefs`. For time gates, set `remaining = seconds`. Add `P.gateKeys = new Set()`.

---

## Editor Integration

### New Sidebar Items (under OBSTACLES)

- Gate (Guard) -- red accent icon
- Gate (Key) -- gold accent icon
- Gate (Time) -- cyan accent icon

Each places a gate at the snapped grid position. Default: horizontal orientation, left hinge.

### Gate Properties Toolbar

When a placed gate is clicked/selected in the editor, a floating toolbar appears near it:

**All gate types:**
- **Orientation toggle**: H / V button (switches horizontal/vertical)
- **Hinge toggle**: shows current hinge side, click to cycle (left/right or top/bottom)

**Guard gates additionally:**
- **ASSIGN GUARD** button -- enters assign mode. Next enemy clicked on grid is linked to this gate. A cyan line draws from gate to each assigned guard while selected.
- **CLEAR GUARDS** button -- removes all guard assignments
- Guard count badge: "X guards assigned"

**Key gates additionally:**
- Auto-generates a key ID. A matching key pickup objective is auto-placed 200px away (draggable).

**Time gates additionally:**
- Seconds adjustment: scroll wheel or +/- buttons to set countdown (10-120, default 30)

The toolbar disappears when clicking elsewhere or selecting a different tool.

### Gate Rendering in Editor

Drawn as a thick colored rectangle with accent stripe. Hinge shown as a circle. Guard assignment lines drawn as dashed cyan lines from gate to each assigned guard enemy. Status text on gate face matches the unlock type.

---

## Tick Logic

### `tickGates(dt)`

New function called each playing tick:

```
for each gate in obstacles where type==='gate':
  if gate.open: lerp openPct toward 1
  else if gate.unlockType==='guard':
    check guard refs -- all dead? open=true
    all living guards outside radius? tempOpen=true
    any living guard inside radius? tempOpen=false
    lerp openPct toward (open||tempOpen ? 1 : 0)
  else if gate.unlockType==='key':
    if player within 50px and P.gateKeys.has(keyId): open=true
    lerp openPct toward (open ? 1 : 0)
  else if gate.unlockType==='time':
    remaining -= dt
    if remaining <= 0: open=true
    lerp openPct toward (open ? 1 : 0)
```

---

## Unresolved Questions

None.
