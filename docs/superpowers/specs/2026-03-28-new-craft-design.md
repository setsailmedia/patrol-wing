# New Playable Craft — Design Spec
**Date:** 2026-03-28
**Scope:** Add 3 new player-pilotable craft: Sniper (PRECISION CLASS), Carrier (COMMAND CLASS), Skirmisher (AGILITY CLASS).

## Craft Definitions

### SNIPER — PRECISION CLASS

```javascript
{
  id:'sniper', name:'SNIPER', sub:'PRECISION CLASS',
  desc:'Long-range elimination specialist. Patience is a weapon — the longer between shots, the greater the damage.',
  stats:{speed:4,armor:1,fire:2,battery:3},
  hp:62, spd:6.0, batDrain:2.2, size:15, drag:0.85,
  ability:'DEAD EYE  —  Damage scales ×1–3 with time between shots (max at 2s)',
  startWeapon:11,  // Rico Cannon
  damageMult:1.0, detMult:1.0,
  defaultColor:'#44ffcc',
}
```

### CARRIER — COMMAND CLASS

```javascript
{
  id:'carrier', name:'CARRIER', sub:'COMMAND CLASS',
  desc:'Deploys two attack drones that engage nearby enemies autonomously. Command field slows hostile fire rate.',
  stats:{speed:2,armor:3,fire:3,battery:4},
  hp:140, spd:3.8, batDrain:1.8, size:22, drag:0.91,
  ability:'COMMAND FIELD  —  2 attack drones + enemy fire rate –25% in range',
  startWeapon:0,  // Standard
  damageMult:1.0, detMult:1.0,
  defaultColor:'#00aaff',
}
```

### SKIRMISHER — AGILITY CLASS

```javascript
{
  id:'skirmisher', name:'SKIRMISHER', sub:'AGILITY CLASS',
  desc:'Lowest drag in the fleet. Sharp direction reversals under fire trigger split-second dodge frames.',
  stats:{speed:4,armor:2,fire:4,battery:2},
  hp:80, spd:6.5, batDrain:2.9, size:16, drag:0.78,
  ability:'SLIP STREAM  —  Direction reversal under fire grants 0.4s invincibility',
  startWeapon:10,  // Burst Cannon
  damageMult:1.0, detMult:1.0,
  defaultColor:'#ff44aa',
}
```

## Special Mechanics

### DEAD EYE (Sniper)

Module-level: `let deadEyeMs = 0;` — ms since player last fired a pBullet.

Tick: `if(P.craftIdx === CRAFTS.findIndex(c=>c.id==='sniper')) deadEyeMs += dt*1000;`

Reset: `deadEyeMs = 0` whenever player fires.

Multiplier:
```javascript
const deadEyeMult = P.craftIdx===sniperIdx
  ? Math.min(3.0, 1.0 + (deadEyeMs/2000)*2.0)
  : 1.0;
```

Applied: multiply `WEAPONS[P.weaponIdx].dmg` by `deadEyeMult` when creating pBullet.

HUD: small indicator showing current multiplier (e.g. `×1.4`) in DEAD EYE color `#44ffcc`.

### COMMAND FIELD (Carrier)

Module-level: `let carrierDrones = [];` — array of 2 drone objects.

Drone object: `{angle, hp, maxHp, respawnMs, x, y, lastFired}`

Orbit: drone 0 at `P.rotor + 0`, drone 1 at `P.rotor + Math.PI` — radius 65px.

Drone HP: 40 each. On destruction: `respawnMs = 10000`, removed from active until respawn.

Drone fires: every 900ms at nearest enemy within 280px, dmg 14, bullet spd 12 (same as std).

Command field: in `tickEnemies`, if carrier is active — enemies within 320px have effective `fireMs` threshold increased by 25%.

Tick: `tickCarrierDrones(dt)` — update positions, fire logic, respawn timer.

Draw: `drawCarrierDrones()` — small drone shapes orbiting player, HP ring if damaged.

### SLIP STREAM (Skirmisher)

Module-level: `let slipstreamMs = 0;` — countdown timer for dodge frames.

Trigger condition (check in `tickPlayer`):
- Craft is skirmisher
- Current velocity direction differs from previous frame by > 110 degrees
- `P.iframes === 0`
- At least one eBullet within 180px of player

On trigger: `P.iframes = 400; slipstreamMs = 400; SFX.shield();`

Visual: while `slipstreamMs > 0`, draw 3 afterimage ghost copies of player behind current position (fading opacity), color `#ff44aa`. Decrement: `slipstreamMs -= dt*1000`.

HUD: none needed — iframes flash handles feedback.

## Draw Functions

Each craft gets its own draw function matching the existing signature pattern:

```javascript
drawSniper(x, y, aim, sz, col, acc, spin, hp)
drawCarrier(x, y, aim, sz, col, acc, spin, hp)
drawSkirmisher(x, y, aim, sz, col, acc, spin, hp)
```

All follow the `ctx.save() / translate / rotate / restore` pattern used by existing craft draw functions.

**Visual design direction:**

`drawSniper`: Long narrow fuselage, single elongated barrel extending forward (2x body length), minimal cross-section, no rotors. Two small stabiliser fins at rear. Looks like a dart or arrow.

`drawCarrier`: Wide hexagonal body (larger than Titan), 2 visible hardpoints on sides where drones dock when recalled, prominent command array (small dish or antenna) on top.

`drawSkirmisher`: Swept-back chevron/delta wing shape, no rotors, engine glow at rear. Asymmetric swept wings give a dynamic visual lean. Looks fast and aggressive.

## Hangar Integration

`drawHangarScreen()` live preview dispatch — add 3 new `else if` branches:

```javascript
else if(craft.id==='sniper')     drawSniper(cx, previewCY, ...);
else if(craft.id==='carrier')    drawCarrier(cx, previewCY, ...);
else if(craft.id==='skirmisher') drawSkirmisher(cx, previewCY, ...);
```

## Combat Training

`CT_SEQUENCE` is unchanged — CT fights enemies, not craft. No CT changes needed.

## Tick/Draw Integration

`tickPlayer` gains:
- Dead eye timer increment (sniper only)
- Slip stream trigger check (skirmisher only)

Main `playing` tick block: call `tickCarrierDrones(dt)`.

Draw block: call `drawCarrierDrones()` between `drawPlayer()` and `drawMiniMe()`.

On game start/reset: `carrierDrones = []; deadEyeMs = 0; slipstreamMs = 0;`

`resetPlayer()` initialises carrier drones if selected craft is carrier.

## Unresolved Questions

None.
