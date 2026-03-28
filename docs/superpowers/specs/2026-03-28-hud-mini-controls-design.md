# HUD Mini Controls — Design Spec
**Date:** 2026-03-28
**Scope:** Relocate Pause and Mute buttons from top-right to bottom-left mini HUD; increase score display size.

---

## Deliverables

1. `mlLayout()` helper — returns bottom-left button geometry for desktop and touch
2. `pauseBtnRect()` and `muteBtnRect()` updated to call `mlLayout()` instead of `trLayout()`
3. `trLayout()` stripped of now-unused pause/mute fields (`pbW`, `pbH`, `pbY`, `muteW`, `muteH`, `muteOff`)
4. Score font size increased (desktop: 22px → 32px; touch: 13px → 18px)
5. No changes to `drawPauseBtn()`, `drawMuteBtn()`, or click handlers

---

## `mlLayout()` — Bottom-Left Geometry

```javascript
function mlLayout(){
  const pbW=44, pbH=28, muteW=28, muteH=28, gap=4;
  if(IS_TOUCH){
    const y = canvas.height - STICK_R*2 - 14 - Math.max(pbH, muteH);
    return { pbX:10, pbY:y, pbW, pbH, muteX:10+pbW+gap, muteY:y, muteW, muteH };
  }
  const y = canvas.height - 14 - pbH;
  return { pbX:14, pbY:y, pbW, pbH, muteX:14+pbW+gap, muteY:y, muteW, muteH };
}
```

Pause button: left-anchored at `pbX`. Mute button: immediately to its right with a `4px` gap. Both share the same `y` baseline.

---

## `pauseBtnRect()` and `muteBtnRect()` Updates

```javascript
function pauseBtnRect(){
  const {pbX,pbY,pbW,pbH}=mlLayout();
  return {x:pbX, y:pbY, w:pbW, h:pbH};
}
function muteBtnRect(){
  const {muteX,muteY,muteW,muteH}=mlLayout();
  return {x:muteX, y:muteY, w:muteW, h:muteH};
}
```

---

## `trLayout()` Cleanup

Remove fields: `pbW`, `pbH`, `pbY`, `muteW`, `muteH`, `muteOff`.
Keep fields: `scoreY`, `labelY`, `mmY`.

New form:
```javascript
function trLayout(){
  return IS_TOUCH
    ? { scoreY:22, labelY:12, mmY:100 }
    : { scoreY:22, labelY:12, mmY:94  };
}
```

---

## Score Size Increase

In `drawHUD()`, the score rendering block:

**Desktop** (the `else` branch):
- `SCORE` label: `10px` → `12px`
- Score number: `bold 22px` → `bold 32px`, `shadowBlur:14` → `shadowBlur:18`

**Touch** (the `if(T)` branch):
- `SCORE` label: `7px` → `9px`
- Score number: `bold 13px` → `bold 18px`, `shadowBlur:8` → `shadowBlur:12`

Score `x` anchor (`canvas.width - 14`) and `y` values from `trLayout()` are unchanged.

---

## Click Handler

No changes required. The `playing` state click handler already calls `pauseBtnRect()` and `muteBtnRect()` — since those functions return new bottom-left rects, click detection moves automatically.

---

## Unresolved Questions

None.
