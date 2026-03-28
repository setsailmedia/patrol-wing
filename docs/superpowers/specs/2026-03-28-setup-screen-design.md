# Setup Screen — Design Spec
**Date:** 2026-03-28
**Scope:** Sound toggle on title screen, pointer cursor on title buttons, new Setup screen with audio volumes, particle intensity, screen shake, and Hall of Fame clear.

---

## Deliverables

1. Sound mute toggle button on the title screen (top-right corner)
2. Pointer cursor on all title/setup screen button hover states
3. New `'setup'` game state with `drawSetupScreen()` and click handler
4. Three-bus audio architecture (music / sfx / ui) with per-category volume
5. `pw_settings` localStorage persistence for all settings
6. Screen shake gating via settings flag
7. Particle intensity scaling via settings value

---

## Audio Architecture

### Gain Graph

```
melGain ]
bassGain } → filterNode → masterGain → comp → AC.destination
 arpGain ]

sfxGain → comp
 uiGain → comp
```

`sfxGain` and `uiGain` are created in `Music._init()` alongside `masterGain`, all connecting directly to the compressor.

### SFX Routing

UI sounds routed through `uiGain`: `select`, `back`, `wave`.
All other SFX routed through `sfxGain`.

SFX functions currently call `osc.connect(AC.destination)`. Change to `osc.connect(sfxGain)` or `osc.connect(uiGain)`. Expose `Music.sfxNode()` and `Music.uiNode()` getters so SFX functions don't reach into Music internals directly.

### Mute Behaviour

`Music.toggleMute()` zeros all three buses (`masterGain`, `sfxGain`, `uiGain`). Unmute restores each to its stored `settings.*Vol` level. Title screen toggle and in-game M key both call the same function.

### Volume Application

```javascript
masterGain.gain → settings.musicVol * (muted ? 0 : 1) * BASE_MUSIC_VOL
sfxGain.gain    → settings.sfxVol   * (muted ? 0 : 1)
uiGain.gain     → settings.uiVol    * (muted ? 0 : 1)
```

`BASE_MUSIC_VOL` = existing 0.72 (playing) / 0.38 (menus) adaptive level. Music vol setting multiplies on top of this adaptive scaling, not instead of it.

---

## Settings Object

```javascript
const SETTINGS_KEY = 'pw_settings';

const SETTINGS_DEFAULT = {
  musicVol:    1.0,    // 0.0–1.0
  sfxVol:      1.0,
  uiVol:       1.0,
  screenShake: true,
  particles:   'full'  // 'full' | 'reduced' | 'off'
};
```

`_loadSettings()` / `_saveSettings()` mirror the existing `_hofLoad` / `_hofSave` pattern. Load at startup, before audio init. Save immediately on any interaction in the Setup screen.

---

## Title Screen Changes

### Sound Toggle Button

- Position: top-right, `x = W - pad - btnW`, `y = pad`. `pad = max(14, W*0.02)`. `btnW = ~52px`, `btnH = ~32px`.
- Label: `SOUND ON` / `SOUND OFF` (or speaker icons `♪` / `✕`). Uses `_briefBtn` style.
- Click calls `Music.toggleMute()`.
- Rendered in `drawStartScreen()` after the menu buttons block.
- Click detected in the `'start'` click handler before the menu rect loop.

### Setup Button in Menu

Add `{ label: 'Setup', dim: false }` to the array returned by `getMenuRects()`. Click handler routes to `gameState = 'setup'`.

### Pointer Cursor

In the `mousemove` handler, when `gameState === 'start'` or `gameState === 'setup'`, check if the mouse is over any interactive rect. Set `canvas.style.cursor = 'pointer'` on match, `'default'` otherwise.

---

## Setup Screen

### State

Add `'setup'` to the loop dispatch:
```javascript
} else if(gameState === 'setup'){
  drawSetupScreen();
}
```

Add to the click handler block.

### Layout (top-down, proportional)

```
[title: SETUP]
[section: AUDIO]
  Music Volume     [slider 0–100]
  Effects Volume   [slider 0–100]
  Interface Volume [slider 0–100]
[section: DISPLAY]
  Particle Intensity  [FULL] [REDUCED] [OFF]
[section: GAMEPLAY]
  Screen Shake     [ON] [OFF]
[section: DATA]
  [CLEAR HALL OF FAME]
[BACK]
```

All sections use the same dark grid background as `drawStartScreen()`.

Section headers: small uppercase label, dim cyan, same treatment as column headers elsewhere in the game.

### Sliders

Canvas-drawn. Track: full-width rounded rect, dark fill, cyan border. Fill: cyan from left to thumb position. Thumb: small filled circle, brighter on hover/drag.

Interaction: `mousedown` on thumb begins drag; `mousemove` while dragging updates value clamped 0–1; `mouseup` ends drag and calls `_saveSettings()`.

Display current value as `Math.round(val * 100)` to the right of the slider track.

### Particle Intensity Toggle

Three adjacent buttons `[FULL] [REDUCED] [OFF]`. Active button: filled cyan, dark text. Inactive: dark fill, dim border. Same `_briefBtn` shape. Click updates `settings.particles` and saves.

### Screen Shake Toggle

Two adjacent buttons `[ON] [OFF]`. Same treatment as particle toggle. Click updates `settings.screenShake` and saves.

### Clear Hall of Fame

Single `_briefBtn`-style button, centred. Two-step confirm:
- Step 1: normal state, label `CLEAR HALL OF FAME`
- Step 2 (after first click): border turns red (`#ff3333`), label becomes `CONFIRM CLEAR — CLICK AGAIN`
- Step 3 (second click): calls `localStorage.removeItem(HOF_KEY)`, resets to step 1, flashes `CLEARED` for 2s
- Auto-reset to step 1 after 3s if not confirmed

State tracked in a module-level variable `hofClearStep` (0 / 1) and `hofClearResetAt` timestamp.

### Back Button

Left-anchored `_briefBtn`, `BACK` label → `gameState = 'start'`. Same anchor formula as briefing screens: `max(20, W*3%)`.

---

## Screen Shake Gating

All `shake = <value>` assignments are gated:
```javascript
if(settings.screenShake) shake = 30;
```

The `shake` decay and camera offset application remain unchanged.

---

## Particle Intensity Scaling

`spawnParts(x, y, col, count, ...)` — the `count` argument is multiplied before the call:

```javascript
function _pCount(n){
  if(settings.particles==='off')     return 0;
  if(settings.particles==='reduced') return Math.ceil(n * 0.4);
  return n;
}
```

All `spawnParts` calls replace their count literal with `_pCount(n)`. No change to the particle system internals.

---

## Unresolved Questions

None.
