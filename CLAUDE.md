# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

PATROL WING is a single-file browser arcade space shooter. The entire game lives in `index.php` (~6,800 lines). No build process, no package manager, no test framework. To run: serve `index.php` via any web server and open in browser. The full developer reference is in `PATROL_WING_DEVDOC.md` — read it before making non-trivial changes.

`const GAME_NAME='PATROL WING'` near the top of constants controls the game name at runtime in 6 template-literal locations. The `<title>` and two header comments must be updated manually.

## State Machine

```
'intro' (7 sequences) → 'adBreak' → 'start'

'start' → 'briefing' → startBattle() / startCombatTraining()
        → 'ttLevelSelect' → 'briefing' → startGame() / startNukeDisarm() / etc.
        → 'hangar'
        → 'hallOfFame'

'playing' → 'paused' | 'gameover' | 'waveClear' | 'victory'
          → 'timeTrialResult' (TT L1/L2/L3/L4/L5)
          → 'ctLevelUp' | 'ctResult' (Combat Training)
```

`'briefing'` routes BACK to `'ttLevelSelect'` for TT briefings, `'start'` for all others.

## Game Modes

- **Battle** — 2600x1700, 5 waves, boss W5, hazards W2+
- **Time Trials** — 5 levels: Ghost Run (20800m corridor), Nuclear Disarm (4200x3200, 4 bombs), Dance Birdie Dance (20800m), J R Rescue (4200x3200, 3 captives), Touch N Go (4200x4800, 5 sequenced pads)
- **Combat Training** — 1v1 sequence: `['dart','scout','guard','phantom','wraith','turret','brute','boss']`

## Craft Classes

Phantom (RECON), Viper (ASSAULT), Titan (HEAVY), Specter (STEALTH). 4 classes, class bonuses applied per craft.

## Weapons (17 total)

`std` `rapid` `stun` `spread` `boomr` `sawtooth` `fractal` `plasma` `minime` `tractor` `burst` `rico` `mine` `laser` `rocket` `seekr` `dinf`

Fractal moves with ship: `f.ox/f.oy` updated each tick from inherited `P.vx/P.vy` (dampened x0.94). Rico and plasma share 96 dmg. Seekr deals 30% maxHp.

## Enemy Types (8)

Scout, Guard, Turret, Boss, Dart, Wraith, Brute, Phantom Stalker. All took -15% damage in v6.0. Bullet hitbox: `dist2 < e.size² × 1.21`.

Alert-on-hit (v6.0): any pBullet hit snaps enemy from `'patrol'` to `'chase'`/`'attack'` and fires one retaliatory shot (±0.11 rad). Exceptions: stunned enemies, Phantom Stalker.

## Pickups

Guaranteed drop on every kill (no probability gate). ~50% of enemy drops are `mystery:true` (renders as diamond `?`, type revealed on collection). `mystery` and `hidden` are separate flags. Heavier enemies (guard/turret/brute/phantom) drop a second pickup.

Storage: `HOF_KEY='pw_hof_scores'`, `HOF_MAX=20`. Hangar: `pw_hangar_craft`, `pw_hangar_color`.

## Rendering Draw Order

Insertions must respect this order:
1. drawWorld → drawObstacles → drawParticles → pickups → drawMines → drawRockets → drawSeekers → drawBoomerangs → drawTractorBeam → drawHazards → drawFractals → drawBullets → drawEnemies
2. drawNukes (L2) / drawJRRescue (L4) / drawTNG (L5)
3. drawPlayer → drawFinishLine (L1/L3) → drawEMPFlash → drawLaserFlash → drawPortals
4. drawHUD → drawMinimap → drawCrosshair → drawTouchSticks → drawMiniMe → drawBossWarning

## UI Conventions

All screens use `_briefBtn(x, y, w, h, label, col, primary)`: dark fill, coloured stroke + glow on hover, text centred at `x+w/2`. BACK buttons always left-anchored at `max(20, W×3%)`. Briefing action button anchored at `W − max(20,W×3%) − btnW`. Draw and click handler always use the identical anchor formula to prevent drift.

TT Level Select: 2 rows (3 cards top, 2 cards bottom). Cards use `_card(bx, ry, col, hcol, title, lines, iconFn)`.

## Key Constants

```javascript
GAME_NAME='PATROL WING'
HOF_KEY='pw_hof_scores'   HOF_MAX=20

// Worlds
TT_WORLD_W=20800   TT_FINISH_X=20640
TT_WORLD_W2=4200   TT_WORLD_H2=3200
DBD_WORLD_W=20800  DBD_FINISH_X=20640
JRR_WORLD_W=4200   JRR_WORLD_H=3200   JRR_GRAB_R=65   JRR_BASE_R=90   JRR_CARRY_SPD=0.78
TNG_WORLD_W=4200   TNG_WORLD_H=4800   TNG_PAD_R=52    TNG_TOUCH_R=58  TNG_HOLD_MS=1200

// Combat
NUKE_DISARM_RANGE=70   NUKE_DISARM_TIME=3000
ROCKET_SPD=12   ROCKET_DMG=65   ROCKET_LIFE=2800
SEEKR_SPD=4.37  SEEKR_COL='#ffaa00'
TRACTOR_R=320
```

## Architecture

Main loop: `requestAnimationFrame` → `loop(now)`. Delta-time clamped at 0.05s. Pattern: `tickX()` mirrors `drawX()` for every system. Single Canvas 2D context (`ctx`), single global `state` variable. Audio via Web Audio API; adaptive music ("Ghost Signal", 4 tempo modes). Input via `K` object (keyboard), pointer-lock mouse, dual analog touch sticks (`STICK_DEAD=0.12`, `TOUCH_SPD_MULT=0.42`).
