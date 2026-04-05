# Hangar Horizontal Scroll Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert the Aircraft Hangar craft selection to a horizontally scrollable row showing 4 craft at a time, supporting 7+ craft with ◀▶ arrow buttons and a dot position indicator.

**Architecture:** All changes are in `index.php`. Add `HANGAR_VISIBLE` constant and `hangarScroll` offset variable; update `_hangarLayout()` to center on the visible window and add arrow geometry; update `drawHangarScreen()` to render visible window, arrows, and dots; restrict hover and click detection to visible window; reset scroll on game start.

**Tech Stack:** Vanilla JS, HTML5 Canvas 2D, single-file game.

---

## File Map

- Modify: `index.php` — all changes in this file.

Key locations:
- Line 511: after `CRAFTS` array closing `];` — add `HANGAR_VISIBLE` constant
- Line 537: `let hangarCraft=0,hangarColor='#00ddff';` — add `hangarScroll=0`
- Line 3613–3618: bottom of `_hangarLayout()` — change `startX`, add arrow geometry, expand return
- Line 3622: `drawHangarScreen()` destructure — add new fields
- Line 3645–3648: live preview dispatch — add stubs for new craft IDs
- Line 3654: card row draw — restrict to visible window, add arrows and dots
- Line 4834–4852: `startCombatTraining()` — reset `hangarScroll`
- Line 5917–5927: `startBattle()` — reset `hangarScroll`
- Line 6172: hangar click handler destructure — add new fields
- Line 6173–6177: hangar click card loop — add arrow clicks, restrict to visible window
- Line 6326–6329: hangar hover detection — restrict to visible window

---

## Task 1: Add constant, state variable, and update `_hangarLayout()`

**Files:** Modify `index.php`

- [ ] **Step 1: Read lines 510–512 and 535–538**

  Confirm exact text of `CRAFTS` closing bracket and `hangarCraft` declaration before editing.

- [ ] **Step 2: Add `HANGAR_VISIBLE` constant after the `CRAFTS` array**

  Read lines 510–512, then directly after the `];` that closes `CRAFTS` (line 511), insert:
  ```javascript
  const HANGAR_VISIBLE=4;
  ```

  The result at that location should look like:
  ```javascript
  ];
  const HANGAR_VISIBLE=4;

  // ─── SELECTION STATE ─────────────────────────────────────────────
  ```

- [ ] **Step 3: Add `hangarScroll` to the hangar state variable declaration**

  Replace:
  ```javascript
  let hangarCraft=0,hangarColor='#00ddff';
  ```
  With:
  ```javascript
  let hangarCraft=0,hangarColor='#00ddff',hangarScroll=0;
  ```

- [ ] **Step 4: Read lines 3611–3619 of `_hangarLayout()`**

  Get exact current text of the `spacing`, `startX`, and `return` lines.

- [ ] **Step 5: Update `_hangarLayout()` — new `startX`, add arrow geometry, expand return**

  Replace:
  ```javascript
    const spacing=Math.min(220,W*0.22);
    const startX=cx-(CRAFTS.length-1)*spacing/2;
    const cancelW=Math.min(160,W*0.22), saveW=Math.min(220,W*0.3);
    const totalBtnW=cancelW+saveW+36;
    const cancelX=cx-totalBtnW/2, saveX=cancelX+cancelW+36;
    return {W,H,cx,btnH,btnY,previewSize,previewCY,cardsCY,swatchLabelY,swatchR,rowW,itemStep,rowStartX,swatchCY,spacing,startX,cancelW,saveW,cancelX,saveX};
  ```
  With:
  ```javascript
    const spacing=Math.min(220,W*0.22);
    const startX=cx-(HANGAR_VISIBLE-1)*spacing/2;
    const arrowW=36,arrowH=cardH;
    const arrowLX=startX-arrowW-12, arrowRX=startX+(HANGAR_VISIBLE-1)*spacing+spacing/2+12;
    const cancelW=Math.min(160,W*0.22), saveW=Math.min(220,W*0.3);
    const totalBtnW=cancelW+saveW+36;
    const cancelX=cx-totalBtnW/2, saveX=cancelX+cancelW+36;
    return {W,H,cx,btnH,btnY,previewSize,previewCY,cardsCY,swatchLabelY,swatchR,rowW,itemStep,rowStartX,swatchCY,spacing,startX,arrowW,arrowH,arrowLX,arrowRX,cancelW,saveW,cancelX,saveX};
  ```

---

## Task 2: Update `drawHangarScreen()` — visible window, arrows, dots, preview stubs

**Files:** Modify `index.php`

- [ ] **Step 1: Read line 3622**

  Get exact current destructure line from `_hangarLayout()` in `drawHangarScreen()`.

- [ ] **Step 2: Expand the destructure in `drawHangarScreen()`**

  Replace:
  ```javascript
    const {W,H,cx,btnH,btnY,previewSize,previewCY,cardsCY,swatchLabelY,swatchR,rowW,itemStep,rowStartX,swatchCY,spacing,startX,cancelW,saveW,cancelX,saveX}=_hangarLayout();
  ```
  With:
  ```javascript
    const {W,H,cx,btnH,btnY,previewSize,previewCY,cardsCY,swatchLabelY,swatchR,rowW,itemStep,rowStartX,swatchCY,spacing,startX,arrowW,arrowH,arrowLX,arrowRX,cancelW,saveW,cancelX,saveX}=_hangarLayout();
  ```

- [ ] **Step 3: Read lines 3644–3654**

  Get exact current text of the preview dispatch and card row draw.

- [ ] **Step 4: Update the live preview dispatch to add stubs for new craft IDs**

  Replace:
  ```javascript
    if(craft.id==='phantom')drawPhantom(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
    else if(craft.id==='viper')drawViper(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
    else if(craft.id==='titan')drawTitan(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
    else drawSpecter(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
  ```
  With:
  ```javascript
    if(craft.id==='phantom')drawPhantom(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
    else if(craft.id==='viper')drawViper(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
    else if(craft.id==='titan')drawTitan(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
    else if(craft.id==='specter')drawSpecter(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
    else drawEnemyDrone(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
  ```

- [ ] **Step 5: Replace the card row draw with visible-window rendering, arrows, and dots**

  Replace:
  ```javascript
    CRAFTS.forEach((_,i)=>drawCraftCard(CRAFTS[i],startX+i*spacing,cardsCY,i,hoverCard,hangarCraft));
  ```
  With:
  ```javascript
    // Cards — visible window only
    for(let i=hangarScroll;i<Math.min(hangarScroll+HANGAR_VISIBLE,CRAFTS.length);i++){
      drawCraftCard(CRAFTS[i],startX+(i-hangarScroll)*spacing,cardsCY,i,hoverCard,hangarCraft);
    }
    // Scroll arrows
    const aDisL=hangarScroll===0;
    const aDisR=hangarScroll>=CRAFTS.length-HANGAR_VISIBLE;
    const aLhov=!aDisL&&mouse.x>arrowLX&&mouse.x<arrowLX+arrowW&&mouse.y>cardsCY-arrowH/2&&mouse.y<cardsCY+arrowH/2;
    const aRhov=!aDisR&&mouse.x>arrowRX&&mouse.x<arrowRX+arrowW&&mouse.y>cardsCY-arrowH/2&&mouse.y<cardsCY+arrowH/2;
    roundRect(ctx,arrowLX,cardsCY-arrowH/2,arrowW,arrowH,6);
    ctx.fillStyle=aDisL?'rgba(30,30,60,0.3)':aLhov?'rgba(0,200,255,0.18)':'rgba(0,100,180,0.12)';ctx.fill();
    roundRect(ctx,arrowLX,cardsCY-arrowH/2,arrowW,arrowH,6);
    ctx.strokeStyle=aDisL?'rgba(60,60,100,0.3)':aLhov?'rgba(0,200,255,0.8)':'rgba(0,120,200,0.45)';ctx.lineWidth=1.5;ctx.stroke();
    ctx.font='bold 22px "Courier New"';ctx.textAlign='center';
    ctx.fillStyle=aDisL?'rgba(80,80,120,0.4)':aLhov?'#00ddff':'rgba(0,180,255,0.6)';
    ctx.shadowBlur=aLhov?12:0;ctx.shadowColor='#00ddff';
    ctx.fillText('◀',arrowLX+arrowW/2,cardsCY+8);ctx.shadowBlur=0;
    roundRect(ctx,arrowRX,cardsCY-arrowH/2,arrowW,arrowH,6);
    ctx.fillStyle=aDisR?'rgba(30,30,60,0.3)':aRhov?'rgba(0,200,255,0.18)':'rgba(0,100,180,0.12)';ctx.fill();
    roundRect(ctx,arrowRX,cardsCY-arrowH/2,arrowW,arrowH,6);
    ctx.strokeStyle=aDisR?'rgba(60,60,100,0.3)':aRhov?'rgba(0,200,255,0.8)':'rgba(0,120,200,0.45)';ctx.lineWidth=1.5;ctx.stroke();
    ctx.font='bold 22px "Courier New"';
    ctx.fillStyle=aDisR?'rgba(80,80,120,0.4)':aRhov?'#00ddff':'rgba(0,180,255,0.6)';
    ctx.shadowBlur=aRhov?12:0;ctx.shadowColor='#00ddff';
    ctx.fillText('▶',arrowRX+arrowW/2,cardsCY+8);ctx.shadowBlur=0;
    // Position dots
    const dotY=cardsCY+341/2+16;
    const dotGap=14;
    const dotTotalW=(CRAFTS.length-1)*dotGap;
    const dotStartX=cx-dotTotalW/2;
    for(let i=0;i<CRAFTS.length;i++){
      const dx=dotStartX+i*dotGap;
      const inWindow=i>=hangarScroll&&i<hangarScroll+HANGAR_VISIBLE;
      ctx.beginPath();ctx.arc(dx,dotY,i===hangarCraft?5:3.5,0,Math.PI*2);
      ctx.fillStyle=i===hangarCraft?'#00ddff':inWindow?'rgba(0,180,255,0.45)':'rgba(80,120,180,0.25)';
      ctx.shadowBlur=i===hangarCraft?10:0;ctx.shadowColor='#00ddff';
      ctx.fill();ctx.shadowBlur=0;
    }
  ```

  Note: `341/2` is `cardH/2` — the value of `cardH` is `340` so `cardH/2=170`, but using `341/2` as an approximation is fine since this is purely layout geometry. Use `170` directly for clarity:
  ```javascript
    const dotY=cardsCY+170+16;
  ```

- [ ] **Step 6: Verify in browser**

  Open Hangar. With 4 craft currently, ◀ should be dimmed. Click ▶ — should be dimmed since all 4 fit in the window. Dots should show 4 dots. Selected craft dot is brighter/larger. No console errors.

---

## Task 3: Update hover detection and click handler

**Files:** Modify `index.php`

- [ ] **Step 1: Read lines 6326–6331**

  Get exact current text of the hangar hover detection block.

- [ ] **Step 2: Update hangar hover detection to visible window only**

  Replace:
  ```javascript
    if(gameState==='hangar'){
      const {cardsCY,swatchR,rowStartX,itemStep,swatchCY,startX}=_hangarLayout();
      const spacing=Math.min(220,canvas.width*0.22);
      for(let i=0;i<CRAFTS.length;i++){const cardX=startX+i*spacing;if(mouse.x>cardX-100&&mouse.x<cardX+100&&mouse.y>cardsCY-170&&mouse.y<cardsCY+170){hoverCard=i;break;}}
      for(let i=0;i<SWATCHES.length;i++){const sx=rowStartX+i*itemStep;if(dist(mouse.x,mouse.y,sx,swatchCY)<swatchR+8){hoverSwatch=i;break;}}
    }
  ```
  With:
  ```javascript
    if(gameState==='hangar'){
      const {cardsCY,swatchR,rowStartX,itemStep,swatchCY,startX}=_hangarLayout();
      const spacing=Math.min(220,canvas.width*0.22);
      for(let i=hangarScroll;i<Math.min(hangarScroll+HANGAR_VISIBLE,CRAFTS.length);i++){const cardX=startX+(i-hangarScroll)*spacing;if(mouse.x>cardX-100&&mouse.x<cardX+100&&mouse.y>cardsCY-170&&mouse.y<cardsCY+170){hoverCard=i;break;}}
      for(let i=0;i<SWATCHES.length;i++){const sx=rowStartX+i*itemStep;if(dist(mouse.x,mouse.y,sx,swatchCY)<swatchR+8){hoverSwatch=i;break;}}
    }
  ```

- [ ] **Step 3: Read lines 6171–6194**

  Get exact current text of the full hangar click handler block.

- [ ] **Step 4: Update the hangar click handler**

  Replace:
  ```javascript
    const {btnH,btnY,cardsCY,swatchR,rowStartX,itemStep,swatchCY,startX,cancelW,saveW,cancelX,saveX}=_hangarLayout();
    for(let i=0;i<CRAFTS.length;i++){
      const cardX=startX+i*Math.min(220,canvas.width*0.22);
      if(mouse.x>cardX-100&&mouse.x<cardX+100&&mouse.y>cardsCY-170&&mouse.y<cardsCY+170){
        hangarCraft=i;hangarColor=CRAFTS[i].defaultColor;colorPick.value=hangarColor;SFX.select();return;
      }
    }
  ```
  With:
  ```javascript
    const {btnH,btnY,cardsCY,swatchR,rowStartX,itemStep,swatchCY,startX,arrowW,arrowH,arrowLX,arrowRX,cancelW,saveW,cancelX,saveX}=_hangarLayout();
    const spacing=Math.min(220,canvas.width*0.22);
    if(mouse.x>arrowLX&&mouse.x<arrowLX+arrowW&&mouse.y>cardsCY-arrowH/2&&mouse.y<cardsCY+arrowH/2&&hangarScroll>0){
      hangarScroll=Math.max(0,hangarScroll-1);SFX.select();return;
    }
    if(mouse.x>arrowRX&&mouse.x<arrowRX+arrowW&&mouse.y>cardsCY-arrowH/2&&mouse.y<cardsCY+arrowH/2&&hangarScroll<CRAFTS.length-HANGAR_VISIBLE){
      hangarScroll=Math.min(CRAFTS.length-HANGAR_VISIBLE,hangarScroll+1);SFX.select();return;
    }
    for(let i=hangarScroll;i<Math.min(hangarScroll+HANGAR_VISIBLE,CRAFTS.length);i++){
      const cardX=startX+(i-hangarScroll)*spacing;
      if(mouse.x>cardX-100&&mouse.x<cardX+100&&mouse.y>cardsCY-170&&mouse.y<cardsCY+170){
        hangarCraft=i;
        // Auto-scroll to keep selection visible
        if(i<hangarScroll) hangarScroll=i;
        else if(i>=hangarScroll+HANGAR_VISIBLE) hangarScroll=Math.min(i-HANGAR_VISIBLE+1,Math.max(0,CRAFTS.length-HANGAR_VISIBLE));
        hangarColor=CRAFTS[i].defaultColor;colorPick.value=hangarColor;SFX.select();return;
      }
    }
  ```

- [ ] **Step 5: Verify in browser**

  Open Hangar. Confirm hover highlight on cards works correctly for visible cards only. Click through all 4 visible cards — selection should change and dot indicator should update. Arrows should be non-interactive (dimmed) when at scroll limits.

---

## Task 4: Reset `hangarScroll` on game start

**Files:** Modify `index.php`

- [ ] **Step 1: Read lines 5917–5928 (`startBattle()`)**

  Get exact current text.

- [ ] **Step 2: Add `hangarScroll=0` reset to `startBattle()`**

  Replace:
  ```javascript
    resetPlayer(); camX=P.x-canvas.width/2; camY=P.y-canvas.height/2;
  ```
  (in `startBattle()`)
  With:
  ```javascript
    hangarScroll=0; resetPlayer(); camX=P.x-canvas.width/2; camY=P.y-canvas.height/2;
  ```

- [ ] **Step 3: Read lines 4843–4847 (`startCombatTraining()`)**

  Get exact current text.

- [ ] **Step 4: Add `hangarScroll=0` reset to `startCombatTraining()`**

  Replace:
  ```javascript
    resetPlayer();
    P.x=WORLD_W*0.18; P.y=WORLD_H/2; camX=0; camY=0;
  ```
  (in `startCombatTraining()`)
  With:
  ```javascript
    hangarScroll=0; resetPlayer();
    P.x=WORLD_W*0.18; P.y=WORLD_H/2; camX=0; camY=0;
  ```

- [ ] **Step 5: Verify full flow in browser**

  Scroll the hangar, start a battle, finish or die, return to menu, open hangar — should open at scroll position 0. No console errors.

- [ ] **Step 6: Commit**

  ```
  git add index.php
  git commit -m "feat: hangar horizontal scroll (HANGAR_VISIBLE=4, arrow buttons, dot indicator)"
  ```

---

## Self-Review

- [x] **Spec coverage:**
  - `HANGAR_VISIBLE=4` constant added → Task 1 Step 2
  - `hangarScroll=0` variable added → Task 1 Step 3
  - `_hangarLayout()` startX centers visible window → Task 1 Step 5
  - Arrow geometry `arrowLX`/`arrowRX` returned from `_hangarLayout()` → Task 1 Step 5
  - `drawHangarScreen()` renders only visible window → Task 2 Step 5
  - Arrow buttons drawn with dim-when-disabled style → Task 2 Step 5
  - Position dots below card row → Task 2 Step 5
  - New craft preview stubs (sniper/carrier/skirmisher → `drawEnemyDrone`) → Task 2 Step 4
  - Hover detection restricted to visible window → Task 3 Step 2
  - Click handler: arrow scroll + visible-window card detection → Task 3 Step 4
  - `hangarScroll=0` on `startBattle()` and `startCombatTraining()` → Task 4

- [x] **No placeholders** — all steps contain complete code.

- [x] **Type consistency** — `_hangarLayout()` returns `arrowW, arrowH, arrowLX, arrowRX` and all consumers destructure exactly those fields.
