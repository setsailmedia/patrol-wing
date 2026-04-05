# New Weapons Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add 6 new weapons to PATROL WING — Grappling Hook, Faraday Cage, Grenade Launcher, Gravity Well, Leech Ray, and Shockwave Cannon.

**Architecture:** All new weapons follow the existing pattern: entry in WEAPONS[], fire case in fireWeapon(), tick/draw function pair, reset in all start* functions. Grappling Hook and Faraday Cage share an "anchor" mechanic implemented as custom fields on enemy objects. Grenade Launcher reuses `reflectRicoVsObs()`. Gravity Well, Leech Ray, and Shockwave Cannon are fully self-contained.

**Tech Stack:** Vanilla JS, Canvas 2D, single-file `index.php` (~7200 lines, all game logic inline). No build step. Work in `F:\PATROL WING\.worktrees\new-weapons\index.php`.

---

## Weapon Specs

### Final WEAPONS Array Order (22 total)
Insert grapple at index 7, faraday at index 12. All others append.

```
0:std  1:rapid  2:stun  3:spread  4:boomr  5:sawtooth  6:fractal
7:grapple (NEW)
8:plasma  9:minime  10:tractor  11:burst  12:rico
12:faraday (NEW — rico shifts to 13, all subsequent +1)
Wait — inserting at 12 means:
  0:std 1:rapid 2:stun 3:spread 4:boomr 5:sawtooth 6:fractal
  7:grapple (NEW)
  8:plasma 9:minime 10:tractor 11:burst 12:rico
  12:faraday (NEW — shifts rico to 13)
  13:rico 14:mine 15:laser 16:rocket 17:seekr 18:dinf
  19:grenade (NEW) 20:gravwell (NEW) 21:leech (NEW) 22:shockwave (NEW)
```

Correct final order:
```
0:std  1:rapid  2:stun  3:spread  4:boomr  5:sawtooth  6:fractal
7:grapple
8:plasma  9:minime  10:tractor  11:burst  12:rico
13:faraday
14:mine  15:laser  16:rocket  17:seekr  18:dinf
19:grenade  20:gravwell  21:leech  22:shockwave
```

### WEAPONS entries to add

```javascript
// index 7 — insert BEFORE plasma
{id:'grapple',  name:'GRAPPLING HOOK', color:'#44ddff', fireMs:700,  dmg:0, spd:18, count:1, spread:0, bSz:4, stock:20},

// index 13 — insert AFTER rico (which is already at 12 before insertion, becomes 12 still)
// After inserting grapple at 7, rico is at 12. Insert faraday after rico:
{id:'faraday',  name:'FARADAY CAGE',   color:'#88ffcc', fireMs:800,  dmg:0, spd:0,  count:1, spread:0, bSz:0, stock:25},

// Append after dinf:
{id:'grenade',  name:'GRENADE LAUNCHER',color:'#ffaa22',fireMs:900,  dmg:0, spd:11, count:1, spread:0, bSz:6, stock:15},
{id:'gravwell', name:'GRAVITY WELL',   color:'#cc44ff', fireMs:1800, dmg:0, spd:0,  count:1, spread:0, bSz:0, stock:8},
{id:'leech',    name:'LEECH RAY',      color:'#00ff88', fireMs:800,  dmg:60,spd:0,  count:1, spread:0, bSz:0, stock:12},
{id:'shockwave',name:'SHOCKWAVE CANNON',color:'#ff8844',fireMs:1400, dmg:50,spd:0,  count:1, spread:0, bSz:0, stock:8},
```

### New Constants
```javascript
const GRAPPLE_LEASH   = 55;   // px max movement from anchor
const GRAPPLE_SPD     = 18;
const GRENADE_BLAST_R = 80;   // explosion radius
const GRENADE_BLAST_DMG = 90; // damage to all in radius
const GRENADE_MAX_BOUNCES = 5;
const GRENADE_LIFE    = 3200; // ms
const GRENADE_PROX_R  = 70;   // triggers explosion within this range
const GRAVWELL_R      = 200;  // pull radius
const GRAVWELL_CRUSH_R= 80;   // damage radius (inner)
const GRAVWELL_DPS    = 12;   // damage/s inside crush radius
const GRAVWELL_PULL   = 1.8;  // pull acceleration per frame (px)
const GRAVWELL_LIFE   = 4000; // ms
const FARADAY_TRIGGER_R = 100;// trigger radius
const FARADAY_LIFE    = 8000; // ms before cage expires (releases anchors)
const SHOCKWAVE_R     = 280;  // damage radius
const SHOCKWAVE_KB    = 14;   // knockback velocity
```

---

## Task 1: Foundation — WEAPONS array, new module arrays, constants, resets

**Files:**
- Modify: `F:\PATROL WING\.worktrees\new-weapons\index.php`

### What to change

**A. Insert into WEAPONS[] array (around line 438):**

Find the current array. It starts with `const WEAPONS=[`. Insert `grapple` entry after `fractal` (index 6), and `faraday` entry after `rico` (which will be at index 12 after grapple insertion). Append `grenade`, `gravwell`, `leech`, `shockwave` at the end before the closing `];`.

The exact entries:

```javascript
{id:'grapple',   name:'GRAPPLING HOOK',   color:'#44ddff', fireMs:700,  dmg:0,  spd:18, count:1, spread:0, bSz:4,   stock:20},
```
Insert after the `fractal` entry line.

```javascript
{id:'faraday',   name:'FARADAY CAGE',     color:'#88ffcc', fireMs:800,  dmg:0,  spd:0,  count:1, spread:0, bSz:0,   stock:25},
```
Insert after the `rico` entry line (rico is at index 12 after grapple insertion, so faraday becomes index 13).

```javascript
{id:'grenade',   name:'GRENADE LAUNCHER', color:'#ffaa22', fireMs:900,  dmg:0,  spd:11, count:1, spread:0, bSz:6,   stock:15},
{id:'gravwell',  name:'GRAVITY WELL',     color:'#cc44ff', fireMs:1800, dmg:0,  spd:0,  count:1, spread:0, bSz:0,   stock:8},
{id:'leech',     name:'LEECH RAY',        color:'#00ff88', fireMs:800,  dmg:60, spd:0,  count:1, spread:0, bSz:0,   stock:12},
{id:'shockwave', name:'SHOCKWAVE CANNON', color:'#ff8844', fireMs:1400, dmg:50, spd:0,  count:1, spread:0, bSz:0,   stock:8},
```
Append before `];` at end of WEAPONS array.

**B. Add new constants** (after existing weapon constants near line 645):
```javascript
const GRAPPLE_LEASH    = 55;
const GRENADE_BLAST_R  = 80;
const GRENADE_BLAST_DMG= 90;
const GRENADE_MAX_BOUNCES=5;
const GRENADE_LIFE     = 3200;
const GRENADE_PROX_R   = 70;
const GRAVWELL_R       = 200;
const GRAVWELL_CRUSH_R = 80;
const GRAVWELL_DPS     = 12;
const GRAVWELL_PULL    = 1.8;
const GRAVWELL_LIFE    = 4000;
const FARADAY_TRIGGER_R= 100;
const FARADAY_LIFE     = 8000;
const SHOCKWAVE_R      = 280;
const SHOCKWAVE_KB     = 14;
```

**C. Add new module-level arrays** (on the same line as rockets, around line 641):

The current line is:
```javascript
let particles=[],pickups=[],pBullets=[],eBullets=[],enemies=[],obstacles=[],mines=[],seekers=[],boomerangs=[],fractals=[],hazards=[],rockets=[];
```

Add `grenades=[], gravityWells=[], faradayCages=[]` to this line.

**D. Add resets** — find every place `rockets.length=0` appears (lines ~4631, 5121, 5402, 6297, 6525, 6665) and append `grenades.length=0;gravityWells.length=0;faradayCages.length=0;` to each.

- [ ] **Step 1: Read lines 438–460** to get exact current WEAPONS array text

- [ ] **Step 2: Insert grapple after fractal entry**

Use str_replace to insert grapple after the fractal line:
```
old: {id:'fractal',  name:'FRACTAL FUSION',color:'#ff9900',fireMs:480, dmg:8,  spd:0, count:1,spread:0,   bSz:0,  stock:25},
new: {id:'fractal',  name:'FRACTAL FUSION',color:'#ff9900',fireMs:480, dmg:8,  spd:0, count:1,spread:0,   bSz:0,  stock:25},
  {id:'grapple',   name:'GRAPPLING HOOK',   color:'#44ddff', fireMs:700,  dmg:0,  spd:18, count:1, spread:0, bSz:4,   stock:20},
```

- [ ] **Step 3: Insert faraday after rico entry**

```
old: {id:'rico',     name:'RICO CANNON',  color:'#cc88ff',fireMs:600, dmg:96, spd:10,count:1,spread:0,   bSz:8.5,stock:30},
new: {id:'rico',     name:'RICO CANNON',  color:'#cc88ff',fireMs:600, dmg:96, spd:10,count:1,spread:0,   bSz:8.5,stock:30},
  {id:'faraday',   name:'FARADAY CAGE',     color:'#88ffcc', fireMs:800,  dmg:0,  spd:0,  count:1, spread:0, bSz:0,   stock:25},
```

- [ ] **Step 4: Append grenade/gravwell/leech/shockwave before closing `];`**

Find the last entry `dinf` line and insert after it before `];`.

- [ ] **Step 5: Add constants after ROCKET_SPD/SEEKR lines**

- [ ] **Step 6: Add arrays to module-level let declaration**

- [ ] **Step 7: Add resets — find every `rockets.length=0` occurrence and add new arrays**

Use grep to find all occurrences, then str_replace each one to also clear grenades/gravityWells/faradayCages.

- [ ] **Step 8: Commit**
```bash
git add index.php
git commit -m "feat: add new weapon foundations — arrays, constants, WEAPONS entries"
```

---

## Task 2: Grappling Hook — fire, projectile, anchor mechanic

**Files:**
- Modify: `F:\PATROL WING\.worktrees\new-weapons\index.php`

### Mechanics
- Fire: `pBullets.push({..., isGrapple:true})` — uses standard pBullet, just marked
- On hit in `checkCollisions()`: anchor enemy at hit point, deal `e.maxHp * 0.2` damage, remove bullet
- Anchor fields on enemy: `e.anchorX, e.anchorY` (set on anchor; `undefined` = not anchored)
- In `tickEnemies()`: if `e.anchorX !== undefined`, after normal movement clamp position to GRAPPLE_LEASH from anchor, dampen velocity
- `killEnemy()`: no special handling needed (delete e just removes the object)
- Draw: grapple bullets are drawn as `pBullets` normally, but in `drawBullets()` give them a special hook appearance (small arrowhead + trailing line)

### fireWeapon() case

Add this case in fireWeapon() BEFORE the generic pBullet push section:

```javascript
if(w.id==='grapple'){
  const angle=P.aim;
  pBullets.push({
    x:P.x+Math.cos(angle)*20, y:P.y+Math.sin(angle)*20,
    vx:Math.cos(angle)*GRAPPLE_SPD, vy:Math.sin(angle)*GRAPPLE_SPD,
    dmg:0, bSz:4, col:w.color, stun:false, dinf:false, isGrapple:true,
    fromInfected:false
  });
  P.stocks['grapple']--;
  spawnParts(P.x+Math.cos(angle)*16,P.y+Math.sin(angle)*16,'#44ddff',_pCount(4),2,3,180);
  SFX.select(); // reuse a sound
  return;
}
```

### checkCollisions() — grapple hit

Inside the `outer:for` loop over pBullets, before the generic `e.hp -= b.dmg` line, add a check:

```javascript
if(b.isGrapple){
  // Anchor the enemy
  e.anchorX = b.x;
  e.anchorY = b.y;
  const anchDmg = e.maxHp * 0.2;
  e.hp -= anchDmg;
  spawnParts(b.x, b.y, '#44ddff', _pCount(10), 3, 4.5, 350);
  spawnParts(b.x, b.y, '#ffffff', _pCount(5), 2, 3, 250);
  if(e.hp <= 0){ SFX.boom(); killEnemy(ei); }
  pBullets.splice(bi,1);
  continue outer;
}
```

### tickEnemies() — anchor constraint

After the section that applies enemy movement (after vx/vy are applied to x/y), add:

```javascript
// Anchor constraint (grapple/faraday)
if(e.anchorX !== undefined){
  const adx=e.x-e.anchorX, ady=e.y-e.anchorY;
  const ad=Math.sqrt(adx*adx+ady*ady)||1;
  if(ad > GRAPPLE_LEASH){
    e.x = e.anchorX + (adx/ad)*GRAPPLE_LEASH;
    e.y = e.anchorY + (ady/ad)*GRAPPLE_LEASH;
    e.vx *= 0.25;
    e.vy *= 0.25;
  }
}
```

### drawBullets() — grapple visual

In `drawBullets()`, find where individual bullets are drawn. Add special branch for `isGrapple`:

```javascript
if(b.isGrapple){
  ctx.save();
  const angle = Math.atan2(b.vy, b.vx);
  ctx.translate(sx,sy); ctx.rotate(angle);
  ctx.shadowBlur=14; ctx.shadowColor='#44ddff';
  // Hook head
  ctx.beginPath();
  ctx.moveTo(6,0); ctx.lineTo(-4,4); ctx.lineTo(-3,0); ctx.lineTo(-4,-4); ctx.closePath();
  ctx.fillStyle='#44ddff'; ctx.fill();
  // Cable trail
  ctx.beginPath(); ctx.moveTo(-4,0); ctx.lineTo(-14,0);
  ctx.strokeStyle='rgba(68,221,255,0.6)'; ctx.lineWidth=1.5; ctx.stroke();
  ctx.shadowBlur=0; ctx.restore();
  continue;
}
```

### Steps

- [ ] **Step 1: Read fireWeapon() start** (around line 1120) to find insertion point for grapple case

- [ ] **Step 2: Add grapple fire case** in fireWeapon() before generic pBullet push

- [ ] **Step 3: Read checkCollisions() inner loop** (around line 3325) to find exact insertion point

- [ ] **Step 4: Add isGrapple branch** in checkCollisions() outer loop

- [ ] **Step 5: Read tickEnemies()** — find where vx/vy are applied to x/y to locate anchor insertion point

- [ ] **Step 6: Add anchor constraint** in tickEnemies() after movement application

- [ ] **Step 7: Read drawBullets()** to find per-bullet draw loop

- [ ] **Step 8: Add isGrapple visual branch** in drawBullets()

- [ ] **Step 9: Commit**
```bash
git commit -m "feat: grappling hook weapon — fires, anchors enemy, 20% damage"
```

---

## Task 3: Faraday Cage — placement, trigger, anchor, expiry

**Files:**
- Modify: `F:\PATROL WING\.worktrees\new-weapons\index.php`

### Mechanics
- Place at player position like a mine: `faradayCages.push({x:P.x, y:P.y, life:FARADAY_LIFE, armed:false, armMs:600, trapped:[], blasting:false, blastT:0})`
- `trapped` is an array of enemy indices currently anchored by this cage
- `armMs`: 600ms before cage activates (like mine)
- `life`: 8000ms total. When life expires, release all trapped enemies (delete `anchorX/anchorY` from each still-living enemy)
- While armed: scan enemies in FARADAY_TRIGGER_R. If enemy not already anchored (`e.anchorX === undefined`) and `trapped.length < 2`: anchor enemy at cage position, push enemy index to `trapped`
- Visual: hexagonal wireframe that pulses. Trapped count shown as `X/2` badge. Full cage glows differently.

### fireWeapon() case

```javascript
if(w.id==='faraday'){
  if((P.stocks['faraday']||0)<=0) return;
  P.stocks['faraday']--;
  faradayCages.push({x:P.x, y:P.y, life:FARADAY_LIFE, armed:false, armMs:600, trapped:[], blasting:false, blastT:0});
  spawnParts(P.x,P.y,'#88ffcc',_pCount(8),2,3.5,300);
  SFX.mineset();
  return;
}
```

### tickFaradayCages(dt)

```javascript
function tickFaradayCages(dt){
  const now=performance.now();
  for(let ci=faradayCages.length-1;ci>=0;ci--){
    const c=faradayCages[ci];
    if(c.blasting){ c.blastT-=dt*1000; if(c.blastT<=0) faradayCages.splice(ci,1); continue; }
    if(!c.armed){ c.armMs-=dt*1000; if(c.armMs<=0) c.armed=true; continue; }
    c.life-=dt*1000;
    if(c.life<=0){
      // Release all trapped enemies
      for(const ei of c.trapped){
        if(ei<enemies.length){ delete enemies[ei].anchorX; delete enemies[ei].anchorY; }
      }
      faradayCages.splice(ci,1);
      continue;
    }
    if(c.trapped.length>=2) continue; // full — stop scanning
    for(let ei=0;ei<enemies.length;ei++){
      const e=enemies[ei];
      if(e.anchorX !== undefined) continue; // already anchored
      if(dist2(c.x,c.y,e.x,e.y) < FARADAY_TRIGGER_R*FARADAY_TRIGGER_R){
        e.anchorX = c.x;
        e.anchorY = c.y;
        c.trapped.push(ei);
        spawnParts(c.x,c.y,'#88ffcc',_pCount(14),3,5,420);
        SFX.shield(); // zap sound
        if(c.trapped.length>=2) break;
      }
    }
  }
}
```

**Note on enemy index stability:** When `killEnemy(i)` splices the enemies array, indices shift. The `trapped` array can contain stale indices. Since the anchor fields are ON the enemy object itself, the cage doesn't need valid indices after trapping — it only uses them at expiry to release. Fix: instead of storing index, store a direct reference to the enemy object in `trapped`.

Revised approach — store object references:
- `c.trapped` contains enemy object references, not indices
- On expiry: `for(const e of c.trapped){ delete e.anchorX; delete e.anchorY; }`
- On scan: `if(c.trapped.includes(e)) continue;`

### drawFaradayCages()

```javascript
function drawFaradayCages(){
  const now=Date.now();
  for(const c of faradayCages){
    const sx=c.x-camX, sy=c.y-camY;
    if(sx<-200||sx>canvas.width+200||sy<-200||sy>canvas.height+200) continue;
    ctx.save(); ctx.translate(sx,sy);
    if(c.blasting){
      const prog=1-(c.blastT/400);
      ctx.globalAlpha=Math.max(0,(1-prog)*0.7);
      ctx.beginPath(); ctx.arc(0,0,FARADAY_TRIGGER_R*prog*1.1,0,Math.PI*2);
      ctx.strokeStyle='#88ffcc'; ctx.lineWidth=2; ctx.shadowBlur=20; ctx.shadowColor='#88ffcc';
      ctx.stroke(); ctx.shadowBlur=0; ctx.globalAlpha=1; ctx.restore(); continue;
    }
    const full=c.trapped.length>=2;
    const col=full?'#ffcc00':c.armed?'#88ffcc':'#336655';
    const pulse=0.6+0.4*Math.sin(Date.now()/220);
    // Faint trigger ring
    ctx.globalAlpha=0.1+0.05*pulse;
    ctx.beginPath(); ctx.arc(0,0,FARADAY_TRIGGER_R,0,Math.PI*2);
    ctx.strokeStyle=col; ctx.lineWidth=1; ctx.stroke(); ctx.globalAlpha=1;
    // Hexagon
    ctx.shadowBlur=c.armed?16*pulse:6; ctx.shadowColor=col;
    ctx.beginPath();
    for(let i=0;i<6;i++){
      const a=Math.PI/6+i*Math.PI/3;
      const r=16+(c.armed?2*Math.sin(Date.now()/180+i):0);
      i===0?ctx.moveTo(Math.cos(a)*r,Math.sin(a)*r):ctx.lineTo(Math.cos(a)*r,Math.sin(a)*r);
    }
    ctx.closePath();
    ctx.strokeStyle=col; ctx.lineWidth=2; ctx.stroke(); ctx.shadowBlur=0;
    // Inner lines (cage effect)
    ctx.globalAlpha=0.3;
    for(let i=0;i<6;i++){
      const a=Math.PI/6+i*Math.PI/3;
      ctx.beginPath(); ctx.moveTo(0,0); ctx.lineTo(Math.cos(a)*14,Math.sin(a)*14);
      ctx.strokeStyle=col; ctx.lineWidth=1; ctx.stroke();
    }
    ctx.globalAlpha=1;
    // Trapped count badge
    if(c.armed){
      ctx.font='bold 10px "Courier New"'; ctx.fillStyle=col; ctx.textAlign='center';
      ctx.fillText(`${c.trapped.length}/2`,0,22);
    }
    ctx.restore();
  }
}
```

### Steps

- [ ] **Step 1: Add faraday fire case** in fireWeapon()

- [ ] **Step 2: Add tickFaradayCages(dt)** — place after tickMines()

- [ ] **Step 3: Add drawFaradayCages()** — place after drawMines()

- [ ] **Step 4: Wire into main loop** — add `tickFaradayCages(dt)` and `drawFaradayCages()` calls in the main loop (lines ~7716-7721). Draw call goes between drawMines() and drawRockets().

- [ ] **Step 5: Commit**
```bash
git commit -m "feat: faraday cage weapon — proximity anchor trap, holds 2 enemies"
```

---

## Task 4: Grenade Launcher — bouncing projectile, proximity detonation

**Files:**
- Modify: `F:\PATROL WING\.worktrees\new-weapons\index.php`

### Mechanics
- Fire: `grenades.push({x, y, vx, vy, bounces:0, life:GRENADE_LIFE, blasting:false, blastT:0})`
- Speed 11, aimed at player aim direction
- Each tick: move, call `reflectVsObs` (adapted from `reflectRicoVsObs` — can reuse with a wrapper), increment bounce count if bounce happened
- Also reflect off world walls (clamp + flip velocity)
- Proximity trigger: if any enemy within GRENADE_PROX_R, or bounces >= GRENADE_MAX_BOUNCES, or life <= 0: detonate
- Detonate: deal GRENADE_BLAST_DMG to all enemies within GRENADE_BLAST_R, set `blasting=true, blastT=480`
- Visual: small sphere (green-yellow) with a fuse trail while alive, expanding blast ring when detonating

### fireWeapon() case

```javascript
if(w.id==='grenade'){
  if((P.stocks['grenade']||0)<=0) return;
  P.stocks['grenade']--;
  const angle=P.aim;
  const spd=11;
  grenades.push({
    x:P.x+Math.cos(angle)*22, y:P.y+Math.sin(angle)*22,
    vx:Math.cos(angle)*spd,   vy:Math.sin(angle)*spd,
    bounces:0, life:GRENADE_LIFE, blasting:false, blastT:0
  });
  spawnParts(P.x+Math.cos(angle)*18,P.y+Math.sin(angle)*18,'#ffaa22',_pCount(5),2.5,3.5,200);
  SFX.mineset();
  return;
}
```

### tickGrenades(dt)

```javascript
function tickGrenades(dt){
  const step=dt*60;
  for(let gi=grenades.length-1;gi>=0;gi--){
    const g=grenades[gi];
    if(g.blasting){ g.blastT-=dt*1000; if(g.blastT<=0) grenades.splice(gi,1); continue; }
    g.life-=dt*1000;
    g.x+=g.vx*step; g.y+=g.vy*step;
    // Wall reflection
    if(g.x<6){g.x=6;g.vx=Math.abs(g.vx);g.bounces++;}
    if(g.x>WORLD_W-6){g.x=WORLD_W-6;g.vx=-Math.abs(g.vx);g.bounces++;}
    if(g.y<6){g.y=6;g.vy=Math.abs(g.vy);g.bounces++;}
    if(g.y>WORLD_H-6){g.y=WORLD_H-6;g.vy=-Math.abs(g.vy);g.bounces++;}
    // Obstacle reflection — reuse reflectRicoVsObs by passing a compatible object
    const proxy={x:g.x,y:g.y,vx:g.vx,vy:g.vy,bSz:6};
    if(reflectRicoVsObs(proxy)){
      g.x=proxy.x; g.y=proxy.y; g.vx=proxy.vx; g.vy=proxy.vy;
      g.bounces++;
      spawnParts(g.x,g.y,'#ffaa22',_pCount(3),1.5,2.5,180);
    }
    // Check detonation conditions
    let detonate=g.bounces>=GRENADE_MAX_BOUNCES||g.life<=0;
    if(!detonate){
      for(const e of enemies){ if(dist2(g.x,g.y,e.x,e.y)<GRENADE_PROX_R*GRENADE_PROX_R){detonate=true;break;} }
    }
    if(detonate){
      _detonateGrenade(gi);
    } else {
      // Fuse smoke
      if(Math.random()<0.4) spawnParts(g.x,g.y,'#888844',_pCount(1),0.5,1.5,300);
    }
  }
}

function _detonateGrenade(gi){
  const g=grenades[gi];
  g.blasting=true; g.blastT=480;
  spawnParts(g.x,g.y,'#ffaa22',_pCount(35),8,9,850);
  spawnParts(g.x,g.y,'#ffffff',_pCount(12),4,5,500);
  spawnParts(g.x,g.y,'#ff6600',_pCount(20),6,7,650);
  if(settings.screenShake) shake=Math.max(shake,18);
  SFX.minedet();
  const DMG=GRENADE_BLAST_DMG*(P.overchargeMs>0?2:1);
  for(let ei=enemies.length-1;ei>=0;ei--){
    const e=enemies[ei];
    if(dist2(g.x,g.y,e.x,e.y)>GRENADE_BLAST_R*GRENADE_BLAST_R) continue;
    e.hp-=DMG;
    spawnParts(e.x,e.y,e.color,_pCount(8),3,4.5,300);
    if(e.hp<=0){ SFX.boom(); killEnemy(ei); }
  }
}
```

### drawGrenades()

```javascript
function drawGrenades(){
  for(const g of grenades){
    const sx=g.x-camX, sy=g.y-camY;
    if(sx<-80||sx>canvas.width+80||sy<-80||sy>canvas.height+80) continue;
    ctx.save(); ctx.translate(sx,sy);
    if(g.blasting){
      const prog=1-(g.blastT/480);
      const br=GRENADE_BLAST_R*prog*1.05;
      ctx.globalAlpha=Math.max(0,(1-prog)*0.72);
      ctx.beginPath(); ctx.arc(0,0,br,0,Math.PI*2);
      ctx.fillStyle='rgba(255,140,0,0.28)'; ctx.fill();
      ctx.strokeStyle='#ffaa22'; ctx.lineWidth=3*(1-prog)+1;
      ctx.shadowBlur=26; ctx.shadowColor='#ff6600';
      ctx.stroke(); ctx.shadowBlur=0; ctx.globalAlpha=1;
      ctx.restore(); continue;
    }
    const pulse=0.7+0.3*Math.sin(Date.now()/120);
    ctx.shadowBlur=12*pulse; ctx.shadowColor='#ffaa22';
    // Body — slightly flattened sphere
    ctx.beginPath(); ctx.arc(0,0,6,0,Math.PI*2);
    ctx.fillStyle='#ddaa00'; ctx.fill();
    ctx.strokeStyle='#ffcc44'; ctx.lineWidth=1.5; ctx.stroke();
    // Fuse spark
    ctx.beginPath(); ctx.arc(0,-6,2.5,0,Math.PI*2);
    ctx.fillStyle=`rgba(255,${80+Math.floor(120*pulse)},0,${0.8*pulse})`; ctx.fill();
    ctx.shadowBlur=0; ctx.restore();
  }
}
```

### Steps

- [ ] **Step 1: Add grenade fire case** in fireWeapon()

- [ ] **Step 2: Add `_detonateGrenade()` helper** and `tickGrenades(dt)` — after tickRockets()

- [ ] **Step 3: Add `drawGrenades()`** — after drawRockets()

- [ ] **Step 4: Wire into main loop** — add tick and draw calls

- [ ] **Step 5: Commit**
```bash
git commit -m "feat: grenade launcher — bounces off obstacles, proximity detonation"
```

---

## Task 5: Gravity Well — deployment, pull, tick damage

**Files:**
- Modify: `F:\PATROL WING\.worktrees\new-weapons\index.php`

### Mechanics
- Fire: drops gravity well at player position. `gravityWells.push({x:P.x, y:P.y, life:GRAVWELL_LIFE, blasting:false, blastT:0})`
- Each tick: all enemies within GRAVWELL_R are pulled toward the well center (velocity nudge toward it)
- Enemies inside GRAVWELL_CRUSH_R take GRAVWELL_DPS damage/s
- After life expires: collapse blast (visual only, no damage) then remove
- Visual: pulsing dark singularity with orbiting ring, distortion shimmer

### fireWeapon() case

```javascript
if(w.id==='gravwell'){
  if((P.stocks['gravwell']||0)<=0) return;
  P.stocks['gravwell']--;
  gravityWells.push({x:P.x, y:P.y, life:GRAVWELL_LIFE, blasting:false, blastT:0});
  spawnParts(P.x,P.y,'#cc44ff',_pCount(14),4,6,500);
  SFX.emp(); // deep whoosh
  return;
}
```

### tickGravityWells(dt)

```javascript
function tickGravityWells(dt){
  for(let wi=gravityWells.length-1;wi>=0;wi--){
    const gw=gravityWells[wi];
    if(gw.blasting){ gw.blastT-=dt*1000; if(gw.blastT<=0) gravityWells.splice(wi,1); continue; }
    gw.life-=dt*1000;
    if(gw.life<=0){
      gw.blasting=true; gw.blastT=600;
      spawnParts(gw.x,gw.y,'#cc44ff',_pCount(30),7,8,700);
      spawnParts(gw.x,gw.y,'#ffffff',_pCount(10),3,4,400);
      if(settings.screenShake) shake=Math.max(shake,10);
      continue;
    }
    for(let ei=0;ei<enemies.length;ei++){
      const e=enemies[ei];
      const dx=gw.x-e.x, dy=gw.y-e.y;
      const d2=dx*dx+dy*dy;
      if(d2 > GRAVWELL_R*GRAVWELL_R) continue;
      const d=Math.sqrt(d2)||1;
      // Pull toward center — stronger when closer
      const pullStr = GRAVWELL_PULL * (1 - d/GRAVWELL_R) * dt * 60;
      e.vx += (dx/d)*pullStr;
      e.vy += (dy/d)*pullStr;
      // Crush damage in inner radius
      if(d2 < GRAVWELL_CRUSH_R*GRAVWELL_CRUSH_R){
        e.hp -= GRAVWELL_DPS * dt;
        if(e.hp<=0){ SFX.boom(); killEnemy(ei); }
      }
    }
  }
}
```

### drawGravityWells()

```javascript
function drawGravityWells(){
  const t=Date.now()/1000;
  for(const gw of gravityWells){
    const sx=gw.x-camX, sy=gw.y-camY;
    if(sx<-260||sx>canvas.width+260||sy<-260||sy>canvas.height+260) continue;
    ctx.save(); ctx.translate(sx,sy);
    if(gw.blasting){
      const prog=1-(gw.blastT/600);
      ctx.globalAlpha=Math.max(0,(1-prog)*0.65);
      const br=GRAVWELL_R*prog*0.8;
      ctx.beginPath(); ctx.arc(0,0,br,0,Math.PI*2);
      ctx.strokeStyle='#cc44ff'; ctx.lineWidth=2; ctx.shadowBlur=24; ctx.shadowColor='#cc44ff'; ctx.stroke();
      ctx.shadowBlur=0; ctx.globalAlpha=1; ctx.restore(); continue;
    }
    const lifeFrac=gw.life/GRAVWELL_LIFE;
    // Outer pull ring (faint)
    ctx.globalAlpha=0.08+0.04*Math.sin(t*2);
    ctx.beginPath(); ctx.arc(0,0,GRAVWELL_R,0,Math.PI*2);
    ctx.strokeStyle='#cc44ff'; ctx.lineWidth=1; ctx.stroke();
    ctx.globalAlpha=1;
    // Orbiting ring
    ctx.save(); ctx.rotate(t*1.8);
    ctx.beginPath(); ctx.ellipse(0,0,GRAVWELL_CRUSH_R*1.4,GRAVWELL_CRUSH_R*0.55,0,0,Math.PI*2);
    ctx.strokeStyle=`rgba(200,68,255,${0.35+0.2*Math.sin(t*3)})`; ctx.lineWidth=1.5;
    ctx.shadowBlur=10; ctx.shadowColor='#cc44ff'; ctx.stroke(); ctx.shadowBlur=0;
    ctx.restore();
    // Core singularity
    const coreR=8+4*Math.sin(t*4)*lifeFrac;
    ctx.beginPath(); ctx.arc(0,0,coreR,0,Math.PI*2);
    const grad=ctx.createRadialGradient(0,0,0,0,0,coreR);
    grad.addColorStop(0,'rgba(255,255,255,0.9)');
    grad.addColorStop(0.4,'rgba(200,68,255,0.7)');
    grad.addColorStop(1,'rgba(60,0,100,0)');
    ctx.fillStyle=grad; ctx.fill();
    ctx.restore();
  }
}
```

### Steps

- [ ] **Step 1: Add gravwell fire case** in fireWeapon()

- [ ] **Step 2: Add `tickGravityWells(dt)`** after tickGrenades

- [ ] **Step 3: Add `drawGravityWells()`** after drawGrenades

- [ ] **Step 4: Wire into main loop**

- [ ] **Step 5: Commit**
```bash
git commit -m "feat: gravity well weapon — pulls and crushes nearby enemies"
```

---

## Task 6: Leech Ray + Shockwave Cannon

**Files:**
- Modify: `F:\PATROL WING\.worktrees\new-weapons\index.php`

Both are instant effects with no persistent projectile arrays. Leech Ray uses the existing laser flash (`laserFlash`) or a new short-lived visual. Shockwave uses `empFlash`-style radial animation.

### Leech Ray Mechanics
- Instant hitscan: find first enemy along aim direction within 600px (same pattern as laser)
- Deal 60 damage to that enemy
- Heal player: `P.hp = Math.min(P.maxHp, P.hp + 24)` (40% leech)
- Visual: a green beam drawn as `laserFlash`-style for 400ms, distinct green color `#00ff88`
- Stock: 12

Add a `leechFlash` variable similar to `laserFlash`:
```javascript
let leechFlash={active:false, tx:0, ty:0, ms:0}; // near laserFlash declaration
```

Or simpler: reuse `laserFlash` with a color override. But leechFlash is distinct enough to warrant its own variable. Find where `let laserFlash` is declared and add `leechFlash` nearby.

### fireWeapon() case for leech

```javascript
if(w.id==='leech'){
  if((P.stocks['leech']||0)<=0) return;
  P.stocks['leech']--;
  const cos=Math.cos(P.aim), sin=Math.sin(P.aim);
  let hit=null, hitDist=Infinity;
  for(let ei=0;ei<enemies.length;ei++){
    const e=enemies[ei];
    // Project enemy onto aim ray
    const ex=e.x-P.x, ey=e.y-P.y;
    const proj=ex*cos+ey*sin;
    if(proj<0||proj>600) continue;
    const perpD=Math.abs(ex*sin-ey*cos);
    if(perpD<e.size+4 && proj<hitDist){ hitDist=proj; hit=ei; }
  }
  if(hit!==null){
    const e=enemies[hit];
    const dmg=60*(P.overchargeMs>0?2:1);
    e.hp-=dmg;
    P.hp=Math.min(P.maxHp, P.hp+24);
    spawnParts(e.x,e.y,'#00ff88',_pCount(12),3,4,350);
    spawnParts(e.x,e.y,'#ffffff',_pCount(6),2,3,250);
    leechFlash={active:true,tx:P.x+cos*hitDist,ty:P.y+sin*hitDist,ms:400};
    if(e.hp<=0){ SFX.boom(); killEnemy(hit); }
  } else {
    // Miss — beam extends 600px
    leechFlash={active:true,tx:P.x+cos*600,ty:P.y+sin*600,ms:280};
  }
  SFX.laser(); // reuse laser sound
  return;
}
```

### tickLeechFlash(dt) and drawLeechFlash()

```javascript
function tickLeechFlash(dt){
  if(leechFlash.active){ leechFlash.ms-=dt*1000; if(leechFlash.ms<=0) leechFlash.active=false; }
}

function drawLeechFlash(){
  if(!leechFlash.active) return;
  const alpha=Math.min(1,leechFlash.ms/200);
  ctx.save();
  ctx.globalAlpha=alpha*0.85;
  ctx.beginPath();
  ctx.moveTo(P.x-camX, P.y-camY);
  ctx.lineTo(leechFlash.tx-camX, leechFlash.ty-camY);
  ctx.strokeStyle='#00ff88';
  ctx.lineWidth=3+4*alpha;
  ctx.shadowBlur=22; ctx.shadowColor='#00ff88';
  ctx.stroke(); ctx.shadowBlur=0;
  // Second thinner inner beam
  ctx.globalAlpha=alpha*0.4;
  ctx.lineWidth=1.5;
  ctx.strokeStyle='#ffffff';
  ctx.stroke();
  ctx.globalAlpha=1; ctx.restore();
}
```

### Shockwave Cannon Mechanics
- Instant radial burst from player position
- All enemies within SHOCKWAVE_R take dmg (50 base + overcharge bonus)
- Knockback: push each enemy directly away from player with velocity SHOCKWAVE_KB
- Visual: expanding ring animation (like EMP flash but player-centered)
- Uses existing `empFlash` mechanism (repurpose) OR add `shockwaveFlash`

Add a `shockwaveFlash` variable:
```javascript
let shockwaveFlash={ms:0}; // near empFlash declaration
```

### fireWeapon() case for shockwave

```javascript
if(w.id==='shockwave'){
  if((P.stocks['shockwave']||0)<=0) return;
  P.stocks['shockwave']--;
  const DMG=50*(P.overchargeMs>0?2:1);
  for(let ei=enemies.length-1;ei>=0;ei--){
    const e=enemies[ei];
    const dx=e.x-P.x, dy=e.y-P.y;
    const d2=dx*dx+dy*dy;
    if(d2>SHOCKWAVE_R*SHOCKWAVE_R) continue;
    e.hp-=DMG;
    const d=Math.sqrt(d2)||1;
    e.vx+=(dx/d)*SHOCKWAVE_KB;
    e.vy+=(dy/d)*SHOCKWAVE_KB;
    spawnParts(e.x,e.y,e.color,_pCount(6),2.5,3.5,280);
    if(e.hp<=0){ SFX.boom(); killEnemy(ei); }
  }
  shockwaveFlash={ms:600};
  spawnParts(P.x,P.y,'#ff8844',_pCount(25),6,8,600);
  spawnParts(P.x,P.y,'#ffffff',_pCount(10),4,5,400);
  if(settings.screenShake) shake=Math.max(shake,14);
  SFX.emp();
  return;
}
```

### tickShockwaveFlash(dt) and drawShockwaveFlash()

```javascript
function tickShockwaveFlash(dt){
  if(shockwaveFlash.ms>0) shockwaveFlash.ms-=dt*1000;
}

function drawShockwaveFlash(){
  if(shockwaveFlash.ms<=0) return;
  const prog=1-(shockwaveFlash.ms/600);
  const r=SHOCKWAVE_R*prog;
  const alpha=Math.max(0,(1-prog)*0.7);
  const sx=P.x-camX, sy=P.y-camY;
  ctx.save();
  ctx.globalAlpha=alpha;
  ctx.beginPath(); ctx.arc(sx,sy,r,0,Math.PI*2);
  ctx.strokeStyle='#ff8844'; ctx.lineWidth=4*(1-prog)+1;
  ctx.shadowBlur=20; ctx.shadowColor='#ff8844'; ctx.stroke(); ctx.shadowBlur=0;
  ctx.globalAlpha=alpha*0.18;
  ctx.beginPath(); ctx.arc(sx,sy,r,0,Math.PI*2);
  ctx.fillStyle='#ff8844'; ctx.fill();
  ctx.globalAlpha=1; ctx.restore();
}
```

### leechFlash and shockwaveFlash variable declarations

Find the existing `let laserFlash` declaration and add nearby:
```javascript
let leechFlash={active:false,tx:0,ty:0,ms:0};
let shockwaveFlash={ms:0};
```

Find where `laserFlash` is reset in game start functions and reset these too:
```javascript
leechFlash={active:false,tx:0,ty:0,ms:0};
shockwaveFlash={ms:0};
```

### Main loop wiring

Tick: add `tickLeechFlash(dt)`, `tickShockwaveFlash(dt)` alongside `tickLaserFlash(dt)`.
Draw: `drawLeechFlash()` and `drawShockwaveFlash()` go after `drawPlayer()` and before `drawHUD()`, alongside `drawEMPFlash()` and `drawLaserFlash()`.

### Steps

- [ ] **Step 1: Find laserFlash declaration + reset locations**

- [ ] **Step 2: Add `leechFlash` and `shockwaveFlash` variable declarations**

- [ ] **Step 3: Add leech fire case** in fireWeapon()

- [ ] **Step 4: Add shockwave fire case** in fireWeapon()

- [ ] **Step 5: Add tick+draw functions** for leech and shockwave

- [ ] **Step 6: Wire into main loop** (tick + draw)

- [ ] **Step 7: Reset leechFlash/shockwaveFlash** in all start functions (alongside laserFlash resets)

- [ ] **Step 8: Commit**
```bash
git commit -m "feat: leech ray (hitscan heal) and shockwave cannon (radial knockback)"
```

---

## Unresolved Questions

None.
