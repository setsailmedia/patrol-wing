# PATROL WING — Developer Documentation
### Version 6.0 | Single-File HTML5 Canvas Game

> **Game name:** `PATROL WING` — stored in `const GAME_NAME='PATROL WING'` near the top of the constants section. Change this one value to rename the game everywhere it appears at runtime. Three non-JS occurrences (HTML `<title>`, two header comments) must be updated manually.

---

## Project Overview

Top-down aerial combat drone game, single HTML file, HTML5 Canvas 2D API, vanilla JS, Web Audio API. No dependencies.

**Current file:** `phantom-wing-v3.html`  
**Canvas:** Full-window, resizes dynamically  
**File size:** ~310KB / ~6,200 lines

---

## Game State Machine (Full, v6.0)

```
'intro' → 7 sequences → 'adBreak' → 'start'

'start'
  → Battle Waves      → 'briefing' (brief_battle) → startBattle()
  → Time Trials       → 'ttLevelSelect'
  → Combat Training   → 'briefing' (brief_ct) → startCombatTraining()
  → Aircraft Hangar   → 'hangar'
  → Hall of Fame      → 'hallOfFame'

'ttLevelSelect'  (row 1: 3 cards · row 2: 2 cards)
  → Ghost Run (L1)          → 'briefing' (brief_tt1) → startGame()
  → Nuclear Disarm (L2)     → 'briefing' (brief_tt2) → startNukeDisarm()
  → Dance Birdie Dance (L3) → 'briefing' (brief_tt3) → startDanceBirdie()
  → J R Rescue (L4)         → 'briefing' (brief_tt4) → startJRRescue()
  → Touch N Go (L5)         → 'briefing' (brief_tt5) → startTouchNGo()
  → BACK (left-anchored) → 'start'

'briefing'
  → ▶ TAKE FLIGHT (right) / Space → launchFn()
  → ◀ BACK (left)  → 'start' or 'ttLevelSelect'

'playing'
  → pause             → 'paused'
  → player dies       → 'gameover'
  → battle wave done  → 'waveClear' or 'victory'
  → TT L1/L3 finish   → 'timeTrialResult'
  → TT L2 disarmed    → 'timeTrialResult'
  → TT L4 all rescued → 'timeTrialResult'
  → TT L5 seq done    → 'timeTrialResult'
  → CT kill           → ctKillAndAdvance() → 'ctLevelUp' or 'ctResult'

'gameover'        → drawDeathScreen(): 5s auto / MAIN MENU → 'start'
'timeTrialResult' → click/Space → 'start'
'ctResult'        → click/Space → 'start'
'victory'         → click/Space → 'start'
'hallOfFame'      → tabs, BACK → 'start'
```

---

## Weapons (v6.0)

| # | ID | Name | Dmg | Stock | Icon |
|---|---|---|---|---|---|
| 0 | `std` | STANDARD | 28 | unlimited | `•` |
| 1 | `rapid` | RAPID FIRE | 12 | 1,000 | `►` |
| 2 | `stun` | STUN GUN | 0 | unlimited | `»` |
| 3 | `spread` | SPREAD SHOT | 19 | 100 | `↩` |
| 4 | `boomr` | BOOMERANG | 25 | 100 | `∿` |
| 5 | `sawtooth` | SAWTOOTH | 19 | 200 | `↯` |
| 6 | `fractal` | FRACTAL FUSION | **18 flat** | 25 | `\|` |
| 7 | `plasma` | PLASMA CANNON | 96 | 50 | `⊙` |
| 8 | `minime` | J R | 16‡ | companion | `‖` |
| 9 | `tractor` | TRACTOR FORCE | 0 | 50,000ms | `⊸` |
| 10 | `burst` | BURST CANNON | 25 | 500 | `◈` |
| 11 | `rico` | RICO CANNON | 96 | 30 | `◎` |
| 12 | `mine` | PROX MINE | — | mineStock | `⊛` |
| 13 | `laser` | LASER | 150 | 20 | `⇝` |
| 14 | `rocket` | ROCKET LAUNCHER | 65 | 15 | `⬆` |
| 15 | `seekr` | SEEK MISSILE | 30% maxHp | seekStock | `⊕` |
| 16 | `dinf` | DIGITAL INFECTION | 14 | 800 | `⌬` |

**Fractal Fusion:** Flat 18 dmg. Segments in relative coords; origin tracks ship via inherited `P.vx/P.vy` each tick (dampened ×0.94). Continuous per-frame hit detection in `tickFractals` via `f.hitSet`. Vibration in final 130ms. Life 700ms. Root 80px; branches 130→100→72→48px; 5 gens; hit radius 38px.

**J R pickup behaviour:** When `miniMe.active`, J R overlapping a pickup (within `MM_SIZE+18px`) collects it for the player. Teal sparkle emitted; same full pickup effect applied. Checked in the same pickup loop as player.

---

## Enemy Types (v6.0)

| Type | HP | Dmg | Notes |
|---|---|---|---|
| Scout | 55 | **13** | Standard |
| Guard | 130 | **24** | Standard |
| Turret | 220 | **26** | Stationary burst |
| Boss | 800 | **19** | 5-shot spread |
| Dart | 35 | **9** | Zigzag arrowhead |
| Wraith | 95 | **19** | Teleport + burst |
| Brute | 420 | **36** | Fat slow bolts |
| Phantom | 80 | **15** | Retreat/heal/sniper |

All dmg values reduced **−15%** from v5.0 (applied after prior −20% on Brute/Turret top-30%).

**Alert on hit:** Every enemy that takes a pBullet hit immediately snaps from `'patrol'` to `'chase'` or `'attack'` (turrets → attack instantly). Fires one retaliatory shot with ±0.11 rad spread. Exceptions: stunned enemies, Phantom Stalker (retreat behaviour overrides).

**Bullet hitboxes:** `dist2 < e.size² × 1.21`

---

## Power-ups (v6.0)

| Type | Color | Effect | Notes |
|---|---|---|---|
| `battery` | `#00ff88` | +58 power | High drop weight |
| `health` | `#ff4466` | +48 HP | Red cross |
| `medkit` | `#44ffdd` (draw) / `rgba(40,120,255,...)` | +22 HP | **Blue circle + electric white/blue cross**; shows `MEDKIT +22` flash |
| `weapon` | `#ffee00` | Unlock / +10 ammo | — |
| `shield` | `#44aaff` | 4.8s bubble | — |
| `emp` | `#cc44ff` | Stun all | — |
| `overcharge` | `#ff9900` | ×2.3 dmg 7s | — |
| `points` | `#ffd700` | +250 score | — |
| `ammo` | `#ccddff` | +weapon ammo | — |
| `invincibility` | `#ffffff` | 5s deflect | — |
| `cloak` | `#88ffee` | 5s invisible | — |
| `portal` | `#ff8800` | Teleport | — |
| `nuke_key` | `#ffffff` | Bomb key | L2 only |

**Drop system:**
- Every enemy kill drops ≥1 pickup (guaranteed — no probability gate)
- ~50% of enemy drops tagged `mystery:true` → renders as diamond `?` icon
- Heavier enemies (guard/turret/brute/phantom) drop a second pickup (~50% mystery)
- ~5% of scattered field pickups (`spawnHiddenPickups`) also tagged `mystery:true`
- `mystery` flag is separate from `hidden` flag (`hidden` = dim/small field pickup; `mystery` = diamond concealment)
- On collection, mystery pickups reveal and apply their type normally

**Pickup hitboxes:** dropped 48px · hidden 36px

---

## Game Modes

### Battle Waves
World 2,600×1,700. 5 waves. Boss W5. Hazards W2+.

### Time Trials (5 levels, v6.0)

All TT hostile counts reduced ~12% from v4.5. TT level select has two rows: 3 cards top, 2 cards bottom.

**Ghost Run (L1):** 20,800m corridor. ~140 enemies.

**Nuclear Disarm (L2):** 4,200×3,200. 4 bombs. Roaming patrols: 19.

**Dance Birdie Dance (L3):** 20,800m. ~37 enemies + 38 zap pairs + 28 mines.

**J R Rescue (L4):** 4,200×3,200. 3 captive J R crafts. Kill guards → grab → escort to base. Speed ×0.78 while carrying. Direction arrow in HUD computed each frame. 14 roaming patrols.

**Touch N Go (L5):** 4,200×4,800 (taller). 5 landing pads; numbers shuffled/hidden. Fly over to reveal number. Touch in sequence 1→5 (hold 1.2s). Wrong order → full reset to pad 1. 22 roaming enemies + 10 zap pairs + 8 floor mines.

### Combat Training
Sequence: `['dart','scout','guard','phantom','wraith','turret','brute','boss']`. 1v1 rounds with 7 pillars + 1 pickup. Battery ×0.5.

---

## Touch N Go — Full Spec (TT Level 5)

**Constants:** `TNG_WORLD_W=4200`, `TNG_WORLD_H=4800`, `TNG_PAD_R=52`, `TNG_TOUCH_R=58`, `TNG_HOLD_MS=1200`

**Globals:** `tngPads[]`, `tngSeq` (1-5), `tngOnPad` (index, -1=none), `tngHoldMs`

**Pad object:** `{x, y, num, revealed, done, t}`

**Numbers 1–5 are shuffled** at `_tngPlacePads()` — position gives no clue about order.

**Pad rendering states:**
- Unrevealed: dim blue-grey ring, `?` label
- Revealed (not next): blue-grey ring, number visible
- Revealed (next in sequence): yellow ring + yellow number ← **only in-world, NOT on minimap**
- Done: green ring, `✔` marker above

**Touch flow:**
1. Player within `TNG_TOUCH_R=58px` → pad revealed; hold progress arc appears
2. Hold `TNG_HOLD_MS=1200ms` → evaluate:
   - Correct (`pad.num===tngSeq`) → `pad.done=true`, `tngSeq++`, +500 score, green burst
   - Wrong → all `done` reset, `tngSeq=1`, red burst, `WRONG ORDER — RESTART FROM PAD 1` flash
3. All 5 done → `computeTNGFinalScore()`, `'timeTrialResult'`

**HUD (`drawTNGHUD`):** Timer + 5 badge strip (grey/yellow-next/green-done). Approach hint when near a pad.

**Minimap (`drawTNGMinimap`):** Pads shown as dots — green=done, blue-grey=revealed, dark=unrevealed. **Next pad is NOT highlighted differently** (preserves challenge). Revealed pads show their number as a tiny label.

**Enemies:** 22 roaming (scouts/guards/darts/phantoms/wraiths/brutes/turrets) + hazards.

**Reset in all exit paths:** `tngPads=[];tngSeq=1;tngOnPad=-1;tngHoldMs=0`

---

## J R Rescue — Full Spec (TT Level 4)

**Constants:** `JRR_WORLD_W=4200`, `JRR_WORLD_H=3200`, `JRR_GRAB_R=65`, `JRR_BASE_R=90`, `JRR_CARRY_SPD=0.78`

**Globals:** `jrCaptives[]`, `jrBase{x,y}`, `jrCarrying` (-1=none)

**Captive states:** `'captive'` → `'free'` → `'carried'` → `'rescued'`

Guards tracked by object reference in `c.guards[]`. Captive → free when none remain in `enemies[]`.

**HUD direction arrow:** `Math.atan2(base.y−P.y, base.x−P.x)` → `((round(ang/(π/4))%8)+8)%8` → `['→','↘','↓','↙','←','↖','↑','↗']`. Updates every frame when carrying.

**Speed restore:** On rescue, abort, death, `_returnToStart()`.

---

## Briefing Screens (v6.0)

`const BRIEFINGS` — 7 entries: `brief_battle`, `brief_ct`, `brief_tt1/2/3/4/5`.

**Layout:** Top-down with proportional gaps. Buttons bottom-anchored (`H − btnH − max(28, H×4%)`).

- **◀ BACK** — always left-anchored (`max(20, W×3%)`)
- **▶ TAKE FLIGHT** — always right-anchored (`W − max(20, W×3%) − btnW`)

Click handler uses identical formula to draw — buttons can never drift.

**Icons (`iconFn`):** Present on `brief_battle` and `brief_ct` only. Drawn between title and divider via `ctx.translate(cx, y+iconSlot*0.5)` then `iconFn(0, col, now)`.

- **Battle Waves icon:** 5 animated wave bars of increasing height (equaliser style), faint `WAVE 5` label
- **Combat Training icon:** Two craft silhouettes facing off, pulsing `VS` gold spark between them

TT briefings have no `iconFn` (layout unchanged for those screens).

**Back routing:** `['brief_tt1','brief_tt2','brief_tt3','brief_tt4','brief_tt5']` → `'ttLevelSelect'`; all others → `'start'`.

---

## TT Level Select UI (v6.0)

Top-down layout. Header 88px. Back button 52px bottom. Available height split between two rows + row gap.

**Row 1 (3 cards):** Ghost Run · Nuclear Disarm · Dance Birdie Dance

**Row 2 (2 cards, side by side):** J R Rescue · Touch N Go

Cards use `_card(bx, ry, col, hcol, title, lines, iconFn)`. Inner layout:
- `pad = max(10, cardW×7%)` uniform inner padding
- Icon in proportional top slot (`max(50, cardH×35%)`)
- Divider between icon and title
- Title below divider
- Detail lines evenly distributed to bottom padding — no dead space

**Card icons (all animated):**
- Ghost Run: speed arrow + motion trails + finish post
- Nuclear Disarm: bomb body + animated fuse + crackle spark + X
- Dance Birdie Dance: two pylons + crackle arc + weaving drone
- J R Rescue: base beacon rings + dashed tractor beam + caged JR craft
- Touch N Go: 5 mini numbered pads + dashed flight path + hovering craft

---

## Hall of Fame (v6.0)

**BACK button:** Left-anchored using `_briefBtn` style. Centering bug (old `cx` text anchor on non-centred rect) fixed.

**`MODE_LABELS`:**
```javascript
{ 'battle': 'BATTLE WAVES', 'combattraining': 'COMBAT TRAINING',
  'timetrial_1': 'TT GHOST RUN', 'timetrial_2': 'TT NUCLEAR DISARM',
  'timetrial_3': 'TT DANCE BIRDIE', 'timetrial_4': 'TT J R RESCUE',
  'timetrial_5': 'TT TOUCH N GO' }
```

---

## Screens — Button Conventions (v6.0)

All screens now share the `_briefBtn(x, y, w, h, label, col, primary)` style: dark fill, coloured stroke + glow on hover, label centred at `x+w/2`.

| Screen | BACK position | Action button |
|---|---|---|
| Briefing (all) | Left `max(20,W×3%)` | TAKE FLIGHT right `W−max(20,W×3%)−btnW` |
| TT Level Select | Left `max(20,W×3%)` | — (card click) |
| Hall of Fame | Left `max(20,W×3%)` | — |

---

## Enemy Alert-on-Hit System

Added in v6.0. In `checkCollisions`, when a pBullet hits an enemy:
1. If enemy is in `'patrol'` state → snap to `'chase'` (or `'attack'` if within attack range). Turrets → `'attack'` directly.
2. Fire one retaliatory `eBullet` with ±0.11 rad random spread (resets `e.lastFired`).
3. Exceptions: stunned enemies (`stunMs` or `stunFireMs > 0`), Phantom Stalker (retreat mechanic takes priority), player already dead.

---

## Naming

`const GAME_NAME='PATROL WING'` — used in 6 runtime locations via template literals. Start screen title uses `GAME_NAME.split(' ')` to render word 1 and word 2 on separate lines.

The enemy type `'phantom'` (Phantom Stalker hostile) retains its name — unrelated to the game name.

---

## Constants Reference

```javascript
// Worlds
TT_WORLD_W=20800    TT_FINISH_X=20640
TT_WORLD_W2=4200    TT_WORLD_H2=3200
DBD_WORLD_W=20800   DBD_FINISH_X=20640
JRR_WORLD_W=4200    JRR_WORLD_H=3200
TNG_WORLD_W=4200    TNG_WORLD_H=4800

// JR Rescue
JRR_GRAB_R=65   JRR_BASE_R=90   JRR_CARRY_SPD=0.78

// Touch N Go
TNG_PAD_R=52    TNG_TOUCH_R=58    TNG_HOLD_MS=1200

// Weapons
ROCKET_SPD=12   ROCKET_DMG=65   ROCKET_LIFE=2800
SEEKR_SPD=4.37  SEEKR_COL='#ffaa00'
// CONE_HALF=Math.PI*0.22  TRACTOR_R=320

// Combat
CT_SEQUENCE=['dart','scout','guard','phantom','wraith','turret','brute','boss']

// Nukes
NUKE_DISARM_RANGE=70   NUKE_DISARM_TIME=3000
NUKE_COLORS=['#ff4444','#44ffff','#ffff44','#44ff44']
NUKE_NAMES=['ALPHA','BETA','GAMMA','DELTA']

// Hall of Fame
HOF_KEY='pw_hof_scores'   HOF_MAX=20
GAME_NAME='PATROL WING'
```

---

## Rendering Draw Order (v6.0)

1. drawWorld()
2. drawObstacles()
3. drawParticles()
4. pickups.forEach(drawPickup) — mystery pickups show diamond `?`
5. drawMines()
6. drawRockets()
7. drawSeekers()
8. drawBoomerangs()
9. drawTractorBeam()
10. drawHazards()
11. drawFractals()
12. drawBullets()
13. drawEnemies()
14. drawNukes() *(L2 only)*
15. drawJRRescue() *(L4 only)*
16. **drawTNG()** *(L5 only)* — pads, hold arcs, labels
17. drawPlayer()
18. drawFinishLine() *(L1/L3 only)*
19. drawEMPFlash()
20. drawLaserFlash()
21. drawPortals()
22. drawHUD() → mode-specific HUD
23. drawMinimap() → drawNukeMinimap / drawJRRMinimap / **drawTNGMinimap**
24. drawCrosshair()
25. drawTouchSticks()
26. drawMiniMe()
27. drawBossWarning()

---

## Behaviour Notes (v6.0)

- **TNG next-pad yellow** appears on the in-world pad only, NOT on minimap — deliberate challenge design
- **TNG wrong-order reset:** `done` flags cleared on all pads, `tngSeq=1`, `tngOnPad=-1`, `tngHoldMs=0`
- **Mystery vs hidden:** `p.mystery=true` → diamond `?` render. `p.hidden=true` → dim/small (pre-existing scattered pickups). Separate flags, separate effects.
- **Guaranteed enemy drops:** `killEnemy` always calls `spawnPickup`; old 72% gate removed. `_spreadInfection` kill also guaranteed.
- **J R pickup collection:** checked in pickup loop alongside player; `jrHit = miniMe.active && dist2 < (MM_SIZE+18)²`; teal sparkle on J R collect
- **Medkit icon:** blue translucent circle `rgba(40,120,255,0.22)` fill, `rgba(100,180,255,0.85)` border, white/blue electric cross with shimmer at 4Hz
- **Alert on hit:** All enemies except Phantom + stunned fire retaliatory shot and snap state on pBullet impact
- **Fractal moves with ship:** `f.ox/f.oy` updated each tick from inherited velocity; all segments drawn at `f.ox + seg.x − camX`
- **Briefing buttons:** always bottom-anchored — draw and click handler use identical `H − btnH − max(28,H×0.04)` formula, can never drift apart
- **`_briefBtn` centring:** text always at `x + w/2` — fixed HOF back button misalignment
- `ttLevel` + `nukes[]` + `jrCaptives[]` + `jrCarrying` + `tngPads[]` + `tngSeq` + `tngOnPad` + `tngHoldMs` all reset in every exit path
- Portal freeze branch includes `drawJRRescue()`, `drawNukes()`, `drawTNG()`

---

## How to Use This Document in a Future Chat

Paste this document into the conversation and say:

> "Here is the full developer documentation for Patrol Wing. The game file is `phantom-wing-v3.html`. I want to [describe the change]."

For large changes, provide the relevant section of source alongside this document.
