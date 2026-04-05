# New Hostiles & Bosses Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add 5 new hostile types (Ravager, Splitter/Shard, Cloaker, Demolisher, Hunter) and 2 new bosses (Dreadnought, Harbinger) with placement in Battle W5, Combat Training, and TT Ghost Run/Dance Birdie Dance.

**Architecture:** All changes are in the single file `index.php`. New types are added to the `ETYPES` map; per-type extra fields go in `mkEnemy()`; draw functions follow the `drawEnemyDrone(x,y,aim,sz,col,acc,spin,hp)` signature; mechanic logic goes in `tickEnemies()`, `tickBullets()`, `tickHazards()`, `drawHazards()`, `drawEnemies()`, and `killEnemy()`. A module-level `harbingerRef` pointer tracks the Harbinger for pod-rage coordination.

**Tech Stack:** Vanilla JS / HTML5 Canvas 2D, no build system, no test framework. Verify each task by serving `index.php` through any local web server and testing in browser.

---

## File Structure

All changes to: `index.php`

Key line anchors (verify with Read before editing):
- `1837` — `const ETYPES={`
- `1848` — `function mkEnemy(type,x,y){`
- `1605` — `function drawEnemyDrone(...)` (add new draw functions after `_drawBrute`, before `drawPlayerCraft` at ~1628)
- `2199` — `function drawEnemies()` (dispatch block at ~2251)
- `1313` — `function tickBullets(dt)` (eBullets loop starts ~1333)
- `1911` — `function tickEnemies(dt,now)`
- `725` — `function tickHazards(dt,now)`
- `791` — `function drawHazards()`
- `2296` — `function killEnemy(idx)`
- `2790` — `function checkCollisions()`
- `560` — `const CT_SEQUENCE=[...]`
- `1894` — `function spawnWaveEnemies(n)` (W5 else-branch at ~1909)
- `4127` — `function spawnTTEnemies()` (L1 / Ghost Run)
- `4739` — `function spawnDBDEnemies()` (L3 / Dance Birdie Dance)

Module-level vars block — search for `let deadEyeMs` or similar near top.

---

## Task 1: ETYPES entries + mkEnemy extra fields

**Files:**
- Modify: `index.php:1837-1855`

- [ ] **Step 1: Read current ETYPES and mkEnemy**

```
Read index.php lines 1837–1856
```

- [ ] **Step 2: Add 7 new types to ETYPES**

Append after the `phantom` entry (before the closing `};`):

```javascript
  // ── Phase 3 hostile types ──────────────────────────────────────
  ravager:    {size:18,hp:60, spd:2.2,fireMs:2000,dmg:10,color:'#ff2200',accent:'#ff8866',score:180, det:220,atk:220,patR:120,drag:0.82},
  splitter:   {size:20,hp:110,spd:2.0,fireMs:1000,dmg:16,color:'#ffcc00',accent:'#fff088',score:260, det:290,atk:200,patR:130,drag:0.88},
  shard:      {size:10,hp:30, spd:3.5,fireMs:1300,dmg:8, color:'#ffcc00',accent:'#fff088',score:80,  det:220,atk:160,patR:80, drag:0.85},
  cloaker:    {size:13,hp:70, spd:3.2,fireMs:1100,dmg:17,color:'#88ffee',accent:'#ccffee',score:240, det:320,atk:240,patR:140,drag:0.86},
  demolisher: {size:24,hp:280,spd:1.3,fireMs:2200,dmg:0, color:'#cc44ff',accent:'#ee99ff',score:380, det:340,atk:280,patR:80, drag:0.93},
  hunter:     {size:9, hp:40, spd:6.0,fireMs:1600,dmg:11,color:'#ff44cc',accent:'#ffaaee',score:160, det:280,atk:180,patR:160,drag:0.80},
  // ── Phase 3 bosses ─────────────────────────────────────────────
  dreadnought:{size:42,hp:1400,spd:1.6,fireMs:280,dmg:18,color:'#ff6600',accent:'#ffcc44',score:3500,det:500,atk:380,patR:240,drag:0.91},
  harbinger:  {size:48,hp:1800,spd:1.2,fireMs:380,dmg:20,color:'#9900cc',accent:'#dd66ff',score:4500,det:500,atk:400,patR:280,drag:0.93},
```

- [ ] **Step 3: Add per-type extra fields in mkEnemy**

After the existing `if(type==='phantom')` block (line ~1854), add:

```javascript
  if(type==='ravager')    { e.chargeMs=0; e.chargeVx=0; e.chargeVy=0; }
  if(type==='cloaker')    { e.visibleMs=0; }
  if(type==='dreadnought'){ e.phase=1; e.phaseSwitched=false; e.shotCount=0; e.spiralAngle=0; }
  if(type==='harbinger')  { e.podThresholds=[0.66,0.33]; e.activePods=0; e.rageMs=0; e.spiralAngle=0; }
  if(type==='splitter'||type==='shard') {} // no extra fields; shard uses default
  e.fromHarbinger=false; // all enemies get this — set to true for harbinger pods
```

- [ ] **Step 4: Add module-level harbingerRef**

Find the block of module-level `let` vars (near the top, search for `let wave=` or `let bossWarning=`). Add:

```javascript
let harbingerRef=null; // points to live Harbinger; used by killEnemy for pod-rage
```

- [ ] **Step 5: Verify + commit**

Serve index.php; open browser; confirm no JS errors in console. All existing gameplay must still function.

```bash
git add index.php
git commit -m "feat: add 7 new enemy types to ETYPES + mkEnemy extra fields + harbingerRef"
```

---

## Task 2: Draw functions for all new types

**Files:**
- Modify: `index.php` (add 8 draw functions after `_drawBrute`, before `drawPlayerCraft`)

- [ ] **Step 1: Read existing _drawBrute and drawPlayerCraft lines**

```
Read index.php ~1590–1636 to find exact insertion point
```

- [ ] **Step 2: Add all 8 draw functions**

Insert all of the following after the closing `}` of `_drawBrute` and before `// Dispatcher for player craft`:

```javascript
function drawRavager(x,y,aim,sz,col,acc,spin,hp){
  ctx.save();ctx.translate(x,y);ctx.rotate(aim);
  ctx.shadowBlur=18;ctx.shadowColor=col;
  ctx.beginPath();ctx.moveTo(sz*1.4,0);ctx.lineTo(-sz*0.7,sz*0.9);ctx.lineTo(-sz*0.7,-sz*0.9);ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=2;ctx.stroke();
  ctx.strokeStyle=acc;ctx.lineWidth=2.5;ctx.shadowColor=acc;ctx.beginPath();ctx.moveTo(sz*1.4,0);ctx.lineTo(sz*2.6,0);ctx.stroke();
  ctx.beginPath();ctx.arc(0,0,sz*0.28,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowBlur=12;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.8,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawSplitter(x,y,aim,sz,col,acc,spin,hp){
  ctx.save();ctx.translate(x,y);ctx.rotate(spin);
  ctx.shadowBlur=16;ctx.shadowColor=col;
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*sz,Math.sin(a)*sz):ctx.lineTo(Math.cos(a)*sz,Math.sin(a)*sz);}ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=2;ctx.stroke();
  ctx.strokeStyle=acc;ctx.lineWidth=1.2;ctx.shadowColor=acc;ctx.shadowBlur=8;ctx.globalAlpha=0.7;
  ctx.beginPath();ctx.moveTo(-sz*0.7,sz*0.2);ctx.lineTo(sz*0.7,-sz*0.2);ctx.stroke();
  ctx.beginPath();ctx.moveTo(-sz*0.4,-sz*0.7);ctx.lineTo(sz*0.4,sz*0.5);ctx.stroke();
  ctx.globalAlpha=1;
  ctx.save();ctx.rotate(aim-spin);ctx.strokeStyle=acc;ctx.lineWidth=2;ctx.shadowBlur=8;ctx.shadowColor=acc;ctx.beginPath();ctx.moveTo(sz*0.5,0);ctx.lineTo(sz*1.3,0);ctx.stroke();ctx.restore();
  ctx.beginPath();ctx.arc(0,0,sz*0.25,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowBlur=12;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.8,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawShard(x,y,aim,sz,col,acc,spin,hp){
  ctx.save();ctx.translate(x,y);ctx.rotate(spin);
  ctx.shadowBlur=14;ctx.shadowColor=col;
  const pts=[[sz*1.1,0],[sz*0.3,sz*0.8],[-sz*0.9,sz*0.5],[-sz*0.7,-sz*0.6],[sz*0.4,-sz*0.9]];
  ctx.beginPath();ctx.moveTo(pts[0][0],pts[0][1]);for(let i=1;i<pts.length;i++)ctx.lineTo(pts[i][0],pts[i][1]);ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.8;ctx.stroke();
  ctx.beginPath();ctx.arc(0,0,sz*0.28,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowBlur=10;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.8,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawCloaker(x,y,aim,sz,col,acc,spin,hp,visibleMs){
  ctx.save();ctx.translate(x,y);
  ctx.globalAlpha=visibleMs>0?1.0:0.08;
  ctx.rotate(aim);ctx.shadowBlur=16;ctx.shadowColor=col;
  ctx.beginPath();ctx.moveTo(sz*1.2,0);ctx.lineTo(0,sz*0.7);ctx.lineTo(-sz*0.9,0);ctx.lineTo(0,-sz*0.7);ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.8;ctx.stroke();
  ctx.beginPath();ctx.arc(sz*1.2,0,2.5,0,Math.PI*2);ctx.fillStyle='#fff';ctx.shadowBlur=8;ctx.fill();
  ctx.beginPath();ctx.arc(0,0,sz*0.25,0,Math.PI*2);ctx.fillStyle=acc;ctx.fill();
  if(visibleMs>0){
    ctx.rotate(-aim);
    const pulse=0.5+0.5*Math.sin(Date.now()/80);
    ctx.strokeStyle=`rgba(136,255,238,${0.6*pulse})`;ctx.lineWidth=1.5;ctx.shadowBlur=10;ctx.shadowColor=acc;
    ctx.beginPath();ctx.arc(0,0,sz*1.9,0,Math.PI*2);ctx.stroke();
  }
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.globalAlpha=1;ctx.beginPath();ctx.arc(0,0,sz*0.8,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawDemolisher(x,y,aim,sz,col,acc,spin,hp){
  ctx.save();ctx.translate(x,y);ctx.rotate(aim);
  ctx.shadowBlur=18;ctx.shadowColor=col;
  ctx.save();ctx.scale(1.0,0.65);
  ctx.beginPath();ctx.arc(0,0,sz,0,Math.PI*2);
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=2;ctx.stroke();
  ctx.restore();
  ctx.strokeStyle=acc;ctx.lineWidth=4;ctx.shadowColor=acc;ctx.shadowBlur=12;
  ctx.beginPath();ctx.moveTo(sz*0.4,0);ctx.lineTo(sz*1.6,0);ctx.stroke();
  ctx.beginPath();ctx.arc(sz*1.6,0,4,0,Math.PI*2);ctx.fillStyle=acc;ctx.fill();
  ctx.strokeStyle=col;ctx.lineWidth=2;ctx.shadowBlur=8;ctx.shadowColor=col;
  ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(-sz*0.3,sz*1.1);ctx.stroke();
  ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(-sz*0.3,-sz*1.1);ctx.stroke();
  ctx.beginPath();ctx.arc(0,0,sz*0.28,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowBlur=10;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.75,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawHunter(x,y,aim,sz,col,acc,spin,hp){
  ctx.save();ctx.translate(x,y);ctx.rotate(aim);
  ctx.shadowBlur=14;ctx.shadowColor=col;
  ctx.beginPath();ctx.moveTo(sz*1.3,0);ctx.lineTo(-sz*0.8,sz*0.7);ctx.lineTo(-sz*0.4,0);ctx.lineTo(-sz*0.8,-sz*0.7);ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.6;ctx.stroke();
  const pulse=0.5+0.5*Math.sin(Date.now()/50);
  ctx.globalAlpha=0.6*pulse;ctx.fillStyle=acc;ctx.shadowColor=acc;ctx.shadowBlur=10;
  ctx.beginPath();ctx.moveTo(-sz*0.4,0);ctx.lineTo(-sz*1.4,sz*0.3);ctx.lineTo(-sz*1.1,0);ctx.lineTo(-sz*1.4,-sz*0.3);ctx.closePath();ctx.fill();
  ctx.globalAlpha=1;
  ctx.beginPath();ctx.arc(0,0,sz*0.22,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowBlur=10;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.75,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawDreadnought(x,y,aim,sz,col,acc,spin,hp,phase){
  ctx.save();ctx.translate(x,y);
  ctx.shadowBlur=22;ctx.shadowColor=col;
  if(phase===1){
    ctx.save();ctx.rotate(spin*0.3);
    ctx.strokeStyle=col;ctx.lineWidth=5;ctx.beginPath();ctx.arc(0,0,sz*1.05,0,Math.PI*2);ctx.stroke();
    ctx.strokeStyle=acc;ctx.lineWidth=3;
    for(let i=0;i<4;i++){const a=(Math.PI/2)*i;ctx.beginPath();ctx.arc(0,0,sz*1.05,a+0.15,a+Math.PI/2-0.15);ctx.stroke();}
    ctx.restore();
  } else {
    const pulse=0.5+0.5*Math.sin(Date.now()/100);
    ctx.beginPath();ctx.arc(0,0,sz*0.85,0,Math.PI*2);ctx.fillStyle=`rgba(255,102,0,${0.18*pulse})`;ctx.fill();
    ctx.strokeStyle=`rgba(255,204,68,${0.7+0.3*pulse})`;ctx.lineWidth=2;ctx.shadowColor=acc;ctx.shadowBlur=28*pulse;ctx.stroke();ctx.shadowBlur=22;
  }
  ctx.rotate(aim);
  const bs=sz*0.55;
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*bs,Math.sin(a)*bs):ctx.lineTo(Math.cos(a)*bs,Math.sin(a)*bs);}ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.95)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=2.2;ctx.stroke();
  ctx.strokeStyle=acc;ctx.lineWidth=2.5;ctx.shadowColor=acc;
  ctx.beginPath();ctx.moveTo(bs*0.4,0);ctx.lineTo(bs*1.5,0);ctx.stroke();
  ctx.beginPath();ctx.arc(0,0,sz*0.22,0,Math.PI*2);ctx.fillStyle=phase===2?'#fff':acc;ctx.shadowBlur=16;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.7,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawHarbinger(x,y,aim,sz,col,acc,spin,hp,rageMs){
  ctx.save();ctx.translate(x,y);
  const raging=rageMs>0, pulse=raging?0.5+0.5*Math.sin(Date.now()/60):0;
  ctx.shadowBlur=raging?36:22;ctx.shadowColor=raging?acc:col;
  ctx.save();ctx.rotate(spin*0.2);
  ctx.strokeStyle=raging?acc:col;ctx.lineWidth=raging?3+pulse*2:2;
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i+Math.PI/6;i===0?ctx.moveTo(Math.cos(a)*sz*1.32,Math.sin(a)*sz*1.32):ctx.lineTo(Math.cos(a)*sz*1.32,Math.sin(a)*sz*1.32);}ctx.closePath();ctx.stroke();
  for(let i=0;i<6;i++){const a=(Math.PI/3)*i+Math.PI/6;ctx.beginPath();ctx.arc(Math.cos(a)*sz*1.32,Math.sin(a)*sz*1.32,raging?5+pulse*3:4,0,Math.PI*2);ctx.fillStyle=raging?acc:col;ctx.fill();}
  ctx.restore();
  ctx.rotate(aim);
  const bs=sz*0.62;
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*bs,Math.sin(a)*bs):ctx.lineTo(Math.cos(a)*bs,Math.sin(a)*bs);}ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.95)';ctx.fill();ctx.strokeStyle=raging?acc:col;ctx.lineWidth=2.5;ctx.stroke();
  ctx.strokeStyle=acc;ctx.lineWidth=3;ctx.shadowColor=acc;
  ctx.beginPath();ctx.moveTo(bs*0.45,0);ctx.lineTo(bs*1.4,0);ctx.stroke();
  ctx.beginPath();ctx.arc(0,0,sz*0.25,0,Math.PI*2);ctx.fillStyle=raging?'#fff':acc;ctx.shadowBlur=raging?24:14;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.7,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
```

- [ ] **Step 3: Commit**

```bash
git add index.php
git commit -m "feat: draw functions for all 8 new enemy/boss types"
```

---

## Task 3: Update drawEnemies dispatch + drawBullets bomb rendering

**Files:**
- Modify: `index.php:~2251` (draw dispatch block inside `drawEnemies`)
- Modify: `index.php:~1351` (inside `drawBullets`, eBullet draw loop)

- [ ] **Step 1: Read current draw dispatch**

```
Read index.php 2199–2262
```

- [ ] **Step 2: Replace the craft draw dispatch block**

Find and replace the block (lines ~2251–2257):
```javascript
    // Draw distinct shapes for new types
    if(e.type==='dart'){
      _drawDart(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='brute'){
      _drawBrute(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else {
      drawEnemyDrone(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    }
```

Replace with:

```javascript
    // Set alpha for cloaker stealth
    const cloakerInvis=e.type==='cloaker'&&e.visibleMs<=0;
    if(cloakerInvis) ctx.globalAlpha=0.08;
    // Draw distinct shapes per type
    if(e.type==='dart'){
      _drawDart(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='brute'){
      _drawBrute(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='ravager'){
      // Flush to accent during charge
      const rc=e.chargeMs>0?e.accent:e.color;
      drawRavager(sx,sy,e.aim,e.size,rc,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='splitter'){
      drawSplitter(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='shard'){
      drawShard(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='cloaker'){
      drawCloaker(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp,e.visibleMs);
    } else if(e.type==='demolisher'){
      drawDemolisher(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='hunter'){
      drawHunter(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='dreadnought'){
      drawDreadnought(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp,e.phase);
    } else if(e.type==='harbinger'){
      drawHarbinger(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp,e.rageMs);
    } else {
      drawEnemyDrone(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    }
    if(cloakerInvis) ctx.globalAlpha=1;
```

- [ ] **Step 3: Add Demolisher bomb draw in drawBullets**

Read `drawBullets` (lines ~1351–1500). Find the eBullet draw loop. Inside it, add a `b.isBomb` branch near the top of each bullet's render:

Find the pattern in the eBullet loop that draws standard bullets (after all the pBullet code and into the eBullet section). Look for code like:
```javascript
  for(const b of eBullets){
    const sx=b.x-camX,sy=b.y-camY;
```

Inside that loop, before any existing branch, add:

```javascript
    if(b.isBomb){
      const bsx=b.x-camX,bsy=b.y-camY;
      if(bsx<-60||bsx>canvas.width+60||bsy<-60||bsy>canvas.height+60) continue;
      const pulse=0.5+0.5*Math.sin(Date.now()/90);
      ctx.save();ctx.translate(bsx,bsy);
      ctx.beginPath();ctx.arc(0,0,b.bSz,0,Math.PI*2);
      ctx.fillStyle=`rgba(204,68,255,${0.7+0.3*pulse})`;ctx.shadowBlur=16;ctx.shadowColor='#cc44ff';ctx.fill();
      ctx.strokeStyle='#ee99ff';ctx.lineWidth=1.5;ctx.stroke();
      ctx.restore();
      continue;
    }
```

Note: check exact structure of the eBullet draw loop first; the `continue` must be inside the eBullet loop body.

- [ ] **Step 4: Verify + commit**

Serve and confirm in browser: new enemy shapes visible in-game, no console errors.

```bash
git add index.php
git commit -m "feat: update drawEnemies dispatch + bomb rendering in drawBullets"
```

---

## Task 4: Demolisher — bomb firing + plasma_zone hazard in tickBullets

**Files:**
- Modify: `index.php:~2021` (fire dispatch inside `tickEnemies`)
- Modify: `index.php:~1333` (eBullets loop in `tickBullets`)

- [ ] **Step 1: Read eBullets loop in tickBullets**

```
Read index.php 1333–1350
```

- [ ] **Step 2: Add isBomb early-exit before normal eBullet expiry**

Current eBullet loop starts at ~1333:
```javascript
  for(let i=eBullets.length-1;i>=0;i--){
    const b=eBullets[i];b.x+=b.vx*step;b.y+=b.vy*step;b.life-=dt*1000;
    if(b.life<=0||b.x<-50||b.x>WORLD_W+50||b.y<-50||b.y>WORLD_H+50){eBullets.splice(i,1);continue;}
```

Replace those lines with:

```javascript
  for(let i=eBullets.length-1;i>=0;i--){
    const b=eBullets[i];b.x+=b.vx*step;b.y+=b.vy*step;b.life-=dt*1000;
    // Demolisher plasma bomb — check before normal expiry
    if(b.isBomb){
      if(b.life<=0||dist(b.x,b.y,b.ox,b.oy)>600||circleVsObs(b.x,b.y,b.bSz)){
        hazards.push({type:'plasma_zone',x:b.x,y:b.y,r:55,duration:2500,t:0});
        spawnParts(b.x,b.y,'#cc44ff',_pCount(14),4,6,600);
        if(settings.screenShake)shake=Math.max(shake,12);
        eBullets.splice(i,1);
      }
      continue;
    }
    if(b.life<=0||b.x<-50||b.x>WORLD_W+50||b.y<-50||b.y>WORLD_H+50){eBullets.splice(i,1);continue;}
```

- [ ] **Step 3: Add Demolisher fire case in tickEnemies fire dispatch**

Read lines ~2021–2034 to see the exact fire dispatch:
```javascript
      if(e.type==='boss'){...}
      else if(e.type==='turret'){...}
      else if(e.type==='brute'){...}
      else if(e.type==='phantom'){...}
      else fireEBullet(...);
      e.lastFired=now;
```

Add before the final `else fireEBullet(...)`:

```javascript
      else if(e.type==='demolisher'){
        const angle=e.aim+(Math.random()-0.5)*0.1;
        eBullets.push({x:e.x,y:e.y,vx:Math.cos(angle)*3.5,vy:Math.sin(angle)*3.5,life:2000,dmg:0,bSz:10,isBomb:true,ox:e.x,oy:e.y});
        spawnParts(e.x,e.y,e.color,_pCount(6),2,3.5,280);
      }
```

- [ ] **Step 4: Commit**

```bash
git add index.php
git commit -m "feat: Demolisher bomb firing + plasma_zone hazard spawn in tickBullets"
```

---

## Task 5: plasma_zone hazard — tickHazards + drawHazards

**Files:**
- Modify: `index.php:725` (`tickHazards`)
- Modify: `index.php:791` (`drawHazards`)

- [ ] **Step 1: Read end of tickHazards**

```
Read index.php 760–792
```

The `floor_mine` block ends around 787, closing with `}`. After the closing `}` of the floor_mine block (before the outer `}`), add:

```javascript
    } else if(h.type==='plasma_zone'){
      h.t+=dt*1000;
      if(h.t>=h.duration){hazards.splice(hi,1);continue;}
      if(P.alive&&P.iframes<=0&&P.invincMs<=0&&dist(P.x,P.y,h.x,h.y)<h.r){
        if(P.shieldMs>0){P.shieldMs=0;spawnParts(P.x,P.y,'#44aaff',_pCount(14),4,5,400);SFX.shbreak();P.iframes=400;}
        else{P.hp-=18*dt*P.damageMult;if(settings.screenShake)shake=Math.max(shake,6);SFX.hit();Music.onHit();if(P.hp<=0)P.alive=false;}
      }
```

- [ ] **Step 2: Add plasma_zone rendering in drawHazards**

Read `drawHazards` (~791–860). Find the end of the `floor_mine` render block and add after it:

```javascript
    } else if(h.type==='plasma_zone'){
      const sx=h.x-camX,sy=h.y-camY;
      if(sx<-200||sx>canvas.width+200||sy<-200||sy>canvas.height+200)continue;
      const tPct=h.t/h.duration;
      const alpha=0.28*(1-tPct*0.5);
      const pulse=0.6+0.4*Math.sin(Date.now()/120);
      ctx.save();ctx.translate(sx,sy);
      ctx.beginPath();ctx.arc(0,0,h.r,0,Math.PI*2);
      ctx.fillStyle=`rgba(204,68,255,${alpha*pulse})`;ctx.fill();
      ctx.strokeStyle=`rgba(238,153,255,${0.6*pulse})`;ctx.lineWidth=2;ctx.shadowBlur=18;ctx.shadowColor='#cc44ff';ctx.stroke();
      ctx.shadowBlur=0;ctx.restore();
```

- [ ] **Step 3: Verify + commit**

Spawn a Demolisher in W5 (or via console `enemies.push(mkEnemy('demolisher',P.x+300,P.y))`), confirm bomb fires, lands, and creates a purple hazard zone that damages the player on contact.

```bash
git add index.php
git commit -m "feat: plasma_zone hazard tick + draw (Demolisher bomb landing zone)"
```

---

## Task 6: Ravager charge mechanic in tickEnemies

**Files:**
- Modify: `index.php:~1977` (inside `tickEnemies`, after phantom section)

- [ ] **Step 1: Read phantom section end and standard movement start**

```
Read index.php 1977–2004
```

- [ ] **Step 2: Insert Ravager charge block**

Find the line `if(e.type!=='turret'&&!moveStunned){` (standard movement block, ~line 1997). Insert before it:

```javascript
    // ── RAVAGER: charge attack ──
    if(e.type==='ravager'&&!moveStunned){
      if(e.chargeMs>0){
        e.chargeMs-=dt*1000;
        e.x=clamp(e.x+e.chargeVx*dt*60,e.size,WORLD_W-e.size);
        e.y=clamp(e.y+e.chargeVy*dt*60,e.size,WORLD_H-e.size);
        pushOutObs(e,e.size);
        if(P.alive&&P.iframes===0&&dist(e.x,e.y,P.x,P.y)<e.size+P.size){
          if(P.shieldMs>0){P.shieldMs=0;spawnParts(P.x,P.y,'#44aaff',_pCount(14),4,5,400);SFX.shbreak();P.iframes=400;}
          else{P.hp-=38*P.damageMult;P.iframes=600;if(settings.screenShake)shake=Math.max(shake,18);SFX.hit();if(P.hp<=0)P.alive=false;Music.onHit();}
          e.chargeMs=-1500;
        }
        continue;
      }
      if(e.chargeMs<=0&&e.state==='attack'&&dist(e.x,e.y,P.x,P.y)<220){
        const dd=dist(e.x,e.y,P.x,P.y)||1;
        e.chargeMs=600;
        e.chargeVx=(P.x-e.x)/dd*11;
        e.chargeVy=(P.y-e.y)/dd*11;
        continue;
      }
    }
```

- [ ] **Step 3: Commit**

```bash
git add index.php
git commit -m "feat: Ravager charge attack mechanic in tickEnemies"
```

---

## Task 7: Cloaker visibleMs tick + fire hook

**Files:**
- Modify: `index.php:~1997` (per-type section in tickEnemies)
- Modify: `index.php:~2034` (after `e.lastFired=now`)

- [ ] **Step 1: Add Cloaker visibleMs decrement**

In the per-type section (after wraith, after phantom), insert:

```javascript
    // ── CLOAKER: decrement visibility timer ──
    if(e.type==='cloaker'&&e.visibleMs>0) e.visibleMs-=dt*1000;
```

- [ ] **Step 2: Set visibleMs on fire**

Find `e.lastFired=now;` at the end of the fire block (~line 2034). Replace with:

```javascript
      e.lastFired=now;
      if(e.type==='cloaker') e.visibleMs=420;
```

- [ ] **Step 3: Commit**

```bash
git add index.php
git commit -m "feat: Cloaker stealth visibleMs — decrement tick + reveal on fire"
```

---

## Task 8: Hunter drone-targeting in tickEnemies

**Files:**
- Modify: `index.php:~1997` (per-type section) and standard movement guard (~1997)

- [ ] **Step 1: Read standard movement block**

```
Read index.php 1997–2005
```

The guard line is: `if(e.type!=='turret'&&!moveStunned){`

- [ ] **Step 2: Add Hunter movement override before the guard**

Insert before `if(e.type!=='turret'&&!moveStunned){`:

```javascript
    // ── HUNTER: retarget to carrier drones when available ──
    if(e.type==='hunter'&&!moveStunned){
      let htx=P.x,hty=P.y;
      if(typeof carrierDrones!=='undefined'&&CRAFTS[P.craftIdx].id==='carrier'){
        let bestD=Infinity,bestDr=null;
        for(const dr of carrierDrones){if(dr.hp>0){const dd=dist(e.x,e.y,dr.x,dr.y);if(dd<bestD){bestD=dd;bestDr=dr;}}}
        if(bestDr){htx=bestDr.x;hty=bestDr.y;}
      }
      e.aim=Math.atan2(hty-e.y,htx-e.x);
      const htd=dist(e.x,e.y,htx,hty)||1;
      if(e.state==='chase'||e.state==='attack'){e.vx+=(htx-e.x)/htd*e.spd;e.vy+=(hty-e.y)/htd*e.spd;}
      e.vx*=e.drag;e.vy*=e.drag;
      e.x=clamp(e.x+e.vx*dt*60,e.size,WORLD_W-e.size);
      e.y=clamp(e.y+e.vy*dt*60,e.size,WORLD_H-e.size);
      pushOutObs(e,e.size);
    }
```

- [ ] **Step 3: Exclude Hunter from standard movement block**

Change:
```javascript
    if(e.type!=='turret'&&!moveStunned){
```

To:
```javascript
    if(e.type!=='turret'&&e.type!=='hunter'&&!moveStunned){
```

- [ ] **Step 4: Commit**

```bash
git add index.php
git commit -m "feat: Hunter drone-targeting override in tickEnemies"
```

---

## Task 9: Dreadnought — phase transition + two-phase fire

**Files:**
- Modify: `index.php:~1997` (per-type section)
- Modify: `index.php:~2021` (fire dispatch)

- [ ] **Step 1: Add phase-transition check**

In the per-type section (after the Cloaker block), insert:

```javascript
    // ── DREADNOUGHT: phase 1→2 transition at 50% HP ──
    if(e.type==='dreadnought'&&e.phase===1&&!e.phaseSwitched&&e.hp<e.maxHp*0.5){
      e.phase=2;e.phaseSwitched=true;
      e.spd=2.8;e.fireMs=200;
      spawnParts(e.x,e.y,e.color,_pCount(30),7,9,900);
      spawnParts(e.x,e.y,'#ffffff',_pCount(20),5,6,700);
      if(settings.screenShake)shake=Math.max(shake,28);SFX.boss();
    }
```

- [ ] **Step 2: Add Dreadnought fire case in fire dispatch**

In the fire dispatch block, before `else fireEBullet(e.x,e.y,e.aim+sp,7.5,e.dmg);`, add:

```javascript
      else if(e.type==='dreadnought'){
        e.shotCount++;
        if(e.phase===1){
          for(const off of[-0.28,0,0.28]) fireEBullet(e.x,e.y,e.aim+off,7.5,e.dmg);
          if(e.shotCount%4===0) fireEBullet(e.x,e.y,e.aim,4,e.dmg*1.5); // slow pulse
        } else {
          for(let s=0;s<8;s++) fireEBullet(e.x,e.y,e.spiralAngle+s*(Math.PI/4),6.5,e.dmg*0.8);
          e.spiralAngle+=0.18;
        }
        spawnParts(e.x,e.y,e.color,_pCount(6),3,4.5,220);
      }
```

- [ ] **Step 3: Add Dreadnought phase 2 damage multiplier in checkCollisions**

Read lines 2800–2810. Find `e.hp-=b.dmg;`. Replace with:

```javascript
          const dmgMult=(e.type==='dreadnought'&&e.phase===2)?2.0:1.0;
          e.hp-=b.dmg*dmgMult;
```

- [ ] **Step 4: Commit**

```bash
git add index.php
git commit -m "feat: Dreadnought two-phase mechanic — transition, fire patterns, Phase 2 damage multiplier"
```

---

## Task 10: Harbinger — figure-8 movement + pod spawning + rage

**Files:**
- Modify: `index.php:~1997` (per-type section in tickEnemies)
- Modify: `index.php:~2021` (fire dispatch)
- Modify: `index.php:~1997` (standard movement guard)

- [ ] **Step 1: Add Harbinger per-type block**

In the per-type section (after Dreadnought block), insert:

```javascript
    // ── HARBINGER: figure-8 movement + pod spawning + rage ──
    if(e.type==='harbinger'){
      // Pod threshold check
      if(e.podThresholds.length>0&&e.hp<=e.maxHp*e.podThresholds[0]){
        e.podThresholds.shift();
        for(const yOff of[-60,60]){
          const pod=mkEnemy('turret',e.x,e.y+yOff);
          pod.fromHarbinger=true;
          enemies.push(pod);
          e.activePods++;
        }
        spawnParts(e.x,e.y,e.accent,_pCount(16),4,6,500);
      }
      // Rage countdown
      if(e.rageMs>0){
        e.rageMs-=dt*1000;
        if(e.rageMs<=0) e.fireMs=380; // restore normal rate when rage ends
      }
      // Figure-8 movement
      if(!moveStunned){
        const t=Date.now()/1000;
        const ftx=e.patCx+Math.cos(t*0.4)*e.patR;
        const fty=e.patCy+Math.sin(t*0.8)*e.patR*0.5;
        const ftd=dist(e.x,e.y,ftx,fty)||1;
        e.vx+=(ftx-e.x)/ftd*e.spd;e.vy+=(fty-e.y)/ftd*e.spd;
        e.vx*=e.drag;e.vy*=e.drag;
        e.x=clamp(e.x+e.vx*dt*60,e.size,WORLD_W-e.size);
        e.y=clamp(e.y+e.vy*dt*60,e.size,WORLD_H-e.size);
        pushOutObs(e,e.size);
      }
    }
```

- [ ] **Step 2: Add Harbinger to standard movement exclusion**

Extend the guard (modified in Task 8):
```javascript
    if(e.type!=='turret'&&e.type!=='hunter'&&!moveStunned){
```

To:
```javascript
    if(e.type!=='turret'&&e.type!=='hunter'&&e.type!=='harbinger'&&!moveStunned){
```

- [ ] **Step 3: Add Harbinger fire case in fire dispatch**

Before `else fireEBullet(...)`, add:

```javascript
      else if(e.type==='harbinger'){
        e.spiralAngle+=0.22;
        fireEBullet(e.x,e.y,e.spiralAngle,5.5,e.dmg);
        spawnParts(e.x,e.y,e.color,_pCount(4),2.5,3.5,180);
      }
```

- [ ] **Step 4: Set harbingerRef when harbinger spawns in spawnWaveEnemies**

This is handled in Task 11 (boss placement). No change needed here.

- [ ] **Step 5: Commit**

```bash
git add index.php
git commit -m "feat: Harbinger figure-8 movement + pod spawning + rage mode"
```

---

## Task 11: killEnemy — splitter shards + harbinger pod tracking + boss bonus

**Files:**
- Modify: `index.php:2296`

- [ ] **Step 1: Read killEnemy**

```
Read index.php 2296–2317
```

- [ ] **Step 2: Add splitter split and pod tracking before score line**

Insert at the very top of `killEnemy(idx)`, after `const e=enemies[idx];`:

```javascript
  // Splitter: spawn 2 shards before removing
  if(e.type==='splitter'){
    for(const yOff of[-20,20]){
      const se=mkEnemy('shard',e.x,e.y+yOff);
      se.state='chase';
      enemies.push(se);
    }
  }
  // Harbinger pod: notify parent, trigger rage when all pods dead
  if(e.fromHarbinger&&harbingerRef&&harbingerRef.hp>0){
    harbingerRef.activePods=Math.max(0,harbingerRef.activePods-1);
    if(harbingerRef.activePods===0){harbingerRef.rageMs=4000;harbingerRef.fireMs=80;}
  }
  // Harbinger itself dying: clear the ref
  if(e.type==='harbinger') harbingerRef=null;
```

- [ ] **Step 3: Extend boss bonus to new bosses**

Find:
```javascript
  if(e.type==='boss') score+=100; // Big Boss bonus
```

Replace with:
```javascript
  if(e.type==='boss'||e.type==='dreadnought'||e.type==='harbinger') score+=100;
```

- [ ] **Step 4: Commit**

```bash
git add index.php
git commit -m "feat: killEnemy — splitter shards, harbinger pod-rage tracking, boss bonus"
```

---

## Task 12: Boss placement — W5 random pool, CT_SEQUENCE, TT L1/L3

**Files:**
- Modify: `index.php:560` (`CT_SEQUENCE`)
- Modify: `index.php:~1909` (W5 else-branch in `spawnWaveEnemies`)
- Modify: `index.php:~4163` (end of `spawnTTEnemies`)
- Modify: `index.php:~4767` (end of `spawnDBDEnemies`)

- [ ] **Step 1: Extend CT_SEQUENCE**

Find line 560:
```javascript
const CT_SEQUENCE=['dart','scout','guard','phantom','wraith','turret','brute','boss'];
```

Replace with:
```javascript
const CT_SEQUENCE=['dart','scout','guard','phantom','wraith','turret','brute','boss','dreadnought','harbinger'];
```

- [ ] **Step 2: Replace W5 boss in spawnWaveEnemies**

Read line ~1909. Find:
```javascript
  else{add('scout',3);add('guard',3);add('turret',2);add('dart',2);add('wraith',2);add('brute',2);add('phantom',2);add('boss',1);bossWarning=3500;SFX.boss();}
```

Replace with:
```javascript
  else{
    add('scout',3);add('guard',3);add('turret',2);add('dart',2);add('wraith',2);add('brute',2);add('phantom',2);
    const _bPool=['boss','dreadnought','harbinger'];
    const _bType=_bPool[Math.floor(Math.random()*_bPool.length)];
    add(_bType,1);
    if(_bType==='harbinger') harbingerRef=enemies[enemies.length-1];
    bossWarning=3500;SFX.boss();
  }
```

- [ ] **Step 3: Add boss spawn at end of spawnTTEnemies (L1 Ghost Run)**

Read `spawnTTEnemies` end (~line 4163). After the `spawnHazardMines` call:

```javascript
  // Boss near finish line (Ghost Run only — L1)
  {
    const _bPool=['dreadnought','harbinger'];
    const _bType=_bPool[Math.floor(Math.random()*_bPool.length)];
    const _bE=mkEnemy(_bType,TT_FINISH_X-600,WORLD_H/2);
    enemies.push(_bE);
    if(_bType==='harbinger') harbingerRef=_bE;
  }
```

Note: `spawnTTEnemies` is called only for L1 (Ghost Run). Confirm this by reading `startGame()` context.

- [ ] **Step 4: Add boss spawn at end of spawnDBDEnemies (L3 Dance Birdie Dance)**

Read `spawnDBDEnemies` end (~line 4767). After the `spawnHazardMines` call:

```javascript
  // Boss near finish line (Dance Birdie Dance)
  {
    const _bPool=['dreadnought','harbinger'];
    const _bType=_bPool[Math.floor(Math.random()*_bPool.length)];
    const _bE=mkEnemy(_bType,DBD_FINISH_X-600,WORLD_H/2);
    enemies.push(_bE);
    if(_bType==='harbinger') harbingerRef=_bE;
  }
```

- [ ] **Step 5: Verify + commit**

Test all three placements:
1. Battle → play to W5, confirm boss is drawn correctly as one of three types
2. Combat Training → play through all 8 original rounds, confirm Dreadnought appears in round 9 and Harbinger in round 10
3. Time Trial Ghost Run → near the finish (~20000+ px), confirm a boss appears
4. Time Trial Dance Birdie Dance → confirm boss near finish

```bash
git add index.php
git commit -m "feat: boss placement — W5 random pool, CT_SEQUENCE +2, TT L1/L3 finish line bosses"
```

---

## Task 13: New hostiles in Battle waves 3–5

**Files:**
- Modify: `index.php:~1905–1909` (waves 3/4/5 in `spawnWaveEnemies`)

- [ ] **Step 1: Read spawnWaveEnemies wave definitions**

```
Read index.php 1905–1910
```

- [ ] **Step 2: Add new hostiles to waves 3/4/5**

Find:
```javascript
  else if(n===3){add('scout',4);add('guard',3);add('turret',2);add('dart',2);add('brute',1);}
  else if(n===4){add('scout',4);add('guard',3);add('turret',2);add('dart',3);add('wraith',2);add('brute',1);add('phantom',1);}
  else{
    add('scout',3);add('guard',3);add('turret',2);add('dart',2);add('wraith',2);add('brute',2);add('phantom',2);
```

Replace with:
```javascript
  else if(n===3){add('scout',3);add('guard',2);add('turret',2);add('dart',2);add('brute',1);add('ravager',1);add('cloaker',1);}
  else if(n===4){add('scout',3);add('guard',2);add('turret',2);add('dart',2);add('wraith',2);add('brute',1);add('phantom',1);add('splitter',1);add('hunter',1);add('demolisher',1);}
  else{
    add('scout',2);add('guard',2);add('turret',2);add('dart',2);add('wraith',2);add('brute',2);add('phantom',1);add('ravager',1);add('splitter',1);add('cloaker',1);add('hunter',1);add('demolisher',1);
```

- [ ] **Step 3: Commit**

```bash
git add index.php
git commit -m "feat: new hostile types seeded into Battle waves 3/4/5"
```

---

## Spec Self-Review

After writing all tasks, checking spec coverage:

| Spec Requirement | Task |
|---|---|
| ETYPES entries for all 7 new types | Task 1 |
| mkEnemy extra fields (chargeMs, visibleMs, phase, etc.) | Task 1 |
| harbingerRef module-level | Task 1 |
| Draw functions (8 total) | Task 2 |
| drawEnemies dispatch update | Task 3 |
| Demolisher isBomb bullet (ox/oy, life:2000, bSz:10) | Task 4 |
| plasma_zone hazard spawn in tickBullets | Task 4 |
| plasma_zone tick (damage 18 dmg/s) + draw | Task 5 |
| Ravager charge mechanic (600ms, spd 11, 38 dmg collision) | Task 6 |
| Cloaker visibleMs decrement + set on fire | Task 7 |
| Hunter drone-targeting override | Task 8 |
| Dreadnought phase 1→2 at 50% HP | Task 9 |
| Dreadnought fire: phase 1 3-way + pulse / phase 2 8-shot spiral | Task 9 |
| Dreadnought phase 2 double damage multiplier | Task 9 |
| Harbinger figure-8 movement | Task 10 |
| Harbinger pod spawn at thresholds 0.66 / 0.33 | Task 10 |
| Harbinger rage 4000ms → fireMs 80 when all pods dead | Task 10 |
| Harbinger spiral fire | Task 10 |
| killEnemy: splitter → 2 shards | Task 11 |
| killEnemy: fromHarbinger pod tracking → trigger rage | Task 11 |
| killEnemy: harbingerRef cleared on death | Task 11 |
| New boss score bonus in killEnemy | Task 11 |
| W5 random boss pool | Task 12 |
| CT_SEQUENCE extended with dreadnought + harbinger | Task 12 |
| TT L1 (spawnTTEnemies) boss near finish | Task 12 |
| TT L3 (spawnDBDEnemies) boss near finish | Task 12 |
| New hostiles in battle waves | Task 13 |

---

## Unresolved Questions

- Hunter: `carrierDrones` only exists in the new-craft branch (not yet merged). Safe-guard `typeof carrierDrones!=='undefined'` is included; hunter falls back to player targeting until that branch merges.
- Harbinger pod turrets spawn inside Harbinger's hitbox — may clip. offset ±60px should be sufficient but worth eye-checking.
- No bossWarning for TT finish-line bosses — intentional? Spec did not mention it. Current impl has no boss warning for TT.
