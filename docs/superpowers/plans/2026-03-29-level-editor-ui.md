# Level Editor UI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a full-screen grid-based level editor with toolbox sidebar, setup wizard, save/pack flow, and enhanced customSelect with expandable packs and per-level actions.

**Architecture:** All new game states (`levelSetup`, `levelEditor`, `levelSavePrompt`) added to the single `index.php` file following existing patterns. Module-level editor state variables, draw functions, click handlers, and keyboard input for each state. The editor produces JSON matching the level schema from spec 1 and saves via the existing `_saveCustomLevels()` helper. The customSelect screen is rewritten to support expandable pack views with per-level actions.

**Tech Stack:** Vanilla JS, Canvas 2D, single-file `index.php` (~7800 lines). Work in `F:\PATROL WING\.worktrees\level-editor\index.php`.

---

### Task 1: Module-level editor state + hidden text input

**Files:**
- Modify: `F:\PATROL WING\.worktrees\level-editor\index.php`

- [ ] **Step 1: Add editor state variables**

Find the custom level state block (around line 635, after `let ctNextPickupMs=0;` and the existing `customPack`, `customObjectives`, etc.). After all the `custom*` variables, add:

```javascript
// Level editor state
let editorPack=null;
let editorLevel=null;
let editorTool='';
let editorCamX=0,editorCamY=0;
let editorSidebarScroll=0;
let editorExpandedCat='';
let editorPlacedItems=[];
let editorSpawnX=200,editorSpawnY=200;
let editorPackName='';
let editorLevelName='Untitled Level';
let editorWorldW=2600,editorWorldH=1700;
let editorWinCondition='killAll';
let editorWinSeconds=60;
let editorDirty=false;
let editorSliderDrag=null;
let customSelectExpanded=-1;
let customSelectSelectedLevel=-1;
```

- [ ] **Step 2: Add hidden text input for level/pack names**

Find the existing `<input type="color" id="colorPick"` element (around line 25). After it, add:

```html
<input type="text" id="editorNameInput" style="position:absolute;opacity:0;pointer-events:none;width:1px;height:1px;top:0;left:0;" maxlength="40">
```

Then find where `const colorPick=document.getElementById('colorPick');` is declared (around line 35). After it, add:

```javascript
const editorNameInput=document.getElementById('editorNameInput');
```

- [ ] **Step 3: Commit**
```bash
cd "F:\PATROL WING\.worktrees\level-editor" && git add index.php && git commit -m "feat: editor module state + hidden text input"
```

---

### Task 2: Setup wizard (`levelSetup` state)

**Files:**
- Modify: `F:\PATROL WING\.worktrees\level-editor\index.php`

- [ ] **Step 1: Add `drawLevelSetup()` function**

Add after `drawCustomSelect()`. This draws a centered dialog with level configuration options:

```javascript
function drawLevelSetup(){
  const W=canvas.width,H=canvas.height,cx=W/2;
  ctx.fillStyle='#060c18';ctx.fillRect(0,0,W,H);
  ctx.textAlign='center';
  ctx.font='bold 24px "Courier New"';ctx.fillStyle='#00ccff';ctx.shadowBlur=20;ctx.shadowColor='#00aaff';
  ctx.fillText('LEVEL SETUP',cx,60);ctx.shadowBlur=0;

  const panelW=Math.min(500,W*0.8),panelX=cx-panelW/2;
  let py=100;

  // Level name
  ctx.textAlign='left';ctx.font='bold 12px "Courier New"';ctx.fillStyle='rgba(100,180,255,0.7)';
  ctx.fillText('LEVEL NAME',panelX,py);py+=6;
  const nameW=panelW,nameH=36;
  const nameHov=mouse.x>panelX&&mouse.x<panelX+nameW&&mouse.y>py&&mouse.y<py+nameH;
  ctx.fillStyle=nameHov?'rgba(0,40,80,0.7)':'rgba(0,20,50,0.5)';
  roundRect(ctx,panelX,py,nameW,nameH,6);ctx.fill();
  ctx.strokeStyle=nameHov?'#00ccff':'rgba(0,100,180,0.4)';ctx.lineWidth=1;
  roundRect(ctx,panelX,py,nameW,nameH,6);ctx.stroke();
  ctx.font='14px "Courier New"';ctx.fillStyle='rgba(180,220,255,0.9)';
  ctx.fillText(editorLevelName||'Click to name...',panelX+12,py+24);
  py+=nameH+20;

  // World Width slider
  ctx.font='bold 12px "Courier New"';ctx.fillStyle='rgba(100,180,255,0.7)';
  ctx.fillText(`WORLD WIDTH: ${editorWorldW}`,panelX,py);py+=8;
  const slW=panelW,slH=20,slPct=(editorWorldW-400)/(4500-400);
  ctx.fillStyle='rgba(0,20,50,0.5)';roundRect(ctx,panelX,py,slW,slH,4);ctx.fill();
  ctx.fillStyle='rgba(0,100,200,0.6)';roundRect(ctx,panelX,py,slW*slPct,slH,4);ctx.fill();
  ctx.strokeStyle='rgba(0,100,180,0.4)';ctx.lineWidth=1;roundRect(ctx,panelX,py,slW,slH,4);ctx.stroke();
  const thumbX=panelX+slW*slPct;
  ctx.fillStyle='#00ccff';ctx.beginPath();ctx.arc(thumbX,py+slH/2,8,0,Math.PI*2);ctx.fill();
  py+=slH+18;

  // World Height slider
  ctx.font='bold 12px "Courier New"';ctx.fillStyle='rgba(100,180,255,0.7)';
  ctx.fillText(`WORLD HEIGHT: ${editorWorldH}`,panelX,py);py+=8;
  const slPctH=(editorWorldH-400)/(4500-400);
  ctx.fillStyle='rgba(0,20,50,0.5)';roundRect(ctx,panelX,py,slW,slH,4);ctx.fill();
  ctx.fillStyle='rgba(0,100,200,0.6)';roundRect(ctx,panelX,py,slW*slPctH,slH,4);ctx.fill();
  ctx.strokeStyle='rgba(0,100,180,0.4)';ctx.lineWidth=1;roundRect(ctx,panelX,py,slW,slH,4);ctx.stroke();
  const thumbXH=panelX+slW*slPctH;
  ctx.fillStyle='#00ccff';ctx.beginPath();ctx.arc(thumbXH,py+slH/2,8,0,Math.PI*2);ctx.fill();
  py+=slH+22;

  // Win condition selector
  ctx.font='bold 12px "Courier New"';ctx.fillStyle='rgba(100,180,255,0.7)';
  ctx.fillText('WIN CONDITION',panelX,py);py+=8;
  const conditions=[
    {id:'killAll',label:'KILL ALL',desc:'Eliminate all hostiles'},
    {id:'reachFinish',label:'REACH FINISH',desc:'Reach the finish line'},
    {id:'survive',label:'SURVIVE',desc:'Survive for set duration'},
    {id:'retrieve',label:'RETRIEVE',desc:'Get item, return to goal'},
    {id:'collectAll',label:'COLLECT ALL',desc:'Find all keys'},
  ];
  const condW=(panelW-4*8)/5,condH=52;
  for(let i=0;i<conditions.length;i++){
    const c=conditions[i],cx2=panelX+i*(condW+8),sel=editorWinCondition===c.id;
    const chov=mouse.x>cx2&&mouse.x<cx2+condW&&mouse.y>py&&mouse.y<py+condH;
    ctx.fillStyle=sel?'rgba(0,80,40,0.7)':chov?'rgba(0,40,80,0.7)':'rgba(0,20,50,0.4)';
    roundRect(ctx,cx2,py,condW,condH,6);ctx.fill();
    ctx.strokeStyle=sel?'#00ff88':chov?'#00ccff':'rgba(0,100,180,0.3)';ctx.lineWidth=sel?2:1;
    roundRect(ctx,cx2,py,condW,condH,6);ctx.stroke();
    ctx.textAlign='center';ctx.font='bold 9px "Courier New"';ctx.fillStyle=sel?'#00ff88':'rgba(150,200,255,0.8)';
    ctx.fillText(c.label,cx2+condW/2,py+22);
    ctx.font='8px "Courier New"';ctx.fillStyle='rgba(100,150,200,0.6)';
    ctx.fillText(c.desc,cx2+condW/2,py+38);
  }
  py+=condH+14;

  // Survive seconds (contextual)
  if(editorWinCondition==='survive'){
    ctx.textAlign='left';ctx.font='bold 12px "Courier New"';ctx.fillStyle='rgba(100,180,255,0.7)';
    ctx.fillText(`SURVIVE SECONDS: ${editorWinSeconds}`,panelX,py);py+=8;
    const secPct=(editorWinSeconds-10)/(180-10);
    ctx.fillStyle='rgba(0,20,50,0.5)';roundRect(ctx,panelX,py,slW,slH,4);ctx.fill();
    ctx.fillStyle='rgba(0,100,200,0.6)';roundRect(ctx,panelX,py,slW*secPct,slH,4);ctx.fill();
    ctx.strokeStyle='rgba(0,100,180,0.4)';ctx.lineWidth=1;roundRect(ctx,panelX,py,slW,slH,4);ctx.stroke();
    const thumbXS=panelX+slW*secPct;
    ctx.fillStyle='#00ccff';ctx.beginPath();ctx.arc(thumbXS,py+slH/2,8,0,Math.PI*2);ctx.fill();
    py+=slH+22;
  }

  // Buttons
  ctx.textAlign='center';
  const btnW=200,btnH=44;
  const startX=cx-btnW/2,startY=Math.max(py+20,H-120);
  const shov=mouse.x>startX&&mouse.x<startX+btnW&&mouse.y>startY&&mouse.y<startY+btnH;
  ctx.shadowBlur=shov?24:10;ctx.shadowColor='#00ff88';
  ctx.fillStyle=shov?'#00ff88':'rgba(0,0,0,0.7)';
  roundRect(ctx,startX,startY,btnW,btnH,8);ctx.fill();
  ctx.strokeStyle='#00ff88';ctx.lineWidth=2;
  roundRect(ctx,startX,startY,btnW,btnH,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 14px "Courier New"';ctx.fillStyle=shov?'#000':'#00ff88';
  ctx.fillText('START EDITING',cx,startY+btnH/2+5);

  const backW=120,backH=38,backX=cx-backW/2,backY=startY+btnH+14;
  const bkhov=mouse.x>backX&&mouse.x<backX+backW&&mouse.y>backY&&mouse.y<backY+backH;
  ctx.shadowBlur=bkhov?14:4;ctx.shadowColor='#00ccff';
  ctx.fillStyle=bkhov?'rgba(0,140,200,0.85)':'rgba(0,0,0,0.55)';
  roundRect(ctx,backX,backY,backW,backH,6);ctx.fill();
  ctx.strokeStyle=bkhov?'#00eeff':'rgba(0,140,220,0.5)';ctx.lineWidth=1;
  roundRect(ctx,backX,backY,backW,backH,6);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 11px "Courier New"';ctx.fillStyle=bkhov?'#000':'rgba(100,200,255,0.9)';
  ctx.fillText('BACK',cx,backY+backH/2+4);
  ctx.textAlign='left';
}
```

- [ ] **Step 2: Add levelSetup click handler**

Add in the click dispatch before `gameState==='customSelect'`:

```javascript
  if(gameState==='levelSetup'){
    const W=canvas.width,H=canvas.height,cx=W/2;
    const panelW=Math.min(500,W*0.8),panelX=cx-panelW/2;
    let py=100;
    // Name click
    const nameH=36;
    if(mouse.x>panelX&&mouse.x<panelX+panelW&&mouse.y>py+6&&mouse.y<py+6+nameH){
      editorNameInput.style.pointerEvents='auto';editorNameInput.style.opacity='1';
      editorNameInput.style.position='fixed';editorNameInput.style.left='50%';editorNameInput.style.top='130px';
      editorNameInput.style.transform='translateX(-50%)';editorNameInput.style.width='300px';editorNameInput.style.height='30px';
      editorNameInput.style.fontSize='16px';editorNameInput.style.fontFamily='"Courier New"';
      editorNameInput.style.background='#0a1828';editorNameInput.style.color='#00ccff';editorNameInput.style.border='1px solid #00ccff';
      editorNameInput.style.textAlign='center';editorNameInput.style.zIndex='100';
      editorNameInput.value=editorLevelName;editorNameInput.focus();editorNameInput.select();
      editorNameInput.onblur=()=>{editorLevelName=editorNameInput.value||'Untitled Level';editorNameInput.style.pointerEvents='none';editorNameInput.style.opacity='0';editorNameInput.style.width='1px';editorNameInput.style.height='1px';};
      editorNameInput.onkeydown=(e)=>{if(e.key==='Enter'){editorNameInput.blur();}};
      return;
    }
    py+=nameH+26;
    // Width slider
    const slW=panelW,slH=20;
    if(mouse.y>py&&mouse.y<py+slH){editorSliderDrag='width';return;}
    py+=slH+26;
    // Height slider
    if(mouse.y>py&&mouse.y<py+slH){editorSliderDrag='height';return;}
    py+=slH+22;
    // Win conditions
    const condW=(panelW-4*8)/5,condH=52;
    for(let i=0;i<5;i++){
      const cx2=panelX+i*(condW+8);
      if(mouse.x>cx2&&mouse.x<cx2+condW&&mouse.y>py&&mouse.y<py+condH){
        editorWinCondition=['killAll','reachFinish','survive','retrieve','collectAll'][i];
        SFX.select();return;
      }
    }
    py+=condH+14;
    // Survive slider
    if(editorWinCondition==='survive'){
      if(mouse.y>py+8&&mouse.y<py+8+slH){editorSliderDrag='seconds';return;}
      py+=slH+22;
    }
    // Start editing
    const btnW=200,btnH=44,startX=cx-btnW/2,startY=Math.max(py+20,H-120);
    if(mouse.x>startX&&mouse.x<startX+btnW&&mouse.y>startY&&mouse.y<startY+btnH){
      editorLevel={
        name:editorLevelName,author:'Player',created:Date.now(),
        worldW:editorWorldW,worldH:editorWorldH,
        winCondition:editorWinCondition,
        winParams:editorWinCondition==='survive'?{seconds:editorWinSeconds}:{},
        obstacles:[],enemies:[],pickups:[],hazards:[],objectives:[],
        spawnX:200,spawnY:Math.round(editorWorldH/2),
        leaderboard:[],awards:[],
      };
      editorPlacedItems=[];
      editorSpawnX=200;editorSpawnY=Math.round(editorWorldH/2);
      editorCamX=0;editorCamY=0;editorTool='';editorExpandedCat='';editorDirty=false;
      if(!editorPack) editorPack={packName:editorLevelName,author:'Player',created:Date.now(),levels:[]};
      gameState='levelEditor';SFX.confirm();return;
    }
    // Back
    const backW=120,backH=38,backX=cx-backW/2,backY=startY+btnH+14;
    if(mouse.x>backX&&mouse.x<backX+backW&&mouse.y>backY&&mouse.y<backY+backH){
      gameState='customSelect';SFX.select();return;
    }
    return;
  }
```

- [ ] **Step 3: Add slider drag handling for levelSetup**

Add a mousemove handler for slider dragging. Find the existing `canvas.addEventListener('mousemove'` handler. Inside it, add at the top (before the existing hover tracking):

```javascript
  if(editorSliderDrag&&gameState==='levelSetup'){
    const W=canvas.width,cx=W/2;
    const panelW=Math.min(500,W*0.8),panelX=cx-panelW/2;
    const pct=clamp((mouse.x-panelX)/panelW,0,1);
    if(editorSliderDrag==='width') editorWorldW=Math.round((400+pct*(4500-400))/100)*100;
    else if(editorSliderDrag==='height') editorWorldH=Math.round((400+pct*(4500-400))/100)*100;
    else if(editorSliderDrag==='seconds') editorWinSeconds=Math.round(10+pct*(180-10));
  }
```

Add mouseup to release slider drag. Find the existing `canvas.addEventListener('mouseup'` or the click handler. At the top of the click handler, add:

```javascript
  if(editorSliderDrag){editorSliderDrag=null;return;}
```

- [ ] **Step 4: Wire levelSetup into render loop and key handlers**

Add render block after customSelect:
```javascript
  } else if(gameState==='levelSetup'){
    drawLevelSetup();
```

Add ESC handler: in the key handler, add:
```javascript
    if(gameState==='levelSetup'){K['Space']=false;gameState='customSelect';SFX.select();return;}
```

- [ ] **Step 5: Add CREATE NEW button to customSelect**

In `drawCustomSelect()`, add a "CREATE NEW" button above the pack list. Find the `const startY=90;` line and change it to `const startY=140;`. Before the pack list loop, add:

```javascript
  const newW=200,newH=40,newX=cx-newW/2,newY=82;
  const nhov=mouse.x>newX&&mouse.x<newX+newW&&mouse.y>newY&&mouse.y<newY+newH;
  ctx.shadowBlur=nhov?18:6;ctx.shadowColor='#00ff88';
  ctx.fillStyle=nhov?'#00ff88':'rgba(0,40,20,0.6)';
  roundRect(ctx,newX,newY,newW,newH,6);ctx.fill();
  ctx.strokeStyle=nhov?'#00ff88':'rgba(0,180,100,0.5)';ctx.lineWidth=1.5;
  roundRect(ctx,newX,newY,newW,newH,6);ctx.stroke();ctx.shadowBlur=0;
  ctx.textAlign='center';ctx.font='bold 13px "Courier New"';ctx.fillStyle=nhov?'#000':'#00ff88';
  ctx.fillText('+ CREATE NEW',cx,newY+newH/2+5);
```

In the customSelect click handler, add the CREATE NEW click check before the pack loop:

```javascript
    const newW=200,newH=40,newX=cx-newW/2,newY=82;
    if(mouse.x>newX&&mouse.x<newX+newW&&mouse.y>newY&&mouse.y<newY+newH){
      editorPack=null;editorLevelName='Untitled Level';editorWorldW=2600;editorWorldH=1700;
      editorWinCondition='killAll';editorWinSeconds=60;
      gameState='levelSetup';SFX.select();return;
    }
```

Also update the pack list `startY` in the click handler to match: change `const startY=90;` to `const startY=140;`.

- [ ] **Step 6: Commit**
```bash
git add index.php && git commit -m "feat: levelSetup wizard — name, world size, win condition, CREATE NEW button"
```

---

### Task 3: Level editor grid + camera + item placement

**Files:**
- Modify: `F:\PATROL WING\.worktrees\level-editor\index.php`

This is the core editor canvas. It renders the grid, handles camera panning, shows ghost previews, and places/removes items on click.

- [ ] **Step 1: Add `drawLevelEditor()` function**

Add after `drawLevelSetup()`. This is a large function. It renders the sidebar, grid, placed items, ghost preview, and top-right buttons.

```javascript
function drawLevelEditor(){
  const W=canvas.width,H=canvas.height;
  const sideW=180;
  // Grid area
  ctx.fillStyle='#060c18';ctx.fillRect(0,0,W,H);
  ctx.save();ctx.beginPath();ctx.rect(sideW,0,W-sideW,H);ctx.clip();
  // Grid lines
  const gs=50;
  ctx.strokeStyle='rgba(0,80,180,0.12)';ctx.lineWidth=1;
  for(let gx=0;gx<=editorWorldW;gx+=gs){
    const sx=gx-editorCamX+sideW;
    if(sx<sideW-1||sx>W+1)continue;
    ctx.beginPath();ctx.moveTo(sx,0);ctx.lineTo(sx,H);ctx.stroke();
  }
  for(let gy=0;gy<=editorWorldH;gy+=gs){
    const sy=gy-editorCamY;
    if(sy<-1||sy>H+1)continue;
    ctx.beginPath();ctx.moveTo(sideW,sy);ctx.lineTo(W,sy);ctx.stroke();
  }
  // World bounds
  ctx.strokeStyle='rgba(0,180,255,0.4)';ctx.lineWidth=2;
  ctx.strokeRect(-editorCamX+sideW,-editorCamY,editorWorldW,editorWorldH);

  // Placed items
  const GLYPHS_E={scout:'S',guard:'G',turret:'T',boss:'B',dart:'D',wraith:'W',brute:'Br',phantom:'Ph',ravager:'Rv',splitter:'Sp',cloaker:'Ck',demolisher:'Dm',hunter:'Hn',dreadnought:'Dr',harbinger:'Hb',shard:'Sh'};
  for(const item of editorPlacedItems){
    const sx=item.x-editorCamX+sideW,sy=item.y-editorCamY;
    if(sx<sideW-50||sx>W+50||sy<-50||sy>H+50)continue;
    if(item.cat==='obstacle'){
      ctx.fillStyle='rgba(80,100,120,0.6)';
      if(item.subtype==='pillar'){ctx.beginPath();ctx.arc(sx,sy,item.r||35,0,Math.PI*2);ctx.fill();ctx.strokeStyle='rgba(120,150,180,0.5)';ctx.lineWidth=1;ctx.stroke();}
      else{ctx.fillRect(sx,sy,item.w||26,item.h||100);ctx.strokeStyle='rgba(120,150,180,0.5)';ctx.lineWidth=1;ctx.strokeRect(sx,sy,item.w||26,item.h||100);}
    } else if(item.cat==='enemy'){
      const col=ETYPES[item.subtype]?ETYPES[item.subtype].color:'#ff4444';
      ctx.fillStyle=col;ctx.beginPath();ctx.arc(sx,sy,10,0,Math.PI*2);ctx.fill();
      ctx.font='bold 8px "Courier New"';ctx.fillStyle='#fff';ctx.textAlign='center';
      ctx.fillText(GLYPHS_E[item.subtype]||'?',sx,sy+3);
    } else if(item.cat==='pickup'){
      const col=PTYPES[item.subtype]?PTYPES[item.subtype].color:'#ffee00';
      ctx.fillStyle=col;ctx.save();ctx.translate(sx,sy);ctx.rotate(Math.PI/4);
      ctx.fillRect(-6,-6,12,12);ctx.restore();
      ctx.font='bold 8px "Courier New"';ctx.fillStyle='#000';ctx.textAlign='center';
      ctx.fillText(item.subtype[0].toUpperCase(),sx,sy+3);
    } else if(item.cat==='hazard'){
      if(item.subtype==='zap_pylon'){
        ctx.fillStyle='#ffdd00';
        const dx=Math.cos(item.angle||0)*(item.gap||120)/2,dy=Math.sin(item.angle||0)*(item.gap||120)/2;
        ctx.beginPath();ctx.arc(sx-dx,sy-dy,6,0,Math.PI*2);ctx.fill();
        ctx.beginPath();ctx.arc(sx+dx,sy+dy,6,0,Math.PI*2);ctx.fill();
        ctx.strokeStyle='rgba(255,220,0,0.4)';ctx.lineWidth=2;
        ctx.beginPath();ctx.moveTo(sx-dx,sy-dy);ctx.lineTo(sx+dx,sy+dy);ctx.stroke();
      } else {
        ctx.fillStyle='#ff2200';ctx.beginPath();ctx.arc(sx,sy,8,0,Math.PI*2);ctx.fill();
        ctx.font='bold 8px "Courier New"';ctx.fillStyle='#fff';ctx.textAlign='center';ctx.fillText('M',sx,sy+3);
      }
    } else if(item.cat==='objective'){
      if(item.subtype==='finish'){ctx.strokeStyle='#ffdd00';ctx.lineWidth=3;ctx.beginPath();ctx.moveTo(sx,0);ctx.lineTo(sx,H);ctx.stroke();}
      else if(item.subtype==='key'){ctx.font='bold 16px "Courier New"';ctx.fillStyle='#ffdd00';ctx.textAlign='center';ctx.fillText('K',sx,sy+5);}
      else if(item.subtype==='item'){ctx.fillStyle='#ff8800';ctx.save();ctx.translate(sx,sy);ctx.beginPath();ctx.moveTo(0,-10);ctx.lineTo(8,0);ctx.lineTo(0,10);ctx.lineTo(-8,0);ctx.closePath();ctx.fill();ctx.restore();}
      else if(item.subtype==='goal'){ctx.strokeStyle='#00ff88';ctx.lineWidth=2;ctx.beginPath();ctx.arc(sx,sy,30,0,Math.PI*2);ctx.stroke();ctx.font='9px "Courier New"';ctx.fillStyle='#00ff88';ctx.textAlign='center';ctx.fillText('GOAL',sx,sy+42);}
    }
  }
  // Spawn marker
  const spx=editorSpawnX-editorCamX+sideW,spy=editorSpawnY-editorCamY;
  ctx.strokeStyle='#00ddff';ctx.lineWidth=2;
  ctx.beginPath();ctx.moveTo(spx-12,spy);ctx.lineTo(spx+12,spy);ctx.stroke();
  ctx.beginPath();ctx.moveTo(spx,spy-12);ctx.lineTo(spx,spy+12);ctx.stroke();
  ctx.beginPath();ctx.arc(spx,spy,8,0,Math.PI*2);ctx.stroke();
  ctx.font='9px "Courier New"';ctx.fillStyle='#00ddff';ctx.textAlign='center';ctx.fillText('SPAWN',spx,spy+22);

  // Ghost preview at cursor
  if(editorTool&&mouse.x>sideW){
    const gx=Math.round((mouse.x-sideW+editorCamX)/50)*50;
    const gy=Math.round((mouse.y+editorCamY)/50)*50;
    const gsx=gx-editorCamX+sideW,gsy=gy-editorCamY;
    ctx.globalAlpha=0.4;
    ctx.fillStyle='#00ccff';ctx.beginPath();ctx.arc(gsx,gsy,8,0,Math.PI*2);ctx.fill();
    ctx.globalAlpha=1;
  }
  ctx.restore(); // unclip

  // Sidebar
  _drawEditorSidebar(sideW,H);

  // Top-right buttons
  ctx.textAlign='center';
  const tbtnW=80,tbtnH=32,tgap=8,tmargin=10;
  // SAVE
  const saveX=W-tmargin-tbtnW,saveY=tmargin;
  const savHov=mouse.x>saveX&&mouse.x<saveX+tbtnW&&mouse.y>saveY&&mouse.y<saveY+tbtnH;
  ctx.fillStyle=savHov?'#00ff88':'rgba(0,40,20,0.7)';roundRect(ctx,saveX,saveY,tbtnW,tbtnH,4);ctx.fill();
  ctx.strokeStyle='#00ff88';ctx.lineWidth=1;roundRect(ctx,saveX,saveY,tbtnW,tbtnH,4);ctx.stroke();
  ctx.font='bold 11px "Courier New"';ctx.fillStyle=savHov?'#000':'#00ff88';ctx.fillText('SAVE',saveX+tbtnW/2,saveY+tbtnH/2+4);
  // BACK
  const bkX=saveX-tbtnW-tgap,bkY=tmargin;
  const bkHov=mouse.x>bkX&&mouse.x<bkX+tbtnW&&mouse.y>bkY&&mouse.y<bkY+tbtnH;
  ctx.fillStyle=bkHov?'rgba(0,140,200,0.85)':'rgba(0,30,60,0.6)';roundRect(ctx,bkX,bkY,tbtnW,tbtnH,4);ctx.fill();
  ctx.strokeStyle='rgba(0,140,220,0.6)';ctx.lineWidth=1;roundRect(ctx,bkX,bkY,tbtnW,tbtnH,4);ctx.stroke();
  ctx.font='bold 11px "Courier New"';ctx.fillStyle=bkHov?'#000':'rgba(100,200,255,0.9)';ctx.fillText('BACK',bkX+tbtnW/2,bkY+tbtnH/2+4);
  // Item count
  ctx.textAlign='right';ctx.font='10px "Courier New"';ctx.fillStyle='rgba(100,160,220,0.6)';
  ctx.fillText(`${editorPlacedItems.length} items  |  ${editorLevel?editorLevel.name:''}`,W-tmargin,tmargin+tbtnH+18);
  ctx.textAlign='left';
}
```

- [ ] **Step 2: Add `_drawEditorSidebar(sideW,H)` helper**

Add before `drawLevelEditor()`:

```javascript
function _drawEditorSidebar(sideW,H){
  ctx.fillStyle='rgba(4,10,26,0.95)';ctx.fillRect(0,0,sideW,H);
  ctx.strokeStyle='rgba(0,100,180,0.3)';ctx.lineWidth=1;
  ctx.beginPath();ctx.moveTo(sideW,0);ctx.lineTo(sideW,H);ctx.stroke();
  ctx.textAlign='left';ctx.font='bold 11px "Courier New"';
  const cats=[
    {id:'obstacle',label:'OBSTACLES',items:[
      {tool:'pillar_s',label:'Pillar Small',cat:'obstacle',subtype:'pillar',r:26},
      {tool:'pillar_m',label:'Pillar Medium',cat:'obstacle',subtype:'pillar',r:35},
      {tool:'pillar_l',label:'Pillar Large',cat:'obstacle',subtype:'pillar',r:46},
      {tool:'wall_hs',label:'Wall H Short',cat:'obstacle',subtype:'wall',w:90,h:26},
      {tool:'wall_hl',label:'Wall H Long',cat:'obstacle',subtype:'wall',w:190,h:26},
      {tool:'wall_vs',label:'Wall V Short',cat:'obstacle',subtype:'wall',w:26,h:90},
      {tool:'wall_vl',label:'Wall V Long',cat:'obstacle',subtype:'wall',w:26,h:190},
    ]},
    {id:'enemy',label:'ENEMIES',items:Object.keys(ETYPES).map(k=>({tool:'enemy_'+k,label:k.toUpperCase(),cat:'enemy',subtype:k}))},
    {id:'pickup',label:'PICKUPS',items:Object.keys(PTYPES).filter(k=>k!=='nuke_key'&&k!=='points').map(k=>({tool:'pickup_'+k,label:k.toUpperCase(),cat:'pickup',subtype:k}))},
    {id:'hazard',label:'HAZARDS',items:[
      {tool:'zap_pylon',label:'Zap Pylon',cat:'hazard',subtype:'zap_pylon',angle:0,gap:120},
      {tool:'floor_mine',label:'Floor Mine',cat:'hazard',subtype:'floor_mine'},
    ]},
    {id:'objective',label:'OBJECTIVES',items:[
      {tool:'spawn',label:'Player Spawn',cat:'special',subtype:'spawn'},
      ...(editorWinCondition==='reachFinish'?[{tool:'obj_finish',label:'Finish Line',cat:'objective',subtype:'finish'}]:[]),
      ...(editorWinCondition==='collectAll'?[{tool:'obj_key',label:'Key',cat:'objective',subtype:'key'}]:[]),
      ...(editorWinCondition==='retrieve'?[{tool:'obj_item',label:'Item',cat:'objective',subtype:'item'},{tool:'obj_goal',label:'Goal Zone',cat:'objective',subtype:'goal'}]:[]),
    ]},
    {id:'tools',label:'TOOLS',items:[{tool:'eraser',label:'Eraser',cat:'special',subtype:'eraser'}]},
  ];
  let cy=10-editorSidebarScroll;
  for(const cat of cats){
    const expanded=editorExpandedCat===cat.id;
    const hov=mouse.x<sideW&&mouse.y>cy&&mouse.y<cy+24;
    ctx.fillStyle=hov?'rgba(0,80,160,0.5)':'rgba(0,40,80,0.3)';
    ctx.fillRect(2,cy,sideW-4,24);
    ctx.fillStyle=expanded?'#00ccff':'rgba(120,180,240,0.8)';
    ctx.font='bold 11px "Courier New"';
    ctx.fillText((expanded?'v ':'> ')+cat.label,8,cy+16);
    cy+=26;
    if(expanded){
      for(const item of cat.items){
        const ihov=mouse.x<sideW&&mouse.y>cy&&mouse.y<cy+22;
        const sel=editorTool===item.tool;
        ctx.fillStyle=sel?'rgba(0,100,50,0.6)':ihov?'rgba(0,50,100,0.5)':'transparent';
        ctx.fillRect(4,cy,sideW-8,22);
        if(sel){ctx.strokeStyle='#00ff88';ctx.lineWidth=1;ctx.strokeRect(4,cy,sideW-8,22);}
        ctx.font='10px "Courier New"';
        ctx.fillStyle=sel?'#00ff88':ihov?'#ffffff':'rgba(150,190,230,0.8)';
        ctx.fillText('  '+item.label,10,cy+15);
        cy+=24;
      }
    }
  }
}
```

- [ ] **Step 3: Add editor click handler**

Add in click dispatch before `gameState==='levelSetup'`:

```javascript
  if(gameState==='levelEditor'){
    const W=canvas.width,H=canvas.height,sideW=180;
    // Top-right buttons
    const tbtnW=80,tbtnH=32,tgap=8,tmargin=10;
    const saveX=W-tmargin-tbtnW,saveY=tmargin;
    if(mouse.x>saveX&&mouse.x<saveX+tbtnW&&mouse.y>saveY&&mouse.y<saveY+tbtnH){
      _editorSave();return;
    }
    const bkX=saveX-tbtnW-tgap,bkY=tmargin;
    if(mouse.x>bkX&&mouse.x<bkX+tbtnW&&mouse.y>bkY&&mouse.y<bkY+tbtnH){
      gameState='customSelect';SFX.select();return;
    }
    // Sidebar clicks
    if(mouse.x<sideW){
      let cy=10-editorSidebarScroll;
      const cats=['obstacle','enemy','pickup','hazard','objective','tools'];
      const catLabels={obstacle:'OBSTACLES',enemy:'ENEMIES',pickup:'PICKUPS',hazard:'HAZARDS',objective:'OBJECTIVES',tools:'TOOLS'};
      // Rebuild items for click detection (same structure as draw)
      const allCats=_getEditorCategories();
      for(const cat of allCats){
        if(mouse.y>cy&&mouse.y<cy+24){
          editorExpandedCat=editorExpandedCat===cat.id?'':cat.id;SFX.select();return;
        }
        cy+=26;
        if(editorExpandedCat===cat.id){
          for(const item of cat.items){
            if(mouse.y>cy&&mouse.y<cy+22){editorTool=item.tool;SFX.select();return;}
            cy+=24;
          }
        }
      }
      return;
    }
    // Grid click — place or remove
    if(mouse.x>sideW){
      const gx=Math.round((mouse.x-sideW+editorCamX)/50)*50;
      const gy=Math.round((mouse.y+editorCamY)/50)*50;
      if(gx<0||gx>editorWorldW||gy<0||gy>editorWorldH)return;
      const toolDef=_findToolDef(editorTool);
      if(!toolDef)return;
      if(toolDef.cat==='special'&&toolDef.subtype==='spawn'){
        editorSpawnX=gx;editorSpawnY=gy;editorDirty=true;SFX.select();return;
      }
      if(toolDef.cat==='special'&&toolDef.subtype==='eraser'){
        for(let i=editorPlacedItems.length-1;i>=0;i--){
          if(dist(editorPlacedItems[i].x,editorPlacedItems[i].y,gx,gy)<30){
            editorPlacedItems.splice(i,1);editorDirty=true;SFX.select();return;
          }
        }
        return;
      }
      editorPlacedItems.push({...toolDef,x:gx,y:gy});
      editorDirty=true;SFX.select();
    }
    return;
  }
```

- [ ] **Step 4: Add helper functions**

Add after `_drawEditorSidebar`:

```javascript
function _getEditorCategories(){
  return [
    {id:'obstacle',items:[
      {tool:'pillar_s',label:'Pillar Small',cat:'obstacle',subtype:'pillar',r:26},
      {tool:'pillar_m',label:'Pillar Medium',cat:'obstacle',subtype:'pillar',r:35},
      {tool:'pillar_l',label:'Pillar Large',cat:'obstacle',subtype:'pillar',r:46},
      {tool:'wall_hs',label:'Wall H Short',cat:'obstacle',subtype:'wall',w:90,h:26},
      {tool:'wall_hl',label:'Wall H Long',cat:'obstacle',subtype:'wall',w:190,h:26},
      {tool:'wall_vs',label:'Wall V Short',cat:'obstacle',subtype:'wall',w:26,h:90},
      {tool:'wall_vl',label:'Wall V Long',cat:'obstacle',subtype:'wall',w:26,h:190},
    ]},
    {id:'enemy',items:Object.keys(ETYPES).map(k=>({tool:'enemy_'+k,label:k.toUpperCase(),cat:'enemy',subtype:k}))},
    {id:'pickup',items:Object.keys(PTYPES).filter(k=>k!=='nuke_key'&&k!=='points').map(k=>({tool:'pickup_'+k,label:k.toUpperCase(),cat:'pickup',subtype:k}))},
    {id:'hazard',items:[
      {tool:'zap_pylon',label:'Zap Pylon',cat:'hazard',subtype:'zap_pylon',angle:0,gap:120},
      {tool:'floor_mine',label:'Floor Mine',cat:'hazard',subtype:'floor_mine'},
    ]},
    {id:'objective',items:[
      {tool:'spawn',label:'Player Spawn',cat:'special',subtype:'spawn'},
      ...(editorWinCondition==='reachFinish'?[{tool:'obj_finish',label:'Finish Line',cat:'objective',subtype:'finish'}]:[]),
      ...(editorWinCondition==='collectAll'?[{tool:'obj_key',label:'Key',cat:'objective',subtype:'key'}]:[]),
      ...(editorWinCondition==='retrieve'?[{tool:'obj_item',label:'Item',cat:'objective',subtype:'item'},{tool:'obj_goal',label:'Goal Zone',cat:'objective',subtype:'goal'}]:[]),
    ]},
    {id:'tools',items:[{tool:'eraser',label:'Eraser',cat:'special',subtype:'eraser'}]},
  ];
}
function _findToolDef(toolId){
  for(const cat of _getEditorCategories()){
    for(const item of cat.items){if(item.tool===toolId)return item;}
  }
  return null;
}
function _editorSave(){
  if(editorPlacedItems.length===0){weaponFlash={prefix:'',name:'Place at least one item first',ms:2000};return;}
  const lv=editorLevel;
  lv.spawnX=editorSpawnX;lv.spawnY=editorSpawnY;
  lv.obstacles=[];lv.enemies=[];lv.pickups=[];lv.hazards=[];lv.objectives=[];
  for(const item of editorPlacedItems){
    if(item.cat==='obstacle'){
      if(item.subtype==='pillar') lv.obstacles.push({type:'pillar',x:item.x,y:item.y,r:item.r});
      else lv.obstacles.push({type:'wall',x:item.x,y:item.y,w:item.w,h:item.h});
    } else if(item.cat==='enemy'){
      lv.enemies.push({type:item.subtype,x:item.x,y:item.y});
    } else if(item.cat==='pickup'){
      lv.pickups.push({type:item.subtype,x:item.x,y:item.y,hidden:false});
    } else if(item.cat==='hazard'){
      lv.hazards.push({type:item.subtype,x:item.x,y:item.y,angle:item.angle||0,gap:item.gap||120});
    } else if(item.cat==='objective'){
      lv.objectives.push({type:item.subtype,x:item.x,y:item.y});
    }
  }
  editorPack.levels.push(lv);
  editorDirty=false;
  gameState='levelSavePrompt';SFX.confirm();
}
```

- [ ] **Step 5: Add keyboard handler for camera panning**

In the main game loop tick section, add a block for `levelEditor` camera panning. Find where other state-specific ticks happen. Add:

```javascript
  if(gameState==='levelEditor'){
    const panSpd=8;
    if(K['ArrowLeft']||K['KeyA'])editorCamX=Math.max(0,editorCamX-panSpd);
    if(K['ArrowRight']||K['KeyD'])editorCamX=Math.min(Math.max(0,editorWorldW-canvas.width+180),editorCamX+panSpd);
    if(K['ArrowUp']||K['KeyW'])editorCamY=Math.max(0,editorCamY-panSpd);
    if(K['ArrowDown']||K['KeyS'])editorCamY=Math.min(Math.max(0,editorWorldH-canvas.height),editorCamY+panSpd);
  }
```

- [ ] **Step 6: Add right-click delete in editor**

In the existing right-click mousedown handler, add a check for levelEditor state. Find the `canvas.addEventListener('mousedown'` handler. Add at the top:

```javascript
  if(gameState==='levelEditor'&&e.button===2){
    const sideW=180;
    if(mouse.x>sideW){
      const gx=Math.round((mouse.x-sideW+editorCamX)/50)*50;
      const gy=Math.round((mouse.y+editorCamY)/50)*50;
      for(let i=editorPlacedItems.length-1;i>=0;i--){
        if(dist(editorPlacedItems[i].x,editorPlacedItems[i].y,gx,gy)<30){
          editorPlacedItems.splice(i,1);editorDirty=true;SFX.select();break;
        }
      }
    }
    return;
  }
```

- [ ] **Step 7: Wire levelEditor into render loop**

Add after levelSetup render block:
```javascript
  } else if(gameState==='levelEditor'){
    drawLevelEditor();
```

Add ESC handler to return to customSelect:
```javascript
    if(gameState==='levelEditor'){K['Escape']=false;gameState='customSelect';SFX.select();return;}
```

- [ ] **Step 8: Add sidebar scroll via mouse wheel**

Find the existing wheel event listener (or add one). Add:

```javascript
canvas.addEventListener('wheel',(e)=>{
  if(gameState==='levelEditor'&&mouse.x<180){
    editorSidebarScroll=Math.max(0,editorSidebarScroll+e.deltaY*0.5);
    e.preventDefault();
  }
},{passive:false});
```

- [ ] **Step 9: Commit**
```bash
git add index.php && git commit -m "feat: levelEditor state — grid, sidebar, item placement, camera panning"
```

---

### Task 4: Save prompt + pack flow

**Files:**
- Modify: `F:\PATROL WING\.worktrees\level-editor\index.php`

- [ ] **Step 1: Add `drawLevelSavePrompt()` function**

Add after `_editorSave()`:

```javascript
function drawLevelSavePrompt(){
  const W=canvas.width,H=canvas.height,cx=W/2;
  ctx.fillStyle='#060c18';ctx.fillRect(0,0,W,H);
  ctx.textAlign='center';
  ctx.font='bold 24px "Courier New"';ctx.fillStyle='#00ff88';ctx.shadowBlur=20;ctx.shadowColor='#00ff88';
  ctx.fillText('LEVEL SAVED',cx,H*0.2);ctx.shadowBlur=0;
  ctx.font='14px "Courier New"';ctx.fillStyle='rgba(100,200,255,0.8)';
  ctx.fillText(`"${editorLevel?editorLevel.name:''}" added to "${editorPack?editorPack.packName:''}"`,cx,H*0.2+34);
  ctx.font='12px "Courier New"';ctx.fillStyle='rgba(150,180,220,0.6)';
  const lv=editorLevel;
  if(lv) ctx.fillText(`${lv.worldW}x${lv.worldH}  |  ${editorPlacedItems.filter(i=>i.cat==='enemy').length} enemies  |  ${lv.winCondition}`,cx,H*0.2+60);
  ctx.fillText(`Pack total: ${editorPack?editorPack.levels.length:0} level${editorPack&&editorPack.levels.length!==1?'s':''}`,cx,H*0.2+80);

  const btnW=260,btnH=46,gap=16;
  const addY=H*0.5;
  const ahov=mouse.x>cx-btnW/2&&mouse.x<cx+btnW/2&&mouse.y>addY&&mouse.y<addY+btnH;
  ctx.shadowBlur=ahov?22:8;ctx.shadowColor='#00ccff';
  ctx.fillStyle=ahov?'rgba(0,140,200,0.85)':'rgba(0,0,0,0.65)';
  roundRect(ctx,cx-btnW/2,addY,btnW,btnH,8);ctx.fill();
  ctx.strokeStyle=ahov?'#00eeff':'rgba(0,140,220,0.6)';ctx.lineWidth=1.5;
  roundRect(ctx,cx-btnW/2,addY,btnW,btnH,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 13px "Courier New"';ctx.fillStyle=ahov?'#000':'rgba(100,200,255,0.9)';
  ctx.fillText('+ ADD ANOTHER LEVEL',cx,addY+btnH/2+5);

  const doneY=addY+btnH+gap;
  const dhov=mouse.x>cx-btnW/2&&mouse.x<cx+btnW/2&&mouse.y>doneY&&mouse.y<doneY+btnH;
  ctx.shadowBlur=dhov?24:10;ctx.shadowColor='#00ff88';
  ctx.fillStyle=dhov?'#00ff88':'rgba(0,0,0,0.7)';
  roundRect(ctx,cx-btnW/2,doneY,btnW,btnH,8);ctx.fill();
  ctx.strokeStyle='#00ff88';ctx.lineWidth=2;
  roundRect(ctx,cx-btnW/2,doneY,btnW,btnH,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 13px "Courier New"';ctx.fillStyle=dhov?'#000':'#00ff88';
  ctx.fillText('DONE - SAVE PACK',cx,doneY+btnH/2+5);
  ctx.textAlign='left';
}
```

- [ ] **Step 2: Add click handler**

Add in click dispatch before `gameState==='levelEditor'`:

```javascript
  if(gameState==='levelSavePrompt'){
    const cx=canvas.width/2,H=canvas.height;
    const btnW=260,btnH=46,gap=16;
    const addY=H*0.5;
    if(mouse.x>cx-btnW/2&&mouse.x<cx+btnW/2&&mouse.y>addY&&mouse.y<addY+btnH){
      // Add another level
      editorLevelName='Untitled Level';editorWorldW=2600;editorWorldH=1700;
      editorWinCondition='killAll';editorWinSeconds=60;
      gameState='levelSetup';SFX.select();return;
    }
    const doneY=addY+btnH+gap;
    if(mouse.x>cx-btnW/2&&mouse.x<cx+btnW/2&&mouse.y>doneY&&mouse.y<doneY+btnH){
      // Save pack
      const packs=_loadCustomLevels();
      packs.push(editorPack);
      _saveCustomLevels(packs);
      editorPack=null;editorLevel=null;editorPlacedItems=[];
      gameState='customSelect';SFX.confirm();return;
    }
    return;
  }
```

- [ ] **Step 3: Wire into render loop and key handlers**

Render:
```javascript
  } else if(gameState==='levelSavePrompt'){
    drawLevelSavePrompt();
```

Space/ESC:
```javascript
    if(gameState==='levelSavePrompt'){K['Space']=false;return;} // block space, must click
```

- [ ] **Step 4: Commit**
```bash
git add index.php && git commit -m "feat: levelSavePrompt — add another level or finalize pack"
```

---

### Task 5: Enhanced customSelect with expandable packs + per-level actions

**Files:**
- Modify: `F:\PATROL WING\.worktrees\level-editor\index.php`

- [ ] **Step 1: Rewrite `drawCustomSelect()` with expandable packs**

Replace the entire `drawCustomSelect()` function. The new version shows packs as expandable cards. Clicking a pack expands it to show individual levels with PLAY/COPY/EDIT/DELETE buttons. Bottom section shows LEADERBOARD and AWARDS stubs when a level is selected.

This is a large rewrite. Read the current function first, then replace it entirely with a version that:
- Adds "CREATE NEW" button at top (already partially done in Task 2)
- Pack cards: click to expand/collapse (`customSelectExpanded` index)
- When expanded: show level sub-rows indented with name + win condition
- Per-level buttons: PLAY, COPY TO, EDIT, DEL
- LEADERBOARD and AWARDS placeholder panels at bottom when a level is selected
- Back button at bottom

The exact code is too long to include in the plan but the implementer should:
1. Read current `drawCustomSelect` (lines 7429-7477)
2. Replace with expanded version that uses `customSelectExpanded` and `customSelectSelectedLevel` state
3. Pack header click toggles `customSelectExpanded=i` / `-1`
4. Level sub-rows shown when pack is expanded
5. Bottom panels shown when `customSelectSelectedLevel >= 0`

- [ ] **Step 2: Rewrite customSelect click handler to match**

Replace the click handler to support:
- CREATE NEW (already added)
- Pack header click: toggle expand
- Level PLAY: load single level
- Level COPY TO: for now, copy to a new pack (the destination picker is a v2 refinement)
- Level EDIT: load into editor
- Level DEL: remove from pack, remove pack if empty
- Pack DEL: remove entire pack
- Back button

- [ ] **Step 3: Add edit-existing-level support**

When EDIT is clicked on a level, populate `editorLevel` and `editorPlacedItems` from the stored JSON, then transition to `'levelEditor'`. Add a helper:

```javascript
function _loadLevelIntoEditor(lv,packIdx,levelIdx){
  editorLevel={...lv};
  editorLevelName=lv.name||'Untitled';
  editorWorldW=lv.worldW||2600;editorWorldH=lv.worldH||1700;
  editorWinCondition=lv.winCondition||'killAll';
  editorWinSeconds=(lv.winParams&&lv.winParams.seconds)||60;
  editorSpawnX=lv.spawnX||200;editorSpawnY=lv.spawnY||200;
  editorCamX=0;editorCamY=0;editorTool='';editorExpandedCat='';
  editorPlacedItems=[];
  if(lv.obstacles) for(const o of lv.obstacles){
    if(o.type==='pillar') editorPlacedItems.push({cat:'obstacle',subtype:'pillar',x:o.x,y:o.y,r:o.r||35});
    else editorPlacedItems.push({cat:'obstacle',subtype:'wall',x:o.x,y:o.y,w:o.w||26,h:o.h||100});
  }
  if(lv.enemies) for(const e of lv.enemies) editorPlacedItems.push({cat:'enemy',subtype:e.type,x:e.x,y:e.y});
  if(lv.pickups) for(const p of lv.pickups) editorPlacedItems.push({cat:'pickup',subtype:p.type,x:p.x,y:p.y});
  if(lv.hazards) for(const h of lv.hazards) editorPlacedItems.push({cat:'hazard',subtype:h.type,x:h.x,y:h.y,angle:h.angle||0,gap:h.gap||120});
  if(lv.objectives) for(const o of lv.objectives) editorPlacedItems.push({cat:'objective',subtype:o.type,x:o.x,y:o.y});
  editorDirty=false;
  // Track which pack/level we're editing for in-place save
  editorLevel._editPackIdx=packIdx;
  editorLevel._editLevelIdx=levelIdx;
}
```

Modify `_editorSave()` to detect edit-mode (when `_editPackIdx` is set) and update in-place instead of pushing a new level:

```javascript
// At the start of _editorSave, after building lv:
if(lv._editPackIdx!==undefined){
  const packs=_loadCustomLevels();
  const pi=lv._editPackIdx,li=lv._editLevelIdx;
  delete lv._editPackIdx;delete lv._editLevelIdx;
  if(packs[pi]&&packs[pi].levels[li]){
    packs[pi].levels[li]=lv;
    _saveCustomLevels(packs);
    editorPack=null;editorLevel=null;editorPlacedItems=[];
    gameState='customSelect';SFX.confirm();return;
  }
}
```

- [ ] **Step 4: Commit**
```bash
git add index.php && git commit -m "feat: enhanced customSelect — expandable packs, PLAY/COPY/EDIT/DEL, leaderboard+awards stubs"
```

---

## Unresolved Questions

None.
