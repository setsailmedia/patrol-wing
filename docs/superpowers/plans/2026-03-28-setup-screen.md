# Setup Screen Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Setup screen with per-category audio volume sliders, particle intensity, screen shake toggle, and Hall of Fame clear — plus a sound mute toggle and pointer cursor on the title screen.

**Architecture:** All changes are in `index.php` (single-file game). New `pw_settings` localStorage key persists settings. Three Web Audio gain nodes (`masterGain` music, new `sfxGain`, new `uiGain`) give per-category volume. New `'setup'` game state with `drawSetupScreen()` and click handler follows existing screen patterns.

**Tech Stack:** Vanilla JS, HTML5 Canvas 2D, Web Audio API, localStorage.

---

## File Map

- Modify: `index.php` — all changes in this one file.

Key locations to orient yourself before starting:
- Line ~70: `const SFX = {...}` — sound effects object
- Line ~142: `let masterGain=null, melGain=null...` — audio gain variable declarations
- Line ~154: `function _init(){...}` inside Music closure — audio graph setup
- Line ~270: `function _tick(){...}` — music volume adaptive logic
- Line ~302: `Music.toggleMute()` — mute toggle
- Line ~480: `HANGAR_CRAFT_KEY`, `_loadHangar()` — localStorage pattern to follow
- Line ~3658: `getMenuRects()` — title menu layout (uses `MENU_ITEMS` array; search `MENU_ITEMS` to find the array definition near this function)
- Line ~3671: `drawStartScreen()` — title screen draw
- Line ~5780: `'start'` click handler block
- Line ~6007: `mousemove` hover handler
- Line ~6719: state machine dispatch in `loop()`
- Line ~6749: `shake=30` after player death — example of shake assignments to gate

---

## Task 1: Settings system

**Files:** Modify `index.php`

- [ ] **Step 1: Find `_loadHangar` call site**

  Search for `_loadHangar()` (the call, not the definition) to find the init block at the bottom of the file. Note the line number — you will add `_loadSettings()` call right next to it.

- [ ] **Step 2: Add settings constants and state**

  Find the block around line 480 where `HANGAR_CRAFT_KEY` and `HANGAR_COLOR_KEY` are defined. Directly after `_loadHangar` function definition, add:

  ```javascript
  const SETTINGS_KEY='pw_settings';
  const SETTINGS_DEFAULT={musicVol:1,sfxVol:1,uiVol:1,screenShake:true,particles:'full'};
  let settings=Object.assign({},SETTINGS_DEFAULT);
  function _loadSettings(){
    try{const s=JSON.parse(localStorage.getItem(SETTINGS_KEY));if(s)settings=Object.assign({},SETTINGS_DEFAULT,s);}catch(e){}
  }
  function _saveSettings(){
    try{localStorage.setItem(SETTINGS_KEY,JSON.stringify(settings));}catch(e){}
  }
  ```

- [ ] **Step 3: Call `_loadSettings()` at startup**

  At the `_loadHangar()` call site found in Step 1, add `_loadSettings();` on the line immediately before or after it.

- [ ] **Step 4: Verify in browser**

  Open the game. Open browser DevTools → Application → Local Storage. Confirm no errors in console. Play through to title screen. In console run `settings` — should return `{musicVol:1, sfxVol:1, uiVol:1, screenShake:true, particles:'full'}`. Run `_saveSettings()` and confirm `pw_settings` key appears in localStorage.

- [ ] **Step 5: Commit**

  ```
  git add index.php
  git commit -m "feat: add pw_settings localStorage system"
  ```

---

## Task 2: Audio graph — add sfxGain and uiGain

**Files:** Modify `index.php`

- [ ] **Step 1: Declare sfxGain and uiGain variables**

  Find line ~142: `let masterGain=null, melGain=null, bassGain=null, arpGain=null;`

  Replace with:
  ```javascript
  let masterGain=null, melGain=null, bassGain=null, arpGain=null, sfxGain=null, uiGain=null;
  ```

- [ ] **Step 2: Create sfxGain and uiGain in `_init()`**

  Find line ~167: `masterGain.connect(comp);`

  Directly after that line, add:
  ```javascript
  sfxGain = AC.createGain();
  sfxGain.gain.setValueAtTime(settings.sfxVol, AC.currentTime);
  sfxGain.connect(comp);
  uiGain = AC.createGain();
  uiGain.gain.setValueAtTime(settings.uiVol, AC.currentTime);
  uiGain.connect(comp);
  ```

- [ ] **Step 3: Expose gain node getters on the Music object**

  Find the Music object's exported methods (where `toggleMute`, `isMuted`, etc. are returned). Add two getters:
  ```javascript
  sfxNode: ()=> sfxGain || AC.destination,
  uiNode:  ()=> uiGain  || AC.destination,
  ```

- [ ] **Step 4: Update `toggleMute()` to zero all three buses**

  Find `toggleMute()` at line ~302. Replace the entire function with:
  ```javascript
  toggleMute(){
    muted = !muted;
    const t = AC ? AC.currentTime : 0;
    if(masterGain){
      masterGain.gain.cancelScheduledValues(t);
      const baseVol = (gameState==='playing') ? 0.72 : 0.38;
      const vol = muted ? 0 : (baseVol * settings.musicVol);
      masterGain.gain.setValueAtTime(masterGain.gain.value, t);
      masterGain.gain.linearRampToValueAtTime(vol, t + 0.15);
    }
    if(sfxGain) sfxGain.gain.setValueAtTime(muted ? 0 : settings.sfxVol, t);
    if(uiGain)  uiGain.gain.setValueAtTime(muted ? 0 : settings.uiVol, t);
    return muted;
  },
  ```

- [ ] **Step 5: Update `_tick()` to apply `settings.musicVol`**

  Find line ~290: `const wantVol = muted ? 0 : (playing ? 0.72 : 0.38);`

  Replace that one line with:
  ```javascript
  const baseVol = playing ? 0.72 : 0.38;
  const wantVol = muted ? 0 : (baseVol * settings.musicVol);
  ```

- [ ] **Step 6: Add `_applyVolumes()` helper and call it after settings changes**

  After the `_saveSettings` function added in Task 1, add:
  ```javascript
  function _applyVolumes(){
    if(!AC) return;
    const t=AC.currentTime;
    if(sfxGain)  sfxGain.gain.setValueAtTime(muted ? 0 : settings.sfxVol, t);
    if(uiGain)   uiGain.gain.setValueAtTime(muted ? 0 : settings.uiVol, t);
    // masterGain is handled by Music._tick() adaptive loop — no need to set here
  }
  ```

  (You will call `_applyVolumes()` in Task 7 whenever a slider value changes.)

- [ ] **Step 7: Verify in browser**

  Open game, press M key during gameplay. Music should still mute. No console errors. `Music.sfxNode()` and `Music.uiNode()` should return AudioGainNode objects (not undefined) after first interaction triggers `initAudio()`.

- [ ] **Step 8: Commit**

  ```
  git add index.php
  git commit -m "feat: add sfxGain/uiGain audio buses with volume settings integration"
  ```

---

## Task 3: Route SFX through gain nodes

**Files:** Modify `index.php`

- [ ] **Step 1: Find and read the `beep` function**

  Search for `function beep(` in `index.php`. Read the full function. It creates oscillator nodes and connects them to `AC.destination`. Note its current parameter list.

- [ ] **Step 2: Add `dest` parameter to `beep`**

  Add a final optional parameter `dest` to `beep`. Change every `osc.connect(AC.destination)` (and any gain node `.connect(AC.destination)`) inside `beep` to `.connect(dest || sfxGain || AC.destination)`.

  Example — if `beep` currently ends with:
  ```javascript
  osc.connect(AC.destination);
  osc.start(t); osc.stop(t+sustainMs/1000+0.05);
  ```
  Change to:
  ```javascript
  const _dst = dest || sfxGain || AC.destination;
  osc.connect(_dst);
  osc.start(t); osc.stop(t+sustainMs/1000+0.05);
  ```

  If `beep` uses an intermediate gain envelope node, connect that envelope node to `_dst` instead of `AC.destination`.

- [ ] **Step 3: Route UI sounds through `uiGain`**

  In the `SFX` object (line ~70), update the three UI sounds to pass `Music.uiNode()` as the last argument to every `beep` call within them:

  ```javascript
  select:  ()=>beep(520,'sine',0.12,0.18,780, Music.uiNode()),
  wave:    ()=>beep(300,'triangle',0.4,0.2,600, Music.uiNode()),
  confirm: ()=>{
    beep(440,'sine',0.10,0.16,880, Music.uiNode());
    setTimeout(()=>beep(660,'sine',0.14,0.14,1100, Music.uiNode()),110);
    setTimeout(()=>beep(880,'sine',0.18,0.12,1320, Music.uiNode()),240);
  },
  ```

  All other SFX methods require no change — `beep` defaults to `sfxGain`.

- [ ] **Step 4: Verify in browser**

  Open game. Navigate menus — the `select` click sound should play (through `uiGain`). Fire a weapon in battle — weapon SFX should play (through `sfxGain`). No console errors.

- [ ] **Step 5: Commit**

  ```
  git add index.php
  git commit -m "feat: route SFX through sfxGain/uiGain — per-category volume control ready"
  ```

---

## Task 4: Title screen — Setup button, sound toggle, pointer cursor

**Files:** Modify `index.php`

- [ ] **Step 1: Find `MENU_ITEMS` and add Setup entry**

  Search for `MENU_ITEMS` (it's an array of `{label, dim}` objects near `getMenuRects()`). Add a Setup entry:
  ```javascript
  {label:'Setup', dim:false},
  ```
  Place it as the last item before Hall of Fame, or after it — your call, but after Hall of Fame is natural.

- [ ] **Step 2: Add `'setup'` routing in the start-screen click handler**

  Find the `'start'` click handler at line ~5785. Inside the `item.label` checks, add:
  ```javascript
  if(item.label==='Setup'){ gameState='setup'; SFX.select(); }
  ```

- [ ] **Step 3: Add `'setup'` to the state machine dispatch in `loop()`**

  Find line ~6724 where `gameState==='start'` is dispatched. After the `'briefing'` branch (or wherever fits the existing else-if chain), add:
  ```javascript
  } else if(gameState==='setup'){
    drawSetupScreen();
  ```

- [ ] **Step 4: Add module-level variables for the sound toggle button**

  Near the other module-level UI state variables (search for `let menuHover`), add:
  ```javascript
  let soundToggleHover=false;
  ```

- [ ] **Step 5: Draw the sound toggle button in `drawStartScreen()`**

  At the end of `drawStartScreen()`, before the closing `}`, add:

  ```javascript
  // Sound toggle — top-right corner
  const _stPad=Math.max(14,W*0.02);
  const _stW=Math.max(80,W*0.075), _stH=Math.max(28,H*0.042);
  const _stX=W-_stPad-_stW, _stY=_stPad;
  const _stMuted=Music.isMuted();
  const _stHov=soundToggleHover;
  ctx.fillStyle=_stHov?'rgba(0,180,255,0.18)':'rgba(0,55,115,0.55)';
  ctx.fillRect(_stX,_stY,_stW,_stH);
  ctx.strokeStyle=_stHov?'#00ccff':'rgba(0,140,220,0.75)';
  ctx.lineWidth=_stHov?2:1;
  ctx.shadowBlur=_stHov?20:0; ctx.shadowColor='#00ccff';
  ctx.strokeRect(_stX,_stY,_stW,_stH); ctx.shadowBlur=0;
  const _stSz=Math.max(9,Math.min(_stH*0.38,13));
  ctx.font=`bold ${_stSz}px "Courier New"`;
  ctx.textAlign='center';
  ctx.fillStyle=_stHov?'#00eeff':'rgba(150,205,255,0.92)';
  ctx.fillText(_stMuted?'✕ SOUND OFF':'♪ SOUND ON',_stX+_stW/2,_stY+_stH/2+_stSz*0.36);
  ctx.textAlign='left';
  ```

- [ ] **Step 6: Add sound toggle click detection**

  In the `'start'` click handler (line ~5780), BEFORE the `getMenuRects()` loop, add:

  ```javascript
  // Sound toggle button
  const _stPad2=Math.max(14,canvas.width*0.02);
  const _stW2=Math.max(80,canvas.width*0.075), _stH2=Math.max(28,canvas.height*0.042);
  const _stX2=canvas.width-_stPad2-_stW2, _stY2=_stPad2;
  if(mouse.x>=_stX2&&mouse.x<=_stX2+_stW2&&mouse.y>=_stY2&&mouse.y<=_stY2+_stH2){
    initAudio(); Music.toggleMute(); return;
  }
  ```

- [ ] **Step 7: Add pointer cursor logic to the mousemove handler**

  Find the first `mousemove` listener at line ~6007. It currently handles hover for `'start'`, `'droneSelect'`, `'hangar'`, `'colorSelect'`. Add cursor logic at the END of the existing handler body:

  ```javascript
  // Pointer cursor for start and setup screens
  let _wantPointer=false;
  if(gameState==='start'){
    const _rr=getMenuRects();
    for(let i=0;i<_rr.length;i++){const {x,y,w,h}=_rr[i];if(mouse.x>=x&&mouse.x<=x+w&&mouse.y>=y&&mouse.y<=y+h){_wantPointer=true;break;}}
    // Sound toggle
    const _sp=Math.max(14,canvas.width*0.02),_sw=Math.max(80,canvas.width*0.075),_sh=Math.max(28,canvas.height*0.042);
    const _sx=canvas.width-_sp-_sw,_sy=_sp;
    if(mouse.x>=_sx&&mouse.x<=_sx+_sw&&mouse.y>=_sy&&mouse.y<=_sy+_sh){_wantPointer=true; soundToggleHover=true;}else{soundToggleHover=false;}
  }
  if(gameState==='setup') _wantPointer=_isOverSetupInteractive(mouse.x,mouse.y);
  canvas.style.cursor=_wantPointer?'pointer':'default';
  ```

  Note: `_isOverSetupInteractive` is defined in Task 6. For now you can stub it as `function _isOverSetupInteractive(mx,my){return false;}` — implement it in Task 6.

- [ ] **Step 8: Verify in browser**

  Open game title screen. Buttons should show `pointer` cursor on hover. Sound toggle should appear top-right. Clicking it should toggle music. Setup should appear in the menu list; clicking it should... currently just crash or do nothing because `drawSetupScreen` doesn't exist yet. That's OK — just verify the state transitions correctly (check `gameState` in console becomes `'setup'`).

- [ ] **Step 9: Commit**

  ```
  git add index.php
  git commit -m "feat: Setup menu entry, sound toggle, pointer cursor on title screen"
  ```

---

## Task 5: Draw Setup screen — layout and static rendering

**Files:** Modify `index.php`

- [ ] **Step 1: Add module-level Setup screen state variables**

  Near the `soundToggleHover` variable added in Task 4, add:

  ```javascript
  let hofClearStep=0, hofClearResetAt=0, hofClearFlashMs=0;
  let setupSliderDrag=null; // {key, trackX, trackW} while dragging
  ```

- [ ] **Step 2: Add `_getSetupLayout(W, H)` — single source of truth for all interactive positions**

  Add this function near `getMenuRects()` (around line ~3658):

  ```javascript
  function _getSetupLayout(W,H){
    const padX=Math.max(30,W*0.08), cx=W/2;
    const trackW=Math.max(200,Math.min(W*0.55,520));
    const trackX=cx-trackW/2;
    const labelSz=Math.max(9,Math.min(12,W/90));
    const rowH=Math.max(36,H*0.065);
    const sectionGap=Math.max(18,H*0.032);
    const btnH=Math.max(30,H*0.052);
    const btnW=Math.max(90,W*0.14);
    // Compute Y positions top-down
    const titleH=Math.max(32,H*0.1);
    let y=titleH+Math.max(20,H*0.04);

    // AUDIO section
    const audioHeaderY=y; y+=labelSz*2+8;
    const sliders=[
      {key:'musicVol', label:'MUSIC VOLUME',   y},
      {key:'sfxVol',   label:'EFFECTS VOLUME', y:y+rowH},
      {key:'uiVol',    label:'INTERFACE VOLUME',y:y+rowH*2},
    ];
    y+=rowH*3+sectionGap;

    // DISPLAY section
    const displayHeaderY=y; y+=labelSz*2+8;
    const particleY=y; y+=rowH+sectionGap;

    // GAMEPLAY section
    const gameplayHeaderY=y; y+=labelSz*2+8;
    const shakeY=y; y+=rowH+sectionGap;

    // DATA section
    const dataHeaderY=y; y+=labelSz*2+8;
    const hofBtnY=y; y+=btnH+sectionGap;

    // BACK button
    const backPad=Math.max(20,W*0.03);
    const backH=Math.max(30,H*0.052), backW=Math.max(90,W*0.1);
    const backBtn={x:backPad, y:H-backPad-backH, w:backW, h:backH};

    // Three-option button sets
    const tog3W=Math.max(70,W*0.1);
    const tog2W=Math.max(80,W*0.11);
    const togH=Math.max(26,H*0.045);

    return {
      W,H,cx,trackW,trackX,labelSz,rowH,btnH,btnW,
      audioHeaderY, sliders, trackThumbR:8,
      displayHeaderY, particleY,
      gameplayHeaderY, shakeY,
      dataHeaderY, hofBtnY, hofBtnW:Math.max(220,W*0.28), hofBtnH:btnH,
      backBtn,
      tog3W, tog2W, togH,
    };
  }
  ```

- [ ] **Step 3: Add `_isOverSetupInteractive(mx, my)` stub — replace the one from Task 4**

  Add (or replace the stub from Task 4) near `_getSetupLayout`:

  ```javascript
  function _isOverSetupInteractive(mx,my){
    if(!canvas) return false;
    const L=_getSetupLayout(canvas.width,canvas.height);
    // Sliders
    const th=L.trackThumbR*3;
    for(const s of L.sliders){
      if(mx>=L.trackX&&mx<=L.trackX+L.trackW&&my>=s.y-th&&my<=s.y+th) return true;
    }
    // Particle buttons
    const pBtns=_particleBtnRects(L);
    for(const b of pBtns){if(mx>=b.x&&mx<=b.x+L.tog3W&&my>=b.y&&my<=b.y+L.togH) return true;}
    // Shake buttons
    const sBtns=_shakeBtnRects(L);
    for(const b of sBtns){if(mx>=b.x&&mx<=b.x+L.tog2W&&my>=b.y&&my<=b.y+L.togH) return true;}
    // HOF clear
    const hx=L.cx-L.hofBtnW/2;
    if(mx>=hx&&mx<=hx+L.hofBtnW&&my>=L.hofBtnY&&my<=L.hofBtnY+L.hofBtnH) return true;
    // Back
    const {x,y,w,h}=L.backBtn;
    if(mx>=x&&mx<=x+w&&my>=y&&my<=y+h) return true;
    return false;
  }
  function _particleBtnRects(L){
    const opts=['full','reduced','off'], totalW=opts.length*L.tog3W+(opts.length-1)*8;
    const startX=L.cx-totalW/2;
    return opts.map((v,i)=>({x:startX+i*(L.tog3W+8),y:L.particleY,val:v}));
  }
  function _shakeBtnRects(L){
    const opts=[true,false], labels=['ON','OFF'], totalW=opts.length*L.tog2W+(opts.length-1)*8;
    const startX=L.cx-totalW/2;
    return opts.map((v,i)=>({x:startX+i*(L.tog2W+8),y:L.shakeY,val:v,label:labels[i]}));
  }
  ```

- [ ] **Step 4: Write `drawSetupScreen()`**

  Add this function near `drawStartScreen()` (~line 3735):

  ```javascript
  function drawSetupScreen(){
    const W=canvas.width,H=canvas.height;
    // Background — same grid as start screen
    ctx.fillStyle='#060c18'; ctx.fillRect(0,0,W,H);
    const gs=Math.max(50,Math.min(80,W/14));
    ctx.strokeStyle='rgba(0,80,180,0.09)'; ctx.lineWidth=1;
    for(let x=0;x<W;x+=gs){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x,H);ctx.stroke();}
    for(let y=0;y<H;y+=gs){ctx.beginPath();ctx.moveTo(0,y);ctx.lineTo(W,y);ctx.stroke();}

    const L=_getSetupLayout(W,H);
    const now=Date.now();
    ctx.textAlign='center';

    // Title
    const titleSz=Math.max(20,Math.min(W*0.055,36));
    ctx.font=`bold ${titleSz}px "Courier New"`;
    ctx.shadowBlur=30; ctx.shadowColor='#00aaff'; ctx.fillStyle='#00ccff';
    ctx.fillText('SETUP',W/2,Math.max(titleSz+10,H*0.07));
    ctx.shadowBlur=0;

    // Section header helper
    function _sectionHeader(label, y){
      ctx.font=`bold ${L.labelSz*0.85}px "Courier New"`;
      ctx.fillStyle='rgba(0,140,220,0.55)';
      ctx.fillText(label,L.cx,y+L.labelSz);
      ctx.strokeStyle='rgba(0,100,180,0.3)'; ctx.lineWidth=1;
      const lw=Math.max(100,W*0.35);
      ctx.beginPath(); ctx.moveTo(L.cx-lw/2,y+L.labelSz+5); ctx.lineTo(L.cx+lw/2,y+L.labelSz+5); ctx.stroke();
    }

    // AUDIO
    _sectionHeader('AUDIO',L.audioHeaderY);
    for(const s of L.sliders){
      _drawSlider(L, s.key, s.label, s.y);
    }

    // DISPLAY
    _sectionHeader('DISPLAY',L.displayHeaderY);
    _drawToggle3(L,'particles',['FULL','REDUCED','OFF'],['full','reduced','off'],_particleBtnRects(L),'Particle Intensity');

    // GAMEPLAY
    _sectionHeader('GAMEPLAY',L.gameplayHeaderY);
    _drawToggle2(L,'screenShake',_shakeBtnRects(L),'Screen Shake');

    // DATA
    _sectionHeader('DATA',L.dataHeaderY);
    _drawHofClearBtn(L,now);

    // HOF clear flash
    if(hofClearFlashMs>0){
      ctx.font=`bold ${L.labelSz*1.1}px "Courier New"`;
      ctx.fillStyle='rgba(0,220,120,'+Math.min(1,hofClearFlashMs/400)+')';
      ctx.fillText('CLEARED',L.cx,L.hofBtnY+L.hofBtnH+L.labelSz*2);
      hofClearFlashMs-=16;
    }

    // Back button
    const {x,y,w,h}=L.backBtn;
    const backHov=_isOverSetupInteractive&&mouse.x>=x&&mouse.x<=x+w&&mouse.y>=y&&mouse.y<=y+h;
    ctx.fillStyle=backHov?'rgba(0,180,255,0.18)':'rgba(0,55,115,0.55)';
    ctx.fillRect(x,y,w,h);
    ctx.strokeStyle=backHov?'#00ccff':'rgba(0,140,220,0.75)';
    ctx.lineWidth=backHov?2:1;
    ctx.shadowBlur=backHov?20:0; ctx.shadowColor='#00ccff';
    ctx.strokeRect(x,y,w,h); ctx.shadowBlur=0;
    const bSz=Math.max(9,Math.min(h*0.38,13));
    ctx.font=`bold ${bSz}px "Courier New"`;
    ctx.fillStyle=backHov?'#00eeff':'rgba(150,205,255,0.92)';
    ctx.fillText('◀ BACK',x+w/2,y+h/2+bSz*0.36);
    ctx.textAlign='left';
  }
  ```

- [ ] **Step 5: Write the three draw helpers**

  Add directly after `drawSetupScreen()`:

  ```javascript
  function _drawSlider(L,key,label,cy){
    const val=settings[key]; // 0.0–1.0
    const isDragging=setupSliderDrag&&setupSliderDrag.key===key;
    const thumbX=L.trackX+val*L.trackW;
    // Label
    ctx.font=`bold ${L.labelSz}px "Courier New"`;
    ctx.textAlign='right';
    ctx.fillStyle='rgba(150,205,255,0.85)';
    ctx.fillText(label, L.trackX-14, cy+L.labelSz*0.4);
    // Track bg
    ctx.fillStyle='rgba(0,40,90,0.7)';
    _roundRect(L.trackX, cy-5, L.trackW, 10, 5);
    ctx.fill();
    ctx.strokeStyle='rgba(0,100,180,0.5)'; ctx.lineWidth=1;
    _roundRect(L.trackX, cy-5, L.trackW, 10, 5);
    ctx.stroke();
    // Fill
    ctx.fillStyle='rgba(0,180,255,0.5)';
    _roundRect(L.trackX, cy-5, val*L.trackW, 10, 5);
    ctx.fill();
    // Thumb
    ctx.beginPath();
    ctx.arc(thumbX, cy, isDragging?10:L.trackThumbR, 0, Math.PI*2);
    ctx.fillStyle=isDragging?'#00eeff':'#00ccff';
    ctx.shadowBlur=isDragging?18:10; ctx.shadowColor='#00aaff';
    ctx.fill(); ctx.shadowBlur=0;
    // Value label
    ctx.textAlign='left';
    ctx.fillStyle='rgba(0,200,255,0.7)';
    ctx.font=`bold ${L.labelSz}px "Courier New"`;
    ctx.fillText(Math.round(val*100)+'%', L.trackX+L.trackW+14, cy+L.labelSz*0.4);
    ctx.textAlign='center';
  }

  function _drawToggle3(L,key,labels,vals,rects,rowLabel){
    ctx.font=`bold ${L.labelSz}px "Courier New"`;
    ctx.textAlign='right';
    ctx.fillStyle='rgba(150,205,255,0.85)';
    ctx.fillText(rowLabel, rects[0].x-14, rects[0].y+L.togH/2+L.labelSz*0.4);
    for(let i=0;i<rects.length;i++){
      const {x,y,val}=rects[i]; const active=settings[key]===val;
      ctx.fillStyle=active?'rgba(0,160,255,0.85)':'rgba(0,40,90,0.7)';
      ctx.fillRect(x,y,L.tog3W,L.togH);
      ctx.strokeStyle=active?'#00ccff':'rgba(0,100,180,0.5)'; ctx.lineWidth=active?2:1;
      ctx.shadowBlur=active?12:0; ctx.shadowColor='#00ccff';
      ctx.strokeRect(x,y,L.tog3W,L.togH); ctx.shadowBlur=0;
      ctx.font=`bold ${L.labelSz*0.9}px "Courier New"`;
      ctx.textAlign='center';
      ctx.fillStyle=active?'#060c18':'rgba(100,170,230,0.7)';
      ctx.fillText(labels[i],x+L.tog3W/2,y+L.togH/2+L.labelSz*0.36);
    }
    ctx.textAlign='center';
  }

  function _drawToggle2(L,key,rects,rowLabel){
    ctx.font=`bold ${L.labelSz}px "Courier New"`;
    ctx.textAlign='right';
    ctx.fillStyle='rgba(150,205,255,0.85)';
    ctx.fillText(rowLabel, rects[0].x-14, rects[0].y+L.togH/2+L.labelSz*0.4);
    for(let i=0;i<rects.length;i++){
      const {x,y,val,label}=rects[i]; const active=settings[key]===val;
      ctx.fillStyle=active?'rgba(0,160,255,0.85)':'rgba(0,40,90,0.7)';
      ctx.fillRect(x,y,L.tog2W,L.togH);
      ctx.strokeStyle=active?'#00ccff':'rgba(0,100,180,0.5)'; ctx.lineWidth=active?2:1;
      ctx.shadowBlur=active?12:0; ctx.shadowColor='#00ccff';
      ctx.strokeRect(x,y,L.tog2W,L.togH); ctx.shadowBlur=0;
      ctx.font=`bold ${L.labelSz*0.9}px "Courier New"`;
      ctx.textAlign='center';
      ctx.fillStyle=active?'#060c18':'rgba(100,170,230,0.7)';
      ctx.fillText(label,x+L.tog2W/2,y+L.togH/2+L.labelSz*0.36);
    }
    ctx.textAlign='center';
  }

  function _drawHofClearBtn(L,now){
    if(hofClearStep===1&&now>hofClearResetAt){ hofClearStep=0; }
    const hx=L.cx-L.hofBtnW/2;
    const label=hofClearStep===1?'CONFIRM CLEAR — CLICK AGAIN':'CLEAR HALL OF FAME';
    const hov=mouse.x>=hx&&mouse.x<=hx+L.hofBtnW&&mouse.y>=L.hofBtnY&&mouse.y<=L.hofBtnY+L.hofBtnH;
    ctx.fillStyle=hofClearStep===1?'rgba(120,0,0,0.7)':hov?'rgba(0,180,255,0.18)':'rgba(0,55,115,0.55)';
    ctx.fillRect(hx,L.hofBtnY,L.hofBtnW,L.hofBtnH);
    ctx.strokeStyle=hofClearStep===1?'#ff3333':hov?'#00ccff':'rgba(0,140,220,0.75)';
    ctx.lineWidth=hov||hofClearStep===1?2:1;
    ctx.shadowBlur=(hov||hofClearStep===1)?16:0;
    ctx.shadowColor=hofClearStep===1?'#ff3333':'#00ccff';
    ctx.strokeRect(hx,L.hofBtnY,L.hofBtnW,L.hofBtnH); ctx.shadowBlur=0;
    const bSz=Math.max(9,Math.min(L.hofBtnH*0.36,12));
    ctx.font=`bold ${bSz}px "Courier New"`;
    ctx.fillStyle=hofClearStep===1?'#ff8888':hov?'#00eeff':'rgba(150,205,255,0.92)';
    ctx.textAlign='center';
    ctx.fillText(label,L.cx,L.hofBtnY+L.hofBtnH/2+bSz*0.36);
  }

  function _roundRect(x,y,w,h,r){
    ctx.beginPath();
    ctx.moveTo(x+r,y); ctx.lineTo(x+w-r,y);
    ctx.arcTo(x+w,y,x+w,y+r,r); ctx.lineTo(x+w,y+h-r);
    ctx.arcTo(x+w,y+h,x+w-r,y+h,r); ctx.lineTo(x+r,y+h);
    ctx.arcTo(x,y+h,x,y+h-r,r); ctx.lineTo(x,y+r);
    ctx.arcTo(x,y,x+r,y,r); ctx.closePath();
  }
  ```

- [ ] **Step 6: Verify in browser**

  Navigate to Setup from title. Screen should render: SETUP title, four sections (AUDIO/DISPLAY/GAMEPLAY/DATA), three sliders, two toggle rows, HOF clear button, BACK button. No console errors. Nothing interactive yet (no click handler).

- [ ] **Step 7: Commit**

  ```
  git add index.php
  git commit -m "feat: drawSetupScreen() with all sections rendered"
  ```

---

## Task 6: Setup screen interaction — sliders, toggles, HOF clear, back

**Files:** Modify `index.php`

- [ ] **Step 1: Add slider mousedown detection**

  Find the main `mousedown` event listener (search for `canvas.addEventListener('mousedown'`). Inside it, add a block for `gameState==='setup'`:

  ```javascript
  if(gameState==='setup'){
    const L=_getSetupLayout(canvas.width,canvas.height);
    // Sliders — start drag if near track
    for(const s of L.sliders){
      const ty=s.y, th=L.trackThumbR*3;
      if(mouse.x>=L.trackX&&mouse.x<=L.trackX+L.trackW&&mouse.y>=ty-th&&mouse.y<=ty+th){
        setupSliderDrag={key:s.key, trackX:L.trackX, trackW:L.trackW};
        // Update value immediately on click
        settings[s.key]=Math.max(0,Math.min(1,(mouse.x-L.trackX)/L.trackW));
        _saveSettings(); _applyVolumes();
        return;
      }
    }
  }
  ```

- [ ] **Step 2: Add slider mousemove drag handling**

  In the SECOND `mousemove` listener (the one at line ~6040 that sets `mouse.x`/`mouse.y`), after updating `mouse.x`/`mouse.y`, add:

  ```javascript
  if(setupSliderDrag){
    settings[setupSliderDrag.key]=Math.max(0,Math.min(1,(mouse.x-setupSliderDrag.trackX)/setupSliderDrag.trackW));
    _saveSettings(); _applyVolumes();
  }
  ```

- [ ] **Step 3: Add mouseup to end slider drag**

  Find the `mouseup` event listener (search `addEventListener('mouseup'`). Add:

  ```javascript
  if(setupSliderDrag){ setupSliderDrag=null; }
  ```

- [ ] **Step 4: Add click handler for Setup screen**

  Find the main click handler block (near line ~5780). Add a block for `'setup'`:

  ```javascript
  if(gameState==='setup'){
    const L=_getSetupLayout(canvas.width,canvas.height);
    // Particle intensity
    for(const b of _particleBtnRects(L)){
      if(mouse.x>=b.x&&mouse.x<=b.x+L.tog3W&&mouse.y>=b.y&&mouse.y<=b.y+L.togH){
        settings.particles=b.val; _saveSettings(); SFX.select(); return;
      }
    }
    // Screen shake
    for(const b of _shakeBtnRects(L)){
      if(mouse.x>=b.x&&mouse.x<=b.x+L.tog2W&&mouse.y>=b.y&&mouse.y<=b.y+L.togH){
        settings.screenShake=b.val; _saveSettings(); SFX.select(); return;
      }
    }
    // HOF clear
    const hx=L.cx-L.hofBtnW/2;
    if(mouse.x>=hx&&mouse.x<=hx+L.hofBtnW&&mouse.y>=L.hofBtnY&&mouse.y<=L.hofBtnY+L.hofBtnH){
      if(hofClearStep===0){
        hofClearStep=1; hofClearResetAt=Date.now()+3000; SFX.select();
      } else {
        try{localStorage.removeItem(HOF_KEY);}catch(e){}
        hofClearStep=0; hofClearFlashMs=2000; SFX.confirm();
      }
      return;
    }
    // Back
    const {x,y,w,h}=L.backBtn;
    if(mouse.x>=x&&mouse.x<=x+w&&mouse.y>=y&&mouse.y<=y+h){
      gameState='start'; SFX.select(); return;
    }
    return;
  }
  ```

- [ ] **Step 5: Verify in browser**

  Open Setup screen. Drag each slider — values should change, percentages should update. Clicking FULL/REDUCED/OFF should highlight active choice. Clicking ON/OFF for screen shake should highlight. Clicking CLEAR HOF should change button to red confirm state; clicking again should clear and show CLEARED flash. BACK should return to title.

  Also verify volume takes effect: drag Music Volume to 0% — music should go silent. Drag SFX Volume to 50% — weapon sounds should be quieter. Drag Interface Volume — menu click sounds should change volume.

- [ ] **Step 6: Commit**

  ```
  git add index.php
  git commit -m "feat: Setup screen interaction — sliders, toggles, HOF clear, back"
  ```

---

## Task 7: Screen shake gating

**Files:** Modify `index.php`

- [ ] **Step 1: Gate all shake assignments**

  There are ~18 locations where `shake` is assigned a non-zero value (excluding the declaration at line ~499 and the camera application at line ~6717). For each one, wrap the assignment:

  Change every instance of the pattern:
  ```javascript
  shake = <value>;
  ```
  and:
  ```javascript
  shake=Math.max(shake, <value>);
  ```
  to:
  ```javascript
  if(settings.screenShake) shake = <value>;
  ```
  and:
  ```javascript
  if(settings.screenShake) shake=Math.max(shake, <value>);
  ```

  Known locations (from search results — verify each before editing):
  - Line ~689: `shake=8`
  - Line ~690: `shake=12`
  - Line ~719: `shake=18`
  - Line ~727: `shake=Math.max(shake,20)`
  - Line ~1141: `shake=hitEnemy!==null?14:6`
  - Line ~2002: `shake=10`
  - Line ~2213: `shake=Math.max(shake,14)`
  - Line ~2245: `shake=Math.max(shake, e.type==='boss'?32:14)`
  - Line ~2279: `shake=Math.max(shake,22)`
  - Line ~2499: `shake=8`
  - Line ~2637: `shake=Math.max(shake,6)`
  - Line ~2783: `shake=10`
  - Line ~2798: `shake=9`
  - Line ~2799: `shake=16`
  - Line ~5236: `shake=10`
  - Line ~5488: `shake=8`
  - Line ~5502: `shake=14`
  - Line ~6749: `shake=30`

  Do NOT gate line ~499 (declaration) or line ~6717 (camera application/decay).

- [ ] **Step 2: Verify in browser**

  Open Setup → set Screen Shake to OFF. Play a game, take damage, kill enemies. Camera should not shake. Return to Setup → set to ON → play again → shake should return.

- [ ] **Step 3: Commit**

  ```
  git add index.php
  git commit -m "feat: gate screen shake via settings.screenShake"
  ```

---

## Task 8: Particle intensity scaling

**Files:** Modify `index.php`

- [ ] **Step 1: Add `_pCount()` helper**

  Find the `spawnParts` function definition (line ~570). Directly before it, add:

  ```javascript
  function _pCount(n){
    if(settings.particles==='off') return 0;
    if(settings.particles==='reduced') return Math.max(1,Math.ceil(n*0.4));
    return n;
  }
  ```

- [ ] **Step 2: Wrap all `spawnParts` count arguments**

  `spawnParts` has the signature `spawnParts(x, y, col, count, ...)`. The 4th argument is `count`. For every call to `spawnParts` in the file, wrap the count argument with `_pCount(...)`.

  There are ~90 calls. The count argument is always a numeric literal or simple expression. Examples:

  ```javascript
  // Before:
  spawnParts(P.x,P.y,P.color,35,7.5,9.5,1100)
  spawnParts(P.x,P.y,'#ffffff',15,5,4,700)
  spawnParts(e.x,e.y,e.color,14,3.5,5,900)

  // After:
  spawnParts(P.x,P.y,P.color,_pCount(35),7.5,9.5,1100)
  spawnParts(P.x,P.y,'#ffffff',_pCount(15),5,4,700)
  spawnParts(e.x,e.y,e.color,_pCount(14),3.5,5,900)
  ```

  Use a global find-replace across the file for the pattern `spawnParts(` — review each match. The count is always the 4th positional argument. Wrap it individually.

- [ ] **Step 3: Handle zero count in `spawnParts`**

  Open the `spawnParts` function definition (~line 570). Ensure it handles `count=0` gracefully (early return if `count <= 0`):

  ```javascript
  function spawnParts(x,y,col,count,...rest){
    if(count<=0) return;
    // ... existing body
  }
  ```

  If an early return already exists or count is used in a loop that naturally handles 0, no change needed.

- [ ] **Step 4: Verify in browser**

  Open Setup → set Particle Intensity to OFF. Play a game — no particles should appear on hits, kills, explosions. Set to REDUCED — roughly 40% of normal particle density. Set to FULL — normal particles.

- [ ] **Step 5: Commit**

  ```
  git add index.php
  git commit -m "feat: particle intensity scaling via settings.particles"
  ```

---

## Self-Review Checklist

- [x] **Spec coverage:**
  - Sound toggle on title screen → Task 4 Step 5–6
  - Pointer cursor on title/setup buttons → Task 4 Step 7
  - Setup state in state machine → Task 4 Step 3
  - Three audio buses → Task 2
  - SFX routing → Task 3
  - Music/SFX/UI volume sliders → Task 5 (draw) + Task 6 (interact)
  - Particle intensity → Task 5 (draw) + Task 6 (interact) + Task 8
  - Screen shake toggle → Task 5 (draw) + Task 6 (interact) + Task 7
  - HOF clear with confirm → Task 5 `_drawHofClearBtn` + Task 6 Step 4
  - Back button → Task 5 Step 4 + Task 6 Step 4
  - `pw_settings` persistence → Task 1
  - `_applyVolumes()` called on change → Task 2 Step 6 + Task 6 Steps 1–2

- [x] **No placeholders** — all steps contain complete code.

- [x] **Type consistency** — `_getSetupLayout`, `_particleBtnRects`, `_shakeBtnRects`, `_applyVolumes`, `_pCount`, `_roundRect`, `_drawSlider`, `_drawToggle2`, `_drawToggle3`, `_drawHofClearBtn` all defined before first use and referenced consistently throughout.
