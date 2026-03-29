# Level Data Format + Loader Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enable playing custom-designed levels from stored JSON data, with 5 win conditions, pack sequencing, and a level select screen.

**Architecture:** Add module-level custom level state, a `loadCustomLevel()` function that hydrates the game from JSON, win condition tick logic gated by `gameMode==='custom'`, a `customSelect` screen for browsing saved levels, and localStorage helpers. All in the single `index.php` file following existing patterns.

**Tech Stack:** Vanilla JS, Canvas 2D, single-file `index.php` (~7500 lines). Work in `F:\PATROL WING\.worktrees\level-loader\index.php`.

---

### Task 1: Module-level state + localStorage helpers

**Files:**
- Modify: `F:\PATROL WING\.worktrees\level-loader\index.php`

- [ ] **Step 1: Add module-level custom level state**

Find the Combat Training state variables (around line 603-608, where `let ctLevel=0;` etc.). After them, add:

```javascript
// Custom level state
let customPack=null;
let customObjectives=[];
let customWinCondition='';
let customWinParams={};
let customSurviveMs=0;
let customKeysCollected=0;
let customKeysTotal=0;
let customItemHeld=false;
let customFinishX=0,customFinishY=0;
let customGoalX=0,customGoalY=0;
```

- [ ] **Step 2: Add localStorage helpers**

Find `_saveLoadout`/`_loadLoadout` (around line 556). After them, add:

```javascript
function _saveCustomLevels(packs){
  try{localStorage.setItem('pw_custom_levels',JSON.stringify(packs));}catch(e){}
}
function _loadCustomLevels(){
  try{return JSON.parse(localStorage.getItem('pw_custom_levels'))||[];}catch(e){return[];}
}
```

- [ ] **Step 3: Commit**
```bash
cd "F:\PATROL WING\.worktrees\level-loader" && git add index.php && git commit -m "feat: custom level module state + localStorage helpers"
```

---

### Task 2: loadCustomLevel() function

**Files:**
- Modify: `F:\PATROL WING\.worktrees\level-loader\index.php`

- [ ] **Step 1: Add `loadCustomLevel(levelData)` function**

Add after the `startTouchNGo()` function (search for the end of `startTouchNGo` -- it ends around line 7060). Insert before the Time Trial obstacle generation functions:

```javascript
function loadCustomLevel(levelData){
  _hideAllAds();
  gameMode='custom';
  WORLD_W=Math.min(4500,Math.max(canvas.width,levelData.worldW||2600));
  WORLD_H=Math.min(4500,Math.max(canvas.height,levelData.worldH||1700));
  score=0;wave=1;bossWarning=0;empFlash=0;
  weaponFlash={name:levelData.name||'CUSTOM LEVEL',ms:2500};
  leechFlash={active:false,tx:0,ty:0,ms:0};shockwaveFlash={ms:0};
  harbingerRef=null;
  portalActive=false;portalPositions=[];
  particles.length=0;pickups.length=0;pBullets.length=0;eBullets.length=0;
  mines.length=0;seekers.length=0;rockets.length=0;boomerangs.length=0;fractals.length=0;
  hazards.length=0;grenades.length=0;gravityWells.length=0;faradayCages.length=0;enemies.length=0;
  miniMe.active=false;miniMe.lost=false;miniMe.hp=MM_HP;miniMe.iframes=0;
  lastHullBeepMs=0;
  resetPlayer();
  P.x=levelData.spawnX||WORLD_W/2;
  P.y=levelData.spawnY||WORLD_H/2;
  camX=clamp(P.x-canvas.width/2,0,Math.max(0,WORLD_W-canvas.width));
  camY=clamp(P.y-canvas.height/2,0,Math.max(0,WORLD_H-canvas.height));
  // Obstacles
  obstacles=[];
  if(levelData.obstacles){
    for(const o of levelData.obstacles){
      if(o.type==='pillar') obstacles.push({type:'pillar',x:o.x,y:o.y,r:o.r||35,rot:Math.random()*Math.PI});
      else if(o.type==='wall') obstacles.push({type:'wall',x:o.x,y:o.y,w:o.w||26,h:o.h||100});
    }
  }
  // Enemies
  if(levelData.enemies){
    for(const en of levelData.enemies){
      if(ETYPES[en.type]){
        const e=mkEnemy(en.type,en.x,en.y);
        enemies.push(e);
        if(en.type==='harbinger') harbingerRef=e;
      }
    }
  }
  // Pickups
  if(levelData.pickups){
    for(const p of levelData.pickups) spawnPickup(p.x,p.y,p.type,!!p.hidden);
  }
  // Hazards
  if(levelData.hazards){
    for(const h of levelData.hazards){
      if(h.type==='zap_pylon') spawnZapPylonPair(h.x,h.y,h.angle||0,h.gap||120);
      else if(h.type==='floor_mine') spawnFloorMine(h.x,h.y);
    }
  }
  // Win condition setup
  customWinCondition=levelData.winCondition||'killAll';
  customWinParams=levelData.winParams||{};
  customObjectives=levelData.objectives||[];
  customSurviveMs=(customWinCondition==='survive'&&customWinParams.seconds)?customWinParams.seconds*1000:0;
  customKeysCollected=0;
  customKeysTotal=customObjectives.filter(o=>o.type==='key').length;
  customItemHeld=false;
  customFinishX=0;customFinishY=0;
  customGoalX=0;customGoalY=0;
  for(const obj of customObjectives){
    if(obj.type==='finish'){customFinishX=obj.x;customFinishY=obj.y;}
    if(obj.type==='goal'){customGoalX=obj.x;customGoalY=obj.y;}
  }
  gameStartTime=Date.now();
  gameState='playing';
}
```

- [ ] **Step 2: Commit**
```bash
git add index.php && git commit -m "feat: loadCustomLevel() — hydrates game state from JSON level data"
```

---

### Task 3: Win condition tick logic + objective rendering

**Files:**
- Modify: `F:\PATROL WING\.worktrees\level-loader\index.php`

- [ ] **Step 1: Add `customLevelWin()` function**

Add after `loadCustomLevel()`:

```javascript
function customLevelWin(){
  if(customPack&&customPack.currentIdx<customPack.levels.length-1){
    customPack.currentIdx++;
    weaponFlash={name:'LEVEL COMPLETE',ms:2200};
    SFX.wave();
    // Brief pause then load next
    setTimeout(()=>loadCustomLevel(customPack.levels[customPack.currentIdx]),2200);
  } else {
    gameState='customResult';
    SFX.confirm();
  }
}
```

- [ ] **Step 2: Add `tickCustomWinCondition(dt)` function**

Add after `customLevelWin()`:

```javascript
function tickCustomWinCondition(dt){
  if(gameMode!=='custom'||!P.alive)return;
  if(customWinCondition==='killAll'){
    if(enemies.length===0) customLevelWin();
  } else if(customWinCondition==='reachFinish'){
    if(dist(P.x,P.y,customFinishX,customFinishY)<50) customLevelWin();
  } else if(customWinCondition==='survive'){
    customSurviveMs-=dt*1000;
    if(customSurviveMs<=0) customLevelWin();
  } else if(customWinCondition==='retrieve'){
    if(customItemHeld&&dist(P.x,P.y,customGoalX,customGoalY)<50) customLevelWin();
  } else if(customWinCondition==='collectAll'){
    if(customKeysCollected>=customKeysTotal) customLevelWin();
  }
}
```

- [ ] **Step 3: Wire `tickCustomWinCondition` into main loop**

Find the main playing tick line (around line 8098, the long line with `tickPlayer(dt,now);tickCarrierDrones(dt,now);...checkCollisions();`). At the END of that line, append:

```javascript
tickCustomWinCondition(dt);
```

Use str_replace to find the end of that tick chain (ending with `checkCollisions();`) and append the new call.

- [ ] **Step 4: Add `drawCustomObjectives()` function**

Add after `tickCustomWinCondition()`:

```javascript
function drawCustomObjectives(){
  if(gameMode!=='custom')return;
  const t=Date.now()/1000;
  for(const obj of customObjectives){
    const sx=obj.x-camX,sy=obj.y-camY;
    if(sx<-60||sx>canvas.width+60||sy<-60||sy>canvas.height+60)continue;
    if(obj.type==='finish'){
      const pulse=0.7+0.3*Math.sin(t*3);
      ctx.shadowBlur=24*pulse;ctx.shadowColor='#ffdd00';
      ctx.strokeStyle=`rgba(255,220,0,${0.88*pulse})`;ctx.lineWidth=3;
      ctx.beginPath();ctx.moveTo(sx,0);ctx.lineTo(sx,canvas.height);ctx.stroke();ctx.shadowBlur=0;
    } else if(obj.type==='key'&&!obj.collected){
      const pulse=0.6+0.4*Math.sin(t*4+obj.x);
      ctx.save();ctx.translate(sx,sy);ctx.rotate(t*2);
      ctx.shadowBlur=14*pulse;ctx.shadowColor='#ffdd00';
      ctx.font='bold 18px "Courier New"';ctx.fillStyle=`rgba(255,220,0,${0.7+0.3*pulse})`;
      ctx.textAlign='center';ctx.fillText('\u{1F511}',0,6);
      ctx.shadowBlur=0;ctx.restore();
    } else if(obj.type==='item'&&!customItemHeld){
      const pulse=0.6+0.4*Math.sin(t*3);
      ctx.save();ctx.translate(sx,sy);
      ctx.shadowBlur=16*pulse;ctx.shadowColor='#ff8800';
      ctx.beginPath();ctx.moveTo(0,-12);ctx.lineTo(10,0);ctx.lineTo(0,12);ctx.lineTo(-10,0);ctx.closePath();
      ctx.fillStyle=`rgba(255,136,0,${0.75+0.25*pulse})`;ctx.fill();
      ctx.strokeStyle='#ffcc44';ctx.lineWidth=2;ctx.stroke();
      ctx.shadowBlur=0;ctx.restore();
    } else if(obj.type==='goal'){
      const pulse=0.5+0.5*Math.sin(t*2);
      const r=40+6*pulse;
      ctx.beginPath();ctx.arc(sx,sy,r,0,Math.PI*2);
      ctx.strokeStyle=`rgba(0,255,136,${0.4+0.3*pulse})`;ctx.lineWidth=3;
      ctx.shadowBlur=18*pulse;ctx.shadowColor='#00ff88';ctx.stroke();ctx.shadowBlur=0;
      ctx.font='10px "Courier New"';ctx.fillStyle='rgba(0,255,136,0.7)';ctx.textAlign='center';
      ctx.fillText(customWinCondition==='retrieve'?'GOAL':'ZONE',sx,sy+r+14);
    }
  }
  // Survive timer HUD
  if(customWinCondition==='survive'&&customSurviveMs>0){
    const sec=Math.ceil(customSurviveMs/1000);
    ctx.textAlign='center';ctx.font='bold 24px "Courier New"';
    ctx.fillStyle=sec<=10?'#ff4400':'#00ccff';ctx.shadowBlur=14;ctx.shadowColor=sec<=10?'#ff2200':'#00aaff';
    ctx.fillText(`SURVIVE  ${sec}s`,canvas.width/2,80);ctx.shadowBlur=0;
  }
  // Collect All progress
  if(customWinCondition==='collectAll'){
    ctx.textAlign='center';ctx.font='bold 14px "Courier New"';
    ctx.fillStyle='#ffdd00';ctx.shadowBlur=8;ctx.shadowColor='#ffaa00';
    ctx.fillText(`KEYS  ${customKeysCollected}/${customKeysTotal}`,canvas.width/2,80);ctx.shadowBlur=0;
  }
  // Retrieve status
  if(customWinCondition==='retrieve'){
    ctx.textAlign='center';ctx.font='bold 14px "Courier New"';
    ctx.fillStyle=customItemHeld?'#00ff88':'#ff8800';ctx.shadowBlur=8;ctx.shadowColor=customItemHeld?'#00ff88':'#ff8800';
    ctx.fillText(customItemHeld?'ITEM HELD - REACH GOAL':'FIND THE ITEM',canvas.width/2,80);ctx.shadowBlur=0;
  }
  ctx.textAlign='left';
}
```

- [ ] **Step 5: Wire `drawCustomObjectives()` into both draw lines**

Find the playing-state draw lines (there are two: one for portal-frozen and one for normal play). They contain calls like `drawPlayer();drawFinishLine(...)`. After `drawPlayer();` (or after `drawFinishLine` if present), add `drawCustomObjectives();` on both lines.

- [ ] **Step 6: Add pickup-based objective collection**

For `collectAll` and `retrieve` win conditions, the player needs to pick up key/item objectives when walking over them. Find the `tickPickups` section or the main tick chain. Add a check in the main playing tick for custom objective collection:

After the `tickCustomWinCondition(dt)` call just added, insert a call to a new function:

```javascript
function tickCustomObjectivePickup(){
  if(gameMode!=='custom')return;
  for(const obj of customObjectives){
    if(obj.collected)continue;
    if(obj.type==='key'&&dist(P.x,P.y,obj.x,obj.y)<40){
      obj.collected=true;customKeysCollected++;
      spawnParts(obj.x,obj.y,'#ffdd00',_pCount(14),3,5,400);SFX.pickup();
      weaponFlash={prefix:'COLLECTED',name:`KEY ${customKeysCollected}/${customKeysTotal}`,ms:1800};
    }
    if(obj.type==='item'&&!customItemHeld&&dist(P.x,P.y,obj.x,obj.y)<40){
      obj.collected=true;customItemHeld=true;
      spawnParts(obj.x,obj.y,'#ff8800',_pCount(14),3,5,400);SFX.pickup();
      weaponFlash={prefix:'COLLECTED',name:'ITEM - RETURN TO GOAL',ms:2200};
    }
  }
}
```

Add `tickCustomObjectivePickup();` right after `tickCustomWinCondition(dt);` in the main tick line.

- [ ] **Step 7: Commit**
```bash
git add index.php && git commit -m "feat: win condition tick logic, objective rendering, objective pickup"
```

---

### Task 4: customResult screen

**Files:**
- Modify: `F:\PATROL WING\.worktrees\level-loader\index.php`

- [ ] **Step 1: Add `drawCustomResult()` function**

Add after `drawCustomObjectives()`:

```javascript
function drawCustomResult(){
  const W=canvas.width,H=canvas.height,cx=W/2;
  ctx.fillStyle='#060c18';ctx.fillRect(0,0,W,H);
  ctx.textAlign='center';
  ctx.font='bold 36px "Courier New"';ctx.fillStyle='#00ff88';ctx.shadowBlur=30;ctx.shadowColor='#00ff88';
  ctx.fillText('MISSION COMPLETE',cx,H*0.25);ctx.shadowBlur=0;
  if(customPack){
    ctx.font='16px "Courier New"';ctx.fillStyle='rgba(100,200,255,0.8)';
    ctx.fillText(customPack.packName||'CUSTOM PACK',cx,H*0.25+40);
    ctx.font='14px "Courier New"';ctx.fillStyle='rgba(150,180,220,0.7)';
    ctx.fillText(`${customPack.levels.length} LEVEL${customPack.levels.length>1?'S':''} CLEARED`,cx,H*0.25+64);
  }
  const elapsed=Math.floor((Date.now()-gameStartTime)/1000);
  const mins=Math.floor(elapsed/60),secs=elapsed%60;
  ctx.font='14px "Courier New"';ctx.fillStyle='rgba(150,180,220,0.7)';
  ctx.fillText(`SCORE  ${String(score).padStart(8,'0')}   TIME  ${mins}:${String(secs).padStart(2,'0')}`,cx,H*0.25+96);
  // Back button
  const bw=240,bh=46,bx=cx-bw/2,by=H*0.6;
  const bhov=mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh;
  ctx.shadowBlur=bhov?24:10;ctx.shadowColor='#00ff88';
  ctx.fillStyle=bhov?'#00ff88':'rgba(0,0,0,0.7)';
  roundRect(ctx,bx,by,bw,bh,8);ctx.fill();
  ctx.strokeStyle='#00ff88';ctx.lineWidth=2;
  roundRect(ctx,bx,by,bw,bh,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 14px "Courier New"';ctx.fillStyle=bhov?'#000':'#00ff88';
  ctx.fillText('BACK TO MENU',cx,by+bh/2+5);
  ctx.textAlign='left';
}
```

- [ ] **Step 2: Wire customResult into render loop**

Find the main render switch. After the last `} else if(gameState===` block and before the final `}`, add:

```javascript
  } else if(gameState==='customResult'){
    drawCustomResult();
```

- [ ] **Step 3: Add customResult click handler**

In the click dispatch section, add before the `gameState==='start'` block:

```javascript
  if(gameState==='customResult'){
    const cx=canvas.width/2,bw=240,bh=46,bx=cx-bw/2,by=canvas.height*0.6;
    if(mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh){
      gameState='customSelect';SFX.select();return;
    }
    return;
  }
```

- [ ] **Step 4: Commit**
```bash
git add index.php && git commit -m "feat: customResult screen — mission complete display"
```

---

### Task 5: customSelect screen + menu wiring

**Files:**
- Modify: `F:\PATROL WING\.worktrees\level-loader\index.php`

- [ ] **Step 1: Add `drawCustomSelect()` function**

Add after `drawCustomResult()`:

```javascript
function drawCustomSelect(){
  const W=canvas.width,H=canvas.height,cx=W/2;
  ctx.fillStyle='#060c18';ctx.fillRect(0,0,W,H);
  ctx.textAlign='center';
  ctx.font='bold 28px "Courier New"';ctx.fillStyle='#00ccff';ctx.shadowBlur=20;ctx.shadowColor='#00aaff';
  ctx.fillText('CUSTOM LEVELS',cx,52);ctx.shadowBlur=0;
  const packs=_loadCustomLevels();
  if(packs.length===0){
    ctx.font='14px "Courier New"';ctx.fillStyle='rgba(100,140,180,0.6)';
    ctx.fillText('No custom levels yet',cx,H/2);
    ctx.fillText('Use the Level Editor to create levels',cx,H/2+24);
  } else {
    const cardW=Math.min(400,W*0.7),cardH=64,cardGap=10;
    const startY=90;
    for(let i=0;i<packs.length;i++){
      const pk=packs[i];
      const y=startY+i*(cardH+cardGap);
      if(y>H-80)break;
      const hov=mouse.x>cx-cardW/2&&mouse.x<cx+cardW/2&&mouse.y>y&&mouse.y<y+cardH;
      ctx.fillStyle=hov?'rgba(0,60,120,0.7)':'rgba(0,30,60,0.5)';
      roundRect(ctx,cx-cardW/2,y,cardW,cardH,8);ctx.fill();
      ctx.strokeStyle=hov?'#00ccff':'rgba(0,100,180,0.4)';ctx.lineWidth=hov?2:1;
      roundRect(ctx,cx-cardW/2,y,cardW,cardH,8);ctx.stroke();
      ctx.textAlign='left';
      ctx.font='bold 14px "Courier New"';ctx.fillStyle=hov?'#ffffff':'rgba(180,220,255,0.9)';
      ctx.fillText(pk.packName||'Unnamed Pack',cx-cardW/2+16,y+24);
      ctx.font='11px "Courier New"';ctx.fillStyle='rgba(100,160,220,0.7)';
      ctx.fillText(`${pk.levels?pk.levels.length:0} level${pk.levels&&pk.levels.length!==1?'s':''}  |  ${pk.author||'Unknown'}`,cx-cardW/2+16,y+44);
      // Delete button
      const delW=50,delH=28,delX=cx+cardW/2-delW-8,delY=y+(cardH-delH)/2;
      const delHov=mouse.x>delX&&mouse.x<delX+delW&&mouse.y>delY&&mouse.y<delY+delH;
      ctx.fillStyle=delHov?'rgba(180,30,10,0.8)':'rgba(80,20,10,0.5)';
      roundRect(ctx,delX,delY,delW,delH,4);ctx.fill();
      ctx.textAlign='center';ctx.font='bold 10px "Courier New"';
      ctx.fillStyle=delHov?'#ffccaa':'rgba(200,80,50,0.8)';
      ctx.fillText('DEL',delX+delW/2,delY+delH/2+4);
    }
  }
  // Back button
  ctx.textAlign='center';
  const bbw=160,bbh=40,bbx=cx-bbw/2,bby=H-70;
  const bhov=mouse.x>bbx&&mouse.x<bbx+bbw&&mouse.y>bby&&mouse.y<bby+bbh;
  ctx.shadowBlur=bhov?18:6;ctx.shadowColor='#00ccff';
  ctx.fillStyle=bhov?'rgba(0,140,200,0.85)':'rgba(0,0,0,0.65)';
  roundRect(ctx,bbx,bby,bbw,bbh,6);ctx.fill();
  ctx.strokeStyle=bhov?'#00eeff':'rgba(0,140,220,0.6)';ctx.lineWidth=1.5;
  roundRect(ctx,bbx,bby,bbw,bbh,6);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 12px "Courier New"';ctx.fillStyle=bhov?'#000':'rgba(100,200,255,0.9)';
  ctx.fillText('BACK',cx,bby+bbh/2+4);
  ctx.textAlign='left';
}
```

- [ ] **Step 2: Wire customSelect into render loop**

After the `customResult` render block, add:

```javascript
  } else if(gameState==='customSelect'){
    drawCustomSelect();
```

- [ ] **Step 3: Add customSelect click handler**

Add in the click dispatch before the `gameState==='start'` block:

```javascript
  if(gameState==='customSelect'){
    const cx=canvas.width/2,W=canvas.width,H=canvas.height;
    const packs=_loadCustomLevels();
    const cardW=Math.min(400,W*0.7),cardH=64,cardGap=10;
    const startY=90;
    for(let i=0;i<packs.length;i++){
      const y=startY+i*(cardH+cardGap);
      if(y>H-80)break;
      // Delete button
      const delW=50,delH=28,delX=cx+cardW/2-delW-8,delY=y+(cardH-delH)/2;
      if(mouse.x>delX&&mouse.x<delX+delW&&mouse.y>delY&&mouse.y<delY+delH){
        packs.splice(i,1);_saveCustomLevels(packs);SFX.select();return;
      }
      // Pack card click
      if(mouse.x>cx-cardW/2&&mouse.x<cx+cardW/2&&mouse.y>y&&mouse.y<y+cardH){
        const pk=packs[i];
        if(pk.levels&&pk.levels.length>0){
          customPack={packName:pk.packName,levels:pk.levels,currentIdx:0};
          loadCustomLevel(pk.levels[0]);
          SFX.confirm();
        }
        return;
      }
    }
    // Back button
    const bbw=160,bbh=40,bbx=cx-bbw/2,bby=H-70;
    if(mouse.x>bbx&&mouse.x<bbx+bbw&&mouse.y>bby&&mouse.y<bby+bbh){
      gameState='start';SFX.select();return;
    }
    return;
  }
```

- [ ] **Step 4: Wire Level Designer menu button**

Find the start screen click handler (around line 7245-7250). The `'Level Designer'` label is already in MENU_ITEMS. Add a click handler alongside the others:

Find the line:
```javascript
        if(item.label==='Setup'){ gameState='setup'; SFX.select(); }
```

Add before it:
```javascript
        if(item.label==='Level Designer'){ gameState='customSelect'; SFX.select(); }
```

- [ ] **Step 5: Handle ESC/Space on customSelect and customResult**

Find the key handler (around line 371). Add handling for ESC on customSelect to go back to start, and Space on customResult to go back:

Find the Space key handler for game states (search for `if(gameState==='intro')`). Near the other state checks, add:

```javascript
    if(gameState==='customSelect'){K['Space']=false;gameState='start';SFX.select();return;}
    if(gameState==='customResult'){K['Space']=false;gameState='customSelect';SFX.select();return;}
```

- [ ] **Step 6: Handle gameover in custom mode**

When the player dies in a custom level, the existing gameover flow should work. But "RETRY" on the gameover screen should reload the current custom level instead of restarting Battle. Find where gameover RETRY is handled. Read the gameover click handler and add a custom mode check:

Find the gameover retry click. It likely calls `startBattle()` or transitions to briefing. Add before it:

```javascript
if(gameMode==='custom'&&customPack){loadCustomLevel(customPack.levels[customPack.currentIdx]);return;}
```

Read the exact gameover click code first.

- [ ] **Step 7: Commit**
```bash
git add index.php && git commit -m "feat: customSelect screen, menu wiring, gameover retry for custom mode"
```

---

## Unresolved Questions

None.
