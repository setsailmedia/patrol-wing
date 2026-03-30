# New Hazards - Design Spec
**Date:** 2026-03-30
**Scope:** 5 new hazard types for use in custom levels and Battle mode. Each has tick logic, rendering, and level editor integration with configurable options.

---

## Overview

Five new environmental hazards that add tactical depth to level design. Each presents a unique challenge: navigation control, timing, area denial, weapon disruption, and unpredictable projectiles. All are placeable in the level editor with configurable parameters.

---

## Hazard 1: Gravity Vortex

**Challenge:** Pulls player and enemies toward center. Touching the core deals heavy damage. Creates "don't get too close" navigation zones.

**Data model:**
```javascript
{type:'gravity_vortex', x, y, radius:200, pullStr:1.5, coreDmg:30}
```

**Tick logic:** Each frame, for player and enemies within `radius`: apply velocity toward center proportional to `pullStr * (1 - dist/radius)`. If within 20px of center: deal `coreDmg` per second.

**Rendering:** Dark purple swirling disc with orbiting particle ring. Inner core pulses brighter. Faint radius outline.

**Editor options:**
- Radius: 100 / 150 / 200 / 250 / 300 (5 presets)
- Pull strength: Weak(0.8) / Medium(1.5) / Strong(2.5)
- Core damage: 10 / 20 / 30 / 40 / 50

---

## Hazard 2: Laser Grid

**Challenge:** Sweeping laser beam between two emitter points. Touching the beam deals instant damage. Forces timing-based movement.

**Data model:**
```javascript
{type:'laser_grid', x, y, angle:0, beamLen:250, sweepSpd:1.0, sweepArc:180, dmg:25, sweepAngle:0}
```

`(x,y)` is the pivot emitter. The beam sweeps `sweepArc` degrees back and forth at `sweepSpd` radians/second. `sweepAngle` is runtime state.

**Tick logic:** Increment `sweepAngle` with ping-pong oscillation within `sweepArc`. Test player/enemy intersection with the line segment from emitter to `emitter + beamLen` at current angle. On intersection: deal `dmg` damage, apply short iframes (500ms) to prevent multi-hit.

**Rendering:** Bright red beam line with glow. Emitter node drawn as a small pulsing circle. Sweep arc shown as faint wedge outline.

**Editor options:**
- Beam length: 100 / 200 / 300 / 400 (4 presets)
- Sweep speed: Slow(0.5) / Medium(1.0) / Fast(2.0)
- Sweep arc: 90 / 180 / 270 / 360 degrees
- Damage: 15 / 25 / 40

---

## Hazard 3: Acid Pool

**Challenge:** Stationary ground zone. Damage-over-time while inside. Slows movement. Enemies are immune. Area denial.

**Data model:**
```javascript
{type:'acid_pool', x, y, radius:80, dps:15, slowPct:0.4}
```

**Tick logic:** If player within `radius`: apply `dps * dt` damage, multiply player speed by `(1 - slowPct)` this frame. Enemies are unaffected (they know the terrain).

**Rendering:** Translucent green puddle with bubbling animation (small circles popping at random positions within radius). Edge darkens. Faint toxic shimmer.

**Editor options:**
- Radius: 40 / 60 / 80 / 100 / 120 (5 presets)
- Damage/s: 8 / 15 / 25
- Slow amount: 20% / 40% / 60%

---

## Hazard 4: EMP Pylon

**Challenge:** Periodic pulse disables player weapons for a duration. No direct damage but leaves you defenseless. Visible charge-up warning.

**Data model:**
```javascript
{type:'emp_pylon', x, y, pulseInterval:12000, disableMs:3000, pulseRadius:250, chargeMs:0, cooldownMs:0}
```

**Tick logic:**
- `cooldownMs` decrements each tick. When 0: begin charge (`chargeMs = 1500`).
- `chargeMs` decrements. When 0: fire pulse. All players within `pulseRadius` get `P.weaponDisableMs = disableMs`.
- Reset `cooldownMs = pulseInterval`.
- While `P.weaponDisableMs > 0`: `fireWeapon()` is blocked, decrement each tick.

**Rendering:** Metallic pylon with antenna. During charge: pulsing blue energy buildup with expanding warning ring. On fire: bright blue flash ring expanding to `pulseRadius`. Idle: slow blue pulse.

**Editor options:**
- Pulse interval: 8s / 12s / 16s / 20s
- Disable duration: 2s / 3s / 4s
- Pulse radius: 150 / 250 / 350

---

## Hazard 5: Ricochet Turret

**Challenge:** Automated turret firing bouncing projectiles at fixed intervals in a fixed direction. Creates unpredictable bullet patterns in enclosed spaces.

**Data model:**
```javascript
{type:'ricochet_turret', x, y, fireAngle:0, fireInterval:3000, projSpd:6, bounceCount:5, dmg:20, cooldownMs:0}
```

**Tick logic:**
- `cooldownMs` decrements. When 0: fire a bouncing projectile in `fireAngle` direction.
- Projectile stored in `hazardProjectiles[]` (new array): `{x, y, vx, vy, bounces:0, maxBounces, dmg, life:4000}`.
- Projectiles bounce off obstacles using `reflectRicoVsObs` pattern and off world walls.
- On player/enemy contact within 8px: deal `dmg`, remove projectile.
- Remove when `bounces >= maxBounces` or `life <= 0`.
- Reset `cooldownMs = fireInterval`.

**Rendering:** Small grey turret body with rotating barrel indicator pointing at `fireAngle`. Muzzle flash on fire. Projectiles: small orange-yellow circles with short trail.

**Editor options:**
- Fire direction: rotatable (scroll wheel adjusts angle)
- Fire interval: 2s / 3s / 4s / 6s
- Projectile speed: Slow(4) / Medium(6) / Fast(9)
- Bounce count: 3 / 5 / 8
- Damage: 15 / 20 / 30

---

## New Module-Level Arrays

```javascript
let hazardProjectiles=[]; // ricochet turret projectiles
```

Added to all reset/start functions alongside existing array resets.

---

## Integration Points

- `tickHazards(dt, now)` extended or new `tickNewHazards(dt)` function for the 5 new types
- `drawHazards()` extended with 5 new rendering branches
- `hazards[]` array stores all hazard instances (existing zap_pylon and floor_mine already here)
- `hazardProjectiles[]` new array for ricochet turret bullets (tick + draw + collision + reset)
- `P.weaponDisableMs` new player field for EMP pylon effect
- `loadCustomLevel()` hydrates new hazard types with runtime fields
- Level editor: 5 new hazard items in sidebar with per-hazard configuration toolbar (similar to gate toolbar pattern)

---

## Editor Toolbar Pattern

Each new hazard, when selected in the editor, shows a floating toolbar with preset buttons for its configurable options (same UI pattern as gate toolbar). The toolbar appears on single-click, persists until clicking elsewhere.

---

## Unresolved Questions

None.
