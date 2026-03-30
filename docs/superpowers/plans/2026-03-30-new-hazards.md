# New Hazards Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add 5 new hazard types (Gravity Vortex, Laser Grid, Acid Pool, EMP Pylon, Ricochet Turret) with tick logic, rendering, level editor integration, and loadCustomLevel support.

**Architecture:** All 5 hazards are stored in the existing `hazards[]` array with distinct `type` strings. `tickHazards()` and `drawHazards()` gain new `else if` branches. A new `hazardProjectiles[]` array handles ricochet turret bullets. `P.weaponDisableMs` is a new player field for EMP pylon. Each hazard gets a sidebar entry in the level editor with a configurable-options toolbar. `loadCustomLevel()` hydrates new hazard types.

**Tech Stack:** Vanilla JS, Canvas 2D, single-file `index.php` (~8500 lines). Work in `F:\PATROL WING\.worktrees\new-hazards\index.php`.

---

### Task 1: Foundation -- P.weaponDisableMs, hazardProjectiles[], resets

**Files:**
- Modify: `F:\PATROL WING\.worktrees\new-hazards\index.php`

- [ ] **Step 1: Add `P.weaponDisableMs` to player object**

Find the player object P (search for `gateKeys:new Set()`). After it, add `weaponDisableMs:0,`.

Find `resetPlayer()` Object.assign. After `gateKeys:new Set(),` add `weaponDisableMs:0,`.

- [ ] **Step 2: Add `hazardProjectiles` array**

Find the module-level array declaration (around line 920, the line with `let particles=[],pickups=[],...,rockets=[],grenades=[],gravityWells=[],faradayCages=[];`). Append `hazardProjectiles=[]` to the end.

- [ ] **Step 3: Add resets for `hazardProjectiles`**

Find every place `grenades.length=0;gravityWells.length=0;faradayCages.length=0;` appears (there should be 6+ reset sites). Append `hazardProjectiles.length=0;` at each.

- [ ] **Step 4: Block weapon fire during EMP disable**

Find `fireWeapon()` (search for `function fireWeapon`). At the very top of the function, after `Music.onShot();`, add:

```javascript
  if(P.weaponDisableMs>0)return;
```

- [ ] **Step 5: Tick `P.weaponDisableMs` in the player tick**

Find `tickPlayer` (search for `function tickPlayer`). Near the top where other player timers are decremented (search for `P.shieldMs` or `P.overchargeMs` decrement), add:

```javascript
  if(P.weaponDisableMs>0)P.weaponDisableMs-=dt*1000;
```

- [ ] **Step 6: Commit**
```bash
cd "F:\PATROL WING\.worktrees\new-hazards" && git add index.php && git commit -m "feat: hazard foundations — P.weaponDisableMs, hazardProjectiles[], resets"
```

---

### Task 2: Gravity Vortex + Acid Pool (tick + draw)

**Files:**
- Modify: `F:\PATROL WING\.worktrees\new-hazards\index.php`

These two are similar: stationary zone effects with no projectiles.

- [ ] **Step 1: Add Gravity Vortex to `tickHazards()`**

Find `tickHazards(dt,now)`. After the `plasma_zone` block (which ends with `}` around line 1166), add:

```javascript
    } else if(h.type==='gravity_vortex'){
      if(P.alive){
        const dp=dist(P.x,P.y,h.x,h.y);
        if(dp<h.radius&&dp>1){
          const str=h.pullStr*(1-dp/h.radius)*dt*60;
          P.vx+=(h.x-P.x)/dp*str;
          P.vy+=(h.y-P.y)/dp*str;
          if(dp<20&&P.iframes<=0&&P.invincMs<=0){
            P.hp-=h.coreDmg*dt*P.damageMult;
            if(settings.screenShake)shake=Math.max(shake,4);
            if(P.hp<=0)P.alive=false;
          }
        }
      }
      for(const e of enemies){
        const de=dist(e.x,e.y,h.x,h.y);
        if(de<h.radius&&de>1){
          const str=h.pullStr*(1-de/h.radius)*dt*60*0.5;
          e.vx+=(h.x-e.x)/de*str;
          e.vy+=(h.y-e.y)/de*str;
        }
      }
    } else if(h.type==='acid_pool'){
      if(P.alive&&P.iframes<=0&&P.invincMs<=0){
        const dp=dist(P.x,P.y,h.x,h.y);
        if(dp<h.radius){
          P.hp-=h.dps*dt*P.damageMult;
          P.vx*=(1-h.slowPct*dt*4);
          P.vy*=(1-h.slowPct*dt*4);
          if(settings.screenShake)shake=Math.max(shake,2);
          if(P.hp<=0)P.alive=false;
        }
      }
```

- [ ] **Step 2: Add Gravity Vortex + Acid Pool to `drawHazards()`**

Find `drawHazards()`. After the last existing hazard draw branch (plasma_zone), add:

```javascript
    } else if(h.type==='gravity_vortex'){
      const sx=h.x-camX,sy=h.y-camY;
      if(sx<-h.radius-10||sx>canvas.width+h.radius+10||sy<-h.radius-10||sy>canvas.height+h.radius+10)continue;
      const t=now,pulse=0.5+0.5*Math.sin(t*3);
      ctx.globalAlpha=0.1+0.05*pulse;
      ctx.beginPath();ctx.arc(sx,sy,h.radius,0,Math.PI*2);
      ctx.strokeStyle='#9944ff';ctx.lineWidth=1;ctx.stroke();ctx.globalAlpha=1;
      ctx.save();ctx.translate(sx,sy);ctx.rotate(t*1.5);
      ctx.beginPath();ctx.ellipse(0,0,h.radius*0.6,h.radius*0.25,0,0,Math.PI*2);
      ctx.strokeStyle=`rgba(150,68,255,${0.3+0.2*pulse})`;ctx.lineWidth=1.5;ctx.stroke();
      ctx.restore();
      const coreR=6+3*Math.sin(t*5);
      const grad=ctx.createRadialGradient(sx,sy,0,sx,sy,coreR);
      grad.addColorStop(0,'rgba(200,100,255,0.9)');grad.addColorStop(1,'rgba(80,0,140,0)');
      ctx.beginPath();ctx.arc(sx,sy,coreR,0,Math.PI*2);ctx.fillStyle=grad;ctx.fill();
    } else if(h.type==='acid_pool'){
      const sx=h.x-camX,sy=h.y-camY;
      if(sx<-h.radius-10||sx>canvas.width+h.radius+10||sy<-h.radius-10||sy>canvas.height+h.radius+10)continue;
      const t=now;
      const grad=ctx.createRadialGradient(sx,sy,0,sx,sy,h.radius);
      grad.addColorStop(0,'rgba(40,180,20,0.45)');grad.addColorStop(0.7,'rgba(30,140,10,0.3)');grad.addColorStop(1,'rgba(20,80,5,0)');
      ctx.beginPath();ctx.arc(sx,sy,h.radius,0,Math.PI*2);ctx.fillStyle=grad;ctx.fill();
      ctx.strokeStyle='rgba(60,200,30,0.3)';ctx.lineWidth=1;ctx.stroke();
      for(let b=0;b<3;b++){
        const ba=t*2+b*2.1,br=h.radius*0.4*(0.5+0.5*Math.sin(ba));
        const bx=sx+Math.cos(ba*1.3)*br,by=sy+Math.sin(ba*0.9)*br;
        const bs=2+Math.sin(ba*3);
        ctx.beginPath();ctx.arc(bx,by,bs,0,Math.PI*2);
        ctx.fillStyle=`rgba(80,220,40,${0.3+0.2*Math.sin(ba*4)})`;ctx.fill();
      }
```

- [ ] **Step 3: Commit**
```bash
git add index.php && git commit -m "feat: gravity vortex + acid pool — tick logic, rendering"
```

---

### Task 3: Laser Grid + EMP Pylon (tick + draw)

**Files:**
- Modify: `F:\PATROL WING\.worktrees\new-hazards\index.php`

- [ ] **Step 1: Add Laser Grid + EMP Pylon to `tickHazards()`**

After the acid_pool block just added, add:

```javascript
    } else if(h.type==='laser_grid'){
      const arcRad=h.sweepArc*Math.PI/180;
      h.sweepAngle=h.sweepAngle||0;
      h.sweepDir=h.sweepDir||1;
      h.sweepAngle+=h.sweepSpd*dt*h.sweepDir;
      if(h.sweepAngle>arcRad/2){h.sweepAngle=arcRad/2;h.sweepDir=-1;}
      if(h.sweepAngle<-arcRad/2){h.sweepAngle=-arcRad/2;h.sweepDir=1;}
      const beamAngle=h.angle+h.sweepAngle;
      const bx=h.x+Math.cos(beamAngle)*h.beamLen,by=h.y+Math.sin(beamAngle)*h.beamLen;
      h._bx=bx;h._by=by;
      h.hitCooldown=h.hitCooldown||0;
      if(h.hitCooldown>0)h.hitCooldown-=dt*1000;
      if(P.alive&&P.iframes<=0&&P.invincMs<=0&&h.hitCooldown<=0){
        if(_pointSegDist2(P.x,P.y,h.x,h.y,bx,by)<12*12){
          if(P.shieldMs>0){P.shieldMs=0;spawnParts(P.x,P.y,'#44aaff',_pCount(14),4,5,400);SFX.shbreak();P.iframes=400;}
          else{P.hp-=h.dmg*P.damageMult;P.iframes=500;if(settings.screenShake)shake=10;SFX.hit();if(P.hp<=0)P.alive=false;Music.onHit();}
          h.hitCooldown=500;
        }
      }
    } else if(h.type==='emp_pylon'){
      h.cooldownMs=h.cooldownMs||0;h.chargeMs=h.chargeMs||0;h.firing=h.firing||false;h.flashMs=h.flashMs||0;
      if(h.flashMs>0)h.flashMs-=dt*1000;
      if(h.cooldownMs>0){h.cooldownMs-=dt*1000;}
      else if(h.chargeMs>0){
        h.chargeMs-=dt*1000;
        if(h.chargeMs<=0){
          h.firing=true;h.flashMs=400;
          if(P.alive&&dist(P.x,P.y,h.x,h.y)<h.pulseRadius){
            P.weaponDisableMs=h.disableMs;
            spawnParts(P.x,P.y,'#4488ff',_pCount(10),3,4,300);
          }
          h.cooldownMs=h.pulseInterval;h.chargeMs=0;
        }
      } else {
        h.chargeMs=1500;
      }
```

- [ ] **Step 2: Add Laser Grid + EMP Pylon to `drawHazards()`**

After the acid_pool draw block, add:

```javascript
    } else if(h.type==='laser_grid'){
      const sx=h.x-camX,sy=h.y-camY;
      if(sx<-h.beamLen-10||sx>canvas.width+h.beamLen+10||sy<-h.beamLen-10||sy>canvas.height+h.beamLen+10)continue;
      const bsx=(h._bx||h.x)-camX,bsy=(h._by||h.y)-camY;
      ctx.strokeStyle='rgba(255,40,40,0.85)';ctx.lineWidth=3;
      ctx.shadowBlur=16;ctx.shadowColor='#ff2200';
      ctx.beginPath();ctx.moveTo(sx,sy);ctx.lineTo(bsx,bsy);ctx.stroke();ctx.shadowBlur=0;
      ctx.fillStyle='#ff4444';ctx.beginPath();ctx.arc(sx,sy,6,0,Math.PI*2);ctx.fill();
      const arcRad=h.sweepArc*Math.PI/180;
      ctx.globalAlpha=0.1;ctx.beginPath();ctx.arc(sx,sy,h.beamLen,h.angle-arcRad/2,h.angle+arcRad/2);ctx.strokeStyle='#ff4444';ctx.lineWidth=1;ctx.stroke();ctx.globalAlpha=1;
    } else if(h.type==='emp_pylon'){
      const sx=h.x-camX,sy=h.y-camY;
      if(sx<-h.pulseRadius-10||sx>canvas.width+h.pulseRadius+10||sy<-h.pulseRadius-10||sy>canvas.height+h.pulseRadius+10)continue;
      const charging=h.chargeMs>0&&h.chargeMs<1500;
      const pulse=charging?0.5+0.5*Math.sin(now*12):0.3+0.3*Math.sin(now*2);
      ctx.fillStyle='rgba(30,40,60,0.9)';ctx.beginPath();ctx.arc(sx,sy,10,0,Math.PI*2);ctx.fill();
      ctx.strokeStyle=charging?`rgba(68,136,255,${pulse})`:'rgba(40,80,180,0.5)';ctx.lineWidth=2;ctx.beginPath();ctx.arc(sx,sy,10,0,Math.PI*2);ctx.stroke();
      ctx.beginPath();ctx.moveTo(sx,sy-14);ctx.lineTo(sx,sy-8);ctx.strokeStyle=charging?'#4488ff':'#334466';ctx.lineWidth=3;ctx.stroke();
      if(charging){
        const chPct=1-(h.chargeMs/1500);
        ctx.globalAlpha=0.15+0.15*chPct;ctx.beginPath();ctx.arc(sx,sy,h.pulseRadius*chPct,0,Math.PI*2);
        ctx.strokeStyle='#4488ff';ctx.lineWidth=2;ctx.stroke();ctx.globalAlpha=1;
      }
      if(h.flashMs>0){
        const fp=h.flashMs/400;
        ctx.globalAlpha=fp*0.4;ctx.beginPath();ctx.arc(sx,sy,h.pulseRadius,0,Math.PI*2);
        ctx.fillStyle='rgba(68,136,255,0.2)';ctx.fill();
        ctx.strokeStyle='#4488ff';ctx.lineWidth=3*fp;ctx.stroke();ctx.globalAlpha=1;
      }
```

- [ ] **Step 3: Add weapon disable HUD indicator**

Find `drawHUD()` or `drawWeaponBar()`. In `drawWeaponBar()`, after the weapon flash text rendering at the bottom, add a weapon-disabled overlay:

```javascript
  if(P.weaponDisableMs>0){
    const sec=Math.ceil(P.weaponDisableMs/1000);
    ctx.textAlign='center';ctx.font='bold 14px "Courier New"';ctx.fillStyle='#4488ff';
    ctx.shadowBlur=12;ctx.shadowColor='#4488ff';
    ctx.fillText(`WEAPONS DISABLED ${sec}s`,canvas.width/2,by-(T?30:48));
    ctx.shadowBlur=0;
  }
```

- [ ] **Step 4: Commit**
```bash
git add index.php && git commit -m "feat: laser grid + EMP pylon — tick, draw, weapon disable HUD"
```

---

### Task 4: Ricochet Turret (tick + draw + projectiles)

**Files:**
- Modify: `F:\PATROL WING\.worktrees\new-hazards\index.php`

- [ ] **Step 1: Add Ricochet Turret to `tickHazards()`**

After the emp_pylon block, add:

```javascript
    } else if(h.type==='ricochet_turret'){
      h.cooldownMs=h.cooldownMs||0;
      if(h.cooldownMs>0){h.cooldownMs-=dt*1000;}
      else{
        h.cooldownMs=h.fireInterval;
        const spd=h.projSpd||6;
        hazardProjectiles.push({x:h.x,y:h.y,vx:Math.cos(h.fireAngle)*spd,vy:Math.sin(h.fireAngle)*spd,bounces:0,maxBounces:h.bounceCount||5,dmg:h.dmg||20,life:4000});
        spawnParts(h.x,h.y,'#ffaa22',_pCount(3),2,3,200);
      }
```

- [ ] **Step 2: Add `tickHazardProjectiles(dt)` function**

Add after `tickHazards()` closes:

```javascript
function tickHazardProjectiles(dt){
  const step=dt*60;
  for(let i=hazardProjectiles.length-1;i>=0;i--){
    const p=hazardProjectiles[i];
    p.life-=dt*1000;
    if(p.life<=0){hazardProjectiles.splice(i,1);continue;}
    p.x+=p.vx*step;p.y+=p.vy*step;
    // Wall bounce
    if(p.x<6){p.x=6;p.vx=Math.abs(p.vx);p.bounces++;}
    else if(p.x>WORLD_W-6){p.x=WORLD_W-6;p.vx=-Math.abs(p.vx);p.bounces++;}
    else if(p.y<6){p.y=6;p.vy=Math.abs(p.vy);p.bounces++;}
    else if(p.y>WORLD_H-6){p.y=WORLD_H-6;p.vy=-Math.abs(p.vy);p.bounces++;}
    // Obstacle bounce
    const proxy={x:p.x,y:p.y,vx:p.vx,vy:p.vy,bSz:5};
    if(reflectRicoVsObs(proxy)){p.x=proxy.x;p.y=proxy.y;p.vx=proxy.vx;p.vy=proxy.vy;p.bounces++;}
    if(p.bounces>=p.maxBounces){hazardProjectiles.splice(i,1);continue;}
    // Player hit
    if(P.alive&&P.iframes<=0&&P.invincMs<=0&&dist2(P.x,P.y,p.x,p.y)<(P.size+5)**2){
      if(P.shieldMs>0){P.shieldMs=0;spawnParts(P.x,P.y,'#44aaff',_pCount(14),4,5,400);SFX.shbreak();P.iframes=400;}
      else{P.hp-=p.dmg*P.damageMult;P.iframes=500;if(settings.screenShake)shake=8;SFX.hit();if(P.hp<=0)P.alive=false;Music.onHit();}
      hazardProjectiles.splice(i,1);continue;
    }
    // Enemy hit
    for(let ei=enemies.length-1;ei>=0;ei--){
      const e=enemies[ei];
      if(dist2(p.x,p.y,e.x,e.y)<(e.size+5)**2){
        e.hp-=p.dmg;spawnParts(p.x,p.y,e.color,_pCount(4),2,3,200);
        if(e.hp<=0){SFX.boom();killEnemy(ei);}
        hazardProjectiles.splice(i,1);break;
      }
    }
  }
}
```

- [ ] **Step 3: Wire `tickHazardProjectiles(dt)` into main loop**

Find where `tickHazards(dt,now)` is called in the main tick line. After it, add `tickHazardProjectiles(dt);`.

- [ ] **Step 4: Add Ricochet Turret draw + `drawHazardProjectiles()`**

In `drawHazards()`, after the emp_pylon draw block, add:

```javascript
    } else if(h.type==='ricochet_turret'){
      const sx=h.x-camX,sy=h.y-camY;
      if(sx<-30||sx>canvas.width+30||sy<-30||sy>canvas.height+30)continue;
      ctx.fillStyle='rgba(80,80,90,0.9)';ctx.beginPath();ctx.arc(sx,sy,8,0,Math.PI*2);ctx.fill();
      ctx.strokeStyle='rgba(150,150,160,0.7)';ctx.lineWidth=1.5;ctx.beginPath();ctx.arc(sx,sy,8,0,Math.PI*2);ctx.stroke();
      const bx=sx+Math.cos(h.fireAngle)*12,by2=sy+Math.sin(h.fireAngle)*12;
      ctx.strokeStyle='#aaaaaa';ctx.lineWidth=3;ctx.beginPath();ctx.moveTo(sx,sy);ctx.lineTo(bx,by2);ctx.stroke();
```

After `drawHazards()`, add:

```javascript
function drawHazardProjectiles(){
  for(const p of hazardProjectiles){
    const sx=p.x-camX,sy=p.y-camY;
    if(sx<-10||sx>canvas.width+10||sy<-10||sy>canvas.height+10)continue;
    ctx.fillStyle='#ffaa22';ctx.shadowBlur=8;ctx.shadowColor='#ff8800';
    ctx.beginPath();ctx.arc(sx,sy,4,0,Math.PI*2);ctx.fill();ctx.shadowBlur=0;
  }
}
```

- [ ] **Step 5: Wire `drawHazardProjectiles()` into both draw lines**

Find both playing-state draw lines. After `drawHazards();`, add `drawHazardProjectiles();`.

- [ ] **Step 6: Commit**
```bash
git add index.php && git commit -m "feat: ricochet turret — fires bouncing projectiles, tick + draw"
```

---

### Task 5: loadCustomLevel support + level editor integration

**Files:**
- Modify: `F:\PATROL WING\.worktrees\new-hazards\index.php`

- [ ] **Step 1: Handle new hazard types in `loadCustomLevel()`**

Find the hazard loading section in `loadCustomLevel()` (search for `h.type==='zap_pylon'`). After the floor_mine line, add:

```javascript
      else if(h.type==='gravity_vortex') hazards.push({type:'gravity_vortex',x:h.x,y:h.y,radius:h.radius||200,pullStr:h.pullStr||1.5,coreDmg:h.coreDmg||30});
      else if(h.type==='laser_grid') hazards.push({type:'laser_grid',x:h.x,y:h.y,angle:h.angle||0,beamLen:h.beamLen||250,sweepSpd:h.sweepSpd||1.0,sweepArc:h.sweepArc||180,dmg:h.dmg||25,sweepAngle:0,sweepDir:1,hitCooldown:0});
      else if(h.type==='acid_pool') hazards.push({type:'acid_pool',x:h.x,y:h.y,radius:h.radius||80,dps:h.dps||15,slowPct:h.slowPct||0.4});
      else if(h.type==='emp_pylon') hazards.push({type:'emp_pylon',x:h.x,y:h.y,pulseInterval:h.pulseInterval||12000,disableMs:h.disableMs||3000,pulseRadius:h.pulseRadius||250,cooldownMs:0,chargeMs:0,firing:false,flashMs:0});
      else if(h.type==='ricochet_turret') hazards.push({type:'ricochet_turret',x:h.x,y:h.y,fireAngle:h.fireAngle||0,fireInterval:h.fireInterval||3000,projSpd:h.projSpd||6,bounceCount:h.bounceCount||5,dmg:h.dmg||20,cooldownMs:0});
```

Also add `hazardProjectiles.length=0;` to the loadCustomLevel reset section (find the `hazards.length=0` line and add after it).

- [ ] **Step 2: Add 5 new hazard items to editor sidebar**

Find `_getEditorCategories()`. In the `hazard` category, after the existing floor_mine entry, add:

```javascript
      {tool:'gravity_vortex',label:'Gravity Vortex',cat:'hazard',subtype:'gravity_vortex',radius:200,pullStr:1.5,coreDmg:30},
      {tool:'laser_grid',label:'Laser Grid',cat:'hazard',subtype:'laser_grid',angle:0,beamLen:250,sweepSpd:1.0,sweepArc:180,dmg:25},
      {tool:'acid_pool',label:'Acid Pool',cat:'hazard',subtype:'acid_pool',radius:80,dps:15,slowPct:0.4},
      {tool:'emp_pylon',label:'EMP Pylon',cat:'hazard',subtype:'emp_pylon',pulseInterval:12000,disableMs:3000,pulseRadius:250},
      {tool:'ricochet_turret',label:'Ricochet Turret',cat:'hazard',subtype:'ricochet_turret',fireAngle:0,fireInterval:3000,projSpd:6,bounceCount:5,dmg:20},
```

- [ ] **Step 3: Add sidebar icons for new hazards**

Find `_drawEditorSidebar()`, the hazard icon drawing section (where `item.cat==='hazard'` draws a colored dot). Make it type-specific:

Replace the hazard icon drawing with:

```javascript
        } else if(item.cat==='hazard'){
          const hcols={zap_pylon:'#ffdd00',floor_mine:'#ff2200',gravity_vortex:'#9944ff',laser_grid:'#ff4444',acid_pool:'#44cc22',emp_pylon:'#4488ff',ricochet_turret:'#aaaaaa'};
          ctx.fillStyle=hcols[item.subtype]||'#ffdd00';
          ctx.beginPath();ctx.arc(14,cy+11,5,0,Math.PI*2);ctx.fill();
```

- [ ] **Step 4: Add hazard rendering in editor grid**

Find `drawLevelEditor()` placed items loop. In the hazard rendering section, currently there's zap_pylon and floor_mine drawing. After the floor_mine else block, add new hazard previews:

```javascript
      else if(item.subtype==='gravity_vortex'){
        ctx.strokeStyle='rgba(150,68,255,0.5)';ctx.lineWidth=1;ctx.beginPath();ctx.arc(sx,sy,item.radius||200,0,Math.PI*2);ctx.stroke();
        ctx.fillStyle='#9944ff';ctx.beginPath();ctx.arc(sx,sy,8,0,Math.PI*2);ctx.fill();
        ctx.font='8px "Courier New"';ctx.fillStyle='#cc88ff';ctx.textAlign='center';ctx.fillText('VORTEX',sx,sy+16);
      } else if(item.subtype==='laser_grid'){
        const bx=sx+Math.cos(item.angle||0)*(item.beamLen||250),by2=sy+Math.sin(item.angle||0)*(item.beamLen||250);
        ctx.strokeStyle='rgba(255,60,60,0.6)';ctx.lineWidth=2;ctx.beginPath();ctx.moveTo(sx,sy);ctx.lineTo(bx,by2);ctx.stroke();
        ctx.fillStyle='#ff4444';ctx.beginPath();ctx.arc(sx,sy,5,0,Math.PI*2);ctx.fill();
        ctx.font='8px "Courier New"';ctx.fillStyle='#ff6666';ctx.textAlign='center';ctx.fillText('LASER',sx,sy+14);
      } else if(item.subtype==='acid_pool'){
        ctx.fillStyle='rgba(40,180,20,0.3)';ctx.beginPath();ctx.arc(sx,sy,item.radius||80,0,Math.PI*2);ctx.fill();
        ctx.strokeStyle='rgba(60,200,30,0.5)';ctx.lineWidth=1;ctx.stroke();
        ctx.font='8px "Courier New"';ctx.fillStyle='#44cc22';ctx.textAlign='center';ctx.fillText('ACID',sx,sy+4);
      } else if(item.subtype==='emp_pylon'){
        ctx.fillStyle='rgba(30,40,60,0.9)';ctx.beginPath();ctx.arc(sx,sy,8,0,Math.PI*2);ctx.fill();
        ctx.strokeStyle='rgba(68,136,255,0.5)';ctx.lineWidth=1;ctx.beginPath();ctx.arc(sx,sy,item.pulseRadius||250,0,Math.PI*2);ctx.stroke();
        ctx.font='8px "Courier New"';ctx.fillStyle='#4488ff';ctx.textAlign='center';ctx.fillText('EMP',sx,sy+16);
      } else if(item.subtype==='ricochet_turret'){
        ctx.fillStyle='rgba(80,80,90,0.9)';ctx.beginPath();ctx.arc(sx,sy,7,0,Math.PI*2);ctx.fill();
        const bx=sx+Math.cos(item.fireAngle||0)*14,by2=sy+Math.sin(item.fireAngle||0)*14;
        ctx.strokeStyle='#aaa';ctx.lineWidth=2;ctx.beginPath();ctx.moveTo(sx,sy);ctx.lineTo(bx,by2);ctx.stroke();
        ctx.font='8px "Courier New"';ctx.fillStyle='#aaa';ctx.textAlign='center';ctx.fillText('TURRET',sx,sy+16);
      }
```

- [ ] **Step 5: Handle new hazards in `_editorSave()`**

Find `_editorSave()`. The hazard saving section currently saves `type`, `x`, `y`, `angle`, `gap`. Replace with a more generic save that preserves all hazard fields:

Read the current hazard save code. It probably does:
```javascript
    } else if(item.cat==='hazard'){
      lv.hazards.push({type:item.subtype,x:item.x,y:item.y,angle:item.angle||0,gap:item.gap||120});
    }
```

Replace with:
```javascript
    } else if(item.cat==='hazard'){
      const hz={type:item.subtype,x:item.x,y:item.y};
      if(item.subtype==='zap_pylon'){hz.angle=item.angle||0;hz.gap=item.gap||120;}
      else if(item.subtype==='gravity_vortex'){hz.radius=item.radius;hz.pullStr=item.pullStr;hz.coreDmg=item.coreDmg;}
      else if(item.subtype==='laser_grid'){hz.angle=item.angle;hz.beamLen=item.beamLen;hz.sweepSpd=item.sweepSpd;hz.sweepArc=item.sweepArc;hz.dmg=item.dmg;}
      else if(item.subtype==='acid_pool'){hz.radius=item.radius;hz.dps=item.dps;hz.slowPct=item.slowPct;}
      else if(item.subtype==='emp_pylon'){hz.pulseInterval=item.pulseInterval;hz.disableMs=item.disableMs;hz.pulseRadius=item.pulseRadius;}
      else if(item.subtype==='ricochet_turret'){hz.fireAngle=item.fireAngle;hz.fireInterval=item.fireInterval;hz.projSpd=item.projSpd;hz.bounceCount=item.bounceCount;hz.dmg=item.dmg;}
      lv.hazards.push(hz);
    }
```

- [ ] **Step 6: Handle new hazards in `_loadLevelIntoEditor()`**

Find `_loadLevelIntoEditor()`. The hazard loading section currently creates items with `angle` and `gap`. Make it preserve all fields:

Read the current code. Replace the hazard push with:
```javascript
    if(lv.hazards) for(const h of lv.hazards){
      const item={cat:'hazard',subtype:h.type,x:h.x,y:h.y};
      Object.keys(h).forEach(k=>{if(k!=='type'&&k!=='x'&&k!=='y')item[k]=h[k];});
      editorPlacedItems.push(item);
    }
```

- [ ] **Step 7: Commit**
```bash
git add index.php && git commit -m "feat: loadCustomLevel + editor integration for all 5 new hazards"
```

---

## Unresolved Questions

None.
