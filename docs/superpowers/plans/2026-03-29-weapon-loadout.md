# Weapon Loadout System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the current "all unlocked weapons visible" model with a per-craft slot-based loadout system where players choose which weapons to bring into combat.

**Architecture:** Add `maxSlots` to each CRAFTS entry, introduce `P.loadout` array on the player object, rewrite weapon bar HUD to render loadout slots only, add a loadout editor UI accessible from pause and hangar screens, persist loadouts per craft in localStorage.

**Tech Stack:** Vanilla JS, Canvas 2D, single-file `index.php` (~7400 lines). Work in `F:\PATROL WING\.worktrees\weapon-loadout\index.php`.

---

### Task 1: Add maxSlots to CRAFTS and P.loadout to player object

**Files:**
- Modify: `F:\PATROL WING\.worktrees\weapon-loadout\index.php`

This task adds the data model foundation. No behavioral changes yet.

- [ ] **Step 1: Read CRAFTS array** (lines 480-544) and the player object (lines 1920-1930)

- [ ] **Step 2: Add `maxSlots` to each CRAFTS entry**

Add `maxSlots:N,` after `defaultColor` on each craft:

```javascript
// phantom (line 488, after defaultColor):
defaultColor:'#00ddff',maxSlots:7,

// viper (line 497):
defaultColor:'#ff3300',maxSlots:4,

// titan (line 506):
defaultColor:'#ff8800',maxSlots:10,

// specter (line 515):
defaultColor:'#aa44ff',maxSlots:6,

// sniper (line 524):
defaultColor:'#44ffcc',maxSlots:6,

// carrier (line 533):
defaultColor:'#00aaff',maxSlots:9,

// skirmisher (line 542):
defaultColor:'#ff44aa',maxSlots:5,
```

- [ ] **Step 3: Add `loadout` to the player object** (line 1925)

Find:
```javascript
weaponIdx:0,unlockedW:new Set([0]),shieldMs:0,
```
Replace with:
```javascript
weaponIdx:0,unlockedW:new Set([0]),loadout:[0],shieldMs:0,
```

- [ ] **Step 4: Add loadout to resetPlayer()** (line 1942)

Find:
```javascript
weaponIdx:c.startWeapon||0,unlockedW:new Set([0, c.startWeapon||0]),
```
Replace with:
```javascript
weaponIdx:c.startWeapon||0,unlockedW:new Set([0, c.startWeapon||0]),loadout:[c.startWeapon||0],
```

- [ ] **Step 5: Add localStorage loadout helpers** near existing `_saveHangar`/`_loadHangar` (around line 552-555)

After `_loadHangar` function, add:

```javascript
function _saveLoadout(craftId,loadout){
  try{const ids=loadout.map(i=>WEAPONS[i].id);localStorage.setItem('pw_loadout_'+craftId,JSON.stringify(ids));}catch(e){}
}
function _loadLoadout(craftId,maxSlots){
  try{
    const raw=localStorage.getItem('pw_loadout_'+craftId);
    if(!raw)return null;
    const ids=JSON.parse(raw);
    const indices=ids.map(id=>WEAPONS.findIndex(w=>w.id===id)).filter(i=>i>=0);
    return indices.slice(0,maxSlots);
  }catch(e){return null;}
}
```

- [ ] **Step 6: Commit**
```bash
cd "F:\PATROL WING\.worktrees\weapon-loadout" && git add index.php && git commit -m "feat: add maxSlots to CRAFTS, P.loadout array, localStorage helpers"
```

---

### Task 2: Integrate loadout into resetPlayer and weapon unlock pickup

**Files:**
- Modify: `F:\PATROL WING\.worktrees\weapon-loadout\index.php`

This task makes `resetPlayer()` load saved loadouts and changes the weapon unlock pickup to use the loadout system.

- [ ] **Step 1: Modify resetPlayer() to load saved loadout**

Read `resetPlayer()` (around line 1936). After the `Object.assign(P, {...})` block and before `if(c.startEMP)`, add loadout initialization:

```javascript
  // Load saved loadout or default to startWeapon
  const savedLO=_loadLoadout(c.id,c.maxSlots);
  if(savedLO&&savedLO.length>0){
    P.loadout=savedLO;
    P.unlockedW=new Set(savedLO);
    P.weaponIdx=savedLO[0];
  } else {
    P.loadout=[c.startWeapon||0];
  }
```

- [ ] **Step 2: Modify weapon unlock pickup** (around line 3759-3768)

Read the current `case'weapon':` block. Find:
```javascript
        case'weapon':
          if(P.unlockedW.size<WEAPONS.length){
            let next=0; while(P.unlockedW.has(next))next++;
            P.unlockedW.add(next); P.weaponIdx=next;
            score+=100;
            weaponFlash={name:WEAPONS[next].name,ms:3000};
```

Replace with:
```javascript
        case'weapon':
          if(P.unlockedW.size<WEAPONS.length){
            let next=0; while(P.unlockedW.has(next))next++;
            P.unlockedW.add(next);
            const maxSl=CRAFTS[P.craftIdx].maxSlots;
            if(P.loadout.length<maxSl){
              P.loadout.push(next);
              P.weaponIdx=next;
              weaponFlash={name:WEAPONS[next].name,ms:3000};
            } else {
              weaponFlash={prefix:'UNLOCKED',name:`${WEAPONS[next].name} — LOADOUT FULL`,ms:3000};
            }
            score+=100;
```

Leave the rest of the block (spawnParts, SFX, mine/seekr stock lines) unchanged.

- [ ] **Step 3: Commit**
```bash
git add index.php && git commit -m "feat: loadout integration — saved loadout on reset, smart weapon unlock"
```

---

### Task 3: Rewrite weapon switching to use P.loadout

**Files:**
- Modify: `F:\PATROL WING\.worktrees\weapon-loadout\index.php`

- [ ] **Step 1: Rewrite numeric key weapon switching** (line 2056)

Find:
```javascript
  for(let i=0;i<WEAPONS.length;i++){if(K[`Digit${i+1}`]&&P.unlockedW.has(i)){P.weaponIdx=i;K[`Digit${i+1}`]=false;}}
```

Replace with:
```javascript
  for(let i=0;i<Math.min(P.loadout.length,10);i++){const k=i<9?`Digit${i+1}`:'Digit0';if(K[k]){P.weaponIdx=P.loadout[i];K[k]=false;}}
```

- [ ] **Step 2: Rewrite Q/E cycle** (lines 2057-2058)

Find:
```javascript
  if(K['KeyQ']){const arr=[...P.unlockedW].sort((a,b)=>a-b);const ci=arr.indexOf(P.weaponIdx);P.weaponIdx=arr[(ci-1+arr.length)%arr.length];K['KeyQ']=false;}
  if(K['KeyE']){const arr=[...P.unlockedW].sort((a,b)=>a-b);const ci=arr.indexOf(P.weaponIdx);P.weaponIdx=arr[(ci+1)%arr.length];K['KeyE']=false;}
```

Replace with:
```javascript
  if(K['KeyQ']){const ci=P.loadout.indexOf(P.weaponIdx);P.weaponIdx=P.loadout[(ci-1+P.loadout.length)%P.loadout.length];K['KeyQ']=false;}
  if(K['KeyE']){const ci=P.loadout.indexOf(P.weaponIdx);P.weaponIdx=P.loadout[(ci+1)%P.loadout.length];K['KeyE']=false;}
```

- [ ] **Step 3: Rewrite right-click cycle** (around line 418-421)

Find the `canvas.addEventListener('mousedown'` right-click handler. The relevant section is:
```javascript
  const arr=[...P.unlockedW].sort((a,b)=>a-b);
  const ci=arr.indexOf(P.weaponIdx);
  P.weaponIdx=arr[(ci+1)%arr.length];
```

Replace with:
```javascript
  const ci=P.loadout.indexOf(P.weaponIdx);
  P.weaponIdx=P.loadout[(ci+1)%P.loadout.length];
```

- [ ] **Step 4: Rewrite no-ammo auto-switch** (around line 2131)

Find:
```javascript
        const arr=[...P.unlockedW].sort((a,b)=>a-b);
        const ci=arr.indexOf(P.weaponIdx);
        if(ci>0) P.weaponIdx=arr[ci-1];
```

Replace with:
```javascript
        const ci=P.loadout.indexOf(P.weaponIdx);
        if(ci>0) P.weaponIdx=P.loadout[ci-1];
```

- [ ] **Step 5: Rewrite weapon bar slot tap** (around line 7035)

Find the weapon bar click handler in the click dispatch. It currently iterates `for(let i=0;i<WEAPONS.length;i++)` and checks `if(!P.unlockedW.has(i)) continue;`. This needs to iterate over `P.loadout` instead. Read the exact code first, then replace the loop to iterate `P.loadout.length` slots and index via `P.loadout[i]`.

- [ ] **Step 6: Commit**
```bash
git add index.php && git commit -m "feat: weapon switching uses P.loadout — keys, cycle, auto-switch, bar tap"
```

---

### Task 4: Rewrite drawWeaponBar() to render loadout slots

**Files:**
- Modify: `F:\PATROL WING\.worktrees\weapon-loadout\index.php`

The weapon bar currently renders all 23 WEAPONS entries. Rewrite to render only `P.loadout` entries.

- [ ] **Step 1: Read current drawWeaponBar()** (lines 3844-3924)

- [ ] **Step 2: Rewrite the function**

The icon glyph array is currently indexed by weapon position in WEAPONS[]. Change to a lookup map so any weapon index maps to its glyph. Also change the loop from `WEAPONS.length` to `P.loadout.length`.

Replace the entire `drawWeaponBar()` function with:

```javascript
function drawWeaponBar(){
  const T=IS_TOUCH;
  const bw=T?28:42, bh=T?28:42, gap=T?4:6;
  const total=P.loadout.length, bx=(canvas.width-total*(bw+gap)+gap)/2, by=weaponBarY();
  const pad=T?4:8;
  ctx.fillStyle='rgba(0,0,0,0.42)';ctx.fillRect(bx-pad,by-pad,total*(bw+gap)+pad+gap,bh+pad*2+(T?10:16));
  ctx.strokeStyle='rgba(0,100,180,0.3)';ctx.lineWidth=1;ctx.strokeRect(bx-pad,by-pad,total*(bw+gap)+pad+gap,bh+pad*2+(T?10:16));
  const GLYPHS=['•','►','»','↩','∿','↯','|','↪','⊙','‖','⊸','◈','◎','⊞','⊛','⇝','⬆','⊕','⌬','◉','⊗','≋','※'];
  for(let i=0;i<total;i++){
    const wIdx=P.loadout[i],w=WEAPONS[wIdx],x=bx+i*(bw+gap),act=wIdx===P.weaponIdx;
    const wStock=w.stock!==null&&w.id!=='mine'?P.stocks[w.id]??w.stock:null;
    const noAmmo=(w.id==='mine'&&P.mineStock<=0)||(w.id==='seekr'&&P.seekStock<=0)||(wStock!==null&&wStock<=0);
    const mmActive=w.id==='minime'&&miniMe.active;
    const mmLost  =w.id==='minime'&&miniMe.lost;
    ctx.fillStyle=act?'rgba(0,0,0,0.9)':'rgba(0,0,0,0.5)';ctx.fillRect(x,by,bw,bh);
    const borderCol=act?(noAmmo||mmLost?'#ff4400':mmActive?MM_COL:w.color):mmActive?MM_COL:mmLost?'rgba(180,50,50,0.5)':'rgba(50,80,110,0.6)';
    ctx.strokeStyle=borderCol;
    ctx.lineWidth=act?2:mmActive?1.8:1;ctx.shadowBlur=act?(T?8:16):mmActive?10:0;ctx.shadowColor=mmActive?MM_COL:w.color;ctx.strokeRect(x,by,bw,bh);ctx.shadowBlur=0;
    ctx.textAlign='center';
    const numSz=T?7:9, iconSz=T?12:17;
    const iconCol=act?(noAmmo||mmLost?'#ff4400':mmActive?MM_COL:w.color):mmActive?MM_COL:mmLost?'rgba(180,50,50,0.7)':'rgba(65,95,120,0.7)';
    ctx.font=`${act?'bold ':''}${numSz}px "Courier New"`;ctx.fillStyle=act?(noAmmo||mmLost?'#ff4400':mmActive?MM_COL:w.color):'rgba(70,100,130,0.8)';
    ctx.fillText(i<9?`${i+1}`:'0',x+bw-(T?3:5),by+(T?8:12));
    ctx.font=`${act?'bold ':''}${iconSz}px "Courier New"`;ctx.fillStyle=iconCol;
    ctx.fillText(GLYPHS[wIdx]||'?',x+bw/2,by+bh/2+(T?5:7));
    if(w.id==='mine'){
      ctx.font=`bold ${T?7:9}px "Courier New"`;
      ctx.fillStyle=P.mineStock>0?(act?'#ff2200':'rgba(180,60,30,0.8)'):'rgba(100,50,40,0.6)';
      ctx.fillText(`×${P.mineStock}`,x+bw/2,by+bh-(T?2:4));
    } else if(w.id==='seekr'){
      ctx.font=`bold ${T?7:9}px "Courier New"`;
      ctx.fillStyle=P.seekStock>0?(act?SEEKR_COL:'rgba(180,130,30,0.8)'):'rgba(100,50,40,0.6)';
      ctx.fillText(`×${P.seekStock}`,x+bw/2,by+bh-(T?2:4));
    } else if(w.id==='tractor'){
      const secLeft=(P.stocks['tractor']||0)/1000;
      ctx.font=`bold ${T?7:9}px "Courier New"`;
      ctx.fillStyle=secLeft>0?(act?'#44aaff':'rgba(60,130,200,0.8)'):'rgba(60,80,120,0.5)';
      ctx.fillText(secLeft>0?`${Math.ceil(secLeft)}s`:'OUT',x+bw/2,by+bh-(T?2:4));
    } else if(wStock!==null){
      ctx.font=`bold ${T?7:9}px "Courier New"`;
      const label=wStock>=1000?`${Math.floor(wStock/1000)}k`:wStock<=0?'OUT':`×${wStock}`;
      ctx.fillStyle=wStock>0?(act?w.color:'rgba(120,140,160,0.7)'):'rgba(200,60,60,0.7)';
      ctx.fillText(label,x+bw/2,by+bh-(T?2:4));
    }
    if(w.id==='minime'&&miniMe.active){
      const pw=bw-6,ph=T?2:3,px2=x+3,py2=by+bh-(T?3:5);
      ctx.fillStyle='rgba(0,0,0,0.6)';ctx.fillRect(px2,py2,pw,ph);
      const hpPct=miniMe.hp/MM_HP;
      ctx.fillStyle=hpPct>0.5?MM_COL:hpPct>0.25?'#ffaa00':'#ff3333';
      ctx.fillRect(px2,py2,pw*hpPct,ph);
    }
    if(w.id==='minime'&&mmLost){
      ctx.font=`bold ${T?7:9}px "Courier New"`;ctx.fillStyle='rgba(200,60,60,0.8)';
      ctx.fillText('KIA',x+bw/2,by+bh-(T?2:4));
    }
  }
  ctx.textAlign='center';
  const cw=WEAPONS[P.weaponIdx];
  const nameSz=T?8:10;
  ctx.font=`${nameSz}px "Courier New"`;ctx.fillStyle=cw.color;ctx.shadowBlur=T?5:8;ctx.shadowColor=cw.color;
  const mineLabel=cw.id==='mine'?` [${P.mineStock}]`:'';
  const seekrLabel=cw.id==='seekr'?` [${P.seekStock}]`:'';
  const mmLabel=cw.id==='minime'?(miniMe.active?' [ACTIVE]':miniMe.lost?' [KIA]':''):'';
  const cwStock=cw.stock!==null&&cw.id!=='mine'?P.stocks[cw.id]??cw.stock:null;
  const stockLabel=cwStock!==null?(cwStock>=1000?` [${Math.floor(cwStock/1000)}k rds]`:cwStock<=0?' [OUT]':` [${cwStock} rds]`):'';
  ctx.fillText(cw.name+mineLabel+seekrLabel+mmLabel+stockLabel,canvas.width/2,by+bh+(T?9:13));ctx.shadowBlur=0;
  if(weaponFlash.ms>0){
    const a=Math.min(1,weaponFlash.ms/700);ctx.globalAlpha=a;
    ctx.font=`bold ${T?11:16}px "Courier New"`;ctx.fillStyle=cw.color;ctx.shadowBlur=T?14:24;ctx.shadowColor=cw.color;
    ctx.fillText(`${weaponFlash.prefix??'⬆ WEAPON:'} ${weaponFlash.name}`,canvas.width/2,by-(T?14:24));ctx.shadowBlur=0;ctx.globalAlpha=1;
  }
  ctx.textAlign='left';
}
```

Key changes from old version:
- `total=P.loadout.length` instead of `WEAPONS.length`
- Loop indexes into `P.loadout[i]` to get the weapon index
- Glyph lookup uses `GLYPHS[wIdx]` instead of `GLYPHS[i]`
- No locked-weapon rendering (all loadout entries are available)
- Slot labels: 1-9, then 0 for slot 10
- Slightly larger slot sizes (42px vs 38px) since fewer slots means more room

- [ ] **Step 3: Commit**
```bash
git add index.php && git commit -m "feat: rewrite drawWeaponBar to render P.loadout slots only"
```

---

### Task 5: Add MODIFY WEAPONS button to pause screen

**Files:**
- Modify: `F:\PATROL WING\.worktrees\weapon-loadout\index.php`

- [ ] **Step 1: Read drawPauseScreen()** (lines 4062-4133)

- [ ] **Step 2: Insert MODIFY WEAPONS button between Resume and Abort**

Current layout:
- Resume button: `by=cy+48`, height 50
- Space hint: `by+bh+22`
- Music label: `by+bh+40`
- Abort button: `ay=by+bh+44`

New layout — insert a "MODIFY WEAPONS" button after Resume, shift Music and Abort down. Find the Abort button block:

```javascript
  // Abort button
  const aw=290,ah=44,ax=cx-aw/2,ay=by+bh+44;
```

Replace the entire section from `// Space hint` through end of Abort button with:

```javascript
  // Space hint
  if(Math.floor(now*1.6)%2===0){
    ctx.font='11px "Courier New"';
    ctx.fillStyle='rgba(80,140,200,0.55)';
    ctx.fillText(IS_TOUCH?'TAP  ▶ RETURN TO FLIGHT  to resume':'SPACE · P · ESC  to resume',cx,by+bh+22);
  }

  // Modify Weapons button
  const mw2=290,mh2=44,mx2=cx-mw2/2,my2=by+bh+40;
  const mhov=mouse.x>mx2&&mouse.x<mx2+mw2&&mouse.y>my2&&mouse.y<my2+mh2;
  ctx.shadowBlur=mhov?22:8;ctx.shadowColor='#00ccff';
  ctx.fillStyle=mhov?'rgba(0,140,200,0.85)':'rgba(0,0,0,0.65)';
  roundRect(ctx,mx2,my2,mw2,mh2,8);ctx.fill();
  ctx.strokeStyle=mhov?'#00eeff':'rgba(0,140,220,0.7)';ctx.lineWidth=1.8;
  roundRect(ctx,mx2,my2,mw2,mh2,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 13px "Courier New"';
  ctx.fillStyle=mhov?'#000':'rgba(100,200,255,0.9)';
  ctx.fillText('⚙  MODIFY WEAPONS',cx,my2+mh2/2+5);

  // Music status
  ctx.font='10px "Courier New"';
  const musicLabel = Music.isMuted() ? '♪ MUSIC OFF' : `♪ MUSIC  [${Music.mode().toUpperCase()}]`;
  ctx.fillStyle = Music.isMuted() ? 'rgba(160,80,50,0.6)' : 'rgba(0,160,180,0.5)';
  ctx.fillText(`${musicLabel}  ·  M to toggle`,cx,my2+mh2+18);

  // Abort button
  const aw=290,ah=44,ax=cx-aw/2,ay=my2+mh2+30;
  const ahov=mouse.x>ax&&mouse.x<ax+aw&&mouse.y>ay&&mouse.y<ay+ah;
  ctx.shadowBlur=ahov?22:8; ctx.shadowColor='#ff3300';
  ctx.fillStyle=ahov?'rgba(180,30,10,0.92)':'rgba(0,0,0,0.65)';
  roundRect(ctx,ax,ay,aw,ah,8); ctx.fill();
  ctx.strokeStyle=ahov?'#ff5522':'rgba(180,50,30,0.7)'; ctx.lineWidth=1.8;
  roundRect(ctx,ax,ay,aw,ah,8); ctx.stroke();
  ctx.shadowBlur=0;
  ctx.font='bold 13px "Courier New"';
  ctx.fillStyle=ahov?'#ffccaa':'rgba(200,80,50,0.85)';
  ctx.fillText('✕  ABORT THE MISSION',cx,ay+ah/2+5);
```

- [ ] **Step 3: Update pause screen click handler** (around line 7039-7058)

Read the current handler. Add a click check for the MODIFY WEAPONS button between Resume and Abort. The button rect is `mx2,my2,mw2,mh2` — use the same dimensions from the draw code. On click, transition to `gameState='loadoutEdit'`.

Find the Abort button click check:
```javascript
      // Abort button
      const aw=290,ah=44,ax=cx-aw/2,ay=by+bh+44;
```

Replace with (adjusting Abort position to match the new draw layout):
```javascript
      // Modify Weapons button
      const mw2=290,mh2=44,mx2=cx-mw2/2,my2=by+bh+40;
      if(mouse.x>mx2&&mouse.x<mx2+mw2&&mouse.y>my2&&mouse.y<my2+mh2){
        gameState='loadoutEdit';SFX.select();return;
      }
      // Abort button
      const aw=290,ah=44,ax=cx-aw/2,ay=my2+mh2+30;
```

- [ ] **Step 4: Commit**
```bash
git add index.php && git commit -m "feat: MODIFY WEAPONS button on pause screen, transitions to loadoutEdit"
```

---

### Task 6: Implement loadoutEdit state — draw + click handler

**Files:**
- Modify: `F:\PATROL WING\.worktrees\weapon-loadout\index.php`

This is the largest task. It adds the `'loadoutEdit'` game state with its own draw and click functions.

- [ ] **Step 1: Add drawLoadoutEdit() function**

Add after `drawPauseScreen()`:

```javascript
function drawLoadoutEdit(){
  const cx=canvas.width/2,W=canvas.width,H=canvas.height;
  const c=CRAFTS[P.craftIdx];
  // Overlay
  ctx.fillStyle='rgba(4,10,26,0.88)';ctx.fillRect(0,0,W,H);
  // Title
  ctx.textAlign='center';
  ctx.font='bold 24px "Courier New"';ctx.fillStyle='#00ccff';ctx.shadowBlur=20;ctx.shadowColor='#00aaff';
  ctx.fillText('WEAPONS LOADOUT',cx,50);ctx.shadowBlur=0;
  ctx.font='12px "Courier New"';ctx.fillStyle='rgba(100,180,255,0.7)';
  ctx.fillText(`${c.name}  —  ${P.loadout.length}/${c.maxSlots} SLOTS`,cx,72);

  const GLYPHS=['•','►','»','↩','∿','↯','|','↪','⊙','‖','⊸','◈','◎','⊞','⊛','⇝','⬆','⊕','⌬','◉','⊗','≋','※'];
  const cardW=120,cardH=38,cardGap=8;

  // ── LOADED section ──
  ctx.font='bold 12px "Courier New"';ctx.fillStyle='rgba(0,255,136,0.7)';
  ctx.fillText('▼  LOADED',cx,100);
  const loadedY=114;
  const loadedTotalW=c.maxSlots*(cardW+cardGap)-cardGap;
  const loadedStartX=cx-loadedTotalW/2;
  for(let i=0;i<c.maxSlots;i++){
    const x=loadedStartX+i*(cardW+cardGap),y=loadedY;
    if(i<P.loadout.length){
      const wIdx=P.loadout[i],w=WEAPONS[wIdx];
      const hov=mouse.x>x&&mouse.x<x+cardW&&mouse.y>y&&mouse.y<y+cardH;
      ctx.fillStyle=hov?'rgba(0,80,40,0.7)':'rgba(0,40,20,0.5)';
      roundRect(ctx,x,y,cardW,cardH,6);ctx.fill();
      ctx.strokeStyle=hov?'#00ff88':w.color;ctx.lineWidth=hov?2:1;
      roundRect(ctx,x,y,cardW,cardH,6);ctx.stroke();
      ctx.font='14px "Courier New"';ctx.fillStyle=w.color;
      ctx.fillText(GLYPHS[wIdx]||'?',x+16,y+cardH/2+5);
      ctx.font='10px "Courier New"';ctx.fillStyle=hov?'#ffffff':'rgba(180,220,255,0.85)';
      ctx.textAlign='left';ctx.fillText(w.name,x+30,y+cardH/2+4);ctx.textAlign='center';
    } else {
      ctx.strokeStyle='rgba(60,100,140,0.3)';ctx.lineWidth=1;ctx.setLineDash([4,4]);
      roundRect(ctx,x,y,cardW,cardH,6);ctx.stroke();ctx.setLineDash([]);
    }
  }

  // ── AVAILABLE section ──
  const available=[...P.unlockedW].filter(i=>!P.loadout.includes(i)).sort((a,b)=>a-b);
  const availY=loadedY+cardH+40;
  ctx.font='bold 12px "Courier New"';ctx.fillStyle='rgba(150,180,220,0.6)';
  ctx.fillText(`▼  AVAILABLE  (${available.length})`,cx,availY-8);
  const availCols=Math.min(available.length,Math.floor((W-40)/(cardW+cardGap)));
  const availTotalW=Math.min(available.length,availCols)*(cardW+cardGap)-cardGap;
  const availStartX=cx-availTotalW/2;
  for(let i=0;i<available.length;i++){
    const col=i%availCols,row=Math.floor(i/availCols);
    const x=availStartX+col*(cardW+cardGap),y=availY+row*(cardH+cardGap);
    const wIdx=available[i],w=WEAPONS[wIdx];
    const hov=mouse.x>x&&mouse.x<x+cardW&&mouse.y>y&&mouse.y<y+cardH;
    const full=P.loadout.length>=c.maxSlots;
    ctx.fillStyle=hov&&!full?'rgba(0,40,80,0.7)':'rgba(0,20,50,0.4)';
    roundRect(ctx,x,y,cardW,cardH,6);ctx.fill();
    ctx.strokeStyle=full?'rgba(60,80,100,0.3)':hov?'#00ccff':w.color;ctx.lineWidth=hov&&!full?2:1;
    roundRect(ctx,x,y,cardW,cardH,6);ctx.stroke();
    ctx.font='14px "Courier New"';ctx.fillStyle=full?'rgba(80,100,120,0.5)':w.color;
    ctx.fillText(GLYPHS[wIdx]||'?',x+16,y+cardH/2+5);
    ctx.font='10px "Courier New"';ctx.fillStyle=full?'rgba(80,100,120,0.5)':hov?'#ffffff':'rgba(150,180,210,0.75)';
    ctx.textAlign='left';ctx.fillText(w.name,x+30,y+cardH/2+4);ctx.textAlign='center';
  }

  // ── DONE button ──
  const dbw=200,dbh=44,dbx=cx-dbw/2,dby=H-80;
  const dhov=mouse.x>dbx&&mouse.x<dbx+dbw&&mouse.y>dby&&mouse.y<dby+dbh;
  ctx.shadowBlur=dhov?24:10;ctx.shadowColor='#00ff88';
  ctx.fillStyle=dhov?'#00ff88':'rgba(0,0,0,0.7)';
  roundRect(ctx,dbx,dby,dbw,dbh,8);ctx.fill();
  ctx.strokeStyle='#00ff88';ctx.lineWidth=2;
  roundRect(ctx,dbx,dby,dbw,dbh,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 14px "Courier New"';ctx.fillStyle=dhov?'#000':'#00ff88';
  ctx.fillText('✔  DONE',cx,dby+dbh/2+5);
  ctx.textAlign='left';
}
```

- [ ] **Step 2: Add click handler for loadoutEdit state**

Find the click dispatch section (around line 7039). Add a new block for `gameState==='loadoutEdit'` BEFORE the `gameState==='paused'` block:

```javascript
  if(gameState==='loadoutEdit'){
    const cx=canvas.width/2,W=canvas.width,H=canvas.height;
    const c=CRAFTS[P.craftIdx];
    const cardW=120,cardH=38,cardGap=8;
    // Loaded cards
    const loadedTotalW=c.maxSlots*(cardW+cardGap)-cardGap;
    const loadedStartX=cx-loadedTotalW/2;
    const loadedY=114;
    for(let i=0;i<P.loadout.length;i++){
      const x=loadedStartX+i*(cardW+cardGap),y=loadedY;
      if(mouse.x>x&&mouse.x<x+cardW&&mouse.y>y&&mouse.y<y+cardH){
        if(P.loadout.length>1){
          const removed=P.loadout.splice(i,1)[0];
          if(P.weaponIdx===removed) P.weaponIdx=P.loadout[0];
          SFX.select();
        }
        return;
      }
    }
    // Available cards
    const available=[...P.unlockedW].filter(i=>!P.loadout.includes(i)).sort((a,b)=>a-b);
    const availY=loadedY+cardH+40;
    const availCols=Math.min(available.length,Math.floor((W-40)/(cardW+cardGap)));
    const availTotalW=Math.min(available.length,availCols)*(cardW+cardGap)-cardGap;
    const availStartX=cx-availTotalW/2;
    for(let i=0;i<available.length;i++){
      const col=i%availCols,row=Math.floor(i/availCols);
      const x=availStartX+col*(cardW+cardGap),y=availY+row*(cardH+cardGap);
      if(mouse.x>x&&mouse.x<x+cardW&&mouse.y>y&&mouse.y<y+cardH){
        if(P.loadout.length<c.maxSlots){
          P.loadout.push(available[i]);
          SFX.select();
        }
        return;
      }
    }
    // DONE button
    const dbw=200,dbh=44,dbx=cx-dbw/2,dby=H-80;
    if(mouse.x>dbx&&mouse.x<dbx+dbw&&mouse.y>dby&&mouse.y<dby+dbh){
      _saveLoadout(c.id,P.loadout);
      gameState='paused';SFX.confirm();return;
    }
    return;
  }
```

- [ ] **Step 3: Wire loadoutEdit into the main render loop**

Find the pause screen render block (around line 8163, `} else if(gameState==='paused'){`). After the `drawPauseScreen();` line at the end of that block, add a new block:

```javascript
  } else if(gameState==='loadoutEdit'){
    // Same frozen battlefield as pause
    const wallSpin=Date.now()/80;
    drawWorld();drawObstacles();drawParticles();pickups.forEach(drawPickup);drawBullets();
    for(const e of enemies){
      const sx=e.x-camX,sy=e.y-camY;
      if(sx<-90||sx>canvas.width+90||sy<-90||sy>canvas.height+90)continue;
      drawEnemyDrone(sx,sy,e.aim,e.size,e.color,e.accent,wallSpin*(enemies.indexOf(e)%2===0?1:-1.3),e.hp/e.maxHp);
    }
    if(P.alive){const sx=P.x-camX,sy=P.y-camY;drawPlayerCraft(sx,sy,P.aim,P.size,P.color,lighten(P.color,90),wallSpin,P.hp/P.maxHp);}
    drawLoadoutEdit();
```

- [ ] **Step 4: Handle ESC/P key in loadoutEdit state to go back to paused**

Find the pause key handler (around line 371). Modify to also handle loadoutEdit:

Find:
```javascript
  if((e.code==='KeyP'||e.code==='Escape')&&(gameState==='playing'||gameState==='paused')){
```
Replace with:
```javascript
  if((e.code==='KeyP'||e.code==='Escape')&&(gameState==='playing'||gameState==='paused'||gameState==='loadoutEdit')){
```

And inside that handler, add a check: if currently in loadoutEdit, go back to paused instead of toggling play:

Find:
```javascript
    gameState= gameState==='paused' ? 'playing' : 'paused';
```
Replace with:
```javascript
    if(gameState==='loadoutEdit'){gameState='paused';_saveLoadout(CRAFTS[P.craftIdx].id,P.loadout);return;}
    gameState= gameState==='paused' ? 'playing' : 'paused';
```

- [ ] **Step 5: Commit**
```bash
git add index.php && git commit -m "feat: loadoutEdit state — draw, click, ESC handling, save on exit"
```

---

### Task 7: Add EDIT LOADOUT button to Hangar screen

**Files:**
- Modify: `F:\PATROL WING\.worktrees\weapon-loadout\index.php`

- [ ] **Step 1: Read drawHangarScreen()** to find where to insert the button — look for the save/cancel button area at the bottom

- [ ] **Step 2: Add an EDIT LOADOUT button** in the hangar layout, positioned between the craft cards area and the save/cancel buttons. Use `_briefBtn` or manual draw (matching the save/cancel style).

The button should be centered, ~200px wide, using cyan/teal color. On click, open the loadout editor. Since the hangar has no `P.loadout` at play time, the loadout editor in hangar context should work with the SAVED loadout for the currently selected craft.

When entering loadout edit from the hangar:
- Load the saved loadout for `CRAFTS[hangarCraft].id` via `_loadLoadout`
- If no saved loadout exists, default to `[CRAFTS[hangarCraft].startWeapon||0]`
- Store into a temporary `hangarLoadout` array
- The editor renders from `hangarLoadout` and all 23 weapons are available (not gated by `P.unlockedW`)

Add a module-level variable: `let hangarLoadout=[];`

- [ ] **Step 3: Modify drawLoadoutEdit() and its click handler** to support a `hangarMode` flag

Add `let loadoutEditFrom='pause';` near the hangar variables. When entering from pause, set `loadoutEditFrom='pause'`. When entering from hangar, set `loadoutEditFrom='hangar'`.

In `drawLoadoutEdit()`:
- Title shows `CRAFTS[hangarCraft].name` in hangar mode, `CRAFTS[P.craftIdx].name` in pause mode
- Loaded zone reads from `hangarLoadout` in hangar mode, `P.loadout` in pause mode
- Available pool in hangar mode: all WEAPONS indices not in hangarLoadout (all 23 available)
- Available pool in pause mode: `P.unlockedW` minus `P.loadout`
- maxSlots comes from `CRAFTS[hangarCraft].maxSlots` or `CRAFTS[P.craftIdx].maxSlots`

In the click handler:
- Loaded/available clicks modify `hangarLoadout` in hangar mode, `P.loadout` in pause mode
- DONE: in hangar mode, save `hangarLoadout` to localStorage and return to `'hangar'`. In pause mode, save `P.loadout` and return to `'paused'`.

- [ ] **Step 4: Wire hangar button click**

In the hangar click handler, add click detection for the EDIT LOADOUT button. On click:
```javascript
const cId=CRAFTS[hangarCraft].id;
const maxSl=CRAFTS[hangarCraft].maxSlots;
hangarLoadout=_loadLoadout(cId,maxSl)||[CRAFTS[hangarCraft].startWeapon||0];
loadoutEditFrom='hangar';
gameState='loadoutEdit';
SFX.select();
```

- [ ] **Step 5: Load correct loadout when switching crafts in hangar**

In the hangar craft selection code (where `hangarCraft` is changed), no loadout change is needed since the loadout is only loaded when the EDIT LOADOUT button is clicked.

- [ ] **Step 6: Commit**
```bash
git add index.php && git commit -m "feat: EDIT LOADOUT button in hangar, dual-mode loadout editor"
```

---

## Unresolved Questions

None.
