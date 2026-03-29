# New Hostile Types & Bosses — Design Spec
**Date:** 2026-03-28
**Scope:** 5 new hostile enemy types + 2 new boss types. Combined into one implementation. Bosses placed in Battle W5 (random pool), Combat Training sequence, and TT Ghost Run / Dance Birdie Dance final stretches.

---

## New Hostile Types

All new hostiles use the existing enemy data structure `{type, x, y, vx, vy, aim, hp, maxHp, spd, fireMs, dmg, color, accent, score, det, atk, patR, drag, patA, patCx, patCy, state, lastFired, rotor, size, stunMs, stunMoveMs, stunFireMs}` and are added to the `ENEMY_TYPES` map.

### 1. Ravager

```javascript
ravager: {
  size:18, hp:60, spd:2.2, fireMs:2000, dmg:10, color:'#ff2200', accent:'#ff8866',
  score:180, det:220, atk:220, patR:120, drag:0.82
}
```

**Mechanic — Charge Attack:**

Module-level arrays: `ravagerCharge` state is stored per-enemy in a custom field `e.chargeMs` (countdown) and `e.chargeVx/e.chargeVy` (locked direction vector).

Tick logic (in `tickEnemies`):
- When `e.type==='ravager'` and `e.state==='attack'` and `e.chargeMs` is undefined or <= 0:
  - If `dist(e.x,e.y,P.x,P.y) < 220`: lock direction toward player, set `e.chargeMs=600`, `e.chargeVx/Vy = normalised * 11`
- While `e.chargeMs > 0`: `e.x += e.chargeVx * dt * 60`, `e.y += e.chargeVy * dt * 60`, decrement `e.chargeMs -= dt*1000`, skip normal movement
- Collision during charge: if `dist(e.x,e.y,P.x,P.y) < e.size + P.size` and `P.iframes===0`: deal 38 damage to player, end charge early (`e.chargeMs = -1500` as a recharge cooldown)

Draw: draws using `drawEnemyDrone` base, with a forward-pointing elongated nose indicator during charge (color flush to full accent).

---

### 2. Splitter

```javascript
splitter: {
  size:20, hp:110, spd:2.0, fireMs:1000, dmg:16, color:'#ffcc00', accent:'#fff088',
  score:260, det:290, atk:200, patR:130, drag:0.88
}
```

**Sub-type — Shard:**

```javascript
shard: {
  size:10, hp:30, spd:3.5, fireMs:1300, dmg:8, color:'#ffcc00', accent:'#fff088',
  score:80, det:220, atk:160, patR:80, drag:0.85
}
```

**Mechanic:** In `killEnemy(i)`, before removing the enemy, if `enemies[i].type==='splitter'`: spawn 2 Shards at `enemies[i].x/y` offset by ±20px, state `'chase'`. Shards die cleanly (no further split). Shards do NOT trigger the split logic.

---

### 3. Cloaker

```javascript
cloaker: {
  size:13, hp:70, spd:3.2, fireMs:1100, dmg:17, color:'#88ffee', accent:'#ccffee',
  score:240, det:320, atk:240, patR:140, drag:0.86
}
```

**Mechanic — Stealth:**

Custom field: `e.visibleMs` — countdown after last fire.

Draw: render at `ctx.globalAlpha = e.visibleMs > 0 ? 1.0 : 0.08`. When `e.visibleMs > 0`, also render a subtle shimmer ring.

Tick: when Cloaker fires (`e.lastFired` updated), set `e.visibleMs = 420`. Decrement `e.visibleMs -= dt*1000` each tick.

pBullet hits still register at full alpha (hitbox unchanged). The low alpha is visual only.

---

### 4. Demolisher

```javascript
demolisher: {
  size:24, hp:280, spd:1.3, fireMs:2200, dmg:0, color:'#cc44ff', accent:'#ee99ff',
  score:380, det:340, atk:280, patR:80, drag:0.93
}
```

**Mechanic — Plasma Bomb:**

Instead of `fireEBullet`, the Demolisher fires a slow arcing projectile: `eBullets.push({..., isBomb:true, bSz:10, spd:3.5, life:2000})`.

On impact (bullet life expires OR `isBomb:true` bullet travels more than 600px from origin): spawn a hazard zone using the existing `hazards` array — same structure as existing wave hazards, radius 55px, duration 2500ms, color `'#cc44ff'`. Detection handled in `tickBullets`: after updating position, if `b.isBomb && (b.life <= 0 || dist(b.x,b.y,b.ox,b.oy) > 600)` — remove bullet, push hazard. Store `b.ox/b.oy` (origin) at bullet creation time.

`dmg:0` in the stats because damage comes from the hazard zone, not the projectile itself. Hazard zone deals 18 dmg/s to player while inside (consistent with existing hazard system).

---

### 5. Hunter

```javascript
hunter: {
  size:9, hp:40, spd:6.0, fireMs:1600, dmg:11, color:'#ff44cc', accent:'#ffaaee',
  score:160, det:280, atk:180, patR:160, drag:0.80
}
```

**Mechanic — Drone Targeting:**

In `tickEnemies`, when `e.type==='hunter'`:
- If `CRAFTS[P.craftIdx].id==='carrier'` and any `carrierDrones` entry has `hp > 0`:
  - Use nearest active drone as movement/aim target instead of `P.x/P.y`
- Otherwise: normal player targeting

No special fire logic — still uses `fireEBullet` toward the current target.

---

## New Boss Types

### Dreadnought

```javascript
dreadnought: {
  size:42, hp:1400, spd:1.6, fireMs:280, dmg:18, color:'#ff6600', accent:'#ffcc44',
  score:3500, det:500, atk:380, patR:240, drag:0.91
}
```

**Mechanic — Two-Phase:**

Custom field: `e.phase` (1 or 2), `e.phaseSwitched` (bool).

Phase check in `tickEnemies`: if `e.type==='dreadnought'` and `e.phase===1` and `e.hp < e.maxHp*0.5` and `!e.phaseSwitched`:
- Set `e.phase=2`, `e.phaseSwitched=true`
- Spawn burst of particles (armor-shedding visual)
- Increase `e.spd` to `2.8`
- Reduce `e.fireMs` to `200`

Fire patterns:
- Phase 1: 3-way spread `[aim-0.28, aim, aim+0.28]` at spd 7.5, plus every 4th shot fires a slow pulse aimed at the player's position at the moment of fire (spd 4, straight line — not true homing). Track count via `e.shotCount++`; fire the pulse when `e.shotCount % 4 === 0`.
- Phase 2: 8-shot spiral `aim + i*(Math.PI/4)` for i=0..7, rotating the base angle by `+0.18` rad each burst

Weak point: when `e.phase===2`, pBullet damage multiplied by 2.0 (exposed core). Indicated visually by a bright inner glow.

Draw: `drawDreadnought(x,y,aim,sz,col,acc,spin,hp,phase)` — Phase 1 has armored outer ring, Phase 2 shows exposed core.

---

### Harbinger

```javascript
harbinger: {
  size:48, hp:1800, spd:1.2, fireMs:380, dmg:20, color:'#9900cc', accent:'#dd66ff',
  score:4500, det:500, atk:400, patR:280, drag:0.93
}
```

**Mechanic — Turret Pod Deployment:**

Custom fields: `e.podThresholds` (array `[0.66, 0.33]` — remaining HP fractions, shift one off when crossed), `e.rageMs` (countdown), `e.activePods` (currently alive pods).

On HP drop through a threshold: spawn 2 turret-type enemies adjacent to the Harbinger (offset ±60px), set their `e.fromHarbinger=true`. Max 4 pods total across both threshold crossings.

Rage mode: when all `fromHarbinger` turrets are dead (tracked via count), set `e.rageMs=4000`. While `e.rageMs>0`: `e.fireMs` reduced to 80, decrement each tick.

Fire pattern: slow dense spiral — every shot increments a `e.spiralAngle += 0.22`, fires at `e.spiralAngle`. Creates overlapping ring pattern over time.

Figure-8 movement: `e.patCx + Math.cos(t*0.4)*patR` for X, `e.patCy + Math.sin(t*0.8)*patR*0.5` for Y, where `t = Date.now()/1000`.

Draw: `drawHarbinger(x,y,aim,sz,col,acc,spin,hp,rageMs)` — massive hexagonal hull with side emission ports. Glows intensely during rage.

---

## Boss Placement

### Battle Mode — W5 Random Pool

In `spawnWave(5)`, replace the current hardcoded `boss` spawn with a random draw:

```javascript
const bossPool = ['boss','dreadnought','harbinger'];
const bossType = bossPool[Math.floor(Math.random()*bossPool.length)];
spawnEnemy(bossType, cx, cy);
```

### Combat Training — Extended Sequence

Extend `CT_SEQUENCE`:

```javascript
const CT_SEQUENCE=['dart','scout','guard','phantom','wraith','turret','brute','boss','dreadnought','harbinger'];
```

### Time Trial — Ghost Run (L1) and Dance Birdie Dance (L3)

In `spawnTTEnemies()`, add 1 boss-pool enemy near the finish line for L1 and L3:

```javascript
if(ttLevel===1||ttLevel===3){
  const finishX = ttLevel===1 ? TT_FINISH_X : DBD_FINISH_X;
  const bossPool=['dreadnought','harbinger'];
  const bossType=bossPool[Math.floor(Math.random()*bossPool.length)];
  spawnEnemy(bossType, finishX - 600, WORLD_H/2);
}
```

---

## Draw Functions

Each new enemy type needs a draw function following the existing `drawEnemyDrone(x,y,aim,sz,col,acc,spin,hp)` signature pattern:

- `drawRavager(x,y,aim,sz,col,acc,spin,hp)` — wedge-shaped body, elongated forward spike, charge-flush coloring
- `drawSplitter(x,y,aim,sz,col,acc,spin,hp)` — hexagonal body with visible crack/seam lines
- `drawShard(x,y,aim,sz,col,acc,spin,hp)` — irregular shard/fragment shape
- `drawCloaker(x,y,aim,sz,col,acc,spin,hp,visibleMs)` — sleek diamond body; shimmer ring when `visibleMs > 0`
- `drawDemolisher(x,y,aim,sz,col,acc,spin,hp)` — wide squat body, prominent bomb-launcher appendage
- `drawHunter(x,y,aim,sz,col,acc,spin,hp)` — tiny arrowhead, engine trail
- `drawDreadnought(x,y,aim,sz,col,acc,spin,hp,phase)` — armored ring Phase 1, exposed core Phase 2
- `drawHarbinger(x,y,aim,sz,col,acc,spin,hp,rageMs)` — massive hex hull, emission ports; full glow during rage

`drawEnemies()` dispatch updated to call the correct function per `e.type`. All new draw functions added after existing craft draw functions and before the player section.

---

## `killEnemy()` Updates

- If `e.type==='splitter'`: spawn 2 shards before removing
- If `e.fromHarbinger===true`: decrement harbinger's active pod count; if all pods dead, trigger rage on the parent harbinger

Tracking parent reference: module-level `let harbingerRef=null`. When Harbinger spawns, set `harbingerRef` to that enemy object. When a `fromHarbinger` pod is killed, decrement `harbingerRef.activePods`. When `harbingerRef.activePods===0` and Harbinger is still alive, set `harbingerRef.rageMs=4000`. Reset `harbingerRef=null` when Harbinger dies.

---

## `spawnEnemy()` Helper

Currently enemies are created inline. Extract or augment to accept a type string and position:

```javascript
function spawnEnemy(type, x, y){
  const t=ENEMY_TYPES[type];
  enemies.push({type,x,y,...t fields...,chargeMs:0,chargeVx:0,chargeVy:0,visibleMs:0,phase:type==='dreadnought'?1:0,phaseSwitched:false,shotCount:0,spiralAngle:0,rageMs:0,podThresholds:type==='harbinger'?[0.66,0.33]:[],activePods:0,fromHarbinger:false});
}
```

Existing spawn calls refactored to use this helper.

---

## Unresolved Questions

None.
