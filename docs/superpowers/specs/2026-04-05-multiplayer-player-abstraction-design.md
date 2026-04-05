# Multiplayer Sub-Project 1: Player Abstraction - Design Spec
**Date:** 2026-04-05
**Scope:** Refactor the single-player `P` global into a `players[]` array so the codebase can handle N players. Solo play must remain identical. No networking in this sub-project.

---

## Overview

The game currently uses a single global `const P = {...}` for the player. Every system (input, rendering, collision, camera, HUD, enemy AI) reads from `P` directly. To support multiplayer, `P` must become `players[0]` with `P` as an alias, and all systems must be capable of handling additional players in the array.

This is a pure refactor. When complete, solo play is byte-for-byte identical. The `players[]` array simply has one entry. Multiplayer code (Sub-Projects 2-6) will add entries to this array.

---

## Data Model

### players[] array

```javascript
const players = [];
let P; // always points to players[0] (local player)
```

### mkPlayer(craftIdx, color) factory

Returns a player object with the same fields as the current `P`:

```javascript
function mkPlayer(craftIdx, color) {
  const c = CRAFTS[craftIdx];
  return {
    x: WORLD_W/2, y: WORLD_H/2, vx: 0, vy: 0, aim: 0,
    hp: c.hp, maxHp: c.hp, bat: 100, maxBat: 100,
    rotor: 0, iframes: 0, lastShot: 0, alive: true, size: c.size || 18, kills: 0,
    weaponIdx: c.startWeapon || 0,
    unlockedW: new Set([0, c.startWeapon || 0]),
    loadout: [c.startWeapon || 0],
    shieldMs: 0, overchargeMs: 0, invincMs: 0, cloakMs: 0,
    nukeKeys: new Set(), gateKeys: new Set(), weaponDisableMs: 0,
    craftIdx: craftIdx, color: color,
    spd: c.spd, batDrain: c.batDrain, drag: c.drag,
    damageMult: c.damageMult || 1.0, detMult: c.detMult || 1.0,
    stocks: mkStocks(), mineStock: 0, seekStock: 0, noAmmoCount: 0,
    sawtoothAngle: 0,
    isLocal: true, // false for remote players in multiplayer
    teamId: 0, // 0=red, 1=blue (for future CTF)
  };
}
```

### resetPlayer() changes

Currently resets the single `P`. Change to:
1. Clear `players` array
2. Create `players[0]` via `mkPlayer()`
3. Set `P = players[0]`

All existing code referencing `P` continues to work because `P` is an alias.

---

## Systems to Modify

### 1. drawPlayer()

Currently draws only `P`. Change to iterate `players[]`:

```javascript
function drawPlayer() {
  for (const p of players) {
    if (!p.alive) continue;
    if (p.iframes > 0 && Math.floor(p.iframes/75) % 2 === 0) continue;
    // ... existing draw logic but using `p` instead of `P`
    drawPlayerCraft(sx, sy, p.aim, p.size, p.color, lighten(p.color, 90), p.rotor, p.hp/p.maxHp);
  }
}
```

The local player (`P`) draws with all buff effects (shield, overcharge, invincibility). Remote players draw with a simpler subset.

### 2. Enemy AI targeting

Replace direct `P.x`/`P.y` references with `nearestAlivePlayer(x, y)`:

```javascript
function nearestAlivePlayer(ex, ey) {
  let best = P, bestD = Infinity;
  for (const p of players) {
    if (!p.alive) continue;
    const d = dist(ex, ey, p.x, p.y);
    if (d < bestD) { bestD = d; best = p; }
  }
  return best;
}
```

Applied in `tickEnemies()` for: patrol detection range, chase target, attack target, firing aim, retaliatory shots.

### 3. checkCollisions()

**eBullet-vs-player**: currently checks only `P`. Change inner loop to iterate `players[]`. Each player has independent iframes, shield, HP.

**Pickup collection**: currently checks `dist(P.x, P.y, pk.x, pk.y)`. Change to check all alive players. The first player within range collects.

### 4. HUD

`drawHUD()` currently shows one set of HP/battery bars. When `players.length > 1`, add a second set offset to the right side of the screen.

`drawMinimap()` currently shows one player dot. Add dots for all players in `players[]`.

### 5. Death/Gameover

Currently: `if (!P.alive)` triggers gameover. Change to: gameover only when `!players.some(p => p.alive)`.

### 6. Camera

Remains unchanged. Camera follows `P` (the local player). Remote players appear at their world coordinates relative to the local camera. Off-screen remote players show on minimap.

---

## What NOT to Change

- `tickPlayer(dt, now)` stays hardcoded to `P` (local input handling). Remote players will get their own `tickRemotePlayer()` in Sub-Project 4.
- `fireWeapon()` stays hardcoded to `P`. Remote player firing is handled by network messages in Sub-Project 4.
- Camera logic stays on `P`.
- Wave spawning, score tracking, weapon unlock pickups -- all stay on `P` for now.

---

## Unresolved Questions

None.
