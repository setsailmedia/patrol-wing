# HUD Mini Controls Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move Pause and Mute buttons from top-right to bottom-left, and increase score display size.

**Architecture:** All changes are in `index.php`. Add `mlLayout()` for bottom-left geometry; update `pauseBtnRect()` and `muteBtnRect()` to call it; strip `trLayout()` of now-unused pause/mute fields; bump score font sizes. Click handlers need no changes — they already call `pauseBtnRect()`/`muteBtnRect()`.

**Tech Stack:** Vanilla JS, HTML5 Canvas 2D, single-file game.

---

## File Map

- Modify: `index.php` — all changes in this one file.

Key locations:
- Line 349: `trLayout()` — strip pause/mute fields
- Line 355: after `weaponBarY()` — add `mlLayout()` here
- Line 3112: `pauseBtnRect()` — call `mlLayout()`
- Line 3116: `muteBtnRect()` — call `mlLayout()`
- Line 3055: score label in `drawHUD()` — bump font sizes

---

## Task 1: Add `mlLayout()` and update rect helpers

**Files:** Modify `index.php`

- [ ] **Step 1: Read current `trLayout` and surrounding lines**

  Read lines 349–355 to get exact current text before editing.

- [ ] **Step 2: Strip unused fields from `trLayout()`**

  Replace the current `trLayout()` function:
  ```javascript
  // Helper: returns layout constants for the top-right HUD panel
  function trLayout(){
    return IS_TOUCH
      ? {pbW:44, pbH:32, pbY:62, muteW:32, muteH:32, muteOff:50, scoreY:22, labelY:12, mmY:100}
      : {pbW:44, pbH:28, pbY:58, muteW:28, muteH:28, muteOff:52, scoreY:22, labelY:12, mmY:94};
  }
  ```
  With:
  ```javascript
  // Helper: returns layout constants for the top-right HUD panel (score + minimap)
  function trLayout(){
    return IS_TOUCH
      ? {scoreY:22, labelY:12, mmY:100}
      : {scoreY:22, labelY:12, mmY:94};
  }
  ```

- [ ] **Step 3: Add `mlLayout()` after `weaponBarY()`**

  Read line 355 to get exact text of `weaponBarY()`. Directly after that line, add:
  ```javascript
  function mlLayout(){
    const pbW=44,pbH=28,muteW=28,muteH=28,gap=4;
    if(IS_TOUCH){
      const y=canvas.height-STICK_R*2-14-Math.max(pbH,muteH);
      return{pbX:10,pbY:y,pbW,pbH,muteX:10+pbW+gap,muteY:y,muteW,muteH};
    }
    const y=canvas.height-14-pbH;
    return{pbX:14,pbY:y,pbW,pbH,muteX:14+pbW+gap,muteY:y,muteW,muteH};
  }
  ```

- [ ] **Step 4: Read `pauseBtnRect()` and `muteBtnRect()` at lines 3112–3119**

  Get the exact current text before editing.

- [ ] **Step 5: Update `pauseBtnRect()` to use `mlLayout()`**

  Replace:
  ```javascript
  function pauseBtnRect(){
    const {pbW,pbH,pbY}=trLayout();
    return {x:canvas.width-pbW-14, y:pbY, w:pbW, h:pbH};
  }
  ```
  With:
  ```javascript
  function pauseBtnRect(){
    const{pbX,pbY,pbW,pbH}=mlLayout();
    return{x:pbX,y:pbY,w:pbW,h:pbH};
  }
  ```

- [ ] **Step 6: Update `muteBtnRect()` to use `mlLayout()`**

  Replace:
  ```javascript
  function muteBtnRect(){
    const {pbW,pbH,pbY,muteW,muteH,muteOff}=trLayout();
    return {x:canvas.width-pbW-muteOff-muteW-4, y:pbY, w:muteW, h:muteH};
  }
  ```
  With:
  ```javascript
  function muteBtnRect(){
    const{muteX,muteY,muteW,muteH}=mlLayout();
    return{x:muteX,y:muteY,w:muteW,h:muteH};
  }
  ```

- [ ] **Step 7: Verify in browser**

  Open the game and start a Battle match. Pause and Mute buttons should appear at the bottom-left corner. Clicking each should work. Top-right should show only the score. No console errors.

- [ ] **Step 8: Commit**

  ```
  git add index.php
  git commit -m "feat: move pause/mute buttons to bottom-left mini HUD"
  ```

---

## Task 2: Increase score display size

**Files:** Modify `index.php`

- [ ] **Step 1: Read the score block in `drawHUD()`**

  Read lines 3051–3063 to get exact current text.

- [ ] **Step 2: Update score font sizes**

  Replace the score rendering block:
  ```javascript
    ctx.font=`${T?7:10}px "Courier New"`;ctx.fillStyle='rgba(140,100,30,0.75)';
    ctx.fillText('SCORE',scoreX,T?labelY+8:labelY+10);
    if(T){
      ctx.font='bold 13px "Courier New"';ctx.fillStyle='#ffdd00';ctx.shadowBlur=8;ctx.shadowColor='#ffaa00';
      ctx.fillText(String(score).padStart(8,'0'),scoreX,scoreY+16);ctx.shadowBlur=0;
    } else {
      ctx.font='bold 22px "Courier New"';ctx.fillStyle='#ffdd00';ctx.shadowBlur=14;ctx.shadowColor='#ffaa00';
      ctx.fillText(String(score).padStart(8,'0'),scoreX,scoreY+18);ctx.shadowBlur=0;
    }
  ```
  With:
  ```javascript
    ctx.font=`${T?9:12}px "Courier New"`;ctx.fillStyle='rgba(140,100,30,0.75)';
    ctx.fillText('SCORE',scoreX,T?labelY+8:labelY+10);
    if(T){
      ctx.font='bold 18px "Courier New"';ctx.fillStyle='#ffdd00';ctx.shadowBlur=12;ctx.shadowColor='#ffaa00';
      ctx.fillText(String(score).padStart(8,'0'),scoreX,scoreY+20);ctx.shadowBlur=0;
    } else {
      ctx.font='bold 32px "Courier New"';ctx.fillStyle='#ffdd00';ctx.shadowBlur=18;ctx.shadowColor='#ffaa00';
      ctx.fillText(String(score).padStart(8,'0'),scoreX,scoreY+26);ctx.shadowBlur=0;
    }
  ```

  Note: `scoreY+26` (was `+18`) accounts for the larger 32px font descender so the number stays visually in the same top-right zone without clipping.

- [ ] **Step 3: Verify in browser**

  Start a match. Score should be noticeably larger at top-right. No overlap with other HUD elements (health bars are top-left, wave info is top-center). Accumulate some score and confirm the 8-digit padded display looks right at the larger size.

- [ ] **Step 4: Commit**

  ```
  git add index.php
  git commit -m "feat: increase score display size (22px→32px desktop, 13px→18px touch)"
  ```

---

## Self-Review

- [x] **Spec coverage:**
  - `mlLayout()` added → Task 1 Step 3
  - `pauseBtnRect()` uses `mlLayout()` → Task 1 Step 5
  - `muteBtnRect()` uses `mlLayout()` → Task 1 Step 6
  - `trLayout()` stripped of unused fields → Task 1 Step 2
  - Score desktop: 22px → 32px, shadowBlur 14 → 18 → Task 2 Step 2
  - Score touch: 13px → 18px, shadowBlur 8 → 12 → Task 2 Step 2
  - Score label desktop: 10px → 12px → Task 2 Step 2
  - Score label touch: 7px → 9px → Task 2 Step 2
  - Click handlers need no changes (confirmed — they call `pauseBtnRect()`/`muteBtnRect()`) → no task needed

- [x] **No placeholders** — all steps contain complete code.

- [x] **Type consistency** — `mlLayout()` returns `{pbX, pbY, pbW, pbH, muteX, muteY, muteW, muteH}` and both rect helpers destructure exactly those fields.
