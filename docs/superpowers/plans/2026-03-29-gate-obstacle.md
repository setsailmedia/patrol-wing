# Gate Obstacle Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a new "gate" obstacle type that blocks passage when closed and rotates open on a hinge, with 3 unlock methods (guard, key, time).

**Architecture:** Gates are stored in the existing `obstacles[]` array with `type:'gate'`. Collision functions (`circleVsObs`, `pushOutObs`, `reflectRicoVsObs`) gain gate cases using rotated-rect math. A new `tickGates(dt)` function handles unlock logic and animation. `drawObstacles()` gains a gate rendering branch. The level editor toolbox gets 3 new gate items with a properties toolbar. `loadCustomLevel()` hydrates gate runtime fields.

**Tech Stack:** Vanilla JS, Canvas 2D, single-file `index.php` (~8200 lines). Work in `F:\PATROL WING\.worktrees\gates\index.php`.

---

### Task 1: Gate collision math helpers

**Files:**
- Modify: `F:\PATROL WING\.worktrees\gates\index.php`

Gates are rotated rectangles. We need a helper that computes the gate's current collision rect (position + angle) and a rotated-rect-vs-circle test.

- [ ] **Step 1: Add `gateRect(g)` helper**

Add after `reflectRicoVsObs()` (around line 1014):

```javascript
// Returns the gate's current collision rect as {cx,cy,hw,hh,angle} (center, half-dims, rotation)
function gateRect(g){
  const angle0=g.orient==='h'?0:Math.PI/2;
  const openDir=(g.hinge==='left'||g.hinge==='top')?1:-1;
  const angle=angle0+g.openPct*(Math.PI/2)*openDir;
  // Hinge is at g.x,g.y. Center of rect is len/2 along the angle from hinge
  const cx=g.x+Math.cos(angle)*(g.len/2);
  const cy=g.y+Math.sin(angle)*(g.len/2);
  return {cx,cy,hw:g.len/2,hh:g.w/2,angle};
}
// Rotated rect vs circle collision test
function circleVsRotRect(px,py,pr,rect){
  const cos=Math.cos(-rect.angle),sin=Math.sin(-rect.angle);
  const dx=px-rect.cx,dy=py-rect.cy;
  const lx=dx*cos-dy*sin,ly=dx*sin+dy*cos;
  const nx=Math.max(-rect.hw,Math.min(rect.hw,lx));
  const ny=Math.max(-rect.hh,Math.min(rect.hh,ly));
  const ddx=lx-nx,ddy=ly-ny;
  return ddx*ddx+ddy*ddy<pr*pr;
}
// Push circle out of rotated rect, returns {pushed,nx,ny} in world space
function pushOutRotRect(px,py,pr,rect){
  const cos=Math.cos(-rect.angle),sin=Math.sin(-rect.angle);
  const dx=px-rect.cx,dy=py-rect.cy;
  const lx=dx*cos-dy*sin,ly=dx*sin+dy*cos;
  const nx=Math.max(-rect.hw,Math.min(rect.hw,lx));
  const ny=Math.max(-rect.hh,Math.min(rect.hh,ly));
  const ddx=lx-nx,ddy=ly-ny;
  const d2=ddx*ddx+ddy*ddy;
  if(d2>=pr*pr)return{pushed:false,x:px,y:py,wnx:0,wny:0};
  const d=Math.sqrt(d2)||1;
  const pen=pr-d;
  const lnx=ddx/d,lny=ddy/d;
  const nlx=lx+lnx*pen,nly=ly+lny*pen;
  const cosR=Math.cos(rect.angle),sinR=Math.sin(rect.angle);
  return{pushed:true,x:rect.cx+nlx*cosR-nly*sinR,y:rect.cy+nlx*sinR+nly*cosR,wnx:lnx*cosR-lny*sinR,wny:lnx*sinR+lny*cosR};
}
```

- [ ] **Step 2: Integrate gates into `circleVsObs()`**

Read `circleVsObs()` (line 975). Currently it checks pillars and walls (else). Add a gate check. Replace:

```javascript
function circleVsObs(cx,cy,cr){
  for(const o of obstacles){
    if(o.type==='pillar'){if(dist2(cx,cy,o.x,o.y)<(cr+o.r)**2)return true;}
    else{const nx=clamp(cx,o.x,o.x+o.w),ny=clamp(cy,o.y,o.y+o.h);if(dist2(cx,cy,nx,ny)<cr*cr)return true;}
  }return false;
}
```

With:

```javascript
function circleVsObs(cx,cy,cr){
  for(const o of obstacles){
    if(o.type==='pillar'){if(dist2(cx,cy,o.x,o.y)<(cr+o.r)**2)return true;}
    else if(o.type==='gate'){if(circleVsRotRect(cx,cy,cr,gateRect(o)))return true;}
    else{const nx=clamp(cx,o.x,o.x+o.w),ny=clamp(cy,o.y,o.y+o.h);if(dist2(cx,cy,nx,ny)<cr*cr)return true;}
  }return false;
}
```

- [ ] **Step 3: Integrate gates into `pushOutObs()`**

Read `pushOutObs()` (line 981). Add a gate case between pillar and wall. The gate push-out uses `pushOutRotRect`:

After the pillar case and before the wall `else` case, add:

```javascript
    else if(o.type==='gate'){const r2=pushOutRotRect(obj.x,obj.y,r,gateRect(o));if(r2.pushed){obj.x=r2.x;obj.y=r2.y;const dot=obj.vx*r2.wnx+obj.vy*r2.wny;if(dot<0){obj.vx-=dot*r2.wnx;obj.vy-=dot*r2.wny;}}}
```

- [ ] **Step 4: Integrate gates into `reflectRicoVsObs()`**

Read `reflectRicoVsObs()` (line 988). Add a gate case. After the pillar case and before `} else {`, add:

```javascript
    else if(o.type==='gate'){
      const gr=gateRect(o);
      const r2=pushOutRotRect(b.x,b.y,r,gr);
      if(r2.pushed){b.x=r2.x;b.y=r2.y;const dot=b.vx*r2.wnx+b.vy*r2.wny;if(dot<0){b.vx-=2*dot*r2.wnx;b.vy-=2*dot*r2.wny;}bounced=true;}
    }
```

- [ ] **Step 5: Commit**
```bash
cd "F:\PATROL WING\.worktrees\gates" && git add index.php && git commit -m "feat: gate collision helpers — gateRect, circleVsRotRect, pushOutRotRect, integrated into obs functions"
```

---

### Task 2: Gate tick logic + P.gateKeys + rendering

**Files:**
- Modify: `F:\PATROL WING\.worktrees\gates\index.php`

- [ ] **Step 1: Add `P.gateKeys` to player object**

Find the player object P (around line 2100). Find the line with `nukeKeys:new Set(),`. After `nukeKeys:new Set(),` on the same line or nearby, add `gateKeys:new Set(),`.

Also add it to `resetPlayer()` in the Object.assign block: after `nukeKeys:new Set(),` add `gateKeys:new Set(),`.

- [ ] **Step 2: Add `tickGates(dt)` function**

Add after `tickHazards` or near the other tick functions:

```javascript
function tickGates(dt){
  for(const o of obstacles){
    if(o.type!=='gate')continue;
    // Unlock logic
    if(!o.open){
      if(o.unlockType==='guard'){
        const refs=o.unlockParams.guardRefs||[];
        const allDead=refs.length>0&&refs.every(e=>!e||e.hp<=0);
        if(allDead){o.open=true;}
        else{
          const rad=o.unlockParams.radius||200;
          const gcx=o.x+(o.orient==='h'?o.len/2:0),gcy=o.y+(o.orient==='v'?o.len/2:0);
          const allAway=refs.filter(e=>e&&e.hp>0).every(e=>dist(e.x,e.y,gcx,gcy)>rad);
          o.tempOpen=allAway;
        }
      } else if(o.unlockType==='key'){
        const gcx=o.x+(o.orient==='h'?o.len/2:0),gcy=o.y+(o.orient==='v'?o.len/2:0);
        if(P.gateKeys.has(o.unlockParams.keyId)&&dist(P.x,P.y,gcx,gcy)<80){o.open=true;}
      } else if(o.unlockType==='time'){
        if(o.unlockParams.remaining===undefined) o.unlockParams.remaining=o.unlockParams.seconds||30;
        o.unlockParams.remaining-=dt;
        if(o.unlockParams.remaining<=0){o.open=true;o.unlockParams.remaining=0;}
      }
    }
    // Animation
    const target=(o.open||o.tempOpen)?1:0;
    if(o.openPct<target) o.openPct=Math.min(target,o.openPct+dt*2);
    else if(o.openPct>target) o.openPct=Math.max(target,o.openPct-dt*2);
  }
}
```

- [ ] **Step 3: Wire `tickGates(dt)` into main loop**

Find the main playing tick line. Add `tickGates(dt);` after `tickHazards(dt,now);` or nearby in the tick chain.

- [ ] **Step 4: Add gate rendering to `drawObstacles()`**

Read `drawObstacles()` (line 1223). After the wall else-block and before the closing `}` of the for loop, add an `else if(o.type==='gate')` branch:

```javascript
    else if(o.type==='gate'){
      const gr=gateRect(o);
      const sx=gr.cx-camX,sy=gr.cy-camY;
      if(sx+gr.hw<-10||sx-gr.hw>canvas.width+10||sy+gr.hh<-10||sy-gr.hh>canvas.height+10)continue;
      ctx.save();ctx.translate(sx,sy);ctx.rotate(gr.angle);
      // Gate body
      const alpha=o.openPct>0.95?0.5:0.95;
      ctx.globalAlpha=alpha;
      ctx.fillStyle='#334455';ctx.fillRect(-gr.hw,-gr.hh,gr.hw*2,gr.hh*2);
      // Accent stripe
      const accentCol=o.unlockType==='guard'?'#ff4444':o.unlockType==='key'?'#ffdd00':'#00ccff';
      ctx.fillStyle=accentCol;ctx.fillRect(-gr.hw,-gr.hh,gr.hw*2,3);
      ctx.strokeStyle=accentCol;ctx.lineWidth=1.5;ctx.strokeRect(-gr.hw,-gr.hh,gr.hw*2,gr.hh*2);
      // Status indicator
      ctx.textAlign='center';ctx.font='bold 10px "Courier New"';ctx.fillStyle='#ffffff';
      if(o.unlockType==='guard'){
        const alive=(o.unlockParams.guardRefs||[]).filter(e=>e&&e.hp>0).length;
        ctx.fillText(alive>0?`${alive}G`:'OPEN',0,4);
      } else if(o.unlockType==='key'){
        ctx.fillText(o.open?'OPEN':'LOCKED',0,4);
      } else if(o.unlockType==='time'){
        const sec=Math.ceil(o.unlockParams.remaining||0);
        ctx.fillText(o.open?'OPEN':`${sec}s`,0,4);
      }
      ctx.globalAlpha=1;ctx.restore();
      // Hinge rivet
      const hx=o.x-camX,hy=o.y-camY;
      ctx.fillStyle=accentCol;ctx.beginPath();ctx.arc(hx,hy,4,0,Math.PI*2);ctx.fill();
    }
```

- [ ] **Step 5: Commit**
```bash
git add index.php && git commit -m "feat: tickGates + gate rendering — unlock logic, animation, visual indicators"
```

---

### Task 3: Gate loading in loadCustomLevel + gate key pickups

**Files:**
- Modify: `F:\PATROL WING\.worktrees\gates\index.php`

- [ ] **Step 1: Handle gates in `loadCustomLevel()` obstacle loading**

Find `loadCustomLevel()` (search for `function loadCustomLevel`). In the obstacles loading section, there's code that pushes pillars and walls. Add a gate case:

Read the exact obstacle loading code. After the wall push, add:

```javascript
      else if(o.type==='gate') obstacles.push({
        type:'gate',x:o.x,y:o.y,len:120,w:26,
        orient:o.orient||'h',hinge:o.hinge||'left',
        unlockType:o.unlockType||'guard',
        unlockParams:{...o.unlockParams},
        open:false,openPct:0,tempOpen:false
      });
```

- [ ] **Step 2: Match guard gates to spawned enemies after enemy loading**

After the enemies loading loop in `loadCustomLevel()`, add guard-gate matching:

```javascript
  // Match guard gates to nearest enemies
  for(const o of obstacles){
    if(o.type==='gate'&&o.unlockType==='guard'&&o.unlockParams.guardPositions){
      o.unlockParams.guardRefs=o.unlockParams.guardPositions.map(pos=>{
        let best=null,bestD=Infinity;
        for(const e of enemies){const d=dist(e.x,e.y,pos[0],pos[1]);if(d<bestD){bestD=d;best=e;}}
        return bestD<100?best:null;
      }).filter(Boolean);
    }
    if(o.type==='gate'&&o.unlockType==='time'){
      o.unlockParams.remaining=o.unlockParams.seconds||30;
    }
  }
```

- [ ] **Step 3: Add `P.gateKeys=new Set()` reset in `loadCustomLevel()`**

After `resetPlayer();` in loadCustomLevel, add:
```javascript
  P.gateKeys=new Set();
```

- [ ] **Step 4: Add gate key pickup to `tickCustomObjectivePickup()`**

Find `tickCustomObjectivePickup()`. After the existing `key` and `item` pickup checks, add a check for gate keys. Gate keys are stored as objectives with `type:'gate_key'` and a `keyId` field:

```javascript
    if(obj.type==='gate_key'&&!obj.collected&&dist(P.x,P.y,obj.x,obj.y)<40){
      obj.collected=true;P.gateKeys.add(obj.keyId);
      spawnParts(obj.x,obj.y,'#ffdd00',_pCount(14),3,5,400);SFX.pickup();
      weaponFlash={prefix:'COLLECTED',name:'GATE KEY',ms:1800};
    }
```

- [ ] **Step 5: Add gate key rendering to `drawCustomObjectives()`**

In `drawCustomObjectives()`, add a `gate_key` rendering case alongside the existing key/item/goal cases:

```javascript
    } else if(obj.type==='gate_key'&&!obj.collected){
      const pulse=0.6+0.4*Math.sin(t*4+obj.x);
      ctx.save();ctx.translate(sx,sy);
      ctx.shadowBlur=14*pulse;ctx.shadowColor='#ffdd00';
      ctx.font='bold 16px "Courier New"';ctx.fillStyle=`rgba(255,220,0,${0.8+0.2*pulse})`;
      ctx.textAlign='center';ctx.fillText('\u{1F512}',0,6);
      ctx.shadowBlur=0;ctx.restore();
    }
```

- [ ] **Step 6: Commit**
```bash
git add index.php && git commit -m "feat: gate loading in loadCustomLevel, guard matching, gate key pickups"
```

---

### Task 4: Editor integration -- gate tools + properties toolbar

**Files:**
- Modify: `F:\PATROL WING\.worktrees\gates\index.php`

- [ ] **Step 1: Add gate items to editor sidebar categories**

Find `_getEditorCategories()`. In the `obstacle` category items array, add 3 gate entries after the wall entries:

```javascript
      {tool:'gate_guard',label:'Gate (Guard)',cat:'obstacle',subtype:'gate',unlockType:'guard'},
      {tool:'gate_key',label:'Gate (Key)',cat:'obstacle',subtype:'gate',unlockType:'key'},
      {tool:'gate_time',label:'Gate (Time)',cat:'obstacle',subtype:'gate',unlockType:'time'},
```

- [ ] **Step 2: Handle gate placement in editor click handler**

Find the editor grid click handler where items are placed (the section that does `editorPlacedItems.push({...toolDef,x:gx,y:gy})`). Before that generic push, add a gate-specific handler:

```javascript
      if(toolDef.subtype==='gate'){
        const gate={cat:'obstacle',subtype:'gate',x:gx,y:gy,orient:'h',hinge:'left',unlockType:toolDef.unlockType,unlockParams:{}};
        if(toolDef.unlockType==='guard') gate.unlockParams={guardPositions:[],radius:200};
        else if(toolDef.unlockType==='key') gate.unlockParams={keyId:'gate_key_'+Date.now()};
        else if(toolDef.unlockType==='time') gate.unlockParams={seconds:30};
        editorPlacedItems.push(gate);
        editorDirty=true;SFX.select();
        // Auto-place matching gate key for key-type gates
        if(toolDef.unlockType==='key'){
          editorPlacedItems.push({cat:'objective',subtype:'gate_key',x:gx+200,y:gy,keyId:gate.unlockParams.keyId});
        }
        return;
      }
```

- [ ] **Step 3: Add gate rendering in editor grid**

Find `drawLevelEditor()` where placed items are rendered. In the obstacle rendering section (where `item.cat==='obstacle'`), add a gate case:

```javascript
      if(item.subtype==='gate'){
        const accentCol=item.unlockType==='guard'?'#ff4444':item.unlockType==='key'?'#ffdd00':'#00ccff';
        const len=120,w=26;
        if(item.orient==='h'){ctx.fillStyle='#334455';ctx.fillRect(sx,sy-w/2,len,w);ctx.strokeStyle=accentCol;ctx.lineWidth=2;ctx.strokeRect(sx,sy-w/2,len,w);}
        else{ctx.fillStyle='#334455';ctx.fillRect(sx-w/2,sy,w,len);ctx.strokeStyle=accentCol;ctx.lineWidth=2;ctx.strokeRect(sx-w/2,sy,w,len);}
        ctx.fillStyle=accentCol;ctx.beginPath();ctx.arc(sx,sy,4,0,Math.PI*2);ctx.fill();
        ctx.font='8px "Courier New"';ctx.fillStyle='#fff';ctx.textAlign='center';
        ctx.fillText(item.unlockType.toUpperCase(),sx+(item.orient==='h'?60:0),sy+(item.orient==='v'?60:0)+3);
      } else if(item.subtype==='pillar'){
```

Make sure this goes BEFORE the existing pillar check.

- [ ] **Step 4: Add gate properties toolbar**

Add a module-level variable: `let editorSelectedGate=-1;`

When a gate is clicked in the editor (during the drag-start detection), also set `editorSelectedGate=i`. When clicking elsewhere, clear it to -1.

In `drawLevelEditor()`, after the main grid rendering but before the sidebar, add toolbar rendering:

```javascript
  // Gate properties toolbar
  if(editorSelectedGate>=0&&editorSelectedGate<editorPlacedItems.length){
    const gi=editorPlacedItems[editorSelectedGate];
    if(gi.subtype==='gate'){
      const gsx=gi.x-editorCamX+sideW,gsy=gi.y-editorCamY;
      const tbW=160,tbH=gi.unlockType==='guard'?110:70,tbX=gsx+20,tbY=gsy-tbH-10;
      ctx.fillStyle='rgba(0,20,50,0.9)';roundRect(ctx,tbX,tbY,tbW,tbH,6);ctx.fill();
      ctx.strokeStyle='#00ccff';ctx.lineWidth=1;roundRect(ctx,tbX,tbY,tbW,tbH,6);ctx.stroke();
      ctx.textAlign='center';ctx.font='bold 9px "Courier New"';
      // Orient toggle
      const oX=tbX+4,oY=tbY+4,oW=74,oH=22;
      const oHov=mouse.x>oX&&mouse.x<oX+oW&&mouse.y>oY&&mouse.y<oY+oH;
      ctx.fillStyle=oHov?'rgba(0,100,180,0.6)':'rgba(0,40,80,0.4)';roundRect(ctx,oX,oY,oW,oH,3);ctx.fill();
      ctx.fillStyle='#00ccff';ctx.fillText(gi.orient==='h'?'HORIZ':'VERT',oX+oW/2,oY+15);
      // Hinge toggle
      const hX=tbX+82,hY=tbY+4,hW=74,hH=22;
      const hHov=mouse.x>hX&&mouse.x<hX+hW&&mouse.y>hY&&mouse.y<hY+hH;
      ctx.fillStyle=hHov?'rgba(0,100,180,0.6)':'rgba(0,40,80,0.4)';roundRect(ctx,hX,hY,hW,hH,3);ctx.fill();
      ctx.fillStyle='#00ccff';ctx.fillText('HINGE:'+gi.hinge.toUpperCase(),hX+hW/2,hY+15);
      // Guard-specific
      if(gi.unlockType==='guard'){
        const aX=tbX+4,aY=tbY+30,aW=148,aH=22;
        const aHov=mouse.x>aX&&mouse.x<aX+aW&&mouse.y>aY&&mouse.y<aY+aH;
        ctx.fillStyle=aHov?'rgba(0,80,40,0.6)':'rgba(0,40,20,0.4)';roundRect(ctx,aX,aY,aW,aH,3);ctx.fill();
        ctx.fillStyle='#00ff88';ctx.fillText('ASSIGN GUARD',aX+aW/2,aY+15);
        const cX=tbX+4,cY=tbY+56,cW=148,cH=22;
        const cHov=mouse.x>cX&&mouse.x<cX+cW&&mouse.y>cY&&mouse.y<cY+cH;
        ctx.fillStyle=cHov?'rgba(80,20,10,0.6)':'rgba(40,10,5,0.4)';roundRect(ctx,cX,cY,cW,cH,3);ctx.fill();
        ctx.fillStyle='#ff5544';ctx.fillText('CLEAR GUARDS',cX+cW/2,cY+15);
        const guards=gi.unlockParams.guardPositions||[];
        ctx.font='8px "Courier New"';ctx.fillStyle='rgba(150,200,255,0.7)';
        ctx.fillText(`${guards.length} guard${guards.length!==1?'s':''} assigned`,tbX+tbW/2,tbY+tbH-6);
        // Draw assignment lines
        for(const gp of guards){
          const gpx=gp[0]-editorCamX+sideW,gpy=gp[1]-editorCamY;
          ctx.strokeStyle='rgba(0,220,255,0.4)';ctx.lineWidth=1;ctx.setLineDash([4,4]);
          ctx.beginPath();ctx.moveTo(gsx,gsy);ctx.lineTo(gpx,gpy);ctx.stroke();ctx.setLineDash([]);
        }
      }
      if(gi.unlockType==='time'){
        ctx.font='9px "Courier New"';ctx.fillStyle='rgba(150,200,255,0.7)';
        ctx.fillText(`${gi.unlockParams.seconds||30}s countdown`,tbX+tbW/2,tbY+tbH-6);
      }
    }
  }
```

- [ ] **Step 5: Add gate toolbar click handler**

In the editor click handler, after the top-right buttons check and before the sidebar check, add toolbar click handling when a gate is selected:

```javascript
    // Gate toolbar clicks
    if(editorSelectedGate>=0&&editorSelectedGate<editorPlacedItems.length){
      const gi=editorPlacedItems[editorSelectedGate];
      if(gi.subtype==='gate'){
        const gsx=gi.x-editorCamX+sideW,gsy=gi.y-editorCamY;
        const tbH=gi.unlockType==='guard'?110:70,tbX=gsx+20,tbY=gsy-tbH-10;
        // Orient toggle
        if(mouse.x>tbX+4&&mouse.x<tbX+78&&mouse.y>tbY+4&&mouse.y<tbY+26){
          gi.orient=gi.orient==='h'?'v':'h';
          gi.hinge=gi.orient==='h'?'left':'top';
          editorDirty=true;SFX.select();return;
        }
        // Hinge toggle
        if(mouse.x>tbX+82&&mouse.x<tbX+156&&mouse.y>tbY+4&&mouse.y<tbY+26){
          if(gi.orient==='h') gi.hinge=gi.hinge==='left'?'right':'left';
          else gi.hinge=gi.hinge==='top'?'bottom':'top';
          editorDirty=true;SFX.select();return;
        }
        if(gi.unlockType==='guard'){
          // Assign guard button
          if(mouse.x>tbX+4&&mouse.x<tbX+152&&mouse.y>tbY+30&&mouse.y<tbY+52){
            editorTool='_assignGuard';SFX.select();return;
          }
          // Clear guards
          if(mouse.x>tbX+4&&mouse.x<tbX+152&&mouse.y>tbY+56&&mouse.y<tbY+78){
            gi.unlockParams.guardPositions=[];editorDirty=true;SFX.select();return;
          }
        }
      }
    }
```

And in the grid click section, add guard assignment mode:

```javascript
      // Guard assignment mode
      if(editorTool==='_assignGuard'&&editorSelectedGate>=0){
        const gate=editorPlacedItems[editorSelectedGate];
        // Find nearest enemy item at click position
        for(const item of editorPlacedItems){
          if(item.cat==='enemy'&&dist(item.x,item.y,gx,gy)<30){
            if(!gate.unlockParams.guardPositions) gate.unlockParams.guardPositions=[];
            gate.unlockParams.guardPositions.push([item.x,item.y]);
            editorDirty=true;SFX.select();editorTool='';return;
          }
        }
        editorTool='';return;
      }
```

- [ ] **Step 6: Add gate scroll-wheel for time gates**

In the wheel event listener, add time gate adjustment:

```javascript
  if(gameState==='levelEditor'&&editorSelectedGate>=0){
    const gi=editorPlacedItems[editorSelectedGate];
    if(gi&&gi.subtype==='gate'&&gi.unlockType==='time'){
      gi.unlockParams.seconds=clamp((gi.unlockParams.seconds||30)+(e.deltaY>0?-5:5),10,120);
      editorDirty=true;e.preventDefault();return;
    }
  }
```

- [ ] **Step 7: Handle gates in `_editorSave()`**

Find `_editorSave()`. In the obstacle bundling section, gates need special handling. After the wall push, add:

```javascript
    if(item.cat==='obstacle'&&item.subtype==='gate'){
      lv.obstacles.push({type:'gate',x:item.x,y:item.y,orient:item.orient,hinge:item.hinge,unlockType:item.unlockType,unlockParams:item.unlockParams});
    }
```

Make sure this check runs BEFORE the generic pillar/wall checks (or use an else-if chain).

- [ ] **Step 8: Add gate_key to objective rendering + saving**

In `_editorSave()`, in the objective section, gate_keys need their `keyId` saved:

```javascript
    else if(item.cat==='objective'&&item.subtype==='gate_key'){
      lv.objectives.push({type:'gate_key',x:item.x,y:item.y,keyId:item.keyId});
    }
```

- [ ] **Step 9: Add sidebar icons for gate items**

In `_drawEditorSidebar()`, the icon drawing section, gates are obstacles. Add a gate-specific icon before the generic obstacle icon check:

```javascript
        if(item.subtype==='gate'){
          const gc=item.unlockType==='guard'?'#ff4444':item.unlockType==='key'?'#ffdd00':'#00ccff';
          ctx.fillStyle=gc;ctx.fillRect(8,cy+7,12,8);
          ctx.strokeStyle='#fff';ctx.lineWidth=1;ctx.strokeRect(8,cy+7,12,8);
        } else if(item.subtype==='pillar'){
```

- [ ] **Step 10: Commit**
```bash
git add index.php && git commit -m "feat: gate editor tools — placement, properties toolbar, guard assignment, key auto-place"
```

---

## Unresolved Questions

None.
