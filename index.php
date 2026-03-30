<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, minimal-ui">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-fullscreen">
<meta name="mobile-web-app-capable" content="yes">
<title>PATROL WING v3</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;height:100%;background:#000;overflow:hidden;font-family:'Courier New',monospace;}
canvas{display:block;cursor:none;position:absolute;top:0;left:0;}
#colorPick{position:absolute;opacity:0;pointer-events:none;width:1px;height:1px;top:0;left:0;}
.adSlot{position:fixed;display:none;flex-direction:column;align-items:center;z-index:20;pointer-events:none;}
.adSlot .adLabel{font-family:'Courier New',monospace;font-size:8px;color:rgba(0,140,200,0.45);letter-spacing:2px;margin-bottom:3px;text-transform:uppercase;}
.adSlot .adBox{width:300px;height:250px;background:#070f1e;border:1px solid rgba(0,80,160,0.35);display:flex;align-items:center;justify-content:center;}
.adSlot .adBox span{color:rgba(0,100,170,0.35);font-family:'Courier New',monospace;font-size:9px;text-align:center;line-height:1.8;}
#adSlot1{top:50%;left:50%;transform:translate(-50%,-50%) translateY(-80px);}
@media(max-width:700px){.adSlot{display:none!important;}}
</style>
</head>
<body>
<canvas id="c"></canvas>
<input type="color" id="colorPick" value="#00ddff">
<input type="text" id="editorNameInput" style="position:absolute;opacity:0;pointer-events:none;width:1px;height:1px;top:0;left:0;" maxlength="40">
<div id="adSlot1" class="adSlot">
  <div class="adLabel">Advertisement</div>
  <div class="adBox"><span><!-- Google AdSense 300×250 --><br>Replace with AdSense tag</span></div>
</div>
<script>
// ================================================================
//  PATROL WING  v3.0  —  HANGAR · 4 CRAFTS · COLOR FORGE · COMBAT
// ================================================================
const canvas=document.getElementById('c'),ctx=canvas.getContext('2d');
const colorPick=document.getElementById('colorPick');
const editorNameInput=document.getElementById('editorNameInput');
let gameMode='battle';  // hoisted — needed by resize() before main game state block
let gameState='intro';  // hoisted — needed by resize() before main game state block
function resize(){
  canvas.width=window.innerWidth;
  canvas.height=window.innerHeight;
  if(gameMode==='timetrial'&&gameState==='playing'){
    WORLD_H=(typeof ttLevel!=='undefined'&&(ttLevel===2||ttLevel===4||ttLevel===5))?(ttLevel===4?JRR_WORLD_H:ttLevel===5?TNG_WORLD_H:TT_WORLD_H2):canvas.height;
  }
}
resize();window.addEventListener('resize',resize);

// ─── AUDIO ───────────────────────────────────────────────────────
let AC=null;
function initAudio(){
  if(!AC) AC=new(window.AudioContext||window.webkitAudioContext)();
  Music.init();
  // On first touch gesture, push into fullscreen to hide address bar
  if(IS_TOUCH && !document.fullscreenElement){
    const el=document.documentElement;
    (el.requestFullscreen||el.webkitRequestFullscreen||el.mozRequestFullScreen||el.msRequestFullscreen||function(){}
    ).call(el);
  }
}
function beep(f,t,d,v,sw=0,dest){
  if(!AC)return;
  if(typeof Music!=='undefined'&&Music.isMuted())return;
  try{
  const o=AC.createOscillator(),g=AC.createGain();
  o.connect(g);g.connect(dest||(typeof Music!=='undefined'?Music.sfxNode():AC.destination));
  o.type=t;o.frequency.setValueAtTime(f,AC.currentTime);
  if(sw)o.frequency.exponentialRampToValueAtTime(sw,AC.currentTime+d);
  g.gain.setValueAtTime(v,AC.currentTime);
  g.gain.exponentialRampToValueAtTime(0.001,AC.currentTime+d);
  o.start();o.stop(AC.currentTime+d);}catch(e){}}
const SFX={
  std:    ()=>beep(800,'square',0.07,0.12,350),
  rapid:  ()=>beep(1200,'square',0.04,0.07,700),
  dinf:   ()=>beep(900,'sine',0.04,0.06,600),
  spread:   ()=>{beep(600,'sawtooth',0.08,0.09,240);beep(800,'sawtooth',0.06,0.07,320);},
  boomr:    ()=>{beep(440,'sine',0.12,0.18,350);setTimeout(()=>beep(560,'sine',0.08,0.12,280),100);},
  sawtooth: ()=>{beep(500,'sawtooth',0.06,0.07,200);beep(720,'sawtooth',0.05,0.06,280);},
  burst:  ()=>{[0,85,170].forEach(d=>setTimeout(()=>beep(950,'square',0.06,0.10,420),d));},
  plasma: ()=>beep(200,'sawtooth',0.20,0.25,60),
  rico:   ()=>{beep(160,'sawtooth',0.18,0.22,55);setTimeout(()=>beep(320,'square',0.06,0.10,200),40);},
  ricobounce:()=>beep(600,'square',0.04,0.06,120),
  hit:    ()=>beep(180,'sawtooth',0.22,0.28,70),
  boom:   ()=>{beep(110,'sawtooth',0.55,0.28,35);beep(75,'sine',0.45,0.18,28);},
  pickup: ()=>beep(480,'sine',0.18,0.22,900),
  weapon: ()=>{beep(440,'sine',0.14,0.2,880);setTimeout(()=>beep(660,'sine',0.18,0.15,1200),120);},
  shield: ()=>beep(280,'sine',0.38,0.22,620),
  shbreak:()=>beep(400,'sawtooth',0.18,0.28,100),
  emp:    ()=>{beep(140,'sawtooth',0.6,0.32,38);beep(70,'square',0.5,0.22,28);},
  overchg:()=>{[0,100,200].forEach((d,i)=>setTimeout(()=>beep(330+i*110,'sine',0.18,0.18-i*0.03,(i+1)*660),d));},
  wave:   ()=>beep(300,'triangle',0.4,0.2,600,Music.uiNode()),
  boss:   ()=>beep(60,'sawtooth',0.8,0.3,40),
  wallhit:()=>beep(250,'square',0.05,0.06,180),
  fractal:()=>{beep(180,'sawtooth',0.14,0.06,60);setTimeout(()=>beep(340,'square',0.10,0.04,50),30);setTimeout(()=>beep(520,'sawtooth',0.08,0.03,40),60);},
  select: ()=>{beep(520,'sine',0.12,0.18,780,Music.uiNode());},
  confirm:()=>{beep(440,'sine',0.10,0.16,880,Music.uiNode());setTimeout(()=>beep(660,'sine',0.14,0.14,1100,Music.uiNode()),110);setTimeout(()=>beep(880,'sine',0.18,0.12,1320,Music.uiNode()),240);},
  mineset:()=>{beep(180,'square',0.08,0.14,120);setTimeout(()=>beep(90,'square',0.06,0.10,60),90);},
  minedet:()=>{beep(80,'sawtooth',0.7,0.38,28);beep(140,'sawtooth',0.5,0.28,35);beep(60,'sine',0.55,0.22,24);},
  stun:   ()=>{[0,40,80].forEach(d=>setTimeout(()=>beep(1800,'square',0.04,0.09,900),d));beep(600,'sawtooth',0.06,0.06,40);},
  mmdeploy:()=>{beep(660,'sine',0.12,0.18,880);setTimeout(()=>beep(880,'sine',0.14,0.14,1100),100);setTimeout(()=>beep(1100,'sine',0.10,0.10,1320),200);},
  mmdead: ()=>{beep(440,'sawtooth',0.22,0.20,80);setTimeout(()=>beep(220,'sawtooth',0.18,0.14,60),80);},
  laser:  ()=>{beep(120,'sawtooth',0.55,0.35,45);beep(2400,'square',0.08,0.30,900);setTimeout(()=>beep(80,'sine',0.45,0.20,30),60);},
  seekr:  ()=>{beep(320,'sine',0.10,0.20,600);setTimeout(()=>beep(280,'sine',0.07,0.15,400),80);},
  seekboom:()=>{beep(160,'sawtooth',0.45,0.22,38);beep(90,'sine',0.35,0.18,30);},
};

// ═══════════════════════════════════════════════════════════════
//  PATROL WING MUSIC ENGINE
//  "Ghost Signal" — Am-pentatonic adaptive theme
//
//  One 8-note melody cycles at four tempos depending on game state:
//    ambient  (80 BPM)  — intro, menus, pause, wave clear
//    patrol  (118 BPM)  — playing, no recent action
//    combat  (152 BPM)  — shooting or receiving damage recently
//    danger  (180 BPM)  — HP < 28 % or Wave-5 boss alive
//
//  M key toggles mute during gameplay (indicator on HUD + pause screen)
// ═══════════════════════════════════════════════════════════════
const Music = (function(){

  // ── Melody: Am pentatonic, 8 notes, repeats ──────────────────
  // E4  A4  G4  E4  D4  C4  E4  A3
  const MEL  = [329.63, 440.00, 392.00, 329.63, 293.66, 261.63, 329.63, 220.00];
  // Bass root notes, 4 steps (1 per 2 melody notes)
  const BASS = [110.00, 82.41, 110.00, 98.00];   // A2 E2 A2 G2
  // Arpeggio chord tones, 4 steps (fast — gives urgency in combat)
  const ARP  = [220.00, 261.63, 329.63, 440.00]; // A3 C4 E4 A4

  // ── Per-mode parameters ───────────────────────────────────────
  // melIv  = seconds per melody note (tempo driver)
  // bassIv = seconds per bass note   (2 × melIv for musical phrasing)
  // arpIv  = seconds per arp note    (melIv / 4 for 16th-note feel)
  // melV / bassV / arpV = peak gain per voice
  // fc     = lowpass cutoff (Hz) — opens up in combat
  // fbk    = delay feedback (0–1) — more reverb in ambient
  const MODES = {
    ambient:{ melIv:0.750, bassIv:3.000, arpIv:0.1875, melV:0.048, bassV:0.052, arpV:0,     fc:620,  fbk:0.38 },
    patrol: { melIv:0.508, bassIv:2.032, arpIv:0.127,  melV:0.062, bassV:0.072, arpV:0.036, fc:1700, fbk:0.26 },
    combat: { melIv:0.394, bassIv:1.576, arpIv:0.099,  melV:0.082, bassV:0.105, arpV:0.062, fc:3800, fbk:0.18 },
    danger: { melIv:0.333, bassIv:1.332, arpIv:0.083,  melV:0.094, bassV:0.125, arpV:0.078, fc:6200, fbk:0.10 },
  };

  // ── Internal state ────────────────────────────────────────────
  let masterGain=null, melGain=null, bassGain=null, arpGain=null, sfxGain=null, uiGain=null;
  let filterNode=null, delayNode=null, feedbackGain=null;
  let melStep=0, bassStep=0, arpStep=0;
  let nextMelT=0, nextBassT=0, nextArpT=0;
  let currentMode='ambient', targetMode='ambient';
  let muted=false, ready=false;
  let lastShotMs=0, lastHitMs=0;

  const LOOK_AHEAD = 0.18;  // schedule this many seconds ahead
  const SCHED_MS   = 35;    // scheduler poll interval (ms)

  // ── Build audio graph ─────────────────────────────────────────
  function _init(){
    if(ready || !AC) return;
    try{
      // Compressor at the end — keeps levels controlled
      const comp = AC.createDynamicsCompressor();
      comp.threshold.value = -20; comp.knee.value = 14;
      comp.ratio.value = 5;  comp.attack.value = 0.006;
      comp.release.value = 0.18;
      comp.connect(AC.destination);

      // Master volume bus
      masterGain = AC.createGain();
      masterGain.gain.setValueAtTime(0.42, AC.currentTime); // menus start quieter
      masterGain.connect(comp);
      sfxGain = AC.createGain();
      sfxGain.gain.setValueAtTime(settings.sfxVol, AC.currentTime);
      sfxGain.connect(comp);
      uiGain = AC.createGain();
      uiGain.gain.setValueAtTime(settings.uiVol, AC.currentTime);
      uiGain.connect(comp);

      // Global lowpass — sweeps open during combat
      filterNode = AC.createBiquadFilter();
      filterNode.type = 'lowpass';
      filterNode.frequency.setValueAtTime(620, AC.currentTime);
      filterNode.Q.value = 0.7;
      filterNode.connect(masterGain);

      // Short hall delay — feeds back into filter input for reverb feel
      delayNode    = AC.createDelay(0.6);
      delayNode.delayTime.value = 0.14;
      feedbackGain = AC.createGain();
      feedbackGain.gain.setValueAtTime(0.38, AC.currentTime);
      delayNode.connect(feedbackGain);
      feedbackGain.connect(delayNode);   // feedback loop
      delayNode.connect(filterNode);     // wet signal to filter

      // Voice gain buses — melody and arp also feed the delay wet path
      melGain  = AC.createGain(); melGain.gain.setValueAtTime(0.048, AC.currentTime);
      bassGain = AC.createGain(); bassGain.gain.setValueAtTime(0.052, AC.currentTime);
      arpGain  = AC.createGain(); arpGain.gain.setValueAtTime(0,     AC.currentTime);
      melGain.connect(filterNode); melGain.connect(delayNode);  // melody has reverb
      bassGain.connect(filterNode);                             // bass direct (no reverb)
      arpGain.connect(filterNode);                              // arp direct

      // Start the scheduler
      const now = AC.currentTime;
      nextMelT  = now + 0.08;
      nextBassT = now + 0.08;
      nextArpT  = now + 0.08;
      setInterval(_scheduler, SCHED_MS);
      ready = true;
    }catch(e){ console.warn('Music init failed:', e); }
  }

  // ── Schedule one note ─────────────────────────────────────────
  function _note(freq, type, gainBus, vol, dur, t){
    if(!AC || muted) return;
    try{
      const o = AC.createOscillator();
      const g = AC.createGain();
      o.connect(g); g.connect(gainBus);
      o.type = type;
      o.frequency.value = freq;
      // Smooth attack / exponential decay envelope
      g.gain.setValueAtTime(0, t);
      g.gain.linearRampToValueAtTime(vol, t + 0.018);
      g.gain.setValueAtTime(vol * 0.85, t + dur * 0.55);
      g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
      o.start(t); o.stop(t + dur + 0.025);
    }catch(e){}
  }

  // ── Look-ahead scheduler (runs every SCHED_MS milliseconds) ───
  function _scheduler(){
    if(!AC || !ready) return;
    const now = AC.currentTime;
    const p   = MODES[currentMode];

    // Melody — triangle wave: warm, musical, cuts through without harshness
    while(nextMelT < now + LOOK_AHEAD){
      _note(MEL[melStep % MEL.length], 'triangle', melGain, p.melV, p.melIv * 0.68, nextMelT);
      melStep++;
      nextMelT += p.melIv;
    }

    // Bass — sawtooth: fat low-end body; pitched at musically timed intervals
    while(nextBassT < now + LOOK_AHEAD){
      _note(BASS[bassStep % BASS.length], 'sawtooth', bassGain, p.bassV, p.bassIv * 0.82, nextBassT);
      // Subtle octave-up overtone on bass for definition
      _note(BASS[bassStep % BASS.length] * 2, 'triangle', bassGain, p.bassV * 0.22, p.bassIv * 0.55, nextBassT);
      bassStep++;
      nextBassT += p.bassIv;
    }

    // Arpeggio — sine: crystalline 16th-note glimmer; silent in ambient
    while(nextArpT < now + LOOK_AHEAD){
      if(p.arpV > 0){
        _note(ARP[arpStep % ARP.length], 'sine', arpGain, p.arpV, p.arpIv * 0.48, nextArpT);
      }
      arpStep++;
      nextArpT += p.arpIv;
    }

    // Crossfade into new mode if needed
    if(currentMode !== targetMode){
      currentMode = targetMode;
      const np = MODES[currentMode];
      const tgt = now + 1.6; // 1.6s transition feels natural

      // Ramp voice volumes
      melGain.gain.linearRampToValueAtTime(np.melV,  tgt);
      bassGain.gain.linearRampToValueAtTime(np.bassV, tgt);
      arpGain.gain.linearRampToValueAtTime(np.arpV,  tgt);
      // Sweep filter cutoff
      filterNode.frequency.linearRampToValueAtTime(np.fc, tgt);
      // Adjust delay feedback (more space in calm modes)
      feedbackGain.gain.linearRampToValueAtTime(np.fbk, tgt);
    }
  }

  // ── Mode selection (called every frame from main loop) ────────
  function _tick(){
    if(!AC){ return; }
    if(!ready){ _init(); return; }

    const now     = Date.now();
    const playing = (gameState === 'playing');
    const shot    = playing && (now - lastShotMs < 3500);
    const hit     = playing && (now - lastHitMs  < 5000);
    const lowHp   = playing && typeof P !== 'undefined' && P.alive && (P.hp / P.maxHp) < 0.28;
    const boss    = playing && typeof enemies !== 'undefined' && wave >= 5 && enemies.some(e => e.type === 'boss');

    let mode;
    if(!playing)            mode = 'ambient';
    else if(lowHp || boss)  mode = 'danger';
    else if(shot || hit)    mode = 'combat';
    else                    mode = 'patrol';

    if(mode !== targetMode) targetMode = mode;

    // Dim music during menus; full volume during play — only ramp when value changes
    const baseVol = playing ? 0.72 : 0.38;
    const wantVol = muted ? 0 : (baseVol * settings.musicVol);
    if(Math.abs(masterGain.gain.value - wantVol) > 0.005){
      masterGain.gain.setTargetAtTime(wantVol, AC.currentTime, 0.9);
    }
  }

  // ── Public API ────────────────────────────────────────────────
  return {
    init:    _init,
    tick:    _tick,
    onShot:  ()=>{ lastShotMs = Date.now(); },
    onHit:   ()=>{ lastHitMs  = Date.now(); },
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
    isMuted: ()=> muted,
    mode:    ()=> currentMode,
    sfxNode: ()=> sfxGain || AC.destination,
    uiNode:  ()=> uiGain  || AC.destination,
  };
})();

// ─── INPUT ───────────────────────────────────────────────────────
const K={},mouse={x:0,y:0,down:false,justDown:false};

// ── Touch detection ───────────────────────────────────────────────
const IS_TOUCH = ('ontouchstart' in window) || navigator.maxTouchPoints > 0;
const STICK_R      = 78;   // outer ring radius — larger = more precise range of motion
const STICK_NUB    = 26;   // knob radius
const STICK_DEAD   = 0.12; // deadzone fraction of STICK_R
const TOUCH_SPD_MULT = 0.42; // scale down touch speed vs keyboard (feels smoother, less instant)
const touchSticks = {
  L: {active:false,id:null,ox:0,oy:0,dx:0,dy:0},
  R: {active:false,id:null,ox:0,oy:0,dx:0,dy:0},
};
function resetTouchSticks(){ for(const s of Object.values(touchSticks)){s.active=false;s.id=null;s.dx=0;s.dy=0;} }
// Reset floating stick origins whenever screen dimensions change
if(IS_TOUCH){
  window.addEventListener('resize', resetTouchSticks);
  window.addEventListener('orientationchange', resetTouchSticks);
}
// Helper: returns layout constants for the top-right HUD panel (score + minimap)
function trLayout(){
  return IS_TOUCH
    ? {scoreY:22, labelY:12, mmY:100}
    : {scoreY:22, labelY:12, mmY:94};
}
function weaponBarY(){ return IS_TOUCH ? canvas.height-132 : canvas.height-52; }
function mlLayout(){
  const pbW=44,pbH=28,muteW=28,muteH=28,gap=4;
  if(IS_TOUCH){
    const y=canvas.height-STICK_R*2-14-Math.max(pbH,muteH);
    return{pbX:10,pbY:y,pbW,pbH,muteX:10+pbW+gap,muteY:y,muteW,muteH};
  }
  const y=canvas.height-14-pbH;
  return{pbX:14,pbY:y,pbW,pbH,muteX:14+pbW+gap,muteY:y,muteW,muteH};
}
window.addEventListener('keydown',e=>{
  if(!K[e.code]){K[e.code]=true;initAudio();}
  if(['Space','ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(e.code))e.preventDefault();
  // Music mute toggle — M key, works in any state
  if(e.code==='KeyM'){ Music.toggleMute(); }
  // Pause toggle
  if((e.code==='KeyP'||e.code==='Escape')&&(gameState==='playing'||gameState==='paused'||gameState==='loadoutEdit')){
    if(gameState==='loadoutEdit'){
      if(loadoutEditFrom==='hangar'){_saveLoadout(CRAFTS[hangarCraft].id,hangarLoadout);gameState='hangar';}
      else{_saveLoadout(CRAFTS[P.craftIdx].id,P.loadout);gameState='paused';}
      return;
    }
    if(gameState==='paused'&&screenLockMs>0)return; // locked
    gameState= gameState==='paused' ? 'playing' : 'paused';
    if(gameState==='paused') screenLockMs=2000;
    lastTime=performance.now(); // prevent dt spike on resume
  }
});
window.addEventListener('keyup',e=>{K[e.code]=false;});
// mouse position handled by pointer-lock-aware listener below
canvas.addEventListener('contextmenu',e=>e.preventDefault());
document.addEventListener('contextmenu',e=>e.preventDefault());
canvas.addEventListener('mousedown',e=>{
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
  if(e.button!==0) return;
  initAudio();
  if(gameState==='setup'){
    const L=_getSetupLayout(canvas.width,canvas.height);
    for(const s of L.sliders){
      const th=L.trackThumbR*3;
      if(mouse.x>=L.trackX&&mouse.x<=L.trackX+L.trackW&&mouse.y>=s.y-th&&mouse.y<=s.y+th){
        setupSliderDrag={key:s.key,trackX:L.trackX,trackW:L.trackW};
        settings[s.key]=Math.max(0,Math.min(1,(mouse.x-L.trackX)/L.trackW));
        _saveSettings();_applyVolumes();
        return;
      }
    }
  }
  // Suppress fire if click lands on the weapon bar
  if(gameState==='playing'){
    const slotW=38, slotH=38, slotGap=5, total=WEAPONS.length;
    const barX=(canvas.width-total*(slotW+slotGap)+slotGap)/2, barY=weaponBarY();
    const mx=e.clientX, my=e.clientY;
    if(my>barY&&my<barY+slotH&&mx>barX&&mx<barX+total*(slotW+slotGap)){
      mouse.down=false; mouse.justDown=false; return;
    }
  }
  mouse.down=true; mouse.justDown=true;
});
canvas.addEventListener('mouseup',  e=>{if(e.button!==0)return; mouse.down=false; if(setupSliderDrag)setupSliderDrag=null;});
canvas.addEventListener('mouseup',  e=>{
  if(e.button!==2)return;
  // Portal: right-click cycles through portals (same as Space)
  if(portalActive){
    portalSelected=(portalSelected+1)%portalPositions.length;
    const prev=portalPositions[portalSelected];
    camX=clamp(prev.x-canvas.width/2,0,Math.max(0,WORLD_W-canvas.width));
    camY=(gameMode==='timetrial'&&(ttLevel===1||ttLevel===3))?0:clamp(prev.y-canvas.height/2,0,Math.max(0,WORLD_H-canvas.height));
    SFX.select();return;
  }
  if(gameState!=='playing')return;
  const ci=P.loadout.indexOf(P.weaponIdx);
  P.weaponIdx=P.loadout[(ci+1)%P.loadout.length];
});
canvas.addEventListener('contextmenu',e=>e.preventDefault());
document.addEventListener('mouseup',e=>{if(e.button!==0)return; mouse.down=false;});
canvas.addEventListener('wheel',(e)=>{
  if(gameState==='levelEditor'&&mouse.x<180){
    editorSidebarScroll=Math.max(0,editorSidebarScroll+e.deltaY*0.5);
    e.preventDefault();
  }
},{passive:false});

// ─── CONSTANTS ───────────────────────────────────────────────────
let WORLD_W=2600,WORLD_H=1700;
const TOTAL_WAVES=5;
const clamp=(v,lo,hi)=>v<lo?lo:v>hi?hi:v;
const dist2=(ax,ay,bx,by)=>(ax-bx)**2+(ay-by)**2;
const dist=(ax,ay,bx,by)=>Math.sqrt((ax-bx)**2+(ay-by)**2);
const rng=(a,b)=>a+(b-a)*Math.random();
function hexToRgb(h){return[parseInt(h.slice(1,3),16),parseInt(h.slice(3,5),16),parseInt(h.slice(5,7),16)];}
function lighten(h,a=80){const[r,g,b]=hexToRgb(h);return`rgb(${Math.min(255,r+a)},${Math.min(255,g+a)},${Math.min(255,b+a)})`;}

// ─── WEAPONS ─────────────────────────────────────────────────────
const WEAPONS=[
  {id:'std',      name:'STANDARD',     color:'#00eeff',fireMs:190, dmg:28, spd:16,count:1,spread:0,   bSz:3.5,stock:null},
  {id:'rapid',    name:'RAPID FIRE',   color:'#ffee00',fireMs:68,  dmg:12, spd:20,count:1,spread:0,   bSz:2.5,stock:1000},
  {id:'stun',     name:'STUN GUN',     color:'#aaff44',fireMs:320, dmg:0,  spd:18,count:1,spread:0,   bSz:3.0,stock:null},
  {id:'spread',   name:'SPREAD SHOT',  color:'#ff8800',fireMs:265, dmg:19, spd:14,count:3,spread:0.28,bSz:3.0,stock:100},
  {id:'boomr',    name:'BOOMERANG',    color:'#00ffaa',fireMs:500, dmg:25, spd:0, count:1,spread:0,   bSz:0,  stock:100},
  {id:'sawtooth', name:'SAWTOOTH',     color:'#ff6600',fireMs:80,  dmg:19, spd:14,count:3,spread:0.28,bSz:3.0,stock:200},
  {id:'fractal',  name:'FRACTAL FUSION',color:'#ff9900',fireMs:480, dmg:8,  spd:0, count:1,spread:0,   bSz:0,  stock:25},
  {id:'grapple',  name:'GRAPPLING HOOK',color:'#44ddff',fireMs:700, dmg:0,  spd:18,count:1,spread:0,   bSz:4,  stock:20},
  {id:'plasma',   name:'PLASMA CANNON',color:'#ff44cc',fireMs:540, dmg:96, spd:10,count:1,spread:0,   bSz:8.5,stock:50},
  {id:'minime',   name:'J R',           color:'#44ffcc',fireMs:0,   dmg:0,  spd:0, count:0,spread:0,   bSz:0,  stock:null},
  {id:'tractor',  name:'TRACTOR FORCE', color:'#44aaff',fireMs:0,   dmg:0,  spd:0, count:1,spread:0,   bSz:0,  stock:50000},
  {id:'burst',    name:'BURST CANNON', color:'#cc55ff',fireMs:340, dmg:25, spd:16,count:3,spread:0.07,bSz:3.2,stock:500},
  {id:'rico',     name:'RICO CANNON',  color:'#cc88ff',fireMs:600, dmg:96, spd:10,count:1,spread:0,   bSz:8.5,stock:30},
  {id:'faraday',  name:'FARADAY CAGE',    color:'#88ffcc',fireMs:800, dmg:0,  spd:0, count:1,spread:0,   bSz:0,  stock:25},
  {id:'mine',     name:'PROX MINE',    color:'#ff2200',fireMs:600, dmg:0,  spd:0, count:1,spread:0,   bSz:0,  stock:null},
  {id:'laser',    name:'LASER',        color:'#ff66ff',fireMs:3000,dmg:150,spd:0, count:1,spread:0,   bSz:0,  stock:20},
  {id:'rocket',   name:'ROCKET LAUNCHER',color:'#ff5500',fireMs:700, dmg:65, spd:12,count:1,spread:0,   bSz:0,  stock:15},
  {id:'seekr',    name:'SEEK MISSILE', color:'#ffaa00',fireMs:500, dmg:0,  spd:0, count:1,spread:0,   bSz:0,  stock:null},
  {id:'dinf',     name:'DIGITAL INFECTION',color:'#00ff88',fireMs:72,dmg:14,spd:20,count:1,spread:0,  bSz:2.5,stock:800},
  {id:'grenade',  name:'GRENADE LAUNCHER',color:'#ffaa22',fireMs:900, dmg:0,  spd:11,count:1,spread:0,   bSz:6,  stock:15},
  {id:'gravwell', name:'GRAVITY WELL',    color:'#cc44ff',fireMs:1800,dmg:0,  spd:0, count:1,spread:0,   bSz:0,  stock:8},
  {id:'leech',    name:'LEECH RAY',       color:'#00ff88',fireMs:800, dmg:60, spd:0, count:1,spread:0,   bSz:0,  stock:12},
  {id:'shockwave',name:'SHOCKWAVE CANNON',color:'#ff8844',fireMs:1400,dmg:50, spd:0, count:1,spread:0,   bSz:0,  stock:8},
];
const MINE_TRIGGER_R=110;
const MINE_BLAST_R  =145;
const MM_HP        =45;   // miniMe starting HP
const MM_SIZE      =9;    // collision radius
const MM_FIRE_MS   =480;  // fire interval ms
const MM_DMG       =16;   // bullet damage (can kill, less than standard 28)
const MM_SPD_BLT   =15;   // bullet speed
const MM_DET       =340;  // enemy detection range
const MM_ORBIT_R   =115;  // preferred orbit radius around player
const MM_MAX_RANGE =380;  // hard leash — always returns within this
const MM_BATT_MULT =1.25; // extra battery drain multiplier while active
const MM_COL       ='#44ffcc';
const MM_ACC       ='#aaffee';
// Global miniMe state
let miniMe={active:false,lost:false,x:0,y:0,vx:0,vy:0,aim:0,rotor:0,hp:MM_HP,iframes:0,orbitAngle:0,lastFired:0};

// ─── CRAFT DEFINITIONS ────────────────────────────────────────────
const CRAFTS=[
  {
    id:'phantom',name:'PHANTOM',sub:'RECON CLASS',
    desc:'Balanced all-purpose combat drone. Adaptive systems provide steady performance across all mission profiles.',
    stats:{speed:3,armor:3,fire:3,battery:3},
    hp:100,spd:5.2,batDrain:2.4,size:18,drag:0.87,
    ability:'ADAPTIVE  —  Weapon fire rate +5% per wave cleared',
    startWeapon:0,damageMult:1.0,detMult:1.0,
    defaultColor:'#00ddff',maxSlots:7,
  },
  {
    id:'viper',name:'VIPER',sub:'ASSAULT CLASS',
    desc:'High-speed interceptor built for hit-and-run. Fragile but blindingly fast with enhanced boost recharge.',
    stats:{speed:5,armor:2,fire:4,battery:2},
    hp:72,spd:7.8,batDrain:3.9,size:14,drag:0.84,
    ability:'OVERDRIVE  —  Boost battery drains 60% slower',
    startWeapon:1,damageMult:1.0,detMult:1.0,
    defaultColor:'#ff3300',maxSlots:4,
  },
  {
    id:'titan',name:'TITAN',sub:'HEAVY CLASS',
    desc:'Armored assault platform. Slow to maneuver but absorbs enormous punishment. Starts with spread ordnance.',
    stats:{speed:2,armor:5,fire:2,battery:4},
    hp:185,spd:3.3,batDrain:1.4,size:24,drag:0.90,
    ability:'IRON CRAFT  —  Incoming damage reduced 28%',
    startWeapon:3,damageMult:0.72,detMult:1.0,
    defaultColor:'#ff8800',maxSlots:10,
  },
  {
    id:'specter',name:'SPECTER',sub:'STEALTH CLASS',
    desc:'Electronic warfare specialist. Ghostlike signature shrinks enemy detection radius. Starts combat with EMP.',
    stats:{speed:4,armor:2,fire:3,battery:3},
    hp:78,spd:6.2,batDrain:2.7,size:15,drag:0.85,
    ability:'GHOST FIELD  —  Enemy detection range –38%',
    startWeapon:0,damageMult:1.0,detMult:0.62,startEMP:true,
    defaultColor:'#aa44ff',maxSlots:6,
  },
  {
    id:'sniper',name:'SNIPER',sub:'PRECISION CLASS',
    desc:'Long-range elimination specialist. Patience is a weapon — the longer between shots, the greater the damage.',
    stats:{speed:4,armor:1,fire:2,battery:3},
    hp:62,spd:6.0,batDrain:2.2,size:15,drag:0.85,
    ability:'DEAD EYE  —  Damage scales ×1–3 with time between shots (max at 2s)',
    startWeapon:11,damageMult:1.0,detMult:1.0,
    defaultColor:'#44ffcc',maxSlots:6,
  },
  {
    id:'carrier',name:'CARRIER',sub:'COMMAND CLASS',
    desc:'Deploys two attack drones that engage nearby enemies autonomously. Command field slows hostile fire rate.',
    stats:{speed:2,armor:3,fire:3,battery:4},
    hp:140,spd:3.8,batDrain:1.8,size:22,drag:0.91,
    ability:'COMMAND FIELD  —  2 attack drones + enemy fire rate –25% in range',
    startWeapon:0,damageMult:1.0,detMult:1.0,
    defaultColor:'#00aaff',maxSlots:9,
  },
  {
    id:'skirmisher',name:'SKIRMISHER',sub:'AGILITY CLASS',
    desc:'Lowest drag in the fleet. Sharp direction reversals under fire trigger split-second dodge frames.',
    stats:{speed:4,armor:2,fire:4,battery:2},
    hp:80,spd:6.5,batDrain:2.9,size:16,drag:0.78,
    ability:'SLIP STREAM  —  Direction reversal under fire grants 0.4s invincibility',
    startWeapon:10,damageMult:1.0,detMult:1.0,
    defaultColor:'#ff44aa',maxSlots:5,
  },
];
const HANGAR_VISIBLE=4;

const SNIPER_IDX=()=>CRAFTS.findIndex(c=>c.id==='sniper');
const CARRIER_IDX=()=>CRAFTS.findIndex(c=>c.id==='carrier');
const SKIRMISHER_IDX=()=>CRAFTS.findIndex(c=>c.id==='skirmisher');

// ─── SELECTION STATE ─────────────────────────────────────────────
const HANGAR_CRAFT_KEY='pw_hangar_craft';
const HANGAR_COLOR_KEY='pw_hangar_color';
function _saveHangar(){ try{localStorage.setItem(HANGAR_CRAFT_KEY,selectedCraft);localStorage.setItem(HANGAR_COLOR_KEY,selectedColor);}catch(e){} }
function _loadHangar(){ try{const c=localStorage.getItem(HANGAR_CRAFT_KEY);const col=localStorage.getItem(HANGAR_COLOR_KEY);if(c!==null){selectedCraft=parseInt(c)||0;}if(col){selectedColor=col;colorPick.value=col;}}catch(e){} }
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
function _saveCustomLevels(packs){
  try{localStorage.setItem('pw_custom_levels',JSON.stringify(packs));}catch(e){}
}
function _loadCustomLevels(){
  try{
    const stored=JSON.parse(localStorage.getItem('pw_custom_levels'))||[];
    // Seed Training Protocols if no packs exist yet
    if(stored.length===0){
      stored.push(TRAINING_PROTOCOLS);
      _saveCustomLevels(stored);
    }
    return stored;
  }catch(e){return[TRAINING_PROTOCOLS];}
}
const TRAINING_PROTOCOLS={
  packName:'Training Protocols',author:'PATROL WING',created:Date.now(),
  levels:[
    {
      name:'Target Practice',author:'PATROL WING',created:Date.now(),
      worldW:2600,worldH:1700,winCondition:'killAll',winParams:{},
      spawnX:300,spawnY:850,
      obstacles:[
        {type:'pillar',x:800,y:400,r:40},{type:'pillar',x:800,y:1100,r:40},
        {type:'pillar',x:1300,y:750,r:35},{type:'pillar',x:1300,y:950,r:35},
        {type:'pillar',x:1800,y:400,r:30},{type:'pillar',x:1800,y:1300,r:30},
        {type:'wall',x:1050,y:700,w:26,h:160},{type:'wall',x:1050,y:840,w:26,h:160},
        {type:'wall',x:2000,y:600,w:140,h:26},{type:'wall',x:2000,y:1074,w:140,h:26},
      ],
      enemies:[
        {type:'scout',x:1200,y:500},{type:'scout',x:1200,y:1200},
        {type:'scout',x:1600,y:850},{type:'scout',x:2000,y:400},
        {type:'scout',x:2000,y:1300},{type:'dart',x:2200,y:850},
      ],
      pickups:[
        {type:'battery',x:600,y:850,hidden:false},
        {type:'health',x:1500,y:850,hidden:true},
        {type:'weapon',x:1000,y:300,hidden:false},
      ],
      hazards:[],objectives:[],
    },
    {
      name:'Obstacle Course',author:'PATROL WING',created:Date.now(),
      worldW:3600,worldH:2400,winCondition:'reachFinish',winParams:{},
      spawnX:200,spawnY:1200,
      obstacles:[
        {type:'wall',x:600,y:200,w:26,h:800},{type:'wall',x:600,y:1400,w:26,h:800},
        {type:'wall',x:1200,y:400,w:26,h:700},{type:'wall',x:1200,y:1300,w:26,h:700},
        {type:'wall',x:1800,y:200,w:26,h:900},{type:'wall',x:1800,y:1500,w:26,h:700},
        {type:'wall',x:2400,y:300,w:26,h:600},{type:'wall',x:2400,y:1400,w:26,h:800},
        {type:'pillar',x:900,y:1100,r:45},{type:'pillar',x:1500,y:800,r:45},
        {type:'pillar',x:2100,y:1200,r:40},{type:'pillar',x:2700,y:600,r:35},
        {type:'pillar',x:2700,y:1800,r:35},{type:'pillar',x:3000,y:1200,r:30},
      ],
      enemies:[
        {type:'scout',x:900,y:600},{type:'scout',x:900,y:1800},
        {type:'guard',x:1500,y:1100},{type:'dart',x:2100,y:600},
        {type:'dart',x:2100,y:1800},{type:'turret',x:2700,y:1200},
        {type:'scout',x:3200,y:800},{type:'scout',x:3200,y:1600},
      ],
      pickups:[
        {type:'battery',x:400,y:600,hidden:false},{type:'health',x:1500,y:400,hidden:false},
        {type:'weapon',x:2100,y:1000,hidden:false},{type:'shield',x:2900,y:1200,hidden:true},
      ],
      hazards:[
        {type:'zap_pylon',x:1000,y:1200,angle:0,gap:140},
        {type:'floor_mine',x:2000,y:1100},{type:'floor_mine',x:2000,y:1300},
      ],
      objectives:[{type:'finish',x:3400,y:1200}],
    },
    {
      name:'Survival Gauntlet',author:'PATROL WING',created:Date.now(),
      worldW:2600,worldH:1700,winCondition:'survive',winParams:{seconds:45},
      spawnX:1300,spawnY:850,
      obstacles:[
        {type:'pillar',x:500,y:400,r:38},{type:'pillar',x:500,y:1300,r:38},
        {type:'pillar',x:2100,y:400,r:38},{type:'pillar',x:2100,y:1300,r:38},
        {type:'pillar',x:1300,y:300,r:30},{type:'pillar',x:1300,y:1400,r:30},
        {type:'wall',x:800,y:750,w:26,h:200},{type:'wall',x:1800,y:750,w:26,h:200},
      ],
      enemies:[
        {type:'scout',x:400,y:300},{type:'scout',x:400,y:1400},
        {type:'scout',x:2200,y:300},{type:'scout',x:2200,y:1400},
        {type:'guard',x:700,y:850},{type:'guard',x:1900,y:850},
        {type:'dart',x:1300,y:200},{type:'dart',x:1300,y:1500},
        {type:'wraith',x:300,y:850},{type:'wraith',x:2300,y:850},
        {type:'brute',x:1000,y:500},{type:'brute',x:1600,y:1200},
      ],
      pickups:[
        {type:'health',x:1300,y:850,hidden:false},{type:'battery',x:600,y:850,hidden:false},
        {type:'battery',x:2000,y:850,hidden:false},{type:'shield',x:1300,y:500,hidden:true},
        {type:'weapon',x:800,y:400,hidden:true},{type:'weapon',x:1800,y:1300,hidden:true},
      ],
      hazards:[
        {type:'zap_pylon',x:1000,y:850,angle:1.57,gap:120},
        {type:'zap_pylon',x:1600,y:850,angle:1.57,gap:120},
        {type:'floor_mine',x:500,y:850},{type:'floor_mine',x:2100,y:850},
      ],
      objectives:[],
    },
    {
      name:'Key Hunt',author:'PATROL WING',created:Date.now(),
      worldW:3200,worldH:3200,winCondition:'collectAll',winParams:{},
      spawnX:1600,spawnY:1600,
      obstacles:[
        {type:'pillar',x:800,y:800,r:42},{type:'pillar',x:2400,y:800,r:42},
        {type:'pillar',x:800,y:2400,r:42},{type:'pillar',x:2400,y:2400,r:42},
        {type:'pillar',x:1600,y:600,r:35},{type:'pillar',x:1600,y:2600,r:35},
        {type:'pillar',x:600,y:1600,r:35},{type:'pillar',x:2600,y:1600,r:35},
        {type:'wall',x:1100,y:1100,w:26,h:200},{type:'wall',x:2074,y:1100,w:26,h:200},
        {type:'wall',x:1100,y:1900,w:26,h:200},{type:'wall',x:2074,y:1900,w:26,h:200},
        {type:'wall',x:1300,y:1500,w:200,h:26},{type:'wall',x:1700,y:1500,w:200,h:26},
      ],
      enemies:[
        {type:'guard',x:800,y:800},{type:'guard',x:2400,y:800},
        {type:'guard',x:800,y:2400},{type:'guard',x:2400,y:2400},
        {type:'scout',x:1600,y:400},{type:'scout',x:1600,y:2800},
        {type:'scout',x:400,y:1600},{type:'scout',x:2800,y:1600},
        {type:'turret',x:1600,y:1200},{type:'turret',x:1600,y:2000},
      ],
      pickups:[
        {type:'weapon',x:1200,y:1600,hidden:false},{type:'health',x:2000,y:1600,hidden:false},
        {type:'battery',x:1600,y:1200,hidden:true},{type:'battery',x:1600,y:2000,hidden:true},
      ],
      hazards:[
        {type:'floor_mine',x:1200,y:1200},{type:'floor_mine',x:2000,y:1200},
        {type:'floor_mine',x:1200,y:2000},{type:'floor_mine',x:2000,y:2000},
      ],
      objectives:[
        {type:'key',x:400,y:400},{type:'key',x:2800,y:400},
        {type:'key',x:400,y:2800},{type:'key',x:2800,y:2800},
        {type:'key',x:1600,y:200},
      ],
    },
    {
      name:'Retrieval Op',author:'PATROL WING',created:Date.now(),
      worldW:3600,worldH:2400,winCondition:'retrieve',winParams:{},
      spawnX:200,spawnY:1200,
      obstacles:[
        {type:'wall',x:900,y:400,w:26,h:600},{type:'wall',x:900,y:1400,w:26,h:600},
        {type:'wall',x:1800,y:200,w:26,h:800},{type:'wall',x:1800,y:1400,w:26,h:800},
        {type:'wall',x:2700,y:400,w:26,h:700},{type:'wall',x:2700,y:1300,w:26,h:700},
        {type:'pillar',x:1350,y:700,r:40},{type:'pillar',x:1350,y:1700,r:40},
        {type:'pillar',x:2250,y:900,r:35},{type:'pillar',x:2250,y:1500,r:35},
        {type:'pillar',x:3200,y:1200,r:45},
      ],
      enemies:[
        {type:'scout',x:600,y:500},{type:'scout',x:600,y:1900},
        {type:'guard',x:1350,y:1200},{type:'dart',x:2250,y:600},
        {type:'dart',x:2250,y:1800},{type:'turret',x:3000,y:800},
        {type:'turret',x:3000,y:1600},{type:'brute',x:3300,y:1200},
        {type:'wraith',x:1200,y:1200},{type:'phantom',x:2600,y:1200},
      ],
      pickups:[
        {type:'weapon',x:500,y:1200,hidden:false},{type:'weapon',x:1350,y:500,hidden:false},
        {type:'health',x:2250,y:1200,hidden:false},{type:'battery',x:1800,y:1200,hidden:true},
        {type:'shield',x:3000,y:1200,hidden:true},
      ],
      hazards:[
        {type:'zap_pylon',x:1100,y:1200,angle:0,gap:130},
        {type:'zap_pylon',x:2500,y:1200,angle:1.57,gap:110},
        {type:'floor_mine',x:3100,y:1000},{type:'floor_mine',x:3100,y:1400},
      ],
      objectives:[
        {type:'item',x:3400,y:1200},
        {type:'goal',x:200,y:1200},
      ],
    },
  ],
};
const SETTINGS_KEY='pw_settings';
const SETTINGS_DEFAULT={musicVol:1,sfxVol:1,uiVol:1,screenShake:true,particles:'full'};
let settings=Object.assign({},SETTINGS_DEFAULT);
function _loadSettings(){
  try{const s=JSON.parse(localStorage.getItem(SETTINGS_KEY));if(s)settings=Object.assign({},SETTINGS_DEFAULT,s);}catch(e){}
}
function _saveSettings(){
  try{localStorage.setItem(SETTINGS_KEY,JSON.stringify(settings));}catch(e){}
}
function _applyVolumes(){
  if(!AC) return;
  const t=AC.currentTime;
  const m=Music.isMuted();
  const sfx=Music.sfxNode(),ui=Music.uiNode();
  if(sfx&&sfx!==AC.destination) sfx.gain.setValueAtTime(m?0:settings.sfxVol,t);
  if(ui &&ui !==AC.destination)  ui.gain.setValueAtTime(m?0:settings.uiVol, t);
}
let selectedCraft=0,selectedColor='#00ddff',hoverCard=-1,hoverSwatch=-1;
// Hangar state — separate working copies while editing in the Hangar screen
let hangarCraft=0,hangarColor='#00ddff',hangarScroll=0;
let hangarLoadout=[];
let loadoutEditFrom='pause';
let deadEyeMs=0, carrierDrones=[], slipstreamMs=0, slipPrevVx=0, slipPrevVy=0;
const SWATCHES=['#00ddff','#ff3300','#ffee00','#00ff88','#ff00aa','#aa44ff','#ffffff','#44ffcc','#ff8800','#88aaff'];
colorPick.addEventListener('input',e=>{
  // Update whichever context is active
  if(gameState==='hangar') hangarColor=e.target.value;
  else selectedColor=e.target.value;
});
_loadSettings();
_loadHangar(); // apply saved craft+color immediately

// ─── GAME STATE ──────────────────────────────────────────────────
// gameState declared at top of file (before resize() call)
// gameMode declared at top of file (before resize() call)
let score=0,wave=1,wavePause=0,screenLockMs=0;
let harbingerRef=null;
let shake=0,camX=0,camY=0;
let lastTime=performance.now(),gameStartTime=0,bossWarning=0,lastHullBeepMs=0,gameEndDurationMs=0,gameEndScore=0;
let empFlash=0;
let weaponFlash={name:'',ms:0};
let laserFlash=null; // {x1,y1,x2,y2,life,maxLife,forks,hitEnemy}
let leechFlash={active:false,tx:0,ty:0,ms:0};
let shockwaveFlash={ms:0};
// Time Trial state
let ttStartTime=0,ttElapsed=0,ttFinished=false,ttFinalScore=0,ttTotalEnemies=0;
let ttLevel=1; // 1=Ghost Run, 2=Nuclear Disarm
// ── Combat Training globals ──────────────────────────────────────
const CT_SEQUENCE=['dart','scout','guard','phantom','wraith','turret','brute','boss','dreadnought','harbinger'];
let ctLevel=0;          // index into CT_SEQUENCE
let ctStartTime=0;      // performance.now() when current round started
let ctTotalScore=0;     // accumulated score across rounds
let ctFinalScore=0;     // final computed score
let ctLevelUpMs=0;      // countdown ms for level-up interstitial
let ctLevelUpName='';   // name of enemy just defeated
let ctNextPickupMs=0;
// Custom level state
let customPack=null;
let customObjectives=[];
let customWinCondition='';
let customWinParams={};
let customSurviveMs=0;
let customKeysCollected=0;
let customKeysTotal=0;
let customItemHeld=false;
let customFinishX=0,customFinishY=0;
let customGoalX=0,customGoalY=0;
let customTransitionMs=0;
let customTransitionText='';
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
// Level 2 — Nuclear Disarm
const NUKE_COLORS=['#ff2244','#00eeff','#ffdd00','#44ff88'];
const NUKE_NAMES=['ALPHA','BETA','GAMMA','DELTA'];
const TT_WORLD_W2=4200, TT_WORLD_H2=3200;
const DBD_WORLD_W=20800; // same corridor width as Ghost Run
const JRR_WORLD_W=4200, JRR_WORLD_H=3200; // JR Rescue world size
let jrCaptives=[]; // [{x,y,state,guards,rotor,t}]
let jrBase={x:0,y:0}; // central rescue base position
let jrCarrying=-1;    // index of captive being carried (-1 = none)

const TNG_WORLD_W=4200, TNG_WORLD_H=4800; // Touch N Go — taller open world
const TNG_PAD_R=52;     // landing pad radius
const TNG_TOUCH_R=58;   // player proximity to trigger touch
const TNG_HOLD_MS=1200; // ms player must hold on pad to register
let tngPads=[];          // [{x,y,num,revealed,done}]
let tngSeq=1;            // next required pad number (1-5)
let tngOnPad=-1;         // index of pad player is currently over
let tngHoldMs=0;         // ms held on current pad
const DBD_FINISH_X=DBD_WORLD_W-160;
const NUKE_DISARM_RANGE=70, NUKE_DISARM_TIME=3000; // ms to hold to disarm
let nukes=[]; // {x,y,id,armed,disarmProgress,color,name}
// P.nukeKeys = Set of collected key IDs (added to P in resetPlayer)
// Portal state
let portalActive=false,portalCountdown=0,portalPositions=[],portalSelected=0;
// Ammo ranges keyed by weapon id [min, max] (multiples of 5)
const AMMO_RANGES={rapid:[20,150],spread:[5,50],boomr:[5,50],sawtooth:[10,75],laser:[5,20],burst:[20,100],plasma:[5,30],rico:[5,25],mine:[5,20],seekr:[5,15],rocket:[5,15],tractor:[10000,20000],dinf:[50,150]};
function _randomAmmoAmt(weaponId){
  const r=AMMO_RANGES[weaponId]||[5,20];
  const steps=Math.floor((r[1]-r[0])/5)+1;
  return r[0]+Math.floor(Math.random()*steps)*5;
}
function _pickAmmoWeapon(){
  // Returns a random unlocked weapon that uses stock (not std/stun/minime)
  const eligible=[...P.unlockedW].filter(idx=>{
    const w=WEAPONS[idx];
    return w.id in AMMO_RANGES;
  });
  if(!eligible.length) return null;
  return WEAPONS[eligible[Math.floor(Math.random()*eligible.length)]];
}
let particles=[],pickups=[],pBullets=[],eBullets=[],enemies=[],obstacles=[],mines=[],seekers=[],boomerangs=[],fractals=[],hazards=[],rockets=[],grenades=[],gravityWells=[],faradayCages=[];
const SEEKR_SPD     =5.68;  // travel speed (+30% from 4.37)
const ROCKET_SPD    =12;
const ROCKET_DMG    =65;
const ROCKET_LIFE   =2800; // ms — long enough to cross the world
const ROCKET_COL    ='#ff5500';
const ROCKET_TRAIL_COL='#ff2200';
const SEEKR_TURN    =0.055; // max radians turned per frame (≈3.1°) — curves around obstacles
const SEEKR_DET     =400;   // acquisition range
const SEEKR_BLAST_R =50;    // explosion radius
const SEEKR_LIFE    =8000;  // ms before self-destruct if no kill
const SEEKR_COL     ='#ffaa00';
const SEEKR_ACC     ='#ffddaa';
const GRAPPLE_LEASH    =55;
const GRENADE_BLAST_R  =80;
const GRENADE_BLAST_DMG=90;
const GRENADE_MAX_BOUNCES=5;
const GRENADE_LIFE     =3200;
const GRENADE_PROX_R   =70;
const GRAVWELL_R       =200;
const GRAVWELL_CRUSH_R =80;
const GRAVWELL_DPS     =12;
const GRAVWELL_PULL    =1.8;
const GRAVWELL_LIFE    =10000;
const FARADAY_TRIGGER_R=100;
const FARADAY_LIFE     =20000;
const SHOCKWAVE_R      =280;
const SHOCKWAVE_KB     =14;

// ─── PARTICLES ───────────────────────────────────────────────────
function _pCount(n){
  if(settings.particles==='off')return 0;
  if(settings.particles==='reduced')return Math.max(1,Math.ceil(n*0.4));
  return n;
}
function spawnParts(x,y,color,n=10,sp=4,sz=5,life=450){
  if(n<=0)return;
  for(let i=0;i<n;i++){
    const a=rng(0,Math.PI*2),s=sp*(0.35+rng(0,0.8));
    particles.push({x,y,vx:Math.cos(a)*s,vy:Math.sin(a)*s,color,sz:sz*(0.5+rng(0,0.7)),life,maxLife:life,drag:0.91});
  }
}
function tickParticles(dt){
  for(let i=particles.length-1;i>=0;i--){
    const p=particles[i];
    p.x+=p.vx*dt*60;p.y+=p.vy*dt*60;p.vx*=p.drag;p.vy*=p.drag;p.life-=dt*1000;
    if(p.life<=0)particles.splice(i,1);
  }
}
function drawParticles(){
  for(const p of particles){const a=p.life/p.maxLife;ctx.globalAlpha=a;ctx.fillStyle=p.color;ctx.beginPath();ctx.arc(p.x-camX,p.y-camY,Math.max(0.3,p.sz*a),0,Math.PI*2);ctx.fill();}
  ctx.globalAlpha=1;
}

// ─── OBSTACLES ───────────────────────────────────────────────────
function generateObstacles(spawnX,spawnY){
  if(spawnX===undefined)spawnX=WORLD_W/2;
  if(spawnY===undefined)spawnY=WORLD_H/2;
  obstacles=[];
  for(let i=0;i<16;i++){let x,y,a=0;do{x=rng(130,WORLD_W-130);y=rng(130,WORLD_H-130);a++;}while(dist(x,y,spawnX,spawnY)<280&&a<40);obstacles.push({type:'pillar',x,y,r:rng(26,46),rot:rng(0,Math.PI)});}
  for(let i=0;i<9;i++){let x,y,a=0;do{x=rng(160,WORLD_W-320);y=rng(160,WORLD_H-320);a++;}while(dist(x,y,spawnX,spawnY)<240&&a<40);const vert=Math.random()<0.5,len=rng(90,190);obstacles.push({type:'wall',x:x-(vert?13:len/2),y:y-(vert?len/2:13),w:vert?26:len,h:vert?len:26});}
}
function circleVsObs(cx,cy,cr){
  for(const o of obstacles){
    if(o.type==='pillar'){if(dist2(cx,cy,o.x,o.y)<(cr+o.r)**2)return true;}
    else{const nx=clamp(cx,o.x,o.x+o.w),ny=clamp(cy,o.y,o.y+o.h);if(dist2(cx,cy,nx,ny)<cr*cr)return true;}
  }return false;
}
function pushOutObs(obj,r){
  for(const o of obstacles){
    if(o.type==='pillar'){const dx=obj.x-o.x,dy=obj.y-o.y,d=Math.sqrt(dx*dx+dy*dy)||1,m=r+o.r;if(d<m){obj.x=o.x+(dx/d)*m;obj.y=o.y+(dy/d)*m;const dot=obj.vx*(dx/d)+obj.vy*(dy/d);if(dot<0){obj.vx-=dot*(dx/d);obj.vy-=dot*(dy/d);}}}
    else{const nx=clamp(obj.x,o.x,o.x+o.w),ny=clamp(obj.y,o.y,o.y+o.h),dx=obj.x-nx,dy=obj.y-ny,d=Math.sqrt(dx*dx+dy*dy)||1;if(d<r){obj.x=nx+(dx/d)*r;obj.y=ny+(dy/d)*r;const dot=obj.vx*(dx/d)+obj.vy*(dy/d);if(dot<0){obj.vx-=dot*(dx/d);obj.vy-=dot*(dy/d);}}}
  }
}
// Reflect a rico bullet off obstacles — mutates vx/vy and pushes out, returns true if a bounce happened
function reflectRicoVsObs(b){
  let bounced=false;
  const r=b.bSz;
  for(const o of obstacles){
    if(o.type==='pillar'){
      const dx=b.x-o.x,dy=b.y-o.y,d=Math.sqrt(dx*dx+dy*dy)||1,m=r+o.r;
      if(d<m){
        const nx=dx/d,ny=dy/d;
        b.x=o.x+nx*m;b.y=o.y+ny*m;
        const dot=b.vx*nx+b.vy*ny;
        if(dot<0){b.vx-=2*dot*nx;b.vy-=2*dot*ny;}
        bounced=true;
      }
    } else {
      const cx=clamp(b.x,o.x,o.x+o.w),cy=clamp(b.y,o.y,o.y+o.h);
      const dx=b.x-cx,dy=b.y-cy,d=Math.sqrt(dx*dx+dy*dy)||1;
      if(d<r){
        const nx=dx/d,ny=dy/d;
        b.x=cx+nx*r;b.y=cy+ny*r;
        const dot=b.vx*nx+b.vy*ny;
        if(dot<0){b.vx-=2*dot*nx;b.vy-=2*dot*ny;}
        bounced=true;
      }
    }
  }
  return bounced;
}
// ═══════════════════════════════════════════════════════════════
// HAZARD OBSTACLES  (damage-dealing environmental objects)
// Types:
//   zap_pylon  — pair of small pillars with electric arc between them
//   floor_mine — proximity-triggered explosive disc
// ═══════════════════════════════════════════════════════════════
function spawnZapPylon(ax,ay,bx,by){
  // Two anchor points + shared arc data; damage zone is the line segment between them
  hazards.push({type:'zap_pylon',ax,ay,bx,by,dmg:12,arcT:0,cooldown:0,segments:[]});
}
function spawnFloorMine(x,y){
  hazards.push({type:'floor_mine',x,y,r:20,triggerR:32,blastR:90,dmg:28,armed:true,blasting:false,blastT:0,t:0});
}

// Helper: spawn a pylon pair with automatic spacing along an angle
function spawnZapPylonPair(cx,cy,angle,gap){
  const hg=gap/2;
  spawnZapPylon(
    cx+Math.cos(angle)*hg, cy+Math.sin(angle)*hg,
    cx-Math.cos(angle)*hg, cy-Math.sin(angle)*hg
  );
}

function _pointSegDist2(px,py,ax,ay,bx,by){
  const dx=bx-ax,dy=by-ay,l2=dx*dx+dy*dy;
  if(l2===0) return (px-ax)**2+(py-ay)**2;
  let t=((px-ax)*dx+(py-ay)*dy)/l2;
  t=Math.max(0,Math.min(1,t));
  return (px-ax-t*dx)**2+(py-ay-t*dy)**2;
}

function tickHazards(dt,now){
  for(let hi=hazards.length-1;hi>=0;hi--){
    const h=hazards[hi];
    if(h.type==='zap_pylon'){
      h.arcT+=dt;
      if(h.cooldown>0) h.cooldown-=dt*1000;
      // Regenerate jagged arc segments every ~60ms for crackle
      if(Math.floor(h.arcT*16)!==Math.floor((h.arcT-dt)*16)){
        const pts=8,segs=[];
        for(let i=0;i<=pts;i++){
          const t2=i/pts;
          const bx=h.ax+(h.bx-h.ax)*t2, by=h.ay+(h.by-h.ay)*t2;
          const perp=Math.atan2(h.by-h.ay,h.bx-h.ax)+Math.PI/2;
          const jitter=(i===0||i===pts)?0:(Math.random()-0.5)*22;
          segs.push({x:bx+Math.cos(perp)*jitter,y:by+Math.sin(perp)*jitter});
        }
        h.segments=segs;
      }
      // Damage player if within 18px of arc line
      if(h.cooldown<=0&&P.alive&&P.iframes<=0&&P.invincMs<=0){
        const d2=_pointSegDist2(P.x,P.y,h.ax,h.ay,h.bx,h.by);
        if(d2<18*18){
          if(P.shieldMs>0){P.shieldMs=0;if(settings.screenShake)shake=8;spawnParts(P.x,P.y,'#44aaff',_pCount(14),4,5,400);SFX.shbreak();P.iframes=400;}
          else{P.hp-=h.dmg*P.damageMult;P.iframes=600;if(settings.screenShake)shake=12;SFX.hit();spawnParts(P.x,P.y,'#ffff44',_pCount(8),3,4,300);if(P.hp<=0)P.alive=false;}
          h.cooldown=1200; // 1.2s recharge between zaps
          Music.onHit();
        }
      }
      // Damage nearby enemies within 14px of arc line
      for(let ei=enemies.length-1;ei>=0;ei--){
        const e=enemies[ei];
        if(_pointSegDist2(e.x,e.y,h.ax,h.ay,h.bx,h.by)<14*14){
          e.hp-=3*dt*60; // continuous low damage
          if(e.hp<=0){spawnParts(e.x,e.y,e.color,_pCount(12),4,6,500);killEnemy(ei);}
        }
      }
    } else if(h.type==='floor_mine'){
      h.t+=dt;
      if(h.blasting){
        h.blastT+=dt*1000;
        if(h.blastT>600) hazards.splice(hi,1);
        continue;
      }
      if(!h.armed) continue;
      // Check player proximity
      const dp=dist(P.x,P.y,h.x,h.y);
      if(dp<h.triggerR&&P.alive){
        h.blasting=true;h.armed=false;h.blastT=0;
        // Damage player
        if(P.invincMs<=0){
          const pDmg=Math.round(h.dmg*(1-dp/h.blastR));
          if(P.shieldMs>0){P.shieldMs=0;spawnParts(P.x,P.y,'#44aaff',_pCount(16),4,5,450);SFX.shbreak();P.iframes=400;}
          else if(pDmg>0){P.hp-=pDmg*P.damageMult;P.iframes=500;if(settings.screenShake)shake=18;SFX.hit();if(P.hp<=0)P.alive=false;Music.onHit();}
        }
        // Damage nearby enemies
        for(let ei=enemies.length-1;ei>=0;ei--){
          const de=dist(enemies[ei].x,enemies[ei].y,h.x,h.y);
          if(de<h.blastR){enemies[ei].hp-=h.dmg*(1-de/h.blastR);if(enemies[ei].hp<=0){spawnParts(enemies[ei].x,enemies[ei].y,enemies[ei].color,_pCount(16),5,7,600);killEnemy(ei);}}
        }
        spawnParts(h.x,h.y,'#ff6600',_pCount(24),6,8,700);spawnParts(h.x,h.y,'#ffff44',_pCount(12),4,5,500);
        if(settings.screenShake)shake=Math.max(shake,20);SFX.minedet();
      }
    } else if(h.type==='plasma_zone'){
      h.t+=dt*1000;
      if(h.t>=h.duration){hazards.splice(hi,1);continue;}
      if(P.alive&&P.iframes<=0&&P.invincMs<=0&&dist(P.x,P.y,h.x,h.y)<h.r){
        if(P.shieldMs>0){P.shieldMs=0;spawnParts(P.x,P.y,'#44aaff',_pCount(14),4,5,400);SFX.shbreak();P.iframes=400;}
        else{P.hp-=18*dt*P.damageMult;if(settings.screenShake)shake=Math.max(shake,6);SFX.hit();Music.onHit();if(P.hp<=0)P.alive=false;}
      }
    }
  }
}

function drawHazards(){
  const now=Date.now()/1000;
  for(const h of hazards){
    if(h.type==='zap_pylon'){
      const asx=h.ax-camX,asy=h.ay-camY,bsx=h.bx-camX,bsy=h.by-camY;
      if(asx<-100&&bsx<-100) continue;
      if(asx>canvas.width+100&&bsx>canvas.width+100) continue;
      // Pylon pillars
      for(const [px,py] of [[asx,asy],[bsx,bsy]]){
        ctx.save();ctx.translate(px,py);
        const cycle=0.5+0.5*Math.sin(now*6);
        const cg=Math.round(cycle*80);
        const ringCol=`rgb(255,${cg},0)`;
        ctx.beginPath();ctx.arc(0,0,9,0,Math.PI*2);
        ctx.fillStyle='rgba(10,4,0,0.95)';ctx.fill();
        ctx.shadowBlur=18;ctx.shadowColor=ringCol;
        ctx.strokeStyle=ringCol;ctx.lineWidth=2.4;ctx.stroke();ctx.shadowBlur=0;
        // X mark
        const xs=5;
        ctx.strokeStyle=`rgba(255,${Math.round(cycle*120)},0,${0.85+0.15*cycle})`;
        ctx.lineWidth=2;ctx.shadowBlur=8;ctx.shadowColor=ringCol;
        ctx.beginPath();ctx.moveTo(-xs,-xs);ctx.lineTo(xs,xs);ctx.stroke();
        ctx.beginPath();ctx.moveTo(xs,-xs);ctx.lineTo(-xs,xs);ctx.stroke();
        ctx.shadowBlur=0;
        ctx.restore();
      }
      // Arc
      if(h.segments.length>1){
        const pulse=0.5+0.5*Math.sin(now*12);
        const arcG=Math.round(pulse*60);
        ctx.beginPath();ctx.moveTo(h.segments[0].x-camX,h.segments[0].y-camY);
        for(let i=1;i<h.segments.length;i++) ctx.lineTo(h.segments[i].x-camX,h.segments[i].y-camY);
        ctx.strokeStyle=`rgba(255,${arcG},0,${0.4*pulse})`;ctx.lineWidth=7;
        ctx.shadowBlur=14;ctx.shadowColor=`rgb(255,${arcG},0)`;ctx.stroke();ctx.shadowBlur=0;
        ctx.beginPath();ctx.moveTo(h.segments[0].x-camX,h.segments[0].y-camY);
        for(let i=1;i<h.segments.length;i++) ctx.lineTo(h.segments[i].x-camX,h.segments[i].y-camY);
        const coreG=Math.round(pulse*140);
        ctx.strokeStyle=`rgba(255,${coreG},20,${0.8+0.2*pulse})`;ctx.lineWidth=2;ctx.stroke();
        ctx.lineWidth=1;
      }
    } else if(h.type==='floor_mine'){
      const sx=h.x-camX,sy=h.y-camY;
      if(sx<-80||sx>canvas.width+80||sy<-80||sy>canvas.height+80) continue;
      if(h.blasting){
        const pct=h.blastT/600;
        ctx.beginPath();ctx.arc(sx,sy,h.blastR*pct,0,Math.PI*2);
        ctx.fillStyle=`rgba(255,80,0,${(1-pct)*0.3})`;ctx.fill();
        ctx.strokeStyle=`rgba(255,${Math.round((1-pct)*80)},0,${1-pct})`;
        ctx.lineWidth=3;ctx.shadowBlur=22;ctx.shadowColor='#ff4400';ctx.stroke();ctx.shadowBlur=0;
      } else {
        const pulse=0.5+0.5*Math.sin(now*5+h.x);
        const cg=Math.round(pulse*70);
        ctx.beginPath();ctx.arc(sx,sy,h.r,0,Math.PI*2);
        ctx.fillStyle='rgba(12,4,0,0.92)';ctx.fill();
        ctx.shadowBlur=16;ctx.shadowColor=`rgb(255,${cg},0)`;
        ctx.strokeStyle=`rgba(255,${cg},0,${0.7+0.3*pulse})`;ctx.lineWidth=2.4;ctx.stroke();ctx.shadowBlur=0;
        ctx.beginPath();ctx.arc(sx,sy,h.r*0.55,0,Math.PI*2);
        ctx.strokeStyle=`rgba(255,${Math.round(pulse*100)},0,${0.45*pulse})`;ctx.lineWidth=1.2;ctx.stroke();
        // X mark
        const xs=7;
        ctx.strokeStyle=`rgba(255,${cg},0,${0.75+0.25*pulse})`;ctx.lineWidth=2;
        ctx.shadowBlur=8;ctx.shadowColor=`rgb(255,${cg},0)`;
        ctx.beginPath();ctx.moveTo(sx-xs,sy-xs);ctx.lineTo(sx+xs,sy+xs);ctx.stroke();
        ctx.beginPath();ctx.moveTo(sx+xs,sy-xs);ctx.lineTo(sx-xs,sy+xs);ctx.stroke();
        ctx.shadowBlur=0;
      }
    } else if(h.type==='plasma_zone'){
      const sx=h.x-camX,sy=h.y-camY;
      if(sx<-200||sx>canvas.width+200||sy<-200||sy>canvas.height+200)continue;
      const tPct=h.t/h.duration;
      const alpha=0.28*(1-tPct*0.5);
      const pulse=0.6+0.4*Math.sin(Date.now()/120);
      ctx.save();ctx.translate(sx,sy);
      ctx.beginPath();ctx.arc(0,0,h.r,0,Math.PI*2);
      ctx.fillStyle=`rgba(204,68,255,${alpha*pulse})`;ctx.fill();
      ctx.strokeStyle=`rgba(238,153,255,${0.6*pulse})`;ctx.lineWidth=2;ctx.shadowBlur=18;ctx.shadowColor='#cc44ff';ctx.stroke();
      ctx.shadowBlur=0;ctx.restore();
    }
  }
}

function spawnHazardZaps(count, xMin, xMax, yMin, yMax, gapMin=80, gapMax=160){
  for(let i=0;i<count;i++){
    let cx,cy,a=0;
    const gap=rng(gapMin,gapMax), angle=rng(0,Math.PI);
    do{
      cx=rng(xMin,xMax); cy=rng(yMin,yMax);
      a++;
    }while(a<40&&circleVsObs(cx,cy,gap/2+14));
    spawnZapPylonPair(cx,cy,angle,gap);
  }
}
function spawnHazardMines(count, xMin, xMax, yMin, yMax, clearX=-1, clearY=-1, clearR=200){
  const placed=[];
  for(let i=0;i<count;i++){
    let x,y,a=0;
    do{
      x=rng(xMin,xMax); y=rng(yMin,yMax); a++;
    }while(a<50&&(circleVsObs(x,y,28)||placed.some(p=>dist(x,y,p.x,p.y)<80)||(clearX>=0&&dist(x,y,clearX,clearY)<clearR)));
    spawnFloorMine(x,y);
    placed.push({x,y});
  }
}

function drawObstacles(){
  const now=Date.now();
  for(const o of obstacles){
    if(o.type==='pillar'){
      const sx=o.x-camX,sy=o.y-camY;if(sx+o.r<-5||sx-o.r>canvas.width+5||sy+o.r<-5||sy-o.r>canvas.height+5)continue;
      ctx.save();ctx.translate(sx,sy);ctx.rotate(o.rot);
      ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*o.r,Math.sin(a)*o.r):ctx.lineTo(Math.cos(a)*o.r,Math.sin(a)*o.r);}ctx.closePath();
      const gr=ctx.createRadialGradient(0,0,0,0,0,o.r);gr.addColorStop(0,'rgba(18,42,90,0.98)');gr.addColorStop(1,'rgba(5,14,38,0.99)');
      ctx.fillStyle=gr;ctx.fill();ctx.shadowBlur=12;ctx.shadowColor='rgba(0,100,220,0.55)';ctx.strokeStyle='rgba(30,120,230,0.65)';ctx.lineWidth=2.2;ctx.stroke();ctx.shadowBlur=0;
      ctx.strokeStyle='rgba(0,60,160,0.28)';ctx.lineWidth=0.8;for(let j=0;j<3;j++){const a=(Math.PI/3)*j+Math.PI/6;ctx.beginPath();ctx.moveTo(Math.cos(a)*o.r*0.38,Math.sin(a)*o.r*0.38);ctx.lineTo(-Math.cos(a)*o.r*0.38,-Math.sin(a)*o.r*0.38);ctx.stroke();}
      const pulse=0.5+0.5*Math.sin(now/900+o.r);ctx.beginPath();ctx.arc(0,0,3.5,0,Math.PI*2);ctx.fillStyle=`rgba(0,140,255,${0.3*pulse})`;ctx.fill();ctx.restore();
    }else{
      const wx=o.x-camX,wy=o.y-camY;if(wx+o.w<-5||wx>canvas.width+5||wy+o.h<-5||wy>canvas.height+5)continue;
      ctx.fillStyle='rgba(6,16,44,0.98)';ctx.fillRect(wx,wy,o.w,o.h);ctx.shadowBlur=9;ctx.shadowColor='rgba(0,80,200,0.45)';ctx.strokeStyle='rgba(22,100,210,0.6)';ctx.lineWidth=1.8;ctx.strokeRect(wx,wy,o.w,o.h);ctx.shadowBlur=0;
      ctx.strokeStyle='rgba(0,50,140,0.25)';ctx.lineWidth=0.7;if(o.w>o.h){ctx.beginPath();ctx.moveTo(wx+4,wy+o.h/2);ctx.lineTo(wx+o.w-4,wy+o.h/2);ctx.stroke();}else{ctx.beginPath();ctx.moveTo(wx+o.w/2,wy+4);ctx.lineTo(wx+o.w/2,wy+o.h-4);ctx.stroke();}
    }
  }
}

// ─── PICKUPS ─────────────────────────────────────────────────────
const PTYPES={battery:{color:'#00ff88'},health:{color:'#ff4466'},weapon:{color:'#ffee00'},shield:{color:'#44aaff'},emp:{color:'#cc44ff'},overcharge:{color:'#ff9900'},points:{color:'#ffd700'},ammo:{color:'#ccddff'},invincibility:{color:'#ffffff'},cloak:{color:'#88ffee'},portal:{color:'#ff8800'},nuke_key:{color:'#ffffff'},medkit:{color:'#44ffdd'}};
const DROP_TABLE=['battery','battery','battery','battery','health','health','health','weapon','weapon','shield','shield','emp','overcharge','battery','health','ammo','ammo','invincibility','cloak','portal','medkit','medkit'];
let waveStartTime=0;
function _allWeaponsUnlocked(){ return P.unlockedW&&P.unlockedW.size>=WEAPONS.length; }
function spawnPickup(x,y,type=null,hidden=false){
  if(!type)type=DROP_TABLE[Math.floor(rng(0,DROP_TABLE.length))];
  // Suppress weapon pickups once all weapons are unlocked — nothing to give
  if(type==='weapon'&&_allWeaponsUnlocked()) return;
  // Suppress ammo if no stocked weapons are unlocked yet
  if(type==='ammo'&&!_pickAmmoWeapon()) return;
  const pk={x,y,type,t:rng(0,Math.PI*2),hidden,dropTimer:hidden?null:6000};
  if(type==='ammo'){
    const w=_pickAmmoWeapon();
    if(!w) return;
    pk.weaponId=w.id; pk.ammoAmt=_randomAmmoAmt(w.id); pk.weaponColor=w.color; pk.weaponName=w.name;
  }
  pickups.push(pk);
}
function spawnHiddenPickups(append=false){
  if(!append) pickups=pickups.filter(p=>!p.hidden);
  const allUnlocked=_allWeaponsUnlocked();
  ['battery','battery','battery','battery','health','health','health','weapon','weapon','weapon','weapon','shield','shield','shield','emp','emp','overcharge','overcharge','battery','health','ammo','ammo','invincibility','cloak','portal','medkit','medkit','medkit'].forEach(type=>{
    if(type==='weapon'&&allUnlocked) return;
    if(type==='ammo'&&!_pickAmmoWeapon()) return;
    let x,y,a=0;do{x=rng(140,WORLD_W-140);y=rng(140,WORLD_H-140);a++;}while((circleVsObs(x,y,22)||dist(x,y,WORLD_W/2,WORLD_H/2)<300)&&a<60);
    const pk={x,y,type,t:rng(0,Math.PI*2),hidden:true};
    if(Math.random()<0.05) pk.mystery=true; // ~5% of field pickups are mystery diamonds
    if(type==='ammo'){const w=_pickAmmoWeapon();if(!w)return;pk.weaponId=w.id;pk.ammoAmt=_randomAmmoAmt(w.id);pk.weaponColor=w.color;pk.weaponName=w.name;}
    pickups.push(pk);
  });
}
function tickPickups(dt){
  for(const p of pickups) p.t+=dt*(p.hidden?2.2:3.0);
  for(let i=pickups.length-1;i>=0;i--){
    if(pickups[i].dropTimer!==null){
      pickups[i].dropTimer-=dt*1000;
      if(pickups[i].dropTimer<=0) pickups.splice(i,1);
    }
  }
}
function drawPickup(p){
  const sx=p.x-camX,sy=p.y-camY;if(sx<-50||sx>canvas.width+50||sy<-50||sy>canvas.height+50)return;
  // Dropped pickups: fast pulse below 3s, fade out in final 1s
  let pulse=0.65+Math.sin(p.t)*0.35;
  let sc=p.hidden?0.7:1.0;
  let al=p.hidden?0.48:0.94;
  if(p.dropTimer!==null){
    if(p.dropTimer<=3000){
      // Fast urgent flicker — frequency ramps up as timer approaches 0
      const urgency=1-(p.dropTimer/3000); // 0→1
      pulse=0.5+Math.abs(Math.sin(p.t*(3+urgency*6)))*0.5;
      al=Math.max(0.15, (p.dropTimer/3000)*0.94);
      sc=0.85+pulse*0.2;
    }
  }
  const c=PTYPES[p.type].color;
  ctx.save();ctx.translate(sx,sy);ctx.scale(sc,sc);ctx.globalAlpha=al;ctx.shadowBlur=16*pulse;ctx.shadowColor=c;
  // Mystery pickup (enemy drop concealed): diamond with ? — content hidden until collected
  if(p.mystery){
    const d=10,glow=0.5+0.5*Math.abs(Math.sin(p.t*2));
    ctx.shadowBlur=14*glow;ctx.shadowColor='#aaccff';
    ctx.beginPath();ctx.moveTo(0,-d);ctx.lineTo(d,0);ctx.lineTo(0,d);ctx.lineTo(-d,0);ctx.closePath();
    ctx.fillStyle=`rgba(20,40,90,${0.55+0.2*glow})`;ctx.fill();
    ctx.strokeStyle=`rgba(140,190,255,${0.7+0.3*glow})`;ctx.lineWidth=1.8;ctx.stroke();
    ctx.shadowBlur=0;
    ctx.fillStyle=`rgba(180,220,255,${0.8+0.2*glow})`;
    ctx.font='bold 11px "Courier New"';ctx.textAlign='center';ctx.textBaseline='middle';
    ctx.fillText('?',0,1);ctx.textBaseline='alphabetic';
    ctx.restore();ctx.globalAlpha=1;
    return;
  }
  switch(p.type){
    case'battery':ctx.strokeStyle=c;ctx.fillStyle=c+'18';ctx.lineWidth=2;ctx.fillRect(-10,-6,20,12);ctx.strokeRect(-10,-6,20,12);ctx.fillStyle=c;ctx.fillRect(-8,-4,12,8);ctx.fillRect(10,-2.5,3.5,5);break;
    case'health':ctx.fillStyle=c;ctx.fillRect(-2.5,-9,5,18);ctx.fillRect(-9,-2.5,18,5);break;
    case'medkit':{
      // Semi-solid translucent blue circle
      ctx.beginPath();ctx.arc(0,0,11,0,Math.PI*2);
      ctx.fillStyle='rgba(40,120,255,0.22)';ctx.fill();
      ctx.strokeStyle='rgba(100,180,255,0.85)';ctx.lineWidth=2;
      ctx.shadowBlur=12;ctx.shadowColor='#88ccff';ctx.stroke();ctx.shadowBlur=0;
      // Electric white/blue + sign
      const pw=8,ph=2.5;
      ctx.fillStyle='rgba(200,235,255,0.9)';
      ctx.fillRect(-pw/2,-ph/2,pw,ph);ctx.fillRect(-ph/2,-pw/2,ph,pw);
      // Electric shimmer on cross
      ctx.strokeStyle=`rgba(180,230,255,${0.4+0.4*Math.abs(Math.sin(p.t*4))})`;ctx.lineWidth=1;
      ctx.beginPath();ctx.moveTo(-pw/2,0);ctx.lineTo(pw/2,0);ctx.stroke();
      ctx.beginPath();ctx.moveTo(0,-pw/2);ctx.lineTo(0,pw/2);ctx.stroke();
      break;}
    case'weapon':ctx.rotate(p.t*0.9);ctx.beginPath();for(let i=0;i<8;i++){const a=(Math.PI/4)*i,r=i%2===0?13:5;i===0?ctx.moveTo(Math.cos(a)*r,Math.sin(a)*r):ctx.lineTo(Math.cos(a)*r,Math.sin(a)*r);}ctx.closePath();ctx.fillStyle=c;ctx.fill();break;
    case'shield':ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*12,Math.sin(a)*12):ctx.lineTo(Math.cos(a)*12,Math.sin(a)*12);}ctx.closePath();ctx.strokeStyle=c;ctx.lineWidth=2.5;ctx.stroke();ctx.beginPath();ctx.arc(0,0,5,0,Math.PI*2);ctx.fillStyle=c;ctx.fill();break;
    case'emp':ctx.beginPath();ctx.arc(0,0,7,0,Math.PI*2);ctx.fillStyle=c;ctx.fill();[10,14].forEach((r,i)=>{ctx.beginPath();ctx.arc(0,0,r+pulse*3*i,0,Math.PI*2);ctx.strokeStyle=c+(i===0?'99':'44');ctx.lineWidth=1.5-i*0.5;ctx.stroke();});break;
    case'overcharge':ctx.fillStyle=c;ctx.beginPath();ctx.moveTo(5,-12);ctx.lineTo(-1,0);ctx.lineTo(4,0);ctx.lineTo(-4,12);ctx.lineTo(2,0);ctx.lineTo(-3,0);ctx.closePath();ctx.fill();break;
    case'points':
      ctx.beginPath();ctx.arc(0,0,13,0,Math.PI*2);
      ctx.fillStyle=c+'33';ctx.fill();
      ctx.strokeStyle=c;ctx.lineWidth=2;ctx.stroke();
      ctx.beginPath();ctx.arc(0,0,10,0,Math.PI*2);
      ctx.strokeStyle=c+'88';ctx.lineWidth=1;ctx.stroke();
      ctx.fillStyle=c;ctx.font='bold 8px "Courier New"';ctx.textAlign='center';ctx.textBaseline='middle';
      ctx.fillText('250',0,0);ctx.textBaseline='alphabetic';
      break;
    case'ammo':{
      // Crate icon in weapon color with abbreviated weapon name
      const wc=p.weaponColor||c;
      ctx.strokeStyle=wc;ctx.fillStyle=wc+'22';ctx.lineWidth=2;
      ctx.fillRect(-11,-9,22,18);ctx.strokeRect(-11,-9,22,18);
      // Cross-hatch on crate
      ctx.strokeStyle=wc+'66';ctx.lineWidth=0.8;
      ctx.beginPath();ctx.moveTo(-11,0);ctx.lineTo(11,0);ctx.stroke();
      ctx.beginPath();ctx.moveTo(0,-9);ctx.lineTo(0,9);ctx.stroke();
      // Ammo count label
      ctx.fillStyle=wc;ctx.font='bold 7px "Courier New"';ctx.textAlign='center';ctx.textBaseline='middle';
      ctx.fillText(`+${p.ammoAmt}`,0,0);ctx.textBaseline='alphabetic';
      break;
    }
    case'invincibility':
      // White star burst
      ctx.strokeStyle=c;ctx.lineWidth=2;ctx.shadowBlur=20;ctx.shadowColor=c;
      for(let i=0;i<8;i++){const a=(Math.PI/4)*i,r1=5,r2=13;ctx.beginPath();ctx.moveTo(Math.cos(a)*r1,Math.sin(a)*r1);ctx.lineTo(Math.cos(a)*r2,Math.sin(a)*r2);ctx.stroke();}
      ctx.beginPath();ctx.arc(0,0,5,0,Math.PI*2);ctx.fillStyle=c;ctx.fill();
      break;
    case'cloak':
      // Dashed fading circle — "invisible" motif
      ctx.setLineDash([4,5]);ctx.strokeStyle=c;ctx.lineWidth=2;
      ctx.beginPath();ctx.arc(0,0,12,0,Math.PI*2);ctx.stroke();
      ctx.setLineDash([]);
      ctx.beginPath();ctx.arc(0,0,6,0,Math.PI*2);ctx.strokeStyle=c+'88';ctx.lineWidth=1;ctx.stroke();
      ctx.beginPath();ctx.arc(0,0,2,0,Math.PI*2);ctx.fillStyle=c;ctx.fill();
      break;
    case'portal':
      // Black core with orange/yellow swirling rings
      ctx.beginPath();ctx.arc(0,0,12,0,Math.PI*2);ctx.fillStyle='rgba(0,0,0,0.75)';ctx.fill();
      ctx.save();ctx.rotate(p.t*0.6);
      for(let r=5;r<=13;r+=4){ctx.beginPath();ctx.arc(0,0,r,0,Math.PI*1.5);ctx.strokeStyle=r>9?'#ffe040':c;ctx.lineWidth=2.5-r*0.1;ctx.stroke();}
      ctx.restore();
      ctx.save();ctx.rotate(-p.t*0.9);
      for(let r=7;r<=11;r+=4){ctx.beginPath();ctx.arc(0,0,r,Math.PI,Math.PI*2.2);ctx.strokeStyle=c+'88';ctx.lineWidth=1.2;ctx.stroke();}
      ctx.restore();
      ctx.beginPath();ctx.arc(0,0,3,0,Math.PI*2);ctx.fillStyle='#ffe040';ctx.shadowBlur=8;ctx.shadowColor=c;ctx.fill();ctx.shadowBlur=0;
      break;
    case'nuke_key':{
      // Key icon — circle bow + rectangular shaft, colored by bomb id
      const kc=p.nukeColor||c;
      ctx.strokeStyle=kc;ctx.fillStyle=kc+'33';ctx.lineWidth=2;ctx.shadowBlur=18;ctx.shadowColor=kc;
      // Bow (ring)
      ctx.beginPath();ctx.arc(-3,0,6,0,Math.PI*2);ctx.fill();ctx.stroke();
      // Shaft
      ctx.fillStyle=kc;
      ctx.fillRect(3,-1.5,10,3);
      // Teeth
      ctx.fillRect(9,-4,2.5,3);ctx.fillRect(12,-4,2.5,3);
      // Pulsing outer ring
      ctx.beginPath();ctx.arc(-3,0,8+Math.sin(p.t*3)*1.5,0,Math.PI*2);
      ctx.strokeStyle=kc+'66';ctx.lineWidth=1;ctx.stroke();
      ctx.shadowBlur=0;
      break;}
  }
  ctx.shadowBlur=0;ctx.restore();ctx.globalAlpha=1;
}

// ─── BULLETS ─────────────────────────────────────────────────────
function firePBullet(x,y,angle,dmg,spd,bSz,color,stun=false){pBullets.push({x,y,vx:Math.cos(angle)*spd,vy:Math.sin(angle)*spd,life:1700,dmg,bSz,color,stun});}
function fireEBullet(x,y,angle,spd=7.5,dmg=20){eBullets.push({x,y,vx:Math.cos(angle)*spd,vy:Math.sin(angle)*spd,life:2400,dmg});}
function fireWeapon(){
  Music.onShot();
  const w=WEAPONS[P.weaponIdx];
  // Deduct stock for weapons that have one (mine handled separately below)
  if(w.stock!==null&&w.id!=='mine'){
    if((P.stocks[w.id]||0)<=0) return; // blocked — isNoAmmo handles the auto-switch
    P.stocks[w.id]--;
  }
  // ── Seek Missile — homing projectile, curves around obstacles ──
  if(w.id==='seekr'){
    if(P.seekStock<=0) return;
    P.seekStock--;
    // Acquire nearest enemy
    let target=null,bestDist=Infinity;
    for(let i=0;i<enemies.length;i++){
      const d=dist(P.x,P.y,enemies[i].x,enemies[i].y);
      if(d<SEEKR_DET&&d<bestDist){bestDist=d;target=i;}
    }
    // Launch in player's aim direction, let steering take over
    const angle=target!==null?Math.atan2(enemies[target].y-P.y,enemies[target].x-P.x):P.aim;
    seekers.push({
      x:P.x+Math.cos(angle)*24,
      y:P.y+Math.sin(angle)*24,
      vx:Math.cos(angle)*SEEKR_SPD,
      vy:Math.sin(angle)*SEEKR_SPD,
      target,life:SEEKR_LIFE,blasting:false,blastT:0,trail:[]
    });
    spawnParts(P.x+Math.cos(angle)*20,P.y+Math.sin(angle)*20,SEEKR_COL,_pCount(5),2.5,3,180);
    SFX.seekr();
    return;
  }
  // ── Proximity Mine — drop at current position, no bullet ──
  if(w.id==='mine'){
    if(P.mineStock<=0) return;
    P.mineStock--;
    mines.push({x:P.x, y:P.y, t:0, armMs:350, armed:false, blastT:0, blasting:false});
    SFX.mineset();
    return;
  }
  // ── Laser — instant hitscan ray, enormous damage, 3s reload ──
  if(w.id==='laser'){
    const batCost=P.maxBat*0.03;
    if(P.bat<batCost) return; // no battery — cannot fire
    P.bat-=batCost;
    const dmg=w.dmg*(P.overchargeMs>0?2:1);
    const cos=Math.cos(P.aim),sin=Math.sin(P.aim);

    // ── Ray vs obstacle distance helpers ──
    function rayVsPillar(ox,oy,r){
      // Ray from P in direction (cos,sin) vs circle (ox,oy,r)
      const dx=ox-P.x,dy=oy-P.y;
      const along=dx*cos+dy*sin;
      if(along<0)return Infinity;
      const perp2=dx*dx+dy*dy-along*along;
      const r2=(r+3)*(r+3);
      if(perp2>r2)return Infinity;
      return along-Math.sqrt(Math.max(0,r2-perp2));
    }
    function rayVsWall(wx,wy,ww,wh){
      // Slab method: ray vs AABB
      const INF=Infinity;
      let tmin=0,tmax=INF;
      if(Math.abs(cos)<1e-9){if(P.x<wx||P.x>wx+ww)return INF;}
      else{let t1=(wx-P.x)/cos,t2=(wx+ww-P.x)/cos;if(t1>t2){const tmp=t1;t1=t2;t2=tmp;}tmin=Math.max(tmin,t1);tmax=Math.min(tmax,t2);}
      if(Math.abs(sin)<1e-9){if(P.y<wy||P.y>wy+wh)return INF;}
      else{let t1=(wy-P.y)/sin,t2=(wy+wh-P.y)/sin;if(t1>t2){const tmp=t1;t1=t2;t2=tmp;}tmin=Math.max(tmin,t1);tmax=Math.min(tmax,t2);}
      return tmax>=tmin?Math.max(0,tmin):INF;
    }

    // Find nearest enemy on the ray
    let hitEnemy=null,hitDist=Infinity;
    for(let ei=0;ei<enemies.length;ei++){
      const e=enemies[ei];
      const dx=e.x-P.x,dy=e.y-P.y;
      const along=dx*cos+dy*sin;
      if(along<0)continue;
      const perp=Math.abs(dx*sin-dy*cos);
      if(perp<e.size+4&&along<hitDist){hitDist=along;hitEnemy=ei;}
    }

    // Find nearest obstacle on the ray
    let obsDist=Infinity;
    for(const o of obstacles){
      const d=o.type==='pillar'?rayVsPillar(o.x,o.y,o.r):rayVsWall(o.x,o.y,o.w,o.h);
      if(d<obsDist)obsDist=d;
    }

    // Stop at whichever is closer — enemy or obstacle
    let ex,ey,blockedByObs=false;
    if(obsDist<hitDist){
      // Ray hits obstacle first — terminate here, no enemy damage
      hitEnemy=null; blockedByObs=true;
      ex=P.x+cos*obsDist; ey=P.y+sin*obsDist;
    } else if(hitEnemy!==null){
      ex=enemies[hitEnemy].x; ey=enemies[hitEnemy].y;
    } else {
      // Extend to world boundary
      const ts=[];
      if(cos>0)ts.push((WORLD_W-P.x)/cos);else if(cos<0)ts.push(-P.x/cos);
      if(sin>0)ts.push((WORLD_H-P.y)/sin);else if(sin<0)ts.push(-P.y/sin);
      const t2=Math.max(0,Math.min(...ts));
      ex=P.x+cos*t2; ey=P.y+sin*t2;
    }

    // Build jagged fork data (seeded once per shot so it's stable during fade)
    const len=Math.sqrt((ex-P.x)**2+(ey-P.y)**2);
    const segments=Math.max(4,Math.floor(len/60));
    const forks=[];
    for(let s=0;s<segments;s++){
      const t2=(s+0.5)/segments;
      const mx2=P.x+cos*len*t2,my2=P.y+sin*len*t2;
      const jx=(Math.random()-0.5)*28,jy=(Math.random()-0.5)*28;
      forks.push({t:t2,jx,jy});
      if(Math.random()<0.4){
        const bAngle=P.aim+(Math.random()-0.5)*0.9;
        const bLen=40+Math.random()*70;
        forks.push({branch:true,x1:mx2+jx,y1:my2+jy,x2:mx2+jx+Math.cos(bAngle)*bLen,y2:my2+jy+Math.sin(bAngle)*bLen,t:t2});
      }
    }
    laserFlash={x1:P.x,y1:P.y,x2:ex,y2:ey,life:280,maxLife:280,forks,hitEnemy};
    if(hitEnemy!==null){
      const e=enemies[hitEnemy];
      e.hp-=dmg;
      spawnParts(ex,ey,'#ff66ff',_pCount(18),5,6,400);
      spawnParts(ex,ey,'#ffffff',_pCount(8),3,4,280);
      if(e.hp<=0){SFX.boom();killEnemy(hitEnemy);}
    } else if(blockedByObs){
      // Spark off the obstacle surface
      spawnParts(ex,ey,'#ff66ff',_pCount(8),3,4,250);
      spawnParts(ex,ey,'#ffffff',_pCount(4),2,3,180);
    }
    if(settings.screenShake)shake=hitEnemy!==null?14:6;
    SFX.laser();
    P.vx-=cos*2.2;P.vy-=sin*2.2;
    return;
  }
  // ── Rocket Launcher — piercing straight projectile ──
  if(w.id==='rocket'){
    const angle=P.aim;
    rockets.push({
      x:P.x+Math.cos(angle)*22, y:P.y+Math.sin(angle)*22,
      vx:Math.cos(angle)*ROCKET_SPD, vy:Math.sin(angle)*ROCKET_SPD,
      angle, life:ROCKET_LIFE,
      hitEnemies:new Set(),  // avoid hitting same enemy twice
      trail:[],              // {x,y} world coords — burn scorch marks
    });
    spawnParts(P.x+Math.cos(angle)*18,P.y+Math.sin(angle)*18,ROCKET_COL,_pCount(6),3,4.5,240);
    SFX.seekr(); // reuse seekr launch sound — fits a rocket well
    Music.onShot();
    P.vx-=Math.cos(angle)*0.9; P.vy-=Math.sin(angle)*0.9;
    return;
  }
  if(w.id==='minime'){
    if(miniMe.active||miniMe.lost) return; // already out or lost this wave
    miniMe.active=true; miniMe.lost=false;
    miniMe.hp=MM_HP; miniMe.iframes=0;
    // Spawn beside player
    miniMe.x=P.x+Math.cos(P.aim+Math.PI*0.6)*50;
    miniMe.y=P.y+Math.sin(P.aim+Math.PI*0.6)*50;
    miniMe.vx=P.vx; miniMe.vy=P.vy;
    miniMe.orbitAngle=Math.atan2(miniMe.y-P.y,miniMe.x-P.x);
    miniMe.aim=P.aim; miniMe.rotor=0; miniMe.lastFired=0;
    spawnParts(miniMe.x,miniMe.y,MM_COL,_pCount(16),4,5,420);
    SFX.mmdeploy();
    return;
  }
  if(w.id==='stun'){
    firePBullet(P.x,P.y,P.aim,0,w.spd,w.bSz,w.color,true);
    spawnParts(P.x+Math.cos(P.aim)*28,P.y+Math.sin(P.aim)*28,'#aaff44',_pCount(4),3,3,120);
    SFX.stun();P.vx-=Math.cos(P.aim)*0.3;P.vy-=Math.sin(P.aim)*0.3;
    return;
  }
  // ── Fractal Fusion — branching electrical burst, obstacle-aware ──
  if(w.id==='fractal'){
    const DMG=18;
    const segs=[];
    const hitSet=new Set(); // enemies already damaged this pulse — prevents double-hit at fire
    const originX=P.x+Math.cos(P.aim)*20, originY=P.y+Math.sin(P.aim)*20;
    function _fracSeg(rx,ry,angle,length,gen){
      const STEP=7, steps=Math.ceil(length/STEP);
      let ex=rx,ey=ry;
      for(let s=1;s<=steps;s++){
        const nx=rx+Math.cos(angle)*STEP*s, ny=ry+Math.sin(angle)*STEP*s;
        const wx=originX+nx, wy=originY+ny;
        if(circleVsObs(wx,wy,4)||wx<0||wx>WORLD_W||wy<0||wy>WORLD_H) break;
        ex=nx; ey=ny;
        for(let ei=0;ei<enemies.length;ei++){
          if(!hitSet.has(ei)&&dist2(wx,wy,enemies[ei].x,enemies[ei].y)<38*38) hitSet.add(ei);
        }
      }
      const jx=ex+(Math.random()-0.5)*10, jy=ey+(Math.random()-0.5)*10;
      segs.push({x1:rx,y1:ry,x2:jx,y2:jy,gen});
      if(gen<4&&(ex!==rx||ey!==ry)){
        const branches=gen===0?3:gen===1?3:2;
        const spread=gen===0?0.55:gen===1?0.72:0.88;
        const nextLen=gen===0?130:gen===1?100:gen===2?72:48;
        for(let b=0;b<branches;b++){
          _fracSeg(jx,jy,angle+(Math.random()-0.5)*spread*2,nextLen,gen+1);
        }
      }
    }
    _fracSeg(0,0,P.aim,80,0);
    hitSet.forEach(ei=>{
      if(ei<enemies.length){
        enemies[ei].hp-=DMG;
        spawnParts(enemies[ei].x,enemies[ei].y,'#ff9900',_pCount(5),2.5,3.5,250);
        if(enemies[ei].hp<=0) killEnemy(ei);
      }
    });
    fractals.push({segs, life:700, maxLife:700, ox:originX, oy:originY, vx:P.vx, vy:P.vy, dmg:DMG, hitSet});
    SFX.fractal(); Music.onShot(); return;
  }
  if(w.id==='leech'){
    const cos=Math.cos(P.aim),sin=Math.sin(P.aim);
    let hit=null,hitDist=Infinity;
    for(let ei=0;ei<enemies.length;ei++){
      const e=enemies[ei];
      const ex=e.x-P.x,ey=e.y-P.y;
      const proj=ex*cos+ey*sin;
      if(proj<0||proj>600)continue;
      const perpD=Math.abs(ex*sin-ey*cos);
      if(perpD<e.size+4&&proj<hitDist){hitDist=proj;hit=ei;}
    }
    if(hit!==null){
      const e=enemies[hit];
      const dmg=60*(P.overchargeMs>0?2:1);
      e.hp-=dmg;
      P.hp=Math.min(P.maxHp,P.hp+24);
      spawnParts(e.x,e.y,'#00ff88',_pCount(12),3,4,350);
      spawnParts(e.x,e.y,'#ffffff',_pCount(6),2,3,250);
      leechFlash={active:true,tx:P.x+cos*hitDist,ty:P.y+sin*hitDist,ms:400};
      if(e.hp<=0){SFX.boom();killEnemy(hit);}
    } else {
      leechFlash={active:true,tx:P.x+cos*600,ty:P.y+sin*600,ms:280};
    }
    SFX.laser();
    return;
  }
  if(w.id==='shockwave'){
    const DMG=50*(P.overchargeMs>0?2:1);
    for(let ei=enemies.length-1;ei>=0;ei--){
      const e=enemies[ei];
      const dx=e.x-P.x,dy=e.y-P.y;
      const d2=dx*dx+dy*dy;
      if(d2>SHOCKWAVE_R*SHOCKWAVE_R)continue;
      e.hp-=DMG;
      const d=Math.sqrt(d2)||1;
      e.vx+=(dx/d)*SHOCKWAVE_KB;
      e.vy+=(dy/d)*SHOCKWAVE_KB;
      spawnParts(e.x,e.y,e.color,_pCount(6),2.5,3.5,280);
      if(e.hp<=0){SFX.boom();killEnemy(ei);}
    }
    shockwaveFlash={ms:600};
    spawnParts(P.x,P.y,'#ff8844',_pCount(25),6,8,600);
    spawnParts(P.x,P.y,'#ffffff',_pCount(10),4,5,400);
    if(settings.screenShake)shake=Math.max(shake,14);
    SFX.emp();
    return;
  }
  const deadEyeMult=P.craftIdx===SNIPER_IDX()?Math.min(3.0,1.0+(deadEyeMs/2000)*2.0):1.0;
  const dmg=w.dmg*(P.overchargeMs>0?2.3:1)*deadEyeMult,half=(w.count-1)/2;
  if(w.id==='boomr'){
    const spd=26;
    boomerangs.push({
      x:P.x, y:P.y,
      vx:Math.cos(P.aim)*spd, vy:Math.sin(P.aim)*spd,
      dmg, phase:'out', rot:0, hitEnemies:new Set(),
      color:w.color
    });
    spawnParts(P.x+Math.cos(P.aim)*28,P.y+Math.sin(P.aim)*28,w.color,_pCount(5),2.5,3,180);
    SFX.boomr(); P.vx-=Math.cos(P.aim)*0.7; P.vy-=Math.sin(P.aim)*0.7;
    return;
  }
  if(w.id==='rico'){
    // Fire a rico bullet — identical to plasma but tagged to bounce, 10s max life
    const angle=P.aim;
    pBullets.push({x:P.x,y:P.y,vx:Math.cos(angle)*w.spd,vy:Math.sin(angle)*w.spd,life:10000,dmg,bSz:w.bSz,color:w.color,stun:false,rico:true});
    spawnParts(P.x+Math.cos(angle)*36,P.y+Math.sin(angle)*36,w.color,_pCount(5),2.5,3.5,160);
    SFX.rico();P.vx-=Math.cos(angle)*1.8;P.vy-=Math.sin(angle)*1.8;
    return;
  }
  // ── Digital Infection — rapid green elongated electric rounds ──
  if(w.id==='dinf'){
    const angle=P.aim+(Math.random()-0.5)*0.06;
    pBullets.push({x:P.x+Math.cos(angle)*22,y:P.y+Math.sin(angle)*22,vx:Math.cos(angle)*w.spd,vy:Math.sin(angle)*w.spd,life:1700,dmg,bSz:w.bSz,color:'#00ff88',stun:false,dinf:true});
    spawnParts(P.x+Math.cos(angle)*18,P.y+Math.sin(angle)*18,'#00ff88',_pCount(2),1.5,2.5,140);
    SFX.rapid();P.vx-=Math.cos(P.aim)*0.3;P.vy-=Math.sin(P.aim)*0.3;
    return;
  }
  if(w.id==='grapple'){
    const angle=P.aim;
    pBullets.push({
      x:P.x+Math.cos(angle)*20, y:P.y+Math.sin(angle)*20,
      vx:Math.cos(angle)*18, vy:Math.sin(angle)*18,
      dmg:0, bSz:4, color:'#44ddff', life:1700, stun:false, dinf:false,
      fromInfected:false, isGrapple:true
    });
    spawnParts(P.x+Math.cos(angle)*16,P.y+Math.sin(angle)*16,'#44ddff',_pCount(4),2,3,180);
    SFX.select();
    return;
  }
  if(w.id==='faraday'){
    faradayCages.push({x:P.x,y:P.y,life:FARADAY_LIFE,armed:false,armMs:250,trapped:[],blasting:false,blastT:0});
    spawnParts(P.x,P.y,'#88ffcc',_pCount(8),2,3.5,300);
    SFX.mineset();
    return;
  }
  if(w.id==='grenade'){
    const angle=P.aim;
    grenades.push({x:P.x+Math.cos(angle)*22,y:P.y+Math.sin(angle)*22,vx:Math.cos(angle)*11,vy:Math.sin(angle)*11,bounces:0,life:GRENADE_LIFE,blasting:false,blastT:0});
    spawnParts(P.x+Math.cos(angle)*18,P.y+Math.sin(angle)*18,'#ffaa22',_pCount(5),2.5,3.5,200);
    SFX.mineset();
    return;
  }
  if(w.id==='gravwell'){
    gravityWells.push({x:P.x,y:P.y,life:GRAVWELL_LIFE,blasting:false,blastT:0});
    spawnParts(P.x,P.y,'#cc44ff',_pCount(14),4,6,500);
    SFX.emp();
    return;
  }
  for(let i=0;i<w.count;i++)firePBullet(P.x,P.y,P.aim+(i-half)*w.spread,dmg,w.spd,w.bSz,w.color);
  spawnParts(P.x+Math.cos(P.aim)*36,P.y+Math.sin(P.aim)*36,w.color,_pCount(3+w.count),2,2.5,130);
  SFX[w.id]();P.vx-=Math.cos(P.aim)*0.9;P.vy-=Math.sin(P.aim)*0.9;
}
function tickBullets(dt){
  const step=dt*60;
  for(let i=pBullets.length-1;i>=0;i--){
    const b=pBullets[i];
    b.x+=b.vx*step;b.y+=b.vy*step;b.life-=dt*1000;
    if(b.rico){
      // Rico bullets never leave the world — bounce off world edges
      if(b.x-b.bSz<0){b.x=b.bSz;b.vx=Math.abs(b.vx);spawnParts(b.x,b.y,b.color,_pCount(3),1.5,2.5,160);SFX.ricobounce();}
      else if(b.x+b.bSz>WORLD_W){b.x=WORLD_W-b.bSz;b.vx=-Math.abs(b.vx);spawnParts(b.x,b.y,b.color,_pCount(3),1.5,2.5,160);SFX.ricobounce();}
      if(b.y-b.bSz<0){b.y=b.bSz;b.vy=Math.abs(b.vy);spawnParts(b.x,b.y,b.color,_pCount(3),1.5,2.5,160);SFX.ricobounce();}
      else if(b.y+b.bSz>WORLD_H){b.y=WORLD_H-b.bSz;b.vy=-Math.abs(b.vy);spawnParts(b.x,b.y,b.color,_pCount(3),1.5,2.5,160);SFX.ricobounce();}
      // Bounce off obstacles
      if(reflectRicoVsObs(b)){spawnParts(b.x,b.y,b.color,_pCount(4),2,3,200);SFX.ricobounce();}
      // Rico bullets expire only by time (long life) — never by leaving world
      if(b.life<=0){pBullets.splice(i,1);}
    } else {
      if(b.life<=0||b.x<-50||b.x>WORLD_W+50||b.y<-50||b.y>WORLD_H+50){pBullets.splice(i,1);continue;}
      if(circleVsObs(b.x,b.y,b.bSz)){spawnParts(b.x,b.y,'#5599bb',_pCount(4),1.8,2.5,200);SFX.wallhit();pBullets.splice(i,1);}
    }
  }
  for(let i=eBullets.length-1;i>=0;i--){
    const b=eBullets[i];b.x+=b.vx*step;b.y+=b.vy*step;b.life-=dt*1000;
    // Demolisher plasma bomb — check before normal expiry
    if(b.isBomb){
      if(b.life<=0||dist(b.x,b.y,b.ox,b.oy)>600||circleVsObs(b.x,b.y,b.bSz)){
        hazards.push({type:'plasma_zone',x:b.x,y:b.y,r:55,duration:2500,t:0});
        spawnParts(b.x,b.y,'#cc44ff',_pCount(14),4,6,600);
        if(settings.screenShake)shake=Math.max(shake,12);
        eBullets.splice(i,1);
      }
      continue;
    }
    if(b.life<=0||b.x<-50||b.x>WORLD_W+50||b.y<-50||b.y>WORLD_H+50){eBullets.splice(i,1);continue;}
    const ebs=b.isBrute?b.bSz:3.5;
    if(circleVsObs(b.x,b.y,ebs)){spawnParts(b.x,b.y,'rgba(220,120,0,0.8)',_pCount(b.isBrute?8:3),1.5,2,b.isBrute?300:180);eBullets.splice(i,1);continue;}
    if(b.fromInfected){
      for(let ei=enemies.length-1;ei>=0;ei--){
        const e=enemies[ei];
        if(e.infected) continue;
        if(dist2(b.x,b.y,e.x,e.y)<e.size*e.size*1.21){
          e.hp-=b.dmg;spawnParts(b.x,b.y,'#00ff88',_pCount(5),2,3,200);
          if(e.hp<=0){SFX.boom();killEnemy(ei);}
          eBullets.splice(i,1);break;
        }
      }
    }
  }
}
function drawBullets(){
  ctx.shadowBlur=10;
  const now=Date.now();
  for(const b of pBullets){
    const sx=b.x-camX,sy=b.y-camY;
    if(sx<-20||sx>canvas.width+20||sy<-20||sy>canvas.height+20)continue;
    if(b.isGrapple){
      const angle=Math.atan2(b.vy,b.vx);
      ctx.save();ctx.translate(sx,sy);ctx.rotate(angle);
      ctx.shadowBlur=14;ctx.shadowColor='#44ddff';
      ctx.beginPath();
      ctx.moveTo(6,0);ctx.lineTo(-4,4);ctx.lineTo(-3,0);ctx.lineTo(-4,-4);ctx.closePath();
      ctx.fillStyle='#44ddff';ctx.fill();
      ctx.beginPath();ctx.moveTo(-4,0);ctx.lineTo(-14,0);
      ctx.strokeStyle='rgba(68,221,255,0.6)';ctx.lineWidth=1.5;ctx.stroke();
      ctx.shadowBlur=0;ctx.restore();
      continue;
    }
    if(b.dinf){
      // Digital Infection: elongated green bolt (~40% longer) with electric pulse
      const spd=Math.sqrt(b.vx*b.vx+b.vy*b.vy)||1;
      const nx=b.vx/spd, ny=b.vy/spd;
      const px=-ny, py=nx;
      const len=22;
      const seed=Math.floor(now/55);
      const pulse=0.5+0.5*Math.sin(now/60);
      ctx.save();
      ctx.globalAlpha=0.28*pulse;ctx.fillStyle='#00ff88';
      ctx.beginPath();ctx.arc(sx-nx*len*0.55,sy-ny*len*0.55,3.5,0,Math.PI*2);ctx.fill();
      ctx.globalAlpha=1;
      ctx.strokeStyle='#00ff88';ctx.lineWidth=2.8;
      ctx.shadowBlur=10;ctx.shadowColor='#00ff88';
      ctx.beginPath();ctx.moveTo(sx-nx*len*0.5,sy-ny*len*0.5);ctx.lineTo(sx+nx*len*0.5,sy+ny*len*0.5);ctx.stroke();
      ctx.strokeStyle=`rgba(180,255,200,${0.55+0.45*pulse})`;ctx.lineWidth=1.2;ctx.shadowBlur=6;
      ctx.beginPath();ctx.moveTo(sx-nx*len*0.5,sy-ny*len*0.5);
      for(let seg=1;seg<=4;seg++){
        const t=seg/4;
        const j=(Math.sin(seed*3.1+seg*4.7))*3.5;
        ctx.lineTo(sx-nx*len*0.5+nx*len*t+px*j,sy-ny*len*0.5+ny*len*t+py*j);
      }
      ctx.stroke();
      ctx.fillStyle='#aaffcc';ctx.shadowBlur=8;ctx.shadowColor='#00ff88';
      ctx.beginPath();ctx.arc(sx+nx*len*0.5,sy+ny*len*0.5,2.2,0,Math.PI*2);ctx.fill();
      ctx.shadowBlur=0;ctx.restore();
      continue;
    }
    if(b.stun){
      const seed=Math.floor(now/50); // fast crackle ~20fps
      const spd=Math.sqrt(b.vx*b.vx+b.vy*b.vy)||1;
      const nx=b.vx/spd, ny=b.vy/spd; // forward direction
      const px=-ny, py=nx;              // perpendicular
      const len=28;
      ctx.save();
      ctx.shadowBlur=14;ctx.shadowColor='#aaff44';
      ctx.strokeStyle='#aaff44';ctx.lineWidth=1.6;
      ctx.beginPath();
      ctx.moveTo(sx-nx*len/2, sy-ny*len/2);
      for(let seg=1;seg<=5;seg++){
        const t2=seg/5;
        const j=(Math.sin(seed*2.9+seg*5.3))*7;
        ctx.lineTo(sx-nx*len/2+nx*len*t2+px*j, sy-ny*len/2+ny*len*t2+py*j);
      }
      ctx.stroke();
      // bright white core
      ctx.strokeStyle='#ffffff';ctx.lineWidth=0.8;ctx.shadowBlur=0;
      ctx.beginPath();ctx.moveTo(sx-nx*len/2,sy-ny*len/2);ctx.lineTo(sx+nx*len/2,sy+ny*len/2);ctx.stroke();
      // glow head dot
      ctx.fillStyle='#aaff44';ctx.shadowBlur=10;ctx.shadowColor='#aaff44';
      ctx.beginPath();ctx.arc(sx,sy,3,0,Math.PI*2);ctx.fill();
      ctx.restore();
    } else if(b.rico){
      // Rico: pulsing purple orb with spinning ring to show it's live
      const pulse=0.7+0.3*Math.sin(now/120+b.x*0.05);
      ctx.save();
      ctx.globalAlpha=0.22*pulse;ctx.fillStyle=b.color;
      ctx.beginPath();ctx.arc(sx,sy,b.bSz*2.2,0,Math.PI*2);ctx.fill();
      ctx.globalAlpha=0.28;ctx.fillStyle=b.color;
      ctx.beginPath();ctx.arc(sx-b.vx*3,sy-b.vy*3,b.bSz*0.7,0,Math.PI*2);ctx.fill();
      ctx.globalAlpha=1;
      ctx.shadowBlur=20;ctx.shadowColor=b.color;
      ctx.fillStyle='#ffffff';ctx.beginPath();ctx.arc(sx,sy,b.bSz,0,Math.PI*2);ctx.fill();
      ctx.globalAlpha=0.6;ctx.fillStyle=b.color;ctx.beginPath();ctx.arc(sx,sy,b.bSz*0.65,0,Math.PI*2);ctx.fill();
      ctx.globalAlpha=1;
      const ringAngle=now/200;
      ctx.strokeStyle=b.color;ctx.lineWidth=1.5;ctx.shadowBlur=8;ctx.shadowColor=b.color;
      ctx.beginPath();ctx.ellipse(sx,sy,b.bSz*1.7,b.bSz*0.55,ringAngle,0,Math.PI*2);ctx.stroke();
      ctx.restore();
    } else {
      ctx.shadowColor=b.color;ctx.globalAlpha=0.32;ctx.fillStyle=b.color;ctx.beginPath();ctx.arc(sx-b.vx*2.8,sy-b.vy*2.8,b.bSz*0.65,0,Math.PI*2);ctx.fill();ctx.globalAlpha=1;ctx.fillStyle='#ffffff';ctx.beginPath();ctx.arc(sx,sy,b.bSz,0,Math.PI*2);ctx.fill();if(b.bSz>=8){ctx.globalAlpha=0.25;ctx.fillStyle=b.color;ctx.beginPath();ctx.arc(sx,sy,b.bSz*1.75,0,Math.PI*2);ctx.fill();ctx.globalAlpha=1;}
    }
  }
  // Regular enemy bullets
  ctx.shadowColor='#ff8800';ctx.fillStyle='#ffaa22';
  for(const b of eBullets){
    if(b.isBomb){
      const bsx=b.x-camX,bsy=b.y-camY;
      if(bsx<-60||bsx>canvas.width+60||bsy<-60||bsy>canvas.height+60) continue;
      const pulse=0.5+0.5*Math.sin(Date.now()/90);
      ctx.save();ctx.translate(bsx,bsy);
      ctx.beginPath();ctx.arc(0,0,b.bSz,0,Math.PI*2);
      ctx.fillStyle=`rgba(204,68,255,${0.7+0.3*pulse})`;ctx.shadowBlur=16;ctx.shadowColor='#cc44ff';ctx.fill();
      ctx.strokeStyle='#ee99ff';ctx.lineWidth=1.5;ctx.stroke();
      ctx.restore();
      continue;
    }
    if(b.isBrute) continue; // drawn separately below
    const sx=b.x-camX,sy=b.y-camY;if(sx<-10||sx>canvas.width+10||sy<-10||sy>canvas.height+10)continue;
    ctx.globalAlpha=0.32;ctx.beginPath();ctx.arc(sx-b.vx*2.5,sy-b.vy*2.5,2,0,Math.PI*2);ctx.fill();ctx.globalAlpha=1;ctx.beginPath();ctx.arc(sx,sy,3.5,0,Math.PI*2);ctx.fill();
  }
  // Brute plasma bolts — large, slow, orange glow
  for(const b of eBullets){
    if(!b.isBrute) continue;
    const sx=b.x-camX,sy=b.y-camY;if(sx<-30||sx>canvas.width+30||sy<-30||sy>canvas.height+30)continue;
    ctx.shadowBlur=18;ctx.shadowColor='#ff6600';
    ctx.globalAlpha=0.25;ctx.beginPath();ctx.arc(sx-b.vx*3,sy-b.vy*3,6,0,Math.PI*2);ctx.fillStyle='#ff6600';ctx.fill();
    ctx.globalAlpha=1;ctx.beginPath();ctx.arc(sx,sy,b.bSz,0,Math.PI*2);
    const grad=ctx.createRadialGradient(sx,sy,0,sx,sy,b.bSz);
    grad.addColorStop(0,'#fff8cc');grad.addColorStop(0.4,'#ff9900');grad.addColorStop(1,'rgba(255,60,0,0)');
    ctx.fillStyle=grad;ctx.fill();ctx.shadowBlur=0;
  }
  ctx.shadowBlur=0;
}

// ═══════════════════════════════════════════════════════════════
//  CRAFT DRAW FUNCTIONS  (4 unique craft designs)
// ═══════════════════════════════════════════════════════════════

// ── PHANTOM  hex body, 4 diagonal arms ──────────────────────────
function drawPhantom(x,y,aim,sz,col,acc,spin,hp=1){
  ctx.save();ctx.translate(x,y);
  const arm=sz*1.38,rr=sz*0.72;
  ctx.shadowBlur=18;ctx.shadowColor=col;
  const ARMS=[Math.PI/4,-Math.PI/4,3*Math.PI/4,-3*Math.PI/4];
  ctx.strokeStyle=col;ctx.lineWidth=2.2;
  for(const a of ARMS){
    const ax=Math.cos(a)*arm,ay=Math.sin(a)*arm;
    ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(ax,ay);ctx.stroke();
    ctx.beginPath();ctx.arc(ax,ay,4,0,Math.PI*2);ctx.fillStyle=col;ctx.fill();
    ctx.save();ctx.translate(ax,ay);ctx.rotate(spin);
    for(let b=0;b<2;b++){ctx.save();ctx.rotate(b*Math.PI/2);ctx.beginPath();ctx.ellipse(0,0,rr,rr*0.22,0,0,Math.PI*2);ctx.strokeStyle=acc;ctx.lineWidth=1.5;ctx.globalAlpha=0.62;ctx.stroke();ctx.restore();}
    ctx.globalAlpha=1;ctx.restore();
  }
  const bs=sz*0.54;
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i-Math.PI/6;i===0?ctx.moveTo(Math.cos(a)*bs,Math.sin(a)*bs):ctx.lineTo(Math.cos(a)*bs,Math.sin(a)*bs);}ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.8;ctx.stroke();
  ctx.save();ctx.rotate(aim);ctx.fillStyle=acc;ctx.shadowBlur=8;ctx.shadowColor=acc;ctx.fillRect(bs*0.38,-2,bs*1.2,4);ctx.beginPath();ctx.arc(bs*0.38+bs*1.2,0,2,0,Math.PI*2);ctx.fillStyle='#fff';ctx.fill();ctx.restore();
  ctx.beginPath();ctx.arc(0,0,4.5,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowColor=acc;ctx.shadowBlur=16;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.75,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}

// ── VIPER  swept V-arms, diamond body, twin engines ─────────────
function drawViper(x,y,aim,sz,col,acc,spin,hp=1){
  ctx.save();ctx.translate(x,y);
  ctx.shadowBlur=18;ctx.shadowColor=col;
  const arm=sz*1.5;
  // Two swept-back arms forming V
  const sweep=[-2.2,2.2];
  ctx.strokeStyle=col;ctx.lineWidth=2;
  for(const a of sweep){
    const ax=Math.cos(a)*arm,ay=Math.sin(a)*arm;
    ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(ax,ay);ctx.stroke();
    ctx.beginPath();ctx.arc(ax,ay,4.5,0,Math.PI*2);ctx.fillStyle=col;ctx.fill();
    // Engine pod at tip
    ctx.save();ctx.translate(ax,ay);
    ctx.beginPath();ctx.ellipse(0,0,6,4,0,0,Math.PI*2);ctx.fillStyle='rgba(4,9,22,0.95)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.5;ctx.stroke();
    // Rotor
    ctx.rotate(spin*(a>0?1:-1));
    for(let b=0;b<2;b++){ctx.save();ctx.rotate(b*Math.PI/2);ctx.beginPath();ctx.ellipse(0,0,sz*0.85,sz*0.18,0,0,Math.PI*2);ctx.strokeStyle=acc;ctx.lineWidth=1.2;ctx.globalAlpha=0.6;ctx.stroke();ctx.restore();}
    ctx.globalAlpha=1;ctx.restore();
  }
  // Two small stabilizer fins
  [[Math.PI*0.55,0.4],[Math.PI*1.45,0.4]].forEach(([a,scale])=>{
    const ax=Math.cos(a)*sz*scale,ay=Math.sin(a)*sz*scale;
    ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(ax,ay);ctx.strokeStyle=col;ctx.globalAlpha=0.45;ctx.lineWidth=1.2;ctx.stroke();ctx.globalAlpha=1;
  });
  // Diamond body
  const bw=sz*0.42,bh=sz*0.65;
  ctx.beginPath();ctx.moveTo(0,-bh);ctx.lineTo(bw,0);ctx.lineTo(0,bh);ctx.lineTo(-bw,0);ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.95)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.8;ctx.stroke();
  // Center spine detail
  ctx.strokeStyle=acc;ctx.lineWidth=0.8;ctx.globalAlpha=0.4;ctx.beginPath();ctx.moveTo(0,-bh*0.65);ctx.lineTo(0,bh*0.65);ctx.stroke();ctx.globalAlpha=1;
  // Gun barrel
  ctx.save();ctx.rotate(aim);
  ctx.fillStyle=acc;ctx.shadowBlur=8;ctx.shadowColor=acc;
  // Sleek double barrel
  ctx.fillRect(sz*0.25,-3.5,sz*1.4,2.5);ctx.fillRect(sz*0.25,1,sz*1.4,2.5);
  ctx.beginPath();ctx.arc(sz*0.25+sz*1.4,0,2,0,Math.PI*2);ctx.fillStyle='#fff';ctx.fill();
  ctx.restore();
  // Core
  ctx.beginPath();ctx.arc(0,0,4,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowColor=acc;ctx.shadowBlur=14;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.8,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.18)';ctx.fill();}
  ctx.restore();
}

// ── TITAN  6 thick arms, octagonal armored hull ─────────────────
function drawTitan(x,y,aim,sz,col,acc,spin,hp=1){
  ctx.save();ctx.translate(x,y);
  ctx.shadowBlur=20;ctx.shadowColor=col;
  // 6 arms
  for(let i=0;i<6;i++){
    const a=(Math.PI/3)*i+Math.PI/6;
    const ax=Math.cos(a)*sz*1.3,ay=Math.sin(a)*sz*1.3;
    ctx.strokeStyle=col;ctx.lineWidth=4;
    ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(ax,ay);ctx.stroke();
    ctx.beginPath();ctx.arc(ax,ay,5.5,0,Math.PI*2);ctx.fillStyle=col;ctx.fill();
    ctx.save();ctx.translate(ax,ay);ctx.rotate(spin*(i%2===0?1:-1));
    for(let b=0;b<2;b++){ctx.save();ctx.rotate(b*Math.PI/2);ctx.beginPath();ctx.ellipse(0,0,sz*0.65,sz*0.2,0,0,Math.PI*2);ctx.strokeStyle=acc;ctx.lineWidth=1.8;ctx.globalAlpha=0.55;ctx.stroke();ctx.restore();}
    ctx.globalAlpha=1;ctx.restore();
  }
  // Octagonal body
  const bs=sz*0.6;
  ctx.beginPath();for(let i=0;i<8;i++){const a=(Math.PI/4)*i;i===0?ctx.moveTo(Math.cos(a)*bs,Math.sin(a)*bs):ctx.lineTo(Math.cos(a)*bs,Math.sin(a)*bs);}ctx.closePath();
  ctx.fillStyle='rgba(6,12,28,0.96)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=2.8;ctx.stroke();
  // Inner armor ring
  const br=bs*0.72;
  ctx.beginPath();for(let i=0;i<8;i++){const a=(Math.PI/4)*i+Math.PI/8;i===0?ctx.moveTo(Math.cos(a)*br,Math.sin(a)*br):ctx.lineTo(Math.cos(a)*br,Math.sin(a)*br);}ctx.closePath();
  ctx.strokeStyle=acc;ctx.lineWidth=0.8;ctx.globalAlpha=0.35;ctx.stroke();ctx.globalAlpha=1;
  // Rivet details
  for(let i=0;i<4;i++){const a=(Math.PI/2)*i+Math.PI/4;ctx.beginPath();ctx.arc(Math.cos(a)*bs*0.78,Math.sin(a)*bs*0.78,2.2,0,Math.PI*2);ctx.fillStyle=acc;ctx.globalAlpha=0.5;ctx.fill();ctx.globalAlpha=1;}
  // Dual gun barrel
  ctx.save();ctx.rotate(aim);ctx.fillStyle=acc;ctx.shadowBlur=10;ctx.shadowColor=acc;
  ctx.fillRect(bs*0.42,-4.5,bs*1.05,3.5);ctx.fillRect(bs*0.42,1,bs*1.05,3.5);
  // Barrel tips
  ctx.beginPath();ctx.arc(bs*0.42+bs*1.05,-2.75,2.5,0,Math.PI*2);ctx.fillStyle='#fff';ctx.fill();
  ctx.beginPath();ctx.arc(bs*0.42+bs*1.05,2.75,2.5,0,Math.PI*2);ctx.fillStyle='#fff';ctx.fill();
  ctx.restore();
  // Core
  ctx.beginPath();ctx.arc(0,0,5.5,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowColor=acc;ctx.shadowBlur=18;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.8,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.15)';ctx.fill();}
  ctx.restore();
}

// ── SPECTER  3 swept arms, triangular body, phase rings ─────────
function drawSpecter(x,y,aim,sz,col,acc,spin,hp=1){
  const now=Date.now();
  ctx.save();ctx.translate(x,y);
  ctx.shadowBlur=16;ctx.shadowColor=col;
  // Phase rings (rotating outward)
  for(let i=1;i<=2;i++){
    const r=sz*(1.1+i*0.45),phase=(now/600)*i*(i%2===0?1:-1);
    ctx.beginPath();ctx.arc(0,0,r,phase,phase+Math.PI*1.4);
    ctx.strokeStyle=col;ctx.lineWidth=0.9;ctx.globalAlpha=0.22+0.08*Math.sin(now/700+i);ctx.stroke();ctx.globalAlpha=1;
  }
  // 3 arms at 120° spacing, slightly swept back
  const armAngles=[Math.PI*0.5,Math.PI*1.17,Math.PI*1.83];
  ctx.strokeStyle=col;ctx.lineWidth=1.8;
  for(const a of armAngles){
    const ax=Math.cos(a)*sz*1.35,ay=Math.sin(a)*sz*1.35;
    ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(ax,ay);ctx.stroke();
    ctx.beginPath();ctx.arc(ax,ay,4,0,Math.PI*2);ctx.fillStyle=col;ctx.fill();
    ctx.save();ctx.translate(ax,ay);ctx.rotate(spin);
    for(let b=0;b<3;b++){ctx.save();ctx.rotate(b*(Math.PI*2/3));ctx.beginPath();ctx.ellipse(0,0,sz*0.78,sz*0.16,0,0,Math.PI*2);ctx.strokeStyle=acc;ctx.lineWidth=1.2;ctx.globalAlpha=0.58;ctx.stroke();ctx.restore();}
    ctx.globalAlpha=1;ctx.restore();
  }
  // Triangular body
  const bs=sz*0.52;
  ctx.beginPath();
  ctx.moveTo(0,-bs*1.1);ctx.lineTo(bs*0.95,bs*0.55);ctx.lineTo(-bs*0.95,bs*0.55);ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.93)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.8;ctx.stroke();
  // Interior triangle detail
  ctx.beginPath();
  ctx.moveTo(0,-bs*0.55);ctx.lineTo(bs*0.47,bs*0.27);ctx.lineTo(-bs*0.47,bs*0.27);ctx.closePath();
  ctx.strokeStyle=acc;ctx.lineWidth=0.7;ctx.globalAlpha=0.3;ctx.stroke();ctx.globalAlpha=1;
  // Needle gun
  ctx.save();ctx.rotate(aim);ctx.fillStyle=acc;ctx.shadowBlur=8;ctx.shadowColor=acc;
  ctx.fillRect(bs*0.3,-1.5,bs*1.5,3);ctx.fillRect(bs*0.3+bs*1.5,-0.8,bs*0.5,1.6);
  ctx.beginPath();ctx.arc(bs*0.3+bs*2,0,1.5,0,Math.PI*2);ctx.fillStyle='#fff';ctx.fill();
  ctx.restore();
  // Core — pulsing
  const corePulse=0.7+0.3*Math.sin(now/300);
  ctx.beginPath();ctx.arc(0,0,4*corePulse,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowColor=acc;ctx.shadowBlur=14;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.8,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.18)';ctx.fill();}
  ctx.restore();
}

// ── Generic enemy drone (keep as before) ────────────────────────
function drawEnemyDrone(x,y,aim,sz,col,acc,spin,hp=1){
  ctx.save();ctx.translate(x,y);
  const arm=sz*1.38,rr=sz*0.72;
  ctx.shadowBlur=18;ctx.shadowColor=col;
  const ARMS=[Math.PI/4,-Math.PI/4,3*Math.PI/4,-3*Math.PI/4];
  ctx.strokeStyle=col;ctx.lineWidth=2.2;
  for(const a of ARMS){
    const ax=Math.cos(a)*arm,ay=Math.sin(a)*arm;
    ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(ax,ay);ctx.stroke();
    ctx.beginPath();ctx.arc(ax,ay,4,0,Math.PI*2);ctx.fillStyle=col;ctx.fill();
    ctx.save();ctx.translate(ax,ay);ctx.rotate(spin);
    for(let b=0;b<2;b++){ctx.save();ctx.rotate(b*Math.PI/2);ctx.beginPath();ctx.ellipse(0,0,rr,rr*0.22,0,0,Math.PI*2);ctx.strokeStyle=acc;ctx.lineWidth=1.5;ctx.globalAlpha=0.62;ctx.stroke();ctx.restore();}
    ctx.globalAlpha=1;ctx.restore();
  }
  const bs=sz*0.54;
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i-Math.PI/6;i===0?ctx.moveTo(Math.cos(a)*bs,Math.sin(a)*bs):ctx.lineTo(Math.cos(a)*bs,Math.sin(a)*bs);}ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.8;ctx.stroke();
  ctx.save();ctx.rotate(aim);ctx.fillStyle=acc;ctx.shadowBlur=8;ctx.shadowColor=acc;ctx.fillRect(bs*0.38,-2,bs*1.2,4);ctx.beginPath();ctx.arc(bs*0.38+bs*1.2,0,2,0,Math.PI*2);ctx.fillStyle='#fff';ctx.fill();ctx.restore();
  ctx.beginPath();ctx.arc(0,0,4.5,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowColor=acc;ctx.shadowBlur=16;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.75,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}

  // ── SNIPER  elongated dart — long barrel, narrow fuselage, rear fins ─────
function drawSniper(x,y,aim,sz,col,acc,spin,hp=1){
  ctx.save();ctx.translate(x,y);
  ctx.shadowBlur=16;ctx.shadowColor=col;
  // Elongated fuselage
  ctx.save();ctx.rotate(aim);
  ctx.fillStyle='rgba(4,9,22,0.95)';ctx.strokeStyle=col;ctx.lineWidth=1.8;
  ctx.beginPath();ctx.ellipse(sz*0.4,0,sz*1.4,sz*0.28,0,0,Math.PI*2);ctx.fill();ctx.stroke();
  // Long barrel
  ctx.fillStyle=acc;ctx.shadowBlur=10;ctx.shadowColor=acc;
  ctx.fillRect(sz*0.9,-2.2,sz*2.1,4.4);
  ctx.beginPath();ctx.arc(sz*0.9+sz*2.1,0,2.5,0,Math.PI*2);ctx.fillStyle='#fff';ctx.fill();
  ctx.restore();
  // Two rear stabiliser fins
  const finA=[Math.PI*0.82,Math.PI*1.18];
  for(const a of finA){
    ctx.beginPath();ctx.moveTo(Math.cos(aim)*(-sz*0.7),Math.sin(aim)*(-sz*0.7));
    ctx.lineTo(Math.cos(aim+a)*sz*0.9,Math.sin(aim+a)*sz*0.9);
    ctx.strokeStyle=col;ctx.lineWidth=1.5;ctx.globalAlpha=0.7;ctx.stroke();ctx.globalAlpha=1;
  }
  // Core
  ctx.beginPath();ctx.arc(0,0,3.5,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowColor=acc;ctx.shadowBlur=12;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.7,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.18)';ctx.fill();}
  ctx.restore();
}

// ── CARRIER  wide hexagonal hull, side hardpoints, command dish ──────────
function drawCarrier(x,y,aim,sz,col,acc,spin,hp=1){
  ctx.save();ctx.translate(x,y);
  ctx.shadowBlur=20;ctx.shadowColor=col;
  // Wide hexagonal body
  const bs=sz*0.68;
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i+Math.PI/6;i===0?ctx.moveTo(Math.cos(a)*bs,Math.sin(a)*bs):ctx.lineTo(Math.cos(a)*bs,Math.sin(a)*bs);}ctx.closePath();
  ctx.fillStyle='rgba(5,10,25,0.96)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=2.5;ctx.stroke();
  // Side hardpoints (drone dock positions)
  const hpA=[Math.PI*0.5,Math.PI*1.5];
  for(const a of hpA){
    const hx=Math.cos(a)*sz*0.9,hy=Math.sin(a)*sz*0.9;
    ctx.beginPath();ctx.arc(hx,hy,5,0,Math.PI*2);
    ctx.fillStyle='rgba(5,10,25,0.9)';ctx.fill();ctx.strokeStyle=acc;ctx.lineWidth=1.5;ctx.stroke();
    ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(hx,hy);ctx.strokeStyle=col;ctx.lineWidth=1.2;ctx.globalAlpha=0.4;ctx.stroke();ctx.globalAlpha=1;
  }
  // Command array (small dish on forward face)
  ctx.save();ctx.rotate(aim);
  ctx.strokeStyle=acc;ctx.lineWidth=1.2;ctx.globalAlpha=0.75;
  ctx.beginPath();ctx.arc(bs*0.7,0,sz*0.22,Math.PI*0.6,Math.PI*1.4);ctx.stroke();
  ctx.beginPath();ctx.moveTo(bs*0.7,0);ctx.lineTo(bs*0.7+sz*0.22,0);ctx.stroke();
  ctx.globalAlpha=1;ctx.restore();
  // Gun barrel
  ctx.save();ctx.rotate(aim);ctx.fillStyle=acc;ctx.shadowBlur=8;ctx.shadowColor=acc;
  ctx.fillRect(bs*0.52,-3,bs*0.85,6);
  ctx.beginPath();ctx.arc(bs*0.52+bs*0.85,0,2.5,0,Math.PI*2);ctx.fillStyle='#fff';ctx.fill();
  ctx.restore();
  // Core
  ctx.beginPath();ctx.arc(0,0,5,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowColor=acc;ctx.shadowBlur=16;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.8,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.15)';ctx.fill();}
  ctx.restore();
}

// ── SKIRMISHER  swept chevron delta wing, no rotors, engine glow ─────────
function drawSkirmisher(x,y,aim,sz,col,acc,spin,hp=1){
  ctx.save();ctx.translate(x,y);
  ctx.shadowBlur=18;ctx.shadowColor=col;
  ctx.save();ctx.rotate(aim);
  // Delta/chevron wing body
  ctx.beginPath();
  ctx.moveTo(sz*1.1,0);       // nose
  ctx.lineTo(-sz*0.6,sz*0.9); // port rear
  ctx.lineTo(-sz*0.3,0);      // center notch
  ctx.lineTo(-sz*0.6,-sz*0.9);// starboard rear
  ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.95)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.8;ctx.stroke();
  // Swept inner accent line
  ctx.strokeStyle=acc;ctx.lineWidth=0.9;ctx.globalAlpha=0.45;
  ctx.beginPath();ctx.moveTo(sz*0.7,0);ctx.lineTo(-sz*0.3,sz*0.55);ctx.moveTo(sz*0.7,0);ctx.lineTo(-sz*0.3,-sz*0.55);ctx.stroke();
  ctx.globalAlpha=1;
  // Engine glow at rear
  const engGlow=0.55+0.45*Math.sin(Date.now()/110);
  ctx.beginPath();ctx.arc(-sz*0.48,0,sz*0.32,0,Math.PI*2);
  ctx.fillStyle=`rgba(255,68,170,${0.18*engGlow})`;ctx.fill();
  ctx.strokeStyle=`rgba(255,68,170,${0.5*engGlow})`;ctx.lineWidth=1;ctx.stroke();
  // Barrel
  ctx.fillStyle=acc;ctx.shadowBlur=8;ctx.shadowColor=acc;
  ctx.fillRect(sz*0.5,-2,sz*0.9,4);
  ctx.beginPath();ctx.arc(sz*0.5+sz*0.9,0,2,0,Math.PI*2);ctx.fillStyle='#fff';ctx.fill();
  ctx.restore();
  // Core
  ctx.beginPath();ctx.arc(0,0,3.5,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowColor=acc;ctx.shadowBlur=14;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.75,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.18)';ctx.fill();}
  ctx.restore();
}

// Dispatcher for player craft
function drawPlayerCraft(x,y,aim,sz,col,acc,spin,hp){
  const id=CRAFTS[P.craftIdx].id;
  if(id==='phantom')drawPhantom(x,y,aim,sz,col,acc,spin,hp);
  else if(id==='viper')drawViper(x,y,aim,sz,col,acc,spin,hp);
  else if(id==='titan')drawTitan(x,y,aim,sz,col,acc,spin,hp);
  else if(id==='specter')drawSpecter(x,y,aim,sz,col,acc,spin,hp);
  else if(id==='sniper')drawSniper(x,y,aim,sz,col,acc,spin,hp);
  else if(id==='carrier')drawCarrier(x,y,aim,sz,col,acc,spin,hp);
  else if(id==='skirmisher')drawSkirmisher(x,y,aim,sz,col,acc,spin,hp);
}

// ─── PLAYER ──────────────────────────────────────────────────────
const P={
  x:WORLD_W/2,y:WORLD_H/2,vx:0,vy:0,aim:0,
  hp:100,maxHp:100,bat:100,maxBat:100,
  rotor:0,iframes:0,lastShot:0,alive:true,size:18,kills:0,
  weaponIdx:0,unlockedW:new Set([0]),loadout:[0],shieldMs:0,overchargeMs:0,invincMs:0,cloakMs:0,nukeKeys:new Set(),
  craftIdx:0,color:'#00ddff',
  spd:5.2,batDrain:2.4,drag:0.87,damageMult:1.0,detMult:1.0,
  stocks:{rapid:1000,spread:100,sawtooth:200,laser:20,burst:500,plasma:50,rico:30},mineStock:0,seekStock:0,noAmmoCount:0,
  sawtoothAngle:0,
};
function mkStocks(){
  const s={};
  WEAPONS.forEach(w=>{ if(w.stock!==null) s[w.id]=w.stock; });
  return s;
}
function resetPlayer(){
  const c=CRAFTS[P.craftIdx];
  Object.assign(P,{
    x:WORLD_W/2,y:WORLD_H/2,vx:0,vy:0,aim:0,
    hp:c.hp,maxHp:c.hp,bat:100,maxBat:100,
    rotor:0,iframes:0,lastShot:0,alive:true,kills:0,
    weaponIdx:c.startWeapon||0,unlockedW:new Set([0, c.startWeapon||0]),loadout:[c.startWeapon||0],
    shieldMs:0,overchargeMs:0,invincMs:0,cloakMs:0,nukeKeys:new Set(),
    spd:c.spd,batDrain:c.batDrain,drag:c.drag,
    damageMult:c.damageMult||1.0,detMult:c.detMult||1.0,
    stocks:mkStocks(),mineStock:0,seekStock:0,noAmmoCount:0,sawtoothAngle:0,
  });
  if(c.startEMP){empFlash=750;eBullets.length=0;}
  carrierDrones=[];if(c.id==='carrier') _initCarrierDrones();
  deadEyeMs=0;slipstreamMs=0;slipPrevVx=0;slipPrevVy=0;
  P.color=selectedColor;
}
function triggerEMP(){
  empFlash=750;eBullets.length=0;
  enemies.forEach(e=>{
    const sx=e.x-camX,sy=e.y-camY;
    if(sx<-e.size||sx>canvas.width+e.size||sy<-e.size||sy>canvas.height+e.size)return;
    e.stunMs=3700;spawnParts(e.x,e.y,'#cc44ff',_pCount(14),5,7,650);
  });
  spawnParts(P.x,P.y,'#dd55ff',_pCount(28),10,7,600);SFX.emp();
}
function _initCarrierDrones(){
  carrierDrones=[
    {angle:0,       hp:40,maxHp:40,respawnMs:-1,x:P.x,y:P.y,lastFired:0},
    {angle:Math.PI, hp:40,maxHp:40,respawnMs:-1,x:P.x,y:P.y,lastFired:0},
  ];
}
function tickCarrierDrones(dt,now){
  if(CRAFTS[P.craftIdx].id!=='carrier') return;
  const ORBIT_R=65, FIRE_RANGE=280, FIRE_MS=900, DRONE_DMG=14, DRONE_SPD=12, RESPAWN_MS=10000;
  for(let d=0;d<carrierDrones.length;d++){
    const dr=carrierDrones[d];
    if(dr.hp<=0){
      dr.respawnMs-=dt*1000;
      if(dr.respawnMs<=0){dr.hp=dr.maxHp;dr.respawnMs=-1;}
      continue;
    }
    dr.angle=P.rotor+d*Math.PI;
    dr.x=P.x+Math.cos(dr.angle)*ORBIT_R;
    dr.y=P.y+Math.sin(dr.angle)*ORBIT_R;
    if(now-dr.lastFired>FIRE_MS){
      let bestD=Infinity,bestI=-1;
      for(let i=0;i<enemies.length;i++){
        const ed=dist(dr.x,dr.y,enemies[i].x,enemies[i].y);
        if(ed<FIRE_RANGE&&ed<bestD){bestD=ed;bestI=i;}
      }
      if(bestI>=0){
        const ang=Math.atan2(enemies[bestI].y-dr.y,enemies[bestI].x-dr.x);
        pBullets.push({x:dr.x,y:dr.y,vx:Math.cos(ang)*DRONE_SPD,vy:Math.sin(ang)*DRONE_SPD,life:1700,dmg:DRONE_DMG,bSz:2.5,color:'#00aaff',stun:false});
        dr.lastFired=now;
      }
    }
  }
}
function drawCarrierDrones(){
  if(CRAFTS[P.craftIdx].id!=='carrier') return;
  const now=Date.now();
  for(const dr of carrierDrones){
    if(dr.hp<=0) continue;
    const sx=dr.x-camX, sy=dr.y-camY;
    ctx.save();ctx.translate(sx,sy);ctx.shadowBlur=12;ctx.shadowColor='#00aaff';
    // Small hexagonal drone body
    ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*8,Math.sin(a)*8):ctx.lineTo(Math.cos(a)*8,Math.sin(a)*8);}ctx.closePath();
    ctx.fillStyle='rgba(0,50,100,0.85)';ctx.fill();ctx.strokeStyle='#00aaff';ctx.lineWidth=1.3;ctx.stroke();
    ctx.beginPath();ctx.arc(0,0,2.5,0,Math.PI*2);ctx.fillStyle='#00aaff';ctx.shadowBlur=8;ctx.fill();
    // HP ring if damaged
    if(dr.hp<dr.maxHp){
      const frac=dr.hp/dr.maxHp;
      ctx.beginPath();ctx.arc(0,0,11,0,Math.PI*2*frac);ctx.strokeStyle=frac>0.5?'#44ff88':'#ff6644';ctx.lineWidth=2;ctx.stroke();
    }
    ctx.shadowBlur=0;ctx.restore();
    // Orbit ring (subtle)
    const px=P.x-camX, py=P.y-camY;
    ctx.beginPath();ctx.arc(px,py,65,0,Math.PI*2);ctx.strokeStyle='rgba(0,170,255,0.1)';ctx.lineWidth=1;ctx.stroke();
  }
}
function tickPlayer(dt,now){
  if(!P.alive)return;
  // ── Special mechanic ticks ──
  if(P.craftIdx===SNIPER_IDX()) deadEyeMs+=dt*1000;
  // Track previous velocity for SLIP STREAM reversal detection
  const _prevVx=slipPrevVx, _prevVy=slipPrevVy;
  // Touch boost: left stick pushed to >85% of max radius
  const touchBoost = IS_TOUCH && touchSticks.L.active &&
    Math.sqrt(touchSticks.L.dx**2+touchSticks.L.dy**2)/STICK_R > 0.88;
  const boost=(K['ShiftLeft']||K['ShiftRight'])||touchBoost;
  // VIPER: boost drains slower
  const bd=P.batDrain*(boost?(CRAFTS[P.craftIdx].id==='viper'?1.4:3.5):1)*(miniMe.active?MM_BATT_MULT:1)*(gameMode==='combattraining'?0.5:1);
  const spd=P.spd*(boost?2:1);
  if(!IS_TOUCH){
    // KB_ACCEL controls acceleration impulse per frame — lower = gentler ramp-up, shorter overshoot
    const KB_ACCEL=0.36;
    if(K['KeyW']||K['ArrowUp'])P.vy-=spd*KB_ACCEL;if(K['KeyS']||K['ArrowDown'])P.vy+=spd*KB_ACCEL;
    if(K['KeyA']||K['ArrowLeft'])P.vx-=spd*KB_ACCEL;if(K['KeyD']||K['ArrowRight'])P.vx+=spd*KB_ACCEL;
  }
  // Touch left stick — movement (scaled down for precision)
  if(IS_TOUCH && touchSticks.L.active){
    const mag=Math.sqrt(touchSticks.L.dx**2+touchSticks.L.dy**2);
    const frac=Math.min(1,mag/STICK_R);
    if(frac>STICK_DEAD){
      P.vx+=touchSticks.L.dx/STICK_R*spd*TOUCH_SPD_MULT;
      P.vy+=touchSticks.L.dy/STICK_R*spd*TOUCH_SPD_MULT;
    }
  }
  P.vx*=P.drag;P.vy*=P.drag;
  // Sub-step movement to prevent tunneling through thin walls at high speed.
  // Number of steps scales with velocity magnitude so thin 26px walls are never skipped.
  const moveSpd=Math.sqrt(P.vx*P.vx+P.vy*P.vy);
  const subSteps=Math.max(1,Math.ceil(moveSpd*dt*60/10));
  const stepDt=dt/subSteps;
  for(let s=0;s<subSteps;s++){
    P.x=clamp(P.x+P.vx*stepDt*60,P.size,WORLD_W-P.size);
    P.y=clamp(P.y+P.vy*stepDt*60,P.size,WORLD_H-P.size);
    pushOutObs(P,P.size);
  }
  for(let i=0;i<Math.min(P.loadout.length,10);i++){const k=i<9?`Digit${i+1}`:'Digit0';if(K[k]){P.weaponIdx=P.loadout[i];K[k]=false;}}
  if(K['KeyQ']){const ci=P.loadout.indexOf(P.weaponIdx);P.weaponIdx=P.loadout[(ci-1+P.loadout.length)%P.loadout.length];K['KeyQ']=false;}
  if(K['KeyE']){const ci=P.loadout.indexOf(P.weaponIdx);P.weaponIdx=P.loadout[(ci+1)%P.loadout.length];K['KeyE']=false;}
  // Touch right stick — aim; deflection beyond deadzone auto-fires
  if(WEAPONS[P.weaponIdx].id==='sawtooth'){
    // Sawtooth: gun rotates automatically — override aim, ignore mouse/stick
    P.sawtoothAngle+=dt*8.5; // ~1.35 full rotations per second
    P.aim=P.sawtoothAngle;
  } else if(IS_TOUCH && touchSticks.R.active){
    const mag=Math.sqrt(touchSticks.R.dx**2+touchSticks.R.dy**2);
    if(mag/STICK_R > STICK_DEAD) P.aim=Math.atan2(touchSticks.R.dy,touchSticks.R.dx);
  } else {
    // Mouse always aims; F also rotates clockwise while held
    P.aim = Math.atan2(mouse.y+camY-P.y, mouse.x+camX-P.x);
    if(K['KeyF']){ P.aim += 3.2 * dt; }
  }
  P.rotor+=dt*20*(boost?2.5:1);
  P.bat=Math.max(0,P.bat-bd*dt);if(P.bat<=0)P.hp-=9*dt;
  if(P.iframes>0)P.iframes-=dt*1000;if(P.shieldMs>0)P.shieldMs-=dt*1000;
  if(P.overchargeMs>0)P.overchargeMs-=dt*1000;if(P.invincMs>0)P.invincMs-=dt*1000;if(P.cloakMs>0)P.cloakMs-=dt*1000;
  if(weaponFlash.ms>0)weaponFlash.ms-=dt*1000;if(empFlash>0)empFlash-=dt*1000;
  // Firing: mouse/space OR right stick deflected past deadzone; sawtooth always fires
  const touchFiring = IS_TOUCH && touchSticks.R.active &&
    Math.sqrt(touchSticks.R.dx**2+touchSticks.R.dy**2)/STICK_R > STICK_DEAD;
  const w=WEAPONS[P.weaponIdx];
  const shooting=mouse.down||K['Space']||touchFiring;
  // ── Tractor Force: hold-to-activate directional cone ──────────
  if(w.id==='tractor'&&shooting&&(P.stocks['tractor']||0)>0&&P.alive){
    const TRACTOR_R=320, CONE_HALF=Math.PI*0.22, PULL_SPD_1=80, PULL_SPD_2=38;
    P.stocks['tractor']=Math.max(0,(P.stocks['tractor']||0)-dt*1000);
    const inRange=[];
    for(const e of enemies){
      const d=dist(P.x,P.y,e.x,e.y);
      if(d>=TRACTOR_R) continue;
      const angleToE=Math.atan2(e.y-P.y,e.x-P.x);
      let diff=angleToE-P.aim;
      while(diff>Math.PI) diff-=Math.PI*2;
      while(diff<-Math.PI) diff+=Math.PI*2;
      if(Math.abs(diff)>CONE_HALF) continue;
      inRange.push({e,d});
    }
    inRange.sort((a,b)=>a.d-b.d);
    const pullCount=Math.min(inRange.length,2);
    for(let ti=0;ti<pullCount;ti++){
      const {e,d}=inRange[ti];
      const pullSpd=pullCount===1?PULL_SPD_1:PULL_SPD_2;
      e.stunMoveMs=Math.max(e.stunMoveMs,180);
      e.stunFireMs=Math.max(e.stunFireMs,180);
      if(d>P.size+e.size+4){
        const dx=P.x-e.x, dy=P.y-e.y, dd=Math.sqrt(dx*dx+dy*dy)||1;
        e.vx=(dx/dd)*pullSpd*dt; e.vy=(dy/dd)*pullSpd*dt;
        e.x=clamp(e.x+e.vx,e.size,WORLD_W-e.size);
        e.y=clamp(e.y+e.vy,e.size,WORLD_H-e.size);
      }
      if(Math.random()<0.4) spawnParts(
        e.x+(P.x-e.x)*Math.random(), e.y+(P.y-e.y)*Math.random(),
        '#44aaff',_pCount(1),1.2,2.5,200
      );
    }
    if(P.craftIdx===SNIPER_IDX()) deadEyeMs=0;
    P.lastShot=now;
  }
  // Detect weapons that are out of usable ammo
  const isNoAmmo=(w.id==='mine'&&P.mineStock<=0)
               ||(w.id==='seekr'&&P.seekStock<=0)
               ||(w.id==='tractor'&&(P.stocks['tractor']||0)<=0)
               ||(w.id==='minime'&&(miniMe.active||miniMe.lost))
               ||(w.stock!==null&&w.id!=='mine'&&(P.stocks[w.id]||0)<=0);
  if(shooting&&now-P.lastShot>w.fireMs){
    if(isNoAmmo){
      P.noAmmoCount++;
      P.lastShot=now; // still consume the fire timer so counting isn't instant
      if(P.noAmmoCount>=3){
        P.noAmmoCount=0;
        // Step down to next lower unlocked weapon index
        const ci=P.loadout.indexOf(P.weaponIdx);
        if(ci>0) P.weaponIdx=P.loadout[ci-1];
      }
    } else {
      P.noAmmoCount=0;
      fireWeapon();
      deadEyeMs=0;
    }
    P.lastShot=now;
  }
  // ── SLIP STREAM — direction reversal under fire ──
  const skirmIdx=SKIRMISHER_IDX();
  if(P.craftIdx===skirmIdx&&P.iframes===0){
    const prevSpd=Math.sqrt(_prevVx*_prevVx+_prevVy*_prevVy);
    const curSpd=Math.sqrt(P.vx*P.vx+P.vy*P.vy);
    if(prevSpd>0.5&&curSpd>0.5){
      const dot=(_prevVx*P.vx+_prevVy*P.vy)/(prevSpd*curSpd);
      const angleDiff=Math.acos(Math.max(-1,Math.min(1,dot)))*180/Math.PI;
      if(angleDiff>110){
        const nearFire=eBullets.some(b=>dist(b.x,b.y,P.x,P.y)<180);
        if(nearFire){P.iframes=400;slipstreamMs=400;SFX.shield();}
      }
    }
  }
  slipPrevVx=P.vx; slipPrevVy=P.vy;
  if(slipstreamMs>0) slipstreamMs-=dt*1000;
  if(P.hp<=0)P.alive=false;
  if(gameMode==='timetrial'&&(ttLevel===1||ttLevel===3)&&!ttFinished&&P.x>=(ttLevel===3?DBD_FINISH_X:TT_FINISH_X)){
    ttFinished=true;ttElapsed=performance.now()-ttStartTime;
    computeTTFinalScore();SFX.confirm();saveHighScore(`timetrial_${ttLevel}`,ttFinalScore,ttElapsed);gameState='timeTrialResult';
  }
  const tx=P.x-canvas.width/2,ty=P.y-canvas.height/2;camX+=(tx-camX)*0.13;camY+=(ty-camY)*0.13;
  camX=clamp(camX,0,Math.max(0,WORLD_W-canvas.width));
  if(gameMode==='combattraining'){camX=0;camY=0;}
  else camY=(gameMode==='timetrial'&&(ttLevel===1||ttLevel===3))?0:clamp(camY,0,Math.max(0,WORLD_H-canvas.height));
}
function drawPlayer(){
  if(!P.alive)return;
  if(portalActive) return; // craft hidden during portal — ghost shown at origin portal instead
  if(P.iframes>0&&Math.floor(P.iframes/75)%2===0)return;
  const sx=P.x-camX,sy=P.y-camY,now=Date.now();
  // Cloak: render at very low opacity
  const baseAlpha=P.cloakMs>0?0.07:1.0;
  if(baseAlpha<1) ctx.globalAlpha=baseAlpha;
  if(P.overchargeMs>0){const a=0.5+0.5*Math.sin(now/120);ctx.beginPath();ctx.arc(sx,sy,P.size*1.95,0,Math.PI*2);ctx.fillStyle=`rgba(255,150,0,${0.08*a})`;ctx.fill();ctx.strokeStyle=`rgba(255,180,50,${0.55*a})`;ctx.lineWidth=1.6;ctx.shadowBlur=18;ctx.shadowColor='#ff9900';ctx.stroke();ctx.shadowBlur=0;}
  // Invincibility: white spinning rings
  if(P.invincMs>0){
    const pulse=0.6+0.4*Math.sin(now/90);
    ctx.save();ctx.translate(sx,sy);
    ctx.rotate(now/220);ctx.strokeStyle=`rgba(255,255,255,${0.75*pulse})`;ctx.lineWidth=2.2;ctx.shadowBlur=18;ctx.shadowColor='#ffffff';
    ctx.beginPath();ctx.arc(0,0,P.size*2.1,0,Math.PI*2);ctx.stroke();
    ctx.rotate(-now/140);ctx.strokeStyle=`rgba(200,240,255,${0.45*pulse})`;ctx.lineWidth=1.2;
    ctx.beginPath();ctx.arc(0,0,P.size*2.6,0,Math.PI*2);ctx.stroke();
    ctx.shadowBlur=0;ctx.restore();
  }
  drawPlayerCraft(sx,sy,P.aim,P.size,P.color,lighten(P.color,90),P.rotor,P.hp/P.maxHp);
  if(baseAlpha<1) ctx.globalAlpha=1.0;
  if(P.shieldMs>0){
    const r=P.size*2.15,fa=Math.min(1,P.shieldMs/1200);
    ctx.save();ctx.translate(sx,sy);ctx.rotate(now/580);
    ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*r,Math.sin(a)*r):ctx.lineTo(Math.cos(a)*r,Math.sin(a)*r);}ctx.closePath();
    ctx.strokeStyle=`rgba(68,170,255,${0.88*fa})`;ctx.lineWidth=2.4;ctx.shadowBlur=20;ctx.shadowColor='#44aaff';ctx.stroke();ctx.shadowBlur=0;ctx.restore();
    ctx.save();ctx.translate(sx,sy);ctx.rotate(-now/900);ctx.beginPath();ctx.arc(0,0,r*1.22,0,Math.PI*2);ctx.strokeStyle=`rgba(68,170,255,${0.22*fa})`;ctx.lineWidth=1;ctx.stroke();ctx.restore();
  }
}

// ─── ENEMIES ─────────────────────────────────────────────────────
const ETYPES={
  scout:  {size:13,hp:55, spd:3.0,fireMs:1100,dmg:13, color:'#ff2244',accent:'#ff9aaa',score:100, det:280,atk:190,patR:150,drag:0.87},
  guard:  {size:17,hp:130,spd:2.0,fireMs:820, dmg:24, color:'#ff8800',accent:'#ffcc44',score:200, det:310,atk:210,patR:90, drag:0.89},
  turret: {size:22,hp:220,spd:0,  fireMs:520, dmg:26, color:'#9900ff',accent:'#cc55ff',score:350, det:380,atk:350,patR:0,  drag:1  },
  boss:   {size:35,hp:800,spd:1.7,fireMs:300, dmg:19, color:'#ff0055',accent:'#ff66bb',score:2000,det:500,atk:350,patR:220,drag:0.90},
  // ── New enemy types (waves 3-5 only) ─────────────────────────
  dart:   {size:9, hp:35, spd:5.8,fireMs:1400,dmg:9,  color:'#00ffcc',accent:'#aaffee',score:150, det:260,atk:170,patR:200,drag:0.80}, // tiny fast zigzagger — easy HP, hard to track
  wraith: {size:14,hp:95, spd:2.4,fireMs:900, dmg:19, color:'#8844ff',accent:'#cc99ff',score:280, det:340,atk:240,patR:120,drag:0.88}, // teleports every 4s, fires burst on arrival
  brute:  {size:28,hp:420,spd:1.1,fireMs:1200,dmg:36, color:'#ff6600',accent:'#ffaa44',score:450, det:300,atk:260,patR:60, drag:0.94}, // large slow tank, fat slow bullets
  phantom:{size:12,hp:80, spd:3.5,fireMs:1600,dmg:15, color:'#44ffaa',accent:'#aaffcc',score:320, det:400,atk:340,patR:180,drag:0.86}, // retreats when wounded, heals slowly, sniper range
  // ── Phase 3 hostile types ──────────────────────────────────────
  ravager:    {size:18,hp:60, spd:2.2,fireMs:2000,dmg:10,color:'#ff2200',accent:'#ff8866',score:180, det:220,atk:220,patR:120,drag:0.82},
  splitter:   {size:20,hp:110,spd:2.0,fireMs:1000,dmg:16,color:'#ffcc00',accent:'#fff088',score:260, det:290,atk:200,patR:130,drag:0.88},
  shard:      {size:10,hp:30, spd:3.5,fireMs:1300,dmg:8, color:'#ffcc00',accent:'#fff088',score:80,  det:220,atk:160,patR:80, drag:0.85},
  cloaker:    {size:13,hp:70, spd:3.2,fireMs:1100,dmg:17,color:'#88ffee',accent:'#ccffee',score:240, det:320,atk:240,patR:140,drag:0.86},
  demolisher: {size:24,hp:280,spd:1.3,fireMs:2200,dmg:0, color:'#cc44ff',accent:'#ee99ff',score:380, det:340,atk:280,patR:80, drag:0.93},
  hunter:     {size:9, hp:40, spd:6.0,fireMs:1600,dmg:11,color:'#ff44cc',accent:'#ffaaee',score:160, det:280,atk:180,patR:160,drag:0.80},
  // ── Phase 3 bosses ─────────────────────────────────────────────
  dreadnought:{size:42,hp:1400,spd:1.6,fireMs:280,dmg:18,color:'#ff6600',accent:'#ffcc44',score:3500,det:500,atk:380,patR:240,drag:0.91},
  harbinger:  {size:48,hp:1800,spd:1.2,fireMs:380,dmg:20,color:'#9900cc',accent:'#dd66ff',score:4500,det:500,atk:400,patR:280,drag:0.93},
};
function mkEnemy(type,x,y){
  const t=ETYPES[type];
  const e={type,x,y,vx:0,vy:0,aim:0,hp:t.hp,maxHp:t.hp,spd:t.spd,fireMs:t.fireMs,dmg:t.dmg,color:t.color,accent:t.accent,score:t.score,det:t.det*P.detMult,atk:t.atk,patR:t.patR,drag:t.drag,patA:rng(0,Math.PI*2),patCx:x,patCy:y,state:'patrol',lastFired:0,rotor:rng(0,Math.PI*2),size:t.size,stunMs:0,stunMoveMs:0,stunFireMs:0,infected:false};
  // Extra state for new types
  if(type==='dart')   { e.zigTimer=0; e.zigAngle=rng(0,Math.PI*2); }
  if(type==='wraith') { e.blinkTimer=rng(2000,4000); e.blinking=false; e.blinkFlash=0; }
  if(type==='phantom'){ e.retreating=false; e.healTimer=0; }
  if(type==='ravager')    { e.chargeMs=0; e.chargeVx=0; e.chargeVy=0; }
  if(type==='cloaker')    { e.visibleMs=0; }
  if(type==='dreadnought'){ e.phase=1; e.phaseSwitched=false; e.shotCount=0; e.spiralAngle=0; }
  if(type==='harbinger')  { e.podThresholds=[0.66,0.33]; e.activePods=0; e.rageMs=0; e.spiralAngle=0; }
  e.fromHarbinger=false;
  return e;
}
// Returns a world position for an enemy of given type that:
//   - keeps outside player's attack range (with margin) and 6× player size
//   - keeps away from other already-placed enemies
//   - avoids world centre spawn zone
//   - avoids obstacles
function safeSpawnPos(type, placed){
  const t=ETYPES[type];
  const playerClear = Math.max(P.size*6, t.atk*1.4); // never start within firing range
  const enemySep    = 220;                            // min gap between any two enemies
  const MARGIN      = 60;
  const MAX_TRIES   = 80;
  let best=null, bestScore=-Infinity;
  for(let attempt=0; attempt<MAX_TRIES; attempt++){
    const x=rng(MARGIN, WORLD_W-MARGIN);
    const y=rng(MARGIN, WORLD_H-MARGIN);
    // Distance from player
    const dp=dist(x,y,P.x,P.y);
    if(dp < playerClear) continue;
    // Obstacle clearance
    if(circleVsObs(x,y,t.size+12)) continue;
    // Distance from already-placed enemies
    let minEnemyDist=Infinity;
    for(const p of placed) minEnemyDist=Math.min(minEnemyDist,dist(x,y,p.x,p.y));
    if(minEnemyDist < enemySep) continue;
    // Score = favour positions far from both player AND other enemies
    const score=dp + (minEnemyDist===Infinity ? 0 : minEnemyDist*0.4);
    if(score>bestScore){ bestScore=score; best={x,y}; }
  }
  // Fallback: if we somehow couldn't satisfy all constraints, pick a point far from player
  if(!best){
    const angle=rng(0,Math.PI*2);
    const r=playerClear+rng(0,300);
    best={x:clamp(P.x+Math.cos(angle)*r,MARGIN,WORLD_W-MARGIN),
          y:clamp(P.y+Math.sin(angle)*r,MARGIN,WORLD_H-MARGIN)};
  }
  return best;
}
function spawnWaveEnemies(n){
  enemies.length=0;
  const placed=[];
  const add=(type,count)=>{
    for(let i=0;i<count;i++){
      const p=safeSpawnPos(type,placed);
      const e=mkEnemy(type,p.x,p.y);
      enemies.push(e);
      placed.push(p);
    }
  };
  if(n===1)add('scout',5);
  else if(n===2){add('scout',5);add('guard',2);}
  else if(n===3){add('scout',3);add('guard',2);add('turret',2);add('dart',2);add('brute',1);add('ravager',1);add('cloaker',1);}
  else if(n===4){add('scout',3);add('guard',2);add('turret',2);add('dart',2);add('wraith',2);add('brute',1);add('phantom',1);add('splitter',1);add('hunter',1);add('demolisher',1);}
  else{
    add('scout',2);add('guard',2);add('turret',2);add('dart',2);add('wraith',2);add('brute',2);add('phantom',1);add('ravager',1);add('splitter',1);add('cloaker',1);add('hunter',1);add('demolisher',1);
    const _bPool=['boss','dreadnought','harbinger'];
    const _bType=_bPool[Math.floor(Math.random()*_bPool.length)];
    add(_bType,1);
    if(_bType==='harbinger') harbingerRef=enemies[enemies.length-1];
    bossWarning=3500;SFX.boss();
  }
}
function tickEnemies(dt,now){
  for(const e of enemies){
    const dx=P.x-e.x,dy=P.y-e.y,d=Math.sqrt(dx*dx+dy*dy)||1;
    e.aim=Math.atan2(dy,dx);e.rotor+=dt*14;
    // Tick down all stun timers
    if(e.stunMs>0)    e.stunMs    -=dt*1000;
    if(e.stunMoveMs>0)e.stunMoveMs-=dt*1000;
    if(e.stunFireMs>0)e.stunFireMs -=dt*1000;
    const moveStunned = e.stunMs>0 || e.stunMoveMs>0;
    const fireStunned = e.stunMs>0 || e.stunFireMs>0;
    // Cloak: enemies can't detect or fire at player
    if(P.cloakMs>0){e.state='patrol';e.vx*=0.92;e.vy*=0.92;continue;}
    // EMP stun (full freeze + purple sparks)
    if(e.stunMs>0){e.vx*=0.95;e.vy*=0.95;e.state='patrol';if(Math.random()<0.045)spawnParts(e.x,e.y,'#cc44ff',_pCount(2),2,3,300);}
    // Stun-gun move freeze (sparks without full state override)
    else if(e.stunMoveMs>0){e.vx*=0.90;e.vy*=0.90;e.state='patrol';if(Math.random()<0.06)spawnParts(e.x,e.y,'#aaff44',_pCount(2),2.5,3,200);}
    // Infected enemies: retarget to nearest non-infected foe
    if(e.infected){
      let nearFoe=null,nearFoeD=Infinity;
      for(const f of enemies){if(f===e||f.infected) continue; const fd=dist(e.x,e.y,f.x,f.y); if(fd<nearFoeD){nearFoeD=fd;nearFoe=f;}}
      if(nearFoe){
        const fdx=nearFoe.x-e.x,fdy=nearFoe.y-e.y,fdd=Math.sqrt(fdx*fdx+fdy*fdy)||1;
        e.aim=Math.atan2(fdy,fdx);
        if(nearFoeD<e.atk) e.state='attack';
        else e.state='chase';
        if(!moveStunned){e.vx+=(fdx/fdd)*e.spd;e.vy+=(fdy/fdd)*e.spd;e.vx*=e.drag;e.vy*=e.drag;e.x=clamp(e.x+e.vx*dt*60,e.size,WORLD_W-e.size);e.y=clamp(e.y+e.vy*dt*60,e.size,WORLD_H-e.size);pushOutObs(e,e.size);}
      } else {
        // No foes left — patrol
        e.state='patrol';
      }
      continue; // skip normal AI below
    }
    else if(!P.alive)e.state='patrol';else if(d<e.atk)e.state='attack';else if(d<e.det)e.state='chase';else e.state='patrol';

    // ── DART: zigzag perpendicular thrust when chasing/attacking ──
    if(e.type==='dart'&&!moveStunned){
      e.zigTimer-=dt*1000;
      if(e.zigTimer<=0){e.zigAngle=rng(0,Math.PI*2);e.zigTimer=rng(280,550);}
      if(e.state==='chase'||e.state==='attack'){
        const zigStr=e.state==='attack'?1.8:1.2;
        e.vx+=Math.cos(e.zigAngle)*e.spd*zigStr;
        e.vy+=Math.sin(e.zigAngle)*e.spd*zigStr;
      }
    }

    // ── WRAITH: periodic teleport blink, fires spread burst on arrival ──
    if(e.type==='wraith'&&!moveStunned){
      e.blinkTimer-=dt*1000;
      if(e.blinkFlash>0) e.blinkFlash-=dt*1000;
      if(e.blinkTimer<=0&&(e.state==='chase'||e.state==='attack')){
        // Teleport to a random clear position near the player
        let nx=P.x+rng(-220,220),ny=P.y+rng(-220,220),tries=0;
        while(tries<20&&(circleVsObs(nx,ny,e.size+8)||dist(nx,ny,P.x,P.y)<60)){nx=P.x+rng(-240,240);ny=P.y+rng(-240,240);tries++;}
        nx=clamp(nx,e.size,WORLD_W-e.size);ny=clamp(ny,e.size,WORLD_H-e.size);
        e.x=nx;e.y=ny;e.vx=0;e.vy=0;
        e.blinkFlash=400;
        e.blinkTimer=rng(3500,5500);
        // Burst fire on arrival
        if(!fireStunned&&P.alive){
          for(let s=-1;s<=1;s++) fireEBullet(e.x,e.y,e.aim+s*0.28,8.5,e.dmg*0.8);
          e.lastFired=now+800; // brief refractory after blink
        }
        spawnParts(e.x,e.y,e.color,_pCount(10),3,4.5,300);
      }
    }

    // ── PHANTOM STALKER: retreat when wounded, heal over time, sniper range ──
    if(e.type==='phantom'){
      const hpPct=e.hp/e.maxHp;
      if(!e.retreating&&hpPct<0.35){e.retreating=true;}
      if(e.retreating&&hpPct>0.62){e.retreating=false;}
      if(e.retreating){
        // Passive heal while retreating
        e.healTimer-=dt*1000;
        if(e.healTimer<=0){e.hp=Math.min(e.maxHp,e.hp+e.maxHp*0.04);e.healTimer=800;}
        // Move directly away from player
        if(!moveStunned){
          e.vx+=(-dx/d)*e.spd*1.1;e.vy+=(-dy/d)*e.spd*1.1;
          e.vx*=e.drag;e.vy*=e.drag;
          e.x=clamp(e.x+e.vx*dt*60,e.size,WORLD_W-e.size);
          e.y=clamp(e.y+e.vy*dt*60,e.size,WORLD_H-e.size);
          pushOutObs(e,e.size);
        }
        e.state='patrol'; // suppress standard movement below
      }
    }
    // ── CLOAKER: decrement visibility timer ──
    if(e.type==='cloaker'&&e.visibleMs>0) e.visibleMs-=dt*1000;
    // ── RAVAGER: charge attack ──
    if(e.type==='ravager'&&!moveStunned){
      if(e.chargeMs>0){
        e.chargeMs-=dt*1000;
        e.x=clamp(e.x+e.chargeVx*dt*60,e.size,WORLD_W-e.size);
        e.y=clamp(e.y+e.chargeVy*dt*60,e.size,WORLD_H-e.size);
        pushOutObs(e,e.size);
        if(P.alive&&P.iframes===0&&dist(e.x,e.y,P.x,P.y)<e.size+P.size){
          if(P.shieldMs>0){P.shieldMs=0;spawnParts(P.x,P.y,'#44aaff',_pCount(14),4,5,400);SFX.shbreak();P.iframes=400;}
          else{P.hp-=38*P.damageMult;P.iframes=600;if(settings.screenShake)shake=Math.max(shake,18);SFX.hit();if(P.hp<=0)P.alive=false;Music.onHit();}
          e.chargeMs=-1500;
        }
        continue;
      }
      if(e.chargeMs<=0&&e.state==='attack'&&dist(e.x,e.y,P.x,P.y)<220){
        const dd=dist(e.x,e.y,P.x,P.y)||1;
        e.chargeMs=600;
        e.chargeVx=(P.x-e.x)/dd*11;
        e.chargeVy=(P.y-e.y)/dd*11;
        continue;
      }
    }
    // ── HUNTER: retarget to carrier drones when available ──
    if(e.type==='hunter'&&!moveStunned){
      let htx=P.x,hty=P.y;
      if(typeof carrierDrones!=='undefined'&&CRAFTS[P.craftIdx].id==='carrier'){
        let bestD=Infinity,bestDr=null;
        for(const dr of carrierDrones){if(dr.hp>0){const dd=dist(e.x,e.y,dr.x,dr.y);if(dd<bestD){bestD=dd;bestDr=dr;}}}
        if(bestDr){htx=bestDr.x;hty=bestDr.y;}
      }
      e.aim=Math.atan2(hty-e.y,htx-e.x);
      const htd=dist(e.x,e.y,htx,hty)||1;
      if(e.state==='chase'||e.state==='attack'){e.vx+=(htx-e.x)/htd*e.spd;e.vy+=(hty-e.y)/htd*e.spd;}
      e.vx*=e.drag;e.vy*=e.drag;
      e.x=clamp(e.x+e.vx*dt*60,e.size,WORLD_W-e.size);
      e.y=clamp(e.y+e.vy*dt*60,e.size,WORLD_H-e.size);
      pushOutObs(e,e.size);
    }
    // ── DREADNOUGHT: phase 1→2 transition at 50% HP ──
    if(e.type==='dreadnought'&&e.phase===1&&!e.phaseSwitched&&e.hp<e.maxHp*0.5){
      e.phase=2;e.phaseSwitched=true;
      e.spd=2.8;e.fireMs=200;
      spawnParts(e.x,e.y,e.color,_pCount(30),7,9,900);
      spawnParts(e.x,e.y,'#ffffff',_pCount(20),5,6,700);
      if(settings.screenShake)shake=Math.max(shake,28);SFX.boss();
    }
    // ── HARBINGER: figure-8 movement + pod spawning + rage ──
    if(e.type==='harbinger'){
      // Pod threshold check
      if(e.podThresholds.length>0&&e.hp<=e.maxHp*e.podThresholds[0]){
        e.podThresholds.shift();
        for(const yOff of[-60,60]){
          const pod=mkEnemy('turret',e.x,e.y+yOff);
          pod.fromHarbinger=true;
          pod.lastFired=now;
          enemies.push(pod);
          e.activePods++;
        }
        spawnParts(e.x,e.y,e.accent,_pCount(16),4,6,500);
      }
      // Rage countdown
      if(e.rageMs>0){
        e.rageMs-=dt*1000;
        if(e.rageMs<=0) e.fireMs=380;
      }
      // Figure-8 movement
      if(!moveStunned){
        const t=Date.now()/1000;
        const ftx=e.patCx+Math.cos(t*0.4)*e.patR;
        const fty=e.patCy+Math.sin(t*0.8)*e.patR*0.5;
        const ftd=dist(e.x,e.y,ftx,fty)||1;
        e.vx+=(ftx-e.x)/ftd*e.spd;e.vy+=(fty-e.y)/ftd*e.spd;
        e.vx*=e.drag;e.vy*=e.drag;
        e.x=clamp(e.x+e.vx*dt*60,e.size,WORLD_W-e.size);
        e.y=clamp(e.y+e.vy*dt*60,e.size,WORLD_H-e.size);
        pushOutObs(e,e.size);
      }
    }
    if(e.type!=='turret'&&e.type!=='hunter'&&e.type!=='harbinger'&&!moveStunned){
      if(e.state==='chase'){e.vx+=(dx/d)*e.spd;e.vy+=(dy/d)*e.spd;}
      else if(e.state==='attack'){const ideal=e.atk*0.6,f=d<ideal?-0.7:d>ideal*1.35?0.5:0;e.vx+=(dx/d)*e.spd*f;e.vy+=(dy/d)*e.spd*f;e.vx+=(-dy/d)*e.spd*0.36;e.vy+=(dx/d)*e.spd*0.36;}
      else{e.patA+=dt*0.65;const tx=e.patCx+Math.cos(e.patA)*e.patR,ty=e.patCy+Math.sin(e.patA)*e.patR,pd=dist(e.x,e.y,tx,ty)||1;e.vx+=((tx-e.x)/pd)*e.spd*0.55;e.vy+=((ty-e.y)/pd)*e.spd*0.55;}
      e.vx*=e.drag;e.vy*=e.drag;e.x=clamp(e.x+e.vx*dt*60,e.size,WORLD_W-e.size);e.y=clamp(e.y+e.vy*dt*60,e.size,WORLD_H-e.size);
      pushOutObs(e,e.size);
    }
    const cmdField=CRAFTS[P.craftIdx].id==='carrier'&&dist(e.x,e.y,P.x,P.y)<320;
    const effectiveFireMs=cmdField?e.fireMs*1.25:e.fireMs;
    if(e.state==='attack'&&!fireStunned&&P.alive&&now-e.lastFired>effectiveFireMs){
      // Infected: target nearest non-infected enemy instead of player
      if(e.infected){
        let nearestFoe=null,nearestD=Infinity;
        for(const f of enemies){
          if(f===e||f.infected) continue;
          const fd=dist(e.x,e.y,f.x,f.y);
          if(fd<nearestD){nearestD=fd;nearestFoe=f;}
        }
        if(nearestFoe){
          const aimAt=Math.atan2(nearestFoe.y-e.y,nearestFoe.x-e.x);
          eBullets.push({x:e.x,y:e.y,vx:Math.cos(aimAt)*7.5,vy:Math.sin(aimAt)*7.5,life:2400,dmg:e.dmg,fromInfected:true});
          e.lastFired=now;
        }
        continue;
      }
      const sp=(Math.random()-0.5)*0.14;
      if(e.type==='boss'){for(let s=-2;s<=2;s++)fireEBullet(e.x,e.y,e.aim+s*0.24,7.5,e.dmg*0.7);spawnParts(e.x,e.y,e.color,_pCount(5),3,4,200);}
      else if(e.type==='turret'){fireEBullet(e.x,e.y,e.aim+sp,8,e.dmg);setTimeout(()=>fireEBullet(e.x,e.y,e.aim+sp*1.5,8,e.dmg*0.8),110);}
      else if(e.type==='brute'){
        // Fat slow plasma-style bolt — big bSz, slow, high dmg
        const angle=e.aim+sp*0.5;
        eBullets.push({x:e.x,y:e.y,vx:Math.cos(angle)*4.5,vy:Math.sin(angle)*4.5,life:3200,dmg:e.dmg,bSz:9,isBrute:true});
        spawnParts(e.x,e.y,e.color,_pCount(6),2.5,3.5,240);
      }
      else if(e.type==='phantom'){
        // Sniper shot — fast, accurate, no spread (only fires at long detection range)
        if(d<e.det) fireEBullet(e.x,e.y,e.aim,11,e.dmg);
      }
      else if(e.type==='dreadnought'){
        e.shotCount++;
        if(e.phase===1){
          for(const off of[-0.28,0,0.28]) fireEBullet(e.x,e.y,e.aim+off,7.5,e.dmg);
          if(e.shotCount%4===0) fireEBullet(e.x,e.y,e.aim,4,e.dmg*1.5);
        } else {
          for(let s=0;s<8;s++) fireEBullet(e.x,e.y,e.spiralAngle+s*(Math.PI/4),6.5,e.dmg*0.8);
          e.spiralAngle+=0.18;
        }
        spawnParts(e.x,e.y,e.color,_pCount(6),3,4.5,220);
      }
      else if(e.type==='harbinger'){
        e.spiralAngle+=0.22;
        fireEBullet(e.x,e.y,e.spiralAngle,5.5,e.dmg);
        spawnParts(e.x,e.y,e.color,_pCount(4),2.5,3.5,180);
      }
      else if(e.type==='demolisher'){
        const angle=e.aim+(Math.random()-0.5)*0.1;
        eBullets.push({x:e.x,y:e.y,vx:Math.cos(angle)*3.5,vy:Math.sin(angle)*3.5,life:2000,dmg:0,bSz:10,isBomb:true,ox:e.x,oy:e.y});
        spawnParts(e.x,e.y,e.color,_pCount(6),2,3.5,280);
      }
      else fireEBullet(e.x,e.y,e.aim+sp,7.5,e.dmg);
      e.lastFired=now;
      if(e.type==='cloaker') e.visibleMs=420;
    }
  }
  // Anchor constraint — applied after all movement so it catches every enemy type
  for(const e of enemies){
    if(e.anchorX!==undefined){
      const adx=e.x-e.anchorX,ady=e.y-e.anchorY;
      const ad=Math.sqrt(adx*adx+ady*ady)||1;
      if(ad>GRAPPLE_LEASH){
        e.x=e.anchorX+(adx/ad)*GRAPPLE_LEASH;
        e.y=e.anchorY+(ady/ad)*GRAPPLE_LEASH;
        e.vx*=0.25;e.vy*=0.25;
      }
    }
  }
  // Self-destruct: if only one enemy remains and it's infected, slowly drain its HP
  if(enemies.length===1&&enemies[0].infected){
    const e=enemies[0];
    if(!e.selfDestructing){
      e.selfDestructing=true;
      weaponFlash={name:'SELF DESTRUCT INITIATED',ms:2500};
    }
    e.hp-=e.maxHp*0.012*dt*60; // drains to zero over ~6 seconds
    spawnParts(e.x+rng(-e.size,e.size),e.y+rng(-e.size,e.size),'#00ff88',_pCount(1),1.5,3,400);
    if(Math.random()<0.15) spawnParts(e.x,e.y,'#ffffff',_pCount(2),2,3,300);
    if(e.hp<=0){SFX.boom();killEnemy(0);}
  }
}
function tickMiniMe(dt,now){
  if(!miniMe.active)return;
  if(miniMe.iframes>0)miniMe.iframes-=dt*1000;
  miniMe.rotor+=dt*22;
  // Passive HP decay: 0.5% of max per second
  miniMe.hp-=MM_HP*0.005*dt;
  if(miniMe.hp<=0){
    miniMe.active=false;miniMe.lost=true;
    spawnParts(miniMe.x,miniMe.y,MM_COL,_pCount(20),4.5,6,700);
    spawnParts(miniMe.x,miniMe.y,'#ffffff',_pCount(8),3,4,400);
    if(settings.screenShake)shake=10;SFX.mmdead();return;
  }
  // Find nearest enemy within detection range
  let target=null,bestDist=MM_DET;
  for(const e of enemies){
    const d=dist(miniMe.x,miniMe.y,e.x,e.y);
    if(d<bestDist){bestDist=d;target=e;}
  }
  const dp=dist(miniMe.x,miniMe.y,P.x,P.y);
  const DRAG=0.88;
  if(target){
    // Aim and move toward intercept position (flank between player and target)
    const tx=target.x,ty=target.y;
    miniMe.aim=Math.atan2(ty-miniMe.y,tx-miniMe.x);
    // Preferred spot: ~80px from target, on the player's side
    const ang=Math.atan2(P.y-ty,P.x-tx);
    const gx=tx+Math.cos(ang)*80,gy=ty+Math.sin(ang)*80;
    const gd=dist(miniMe.x,miniMe.y,gx,gy)||1;
    miniMe.vx+=(gx-miniMe.x)/gd*3.8;
    miniMe.vy+=(gy-miniMe.y)/gd*3.8;
    // Fire at target when roughly aligned
    if(now-miniMe.lastFired>MM_FIRE_MS&&bestDist<MM_DET){
      const spread=(Math.random()-0.5)*0.12;
      firePBullet(miniMe.x,miniMe.y,miniMe.aim+spread,MM_DMG,MM_SPD_BLT,2.8,MM_COL);
      miniMe.lastFired=now;
      miniMe.vx-=Math.cos(miniMe.aim)*0.8;miniMe.vy-=Math.sin(miniMe.aim)*0.8;
    }
  } else {
    // Orbit player at MM_ORBIT_R
    miniMe.orbitAngle+=dt*0.9;
    const gx=P.x+Math.cos(miniMe.orbitAngle)*MM_ORBIT_R;
    const gy=P.y+Math.sin(miniMe.orbitAngle)*MM_ORBIT_R;
    const gd=dist(miniMe.x,miniMe.y,gx,gy)||1;
    miniMe.vx+=(gx-miniMe.x)/gd*3.2;
    miniMe.vy+=(gy-miniMe.y)/gd*3.2;
    miniMe.aim=Math.atan2(P.y-miniMe.y,P.x-miniMe.x);
  }
  // Smooth leash — proportional pull that builds gradually beyond orbit radius
  if(dp>MM_ORBIT_R){
    const pa=Math.atan2(P.y-miniMe.y,P.x-miniMe.x);
    const excess=(dp-MM_ORBIT_R)/MM_MAX_RANGE; // 0 at orbit edge, 1 at max range
    const pull=excess*excess*5.5; // quadratic — gentle at first, firmer when far
    miniMe.vx+=Math.cos(pa)*pull;
    miniMe.vy+=Math.sin(pa)*pull;
  }
  miniMe.vx*=DRAG;miniMe.vy*=DRAG;
  miniMe.x=clamp(miniMe.x+miniMe.vx*dt*60,MM_SIZE,WORLD_W-MM_SIZE);
  miniMe.y=clamp(miniMe.y+miniMe.vy*dt*60,MM_SIZE,WORLD_H-MM_SIZE);
}
function drawMiniMe(){
  if(!miniMe.active)return;
  const sx=miniMe.x-camX,sy=miniMe.y-camY;
  if(sx<-60||sx>canvas.width+60||sy<-60||sy>canvas.height+60)return;
  const sz=MM_SIZE,now=Date.now();
  ctx.save();ctx.translate(sx,sy);
  ctx.shadowBlur=14;ctx.shadowColor=MM_COL;
  // 4 small arms
  const ARMS=[Math.PI/4,-Math.PI/4,3*Math.PI/4,-3*Math.PI/4];
  ctx.strokeStyle=MM_COL;ctx.lineWidth=1.4;
  for(const a of ARMS){
    const ax=Math.cos(a)*sz*1.3,ay=Math.sin(a)*sz*1.3;
    ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(ax,ay);ctx.stroke();
    ctx.beginPath();ctx.arc(ax,ay,2.5,0,Math.PI*2);ctx.fillStyle=MM_COL;ctx.fill();
    ctx.save();ctx.translate(ax,ay);ctx.rotate(miniMe.rotor);
    for(let b=0;b<2;b++){ctx.save();ctx.rotate(b*Math.PI/2);ctx.beginPath();ctx.ellipse(0,0,sz*0.68,sz*0.18,0,0,Math.PI*2);ctx.strokeStyle=MM_ACC;ctx.lineWidth=1;ctx.globalAlpha=0.55;ctx.stroke();ctx.restore();}
    ctx.globalAlpha=1;ctx.restore();
  }
  // Hexagonal body
  const bs=sz*0.58;
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*bs,Math.sin(a)*bs):ctx.lineTo(Math.cos(a)*bs,Math.sin(a)*bs);}ctx.closePath();
  ctx.fillStyle='rgba(4,12,24,0.95)';ctx.fill();ctx.strokeStyle=MM_COL;ctx.lineWidth=1.5;ctx.stroke();
  // Gun barrel
  ctx.save();ctx.rotate(miniMe.aim);
  ctx.fillStyle=MM_ACC;ctx.shadowBlur=6;ctx.shadowColor=MM_ACC;
  ctx.fillRect(bs*0.5,-1.5,bs*1.2,3);
  ctx.beginPath();ctx.arc(bs*0.5+bs*1.2,0,1.8,0,Math.PI*2);ctx.fillStyle='#fff';ctx.fill();
  ctx.restore();
  // Pulsing core
  const pulse=0.7+0.3*Math.sin(now/200);
  ctx.beginPath();ctx.arc(0,0,3*pulse,0,Math.PI*2);ctx.fillStyle=MM_ACC;ctx.shadowColor=MM_ACC;ctx.shadowBlur=10;ctx.fill();
  // Low HP warning flash
  if(miniMe.hp/MM_HP<0.35&&Math.floor(now/140)%2===0){ctx.beginPath();ctx.arc(0,0,sz*1.2,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.22)';ctx.fill();}
  // Iframes flash
  if(miniMe.iframes>0&&Math.floor(now/80)%2===0){ctx.globalAlpha=0.25;ctx.restore();return;}
  ctx.restore();
  // HP bar (drawn in screen space above miniMe)
  const bw=MM_SIZE*4.2,bh=4,bx=sx-bw/2,by=sy-sz*2.8;
  ctx.fillStyle='rgba(0,0,0,0.7)';ctx.fillRect(bx-1,by-1,bw+2,bh+2);
  ctx.fillStyle='#111';ctx.fillRect(bx,by,bw,bh);
  const pct=miniMe.hp/MM_HP;
  ctx.fillStyle=pct>0.5?MM_COL:pct>0.25?'#ffaa00':'#ff3333';
  ctx.shadowBlur=5;ctx.shadowColor=ctx.fillStyle;
  ctx.fillRect(bx,by,bw*pct,bh);ctx.shadowBlur=0;
  // Tether line to player when drifting outward
  const dp=dist(miniMe.x,miniMe.y,P.x,P.y);
  if(dp>MM_ORBIT_R*1.4){
    const psx=P.x-camX,psy=P.y-camY;
    ctx.save();ctx.globalAlpha=0.18*(dp-MM_ORBIT_R*1.4)/(MM_MAX_RANGE-MM_ORBIT_R*1.4);
    ctx.strokeStyle=MM_COL;ctx.lineWidth=1;ctx.setLineDash([4,6]);
    ctx.beginPath();ctx.moveTo(sx,sy);ctx.lineTo(psx,psy);ctx.stroke();
    ctx.setLineDash([]);ctx.restore();
  }
}
// ── Dart: sleek arrowhead silhouette — tiny and fast-looking ──
function _drawDart(x,y,aim,sz,col,acc,spin){
  ctx.save();ctx.translate(x,y);ctx.shadowBlur=12;ctx.shadowColor=col;
  ctx.save();ctx.rotate(aim);
  ctx.fillStyle=col;ctx.strokeStyle=acc;ctx.lineWidth=1.5;
  ctx.beginPath();ctx.moveTo(sz*1.4,0);ctx.lineTo(-sz*0.8,sz*0.6);ctx.lineTo(-sz*0.3,0);ctx.lineTo(-sz*0.8,-sz*0.6);ctx.closePath();ctx.fill();ctx.stroke();
  ctx.fillStyle=acc;ctx.fillRect(-sz*0.35,-1.5,sz*0.5,3);
  ctx.restore();
  // Rotor glow ring (single, small)
  ctx.beginPath();ctx.arc(0,0,sz*1.1,spin,spin+Math.PI*1.5);ctx.strokeStyle=acc;ctx.lineWidth=1.2;ctx.globalAlpha=0.55;ctx.stroke();ctx.globalAlpha=1;
  ctx.beginPath();ctx.arc(0,0,2.5,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowBlur=10;ctx.shadowColor=acc;ctx.fill();ctx.shadowBlur=0;
  ctx.restore();
}
// ── Brute: heavy hexagonal frame — large and imposing ──
function _drawBrute(x,y,aim,sz,col,acc,spin,hp=1){
  ctx.save();ctx.translate(x,y);ctx.shadowBlur=22;ctx.shadowColor=col;
  // Outer armor ring
  const sides=6;
  ctx.beginPath();for(let i=0;i<sides;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*sz,Math.sin(a)*sz):ctx.lineTo(Math.cos(a)*sz,Math.sin(a)*sz);}ctx.closePath();
  ctx.fillStyle='rgba(6,10,22,0.9)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=3;ctx.stroke();
  // Secondary inner ring
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i+Math.PI/6;i===0?ctx.moveTo(Math.cos(a)*sz*0.65,Math.sin(a)*sz*0.65):ctx.lineTo(Math.cos(a)*sz*0.65,Math.sin(a)*sz*0.65);}ctx.closePath();
  ctx.strokeStyle=acc;ctx.lineWidth=1.8;ctx.globalAlpha=0.55;ctx.stroke();ctx.globalAlpha=1;
  // Four thick rotor arms (heavier than scout)
  ctx.strokeStyle=col;ctx.lineWidth=4;
  for(let i=0;i<4;i++){const a=spin+i*Math.PI/2;ctx.beginPath();ctx.moveTo(Math.cos(a)*sz*0.65,Math.sin(a)*sz*0.65);ctx.lineTo(Math.cos(a)*sz*1.25,Math.sin(a)*sz*1.25);ctx.stroke();}
  // Gun barrel pointing at aim angle
  ctx.save();ctx.rotate(aim);ctx.fillStyle=acc;ctx.shadowBlur=10;ctx.shadowColor=acc;
  ctx.fillRect(sz*0.4,-3.5,sz*1.1,7);ctx.beginPath();ctx.arc(sz*1.55,0,3.5,0,Math.PI*2);ctx.fillStyle='#fff';ctx.fill();
  ctx.restore();
  // Core
  ctx.beginPath();ctx.arc(0,0,sz*0.3,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowColor=acc;ctx.shadowBlur=14;ctx.fill();ctx.shadowBlur=0;
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.85,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawRavager(x,y,aim,sz,col,acc,spin,hp){
  ctx.save();ctx.translate(x,y);ctx.rotate(aim);
  ctx.shadowBlur=18;ctx.shadowColor=col;
  ctx.beginPath();ctx.moveTo(sz*1.4,0);ctx.lineTo(-sz*0.7,sz*0.9);ctx.lineTo(-sz*0.7,-sz*0.9);ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=2;ctx.stroke();
  ctx.strokeStyle=acc;ctx.lineWidth=2.5;ctx.shadowColor=acc;ctx.beginPath();ctx.moveTo(sz*1.4,0);ctx.lineTo(sz*2.6,0);ctx.stroke();
  ctx.beginPath();ctx.arc(0,0,sz*0.28,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowBlur=12;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.8,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawSplitter(x,y,aim,sz,col,acc,spin,hp){
  ctx.save();ctx.translate(x,y);ctx.rotate(spin);
  ctx.shadowBlur=16;ctx.shadowColor=col;
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*sz,Math.sin(a)*sz):ctx.lineTo(Math.cos(a)*sz,Math.sin(a)*sz);}ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=2;ctx.stroke();
  ctx.strokeStyle=acc;ctx.lineWidth=1.2;ctx.shadowColor=acc;ctx.shadowBlur=8;ctx.globalAlpha=0.7;
  ctx.beginPath();ctx.moveTo(-sz*0.7,sz*0.2);ctx.lineTo(sz*0.7,-sz*0.2);ctx.stroke();
  ctx.beginPath();ctx.moveTo(-sz*0.4,-sz*0.7);ctx.lineTo(sz*0.4,sz*0.5);ctx.stroke();
  ctx.globalAlpha=1;
  ctx.save();ctx.rotate(aim-spin);ctx.strokeStyle=acc;ctx.lineWidth=2;ctx.shadowBlur=8;ctx.shadowColor=acc;ctx.beginPath();ctx.moveTo(sz*0.5,0);ctx.lineTo(sz*1.3,0);ctx.stroke();ctx.restore();
  ctx.beginPath();ctx.arc(0,0,sz*0.25,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowBlur=12;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.8,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawShard(x,y,aim,sz,col,acc,spin,hp){
  ctx.save();ctx.translate(x,y);ctx.rotate(spin);
  ctx.shadowBlur=14;ctx.shadowColor=col;
  const pts=[[sz*1.1,0],[sz*0.3,sz*0.8],[-sz*0.9,sz*0.5],[-sz*0.7,-sz*0.6],[sz*0.4,-sz*0.9]];
  ctx.beginPath();ctx.moveTo(pts[0][0],pts[0][1]);for(let i=1;i<pts.length;i++)ctx.lineTo(pts[i][0],pts[i][1]);ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.8;ctx.stroke();
  ctx.beginPath();ctx.arc(0,0,sz*0.28,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowBlur=10;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.8,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawCloaker(x,y,aim,sz,col,acc,spin,hp,visibleMs){
  ctx.save();ctx.translate(x,y);
  ctx.globalAlpha=visibleMs>0?1.0:0.08;
  ctx.rotate(aim);ctx.shadowBlur=16;ctx.shadowColor=col;
  ctx.beginPath();ctx.moveTo(sz*1.2,0);ctx.lineTo(0,sz*0.7);ctx.lineTo(-sz*0.9,0);ctx.lineTo(0,-sz*0.7);ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.8;ctx.stroke();
  ctx.beginPath();ctx.arc(sz*1.2,0,2.5,0,Math.PI*2);ctx.fillStyle='#fff';ctx.shadowBlur=8;ctx.fill();
  ctx.beginPath();ctx.arc(0,0,sz*0.25,0,Math.PI*2);ctx.fillStyle=acc;ctx.fill();
  if(visibleMs>0){
    ctx.rotate(-aim);
    const pulse=0.5+0.5*Math.sin(Date.now()/80);
    ctx.strokeStyle=`rgba(136,255,238,${0.6*pulse})`;ctx.lineWidth=1.5;ctx.shadowBlur=10;ctx.shadowColor=acc;
    ctx.beginPath();ctx.arc(0,0,sz*1.9,0,Math.PI*2);ctx.stroke();
  }
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.8,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawDemolisher(x,y,aim,sz,col,acc,spin,hp){
  ctx.save();ctx.translate(x,y);ctx.rotate(aim);
  ctx.shadowBlur=18;ctx.shadowColor=col;
  ctx.save();ctx.scale(1.0,0.65);
  ctx.beginPath();ctx.arc(0,0,sz,0,Math.PI*2);
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=2;ctx.stroke();
  ctx.restore();
  ctx.strokeStyle=acc;ctx.lineWidth=4;ctx.shadowColor=acc;ctx.shadowBlur=12;
  ctx.beginPath();ctx.moveTo(sz*0.4,0);ctx.lineTo(sz*1.6,0);ctx.stroke();
  ctx.beginPath();ctx.arc(sz*1.6,0,4,0,Math.PI*2);ctx.fillStyle=acc;ctx.fill();
  ctx.strokeStyle=col;ctx.lineWidth=2;ctx.shadowBlur=8;ctx.shadowColor=col;
  ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(-sz*0.3,sz*1.1);ctx.stroke();
  ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(-sz*0.3,-sz*1.1);ctx.stroke();
  ctx.beginPath();ctx.arc(0,0,sz*0.28,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowBlur=10;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.75,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawHunter(x,y,aim,sz,col,acc,spin,hp){
  ctx.save();ctx.translate(x,y);ctx.rotate(aim);
  ctx.shadowBlur=14;ctx.shadowColor=col;
  ctx.beginPath();ctx.moveTo(sz*1.3,0);ctx.lineTo(-sz*0.8,sz*0.7);ctx.lineTo(-sz*0.4,0);ctx.lineTo(-sz*0.8,-sz*0.7);ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.92)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.6;ctx.stroke();
  const pulse=0.5+0.5*Math.sin(Date.now()/50);
  ctx.globalAlpha=0.6*pulse;ctx.fillStyle=acc;ctx.shadowColor=acc;ctx.shadowBlur=10;
  ctx.beginPath();ctx.moveTo(-sz*0.4,0);ctx.lineTo(-sz*1.4,sz*0.3);ctx.lineTo(-sz*1.1,0);ctx.lineTo(-sz*1.4,-sz*0.3);ctx.closePath();ctx.fill();
  ctx.globalAlpha=1;
  ctx.beginPath();ctx.arc(0,0,sz*0.22,0,Math.PI*2);ctx.fillStyle=acc;ctx.shadowBlur=10;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.75,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawDreadnought(x,y,aim,sz,col,acc,spin,hp,phase){
  ctx.save();ctx.translate(x,y);
  ctx.shadowBlur=22;ctx.shadowColor=col;
  if(phase===1){
    ctx.save();ctx.rotate(spin*0.3);
    ctx.strokeStyle=col;ctx.lineWidth=5;ctx.beginPath();ctx.arc(0,0,sz*1.05,0,Math.PI*2);ctx.stroke();
    ctx.strokeStyle=acc;ctx.lineWidth=3;
    for(let i=0;i<4;i++){const a=(Math.PI/2)*i;ctx.beginPath();ctx.arc(0,0,sz*1.05,a+0.15,a+Math.PI/2-0.15);ctx.stroke();}
    ctx.restore();
  } else {
    const pulse=0.5+0.5*Math.sin(Date.now()/100);
    ctx.beginPath();ctx.arc(0,0,sz*0.85,0,Math.PI*2);ctx.fillStyle=`rgba(255,102,0,${0.18*pulse})`;ctx.fill();
    ctx.strokeStyle=`rgba(255,204,68,${0.7+0.3*pulse})`;ctx.lineWidth=2;ctx.shadowColor=acc;ctx.shadowBlur=28*pulse;ctx.stroke();ctx.shadowBlur=22;
  }
  ctx.rotate(aim);
  const bs=sz*0.55;
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*bs,Math.sin(a)*bs):ctx.lineTo(Math.cos(a)*bs,Math.sin(a)*bs);}ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.95)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=2.2;ctx.stroke();
  ctx.strokeStyle=acc;ctx.lineWidth=2.5;ctx.shadowColor=acc;
  ctx.beginPath();ctx.moveTo(bs*0.4,0);ctx.lineTo(bs*1.5,0);ctx.stroke();
  ctx.beginPath();ctx.arc(0,0,sz*0.22,0,Math.PI*2);ctx.fillStyle=phase===2?'#fff':acc;ctx.shadowBlur=16;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.7,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}
function drawHarbinger(x,y,aim,sz,col,acc,spin,hp,rageMs){
  ctx.save();ctx.translate(x,y);
  const raging=rageMs>0, pulse=raging?0.5+0.5*Math.sin(Date.now()/60):0;
  ctx.shadowBlur=raging?36:22;ctx.shadowColor=raging?acc:col;
  ctx.save();ctx.rotate(spin*0.2);
  ctx.strokeStyle=raging?acc:col;ctx.lineWidth=raging?3+pulse*2:2;
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i+Math.PI/6;i===0?ctx.moveTo(Math.cos(a)*sz*1.32,Math.sin(a)*sz*1.32):ctx.lineTo(Math.cos(a)*sz*1.32,Math.sin(a)*sz*1.32);}ctx.closePath();ctx.stroke();
  for(let i=0;i<6;i++){const a=(Math.PI/3)*i+Math.PI/6;ctx.beginPath();ctx.arc(Math.cos(a)*sz*1.32,Math.sin(a)*sz*1.32,raging?5+pulse*3:4,0,Math.PI*2);ctx.fillStyle=raging?acc:col;ctx.fill();}
  ctx.restore();
  ctx.rotate(aim);
  const bs=sz*0.62;
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*bs,Math.sin(a)*bs):ctx.lineTo(Math.cos(a)*bs,Math.sin(a)*bs);}ctx.closePath();
  ctx.fillStyle='rgba(4,9,22,0.95)';ctx.fill();ctx.strokeStyle=raging?acc:col;ctx.lineWidth=2.5;ctx.stroke();
  ctx.strokeStyle=acc;ctx.lineWidth=3;ctx.shadowColor=acc;
  ctx.beginPath();ctx.moveTo(bs*0.45,0);ctx.lineTo(bs*1.4,0);ctx.stroke();
  ctx.beginPath();ctx.arc(0,0,sz*0.25,0,Math.PI*2);ctx.fillStyle=raging?'#fff':acc;ctx.shadowBlur=raging?24:14;ctx.fill();
  if(hp<0.3&&Math.floor(Date.now()/120)%2===0){ctx.beginPath();ctx.arc(0,0,sz*0.7,0,Math.PI*2);ctx.fillStyle='rgba(255,80,0,0.2)';ctx.fill();}
  ctx.restore();
}

function drawEnemies(){
  const now=Date.now();
  for(const e of enemies){
    const sx=e.x-camX,sy=e.y-camY;if(sx<-90||sx>canvas.width+90||sy<-90||sy>canvas.height+90)continue;
    // EMP stun ring (purple)
    if(e.stunMs>0){ctx.save();ctx.translate(sx,sy);ctx.rotate(now/180);ctx.strokeStyle='rgba(200,80,255,0.5)';ctx.lineWidth=1.5;ctx.beginPath();ctx.arc(0,0,e.size*1.85,0,Math.PI*2);ctx.stroke();ctx.restore();}
    // Stun-gun electric arcs (yellow-green, crackles)
    if(e.stunMoveMs>0||e.stunFireMs>0){
      const seed=Math.floor(now/80); // changes ~12fps for crackle effect
      ctx.save();ctx.translate(sx,sy);
      const arcCol=e.stunFireMs>0?'rgba(170,255,68,0.85)':'rgba(170,255,68,0.4)';
      ctx.strokeStyle=arcCol;ctx.lineWidth=1.8;ctx.shadowBlur=12;ctx.shadowColor='#aaff44';
      // Draw 3 jagged electric arcs radiating outward
      for(let arc=0;arc<3;arc++){
        const baseAngle=(arc/3)*Math.PI*2+(seed*0.7+arc*1.3);
        const r=e.size*1.6;
        ctx.beginPath();ctx.moveTo(0,0);
        let cx2=0,cy2=0;
        for(let seg=1;seg<=4;seg++){
          const t2=seg/4,jitter=(Math.sin(seed*3.7+arc*11+seg*7))*r*0.35;
          cx2=Math.cos(baseAngle)*r*t2+Math.cos(baseAngle+Math.PI/2)*jitter;
          cy2=Math.sin(baseAngle)*r*t2+Math.sin(baseAngle+Math.PI/2)*jitter;
          ctx.lineTo(cx2,cy2);
        }
        ctx.stroke();
      }
      // Outer ring for fire-stun (can't shoot)
      if(e.stunFireMs>0){ctx.strokeStyle='rgba(170,255,68,0.25)';ctx.lineWidth=1;ctx.shadowBlur=0;ctx.beginPath();ctx.arc(0,0,e.size*2.1,0,Math.PI*2);ctx.stroke();}
      ctx.restore();
    }
    // Infected ally: green pulse aura drawn before the craft
    if(e.infected){
      const ip=0.5+0.5*Math.sin(Date.now()/160+sx*0.01);
      ctx.beginPath();ctx.arc(sx,sy,e.size*2.2,0,Math.PI*2);
      ctx.fillStyle=`rgba(0,255,120,${0.12*ip})`;ctx.fill();
      ctx.strokeStyle=`rgba(0,255,120,${0.55*ip})`;ctx.lineWidth=2;
      ctx.shadowBlur=14;ctx.shadowColor='#00ff88';ctx.stroke();ctx.shadowBlur=0;
    }
    if(e.type==='wraith'&&e.blinkFlash>0){
      // Teleport flash — bright expanding ring
      const fPct=1-(e.blinkFlash/400);
      ctx.beginPath();ctx.arc(sx,sy,e.size*(1+fPct*3),0,Math.PI*2);
      ctx.strokeStyle=`rgba(136,68,255,${(1-fPct)*0.9})`;ctx.lineWidth=3;ctx.shadowBlur=18;ctx.shadowColor=e.color;ctx.stroke();ctx.shadowBlur=0;
    }
    // Phantom stalker: pulse green when retreating/healing
    if(e.type==='phantom'&&e.retreating){
      const pulse=0.5+0.5*Math.sin(Date.now()/160);
      ctx.beginPath();ctx.arc(sx,sy,e.size*2,0,Math.PI*2);
      ctx.fillStyle=`rgba(68,255,170,${0.10*pulse})`;ctx.fill();
      ctx.strokeStyle=`rgba(68,255,170,${0.45*pulse})`;ctx.lineWidth=1.5;ctx.stroke();
    }
    // Set alpha for cloaker stealth
    const cloakerInvis=e.type==='cloaker'&&e.visibleMs<=0;
    if(cloakerInvis) ctx.globalAlpha=0.08;
    // Draw distinct shapes per type
    if(e.type==='dart'){
      _drawDart(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='brute'){
      _drawBrute(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='ravager'){
      const rc=e.chargeMs>0?e.accent:e.color;
      drawRavager(sx,sy,e.aim,e.size,rc,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='splitter'){
      drawSplitter(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='shard'){
      drawShard(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='cloaker'){
      drawCloaker(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp,e.visibleMs);
    } else if(e.type==='demolisher'){
      drawDemolisher(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='hunter'){
      drawHunter(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    } else if(e.type==='dreadnought'){
      drawDreadnought(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp,e.phase);
    } else if(e.type==='harbinger'){
      drawHarbinger(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp,e.rageMs);
    } else {
      drawEnemyDrone(sx,sy,e.aim,e.size,e.color,e.accent,e.rotor,e.hp/e.maxHp);
    }
    if(cloakerInvis) ctx.globalAlpha=1;
    const bw=e.size*3.2,bh=5,bx=sx-bw/2,by=sy-e.size*2.4;
    ctx.fillStyle='rgba(0,0,0,0.7)';ctx.fillRect(bx-1,by-1,bw+2,bh+2);ctx.fillStyle='#111';ctx.fillRect(bx,by,bw,bh);
    const pct=e.hp/e.maxHp;ctx.fillStyle=pct>0.5?'#22ee88':pct>0.25?'#ffaa00':'#ff3333';ctx.shadowBlur=5;ctx.shadowColor=ctx.fillStyle;ctx.fillRect(bx,by,bw*pct,bh);ctx.shadowBlur=0;
  }
}

// ─── PROXIMITY MINES ─────────────────────────────────────────────
function _spreadInfection(killedType, killedIdx){
  // Remove the killed enemy first
  const dead=enemies[killedIdx];
  score+=dead.score; P.kills++;
  spawnParts(dead.x,dead.y,'#00ff88',_pCount(30),5,7,900);
  spawnParts(dead.x,dead.y,'#ffffff',_pCount(10),3,4,500);
  if(settings.screenShake)shake=Math.max(shake,14);
  spawnPickup(dead.x,dead.y,null,false);
  if(Math.random()<0.5) pickups[pickups.length-1].mystery=true;
  enemies.splice(killedIdx,1);
  // Find same-type survivors
  const sameType=enemies.filter(e=>!e.infected&&e.type===killedType);
  if(sameType.length>0){
    for(const e of sameType) _infectEnemy(e);
    weaponFlash={name:`${killedType.toUpperCase()} INFECTED ×${sameType.length}`,ms:2800};
  } else {
    // No same-type survivors — infect 25% of remaining non-infected
    const others=enemies.filter(e=>!e.infected);
    const count=Math.max(1,Math.round(others.length*0.25));
    // Shuffle and take first `count`
    for(let i=others.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[others[i],others[j]]=[others[j],others[i]];}
    const toInfect=others.slice(0,count);
    for(const e of toInfect) _infectEnemy(e);
    if(toInfect.length>0) weaponFlash={name:`INFECTION SPREAD ×${toInfect.length}`,ms:2800};
  }
}
function _infectEnemy(e){
  e.infected=true;
  spawnParts(e.x,e.y,'#00ff88',_pCount(20),4,6,700);
  spawnParts(e.x,e.y,'#aaffcc',_pCount(8),2,3.5,500);
}
function killEnemy(idx){
  const e=enemies[idx];
  // Splitter: spawn 2 shards before removing
  if(e.type==='splitter'){
    for(const yOff of[-20,20]){
      const se=mkEnemy('shard',e.x,e.y+yOff);
      se.state='chase';
      enemies.push(se);
    }
  }
  // Harbinger pod: notify parent, trigger rage when all pods dead
  if(e.fromHarbinger&&harbingerRef&&harbingerRef.hp>0){
    harbingerRef.activePods=Math.max(0,harbingerRef.activePods-1);
    if(harbingerRef.activePods===0){harbingerRef.rageMs=4000;harbingerRef.fireMs=80;}
  }
  // Harbinger itself dying: clear the ref
  if(e.type==='harbinger') harbingerRef=null;
  score+=e.score; P.kills++;
  if(e.type==='boss'||e.type==='dreadnought'||e.type==='harbinger') score+=100;
  spawnParts(e.x,e.y,e.color,_pCount(24),6.5,8.5,800);
  spawnParts(e.x,e.y,'#fff',_pCount(10),4,3,500);
  spawnParts(e.x,e.y,'#ffaa00',_pCount(15),5.5,6,650);
  if(settings.screenShake)shake=Math.max(shake, e.type==='boss'?32:14);
  // Every kill drops at least one pickup — half are mystery diamonds
  const isMystery1=Math.random()<0.5;
  spawnPickup(e.x,e.y,null,false);
  if(isMystery1) pickups[pickups.length-1].mystery=true;
  // Heavier enemies drop a second pickup
  if(e.type==='guard'||e.type==='turret'||e.type==='brute'||e.type==='phantom'){
    spawnPickup(e.x+rng(-30,30),e.y+rng(-30,30),null,false);
    if(Math.random()<0.5) pickups[pickups.length-1].mystery=true;
  }
  // 20% chance to drop a bonus points coin (always visible)
  if(Math.random()<0.20) spawnPickup(e.x+rng(-25,25),e.y+rng(-25,25),'points');
  if(e.type==='boss'){spawnPickup(e.x,e.y,'weapon');spawnPickup(e.x+40,e.y,'overcharge');spawnPickup(e.x-40,e.y,'shield');spawnPickup(e.x,e.y+40,'emp');}
  enemies.splice(idx,1);
}
function tickMines(dt){
  for(let mi=mines.length-1;mi>=0;mi--){
    const m=mines[mi];
    m.t+=dt*1000;
    // Arm after delay
    if(!m.armed){ m.armMs-=dt*1000; if(m.armMs<=0) m.armed=true; }
    // Blasting animation — remove after flash
    if(m.blasting){ m.blastT-=dt*1000; if(m.blastT<=0) mines.splice(mi,1); continue; }
    if(!m.armed) continue;
    // Scan for any enemy entering trigger radius
    let triggered=false;
    for(const e of enemies){ if(dist2(m.x,m.y,e.x,e.y)<MINE_TRIGGER_R*MINE_TRIGGER_R){triggered=true;break;} }
    if(!triggered) continue;
    // DETONATE — hit everything in blast radius
    m.blasting=true; m.blastT=520;
    SFX.minedet();
    spawnParts(m.x,m.y,'#ff2200',_pCount(40),9,10,900);
    spawnParts(m.x,m.y,'#ff8800',_pCount(28),7,7,700);
    spawnParts(m.x,m.y,'#ffffff',_pCount(16),5,4,500);
    if(settings.screenShake)shake=Math.max(shake,22);
    // Process enemies in blast radius (iterate backwards for safe splice)
    for(let ei=enemies.length-1;ei>=0;ei--){
      const e=enemies[ei];
      if(dist2(m.x,m.y,e.x,e.y)>MINE_BLAST_R*MINE_BLAST_R) continue;
      if(e.type==='boss'){
        // 50% HP damage to boss — never kills
        e.hp=Math.max(1, e.hp - e.maxHp*0.5);
        spawnParts(e.x,e.y,e.color,_pCount(18),5,7,600);
      } else {
        killEnemy(ei);
      }
    }
  }
}
function drawMines(){
  const now=Date.now();
  for(const m of mines){
    const sx=m.x-camX, sy=m.y-camY;
    if(sx<-200||sx>canvas.width+200||sy<-200||sy>canvas.height+200) continue;
    ctx.save(); ctx.translate(sx,sy);
    if(m.blasting){
      // Expanding blast ring
      const prog=1-(m.blastT/520);
      const br=MINE_BLAST_R*prog*1.1;
      ctx.globalAlpha=Math.max(0,(1-prog)*0.75);
      ctx.beginPath(); ctx.arc(0,0,br,0,Math.PI*2);
      ctx.fillStyle='rgba(255,80,0,0.35)'; ctx.fill();
      ctx.strokeStyle='#ff4400'; ctx.lineWidth=3*(1-prog)+1;
      ctx.shadowBlur=28; ctx.shadowColor='#ff2200';
      ctx.stroke(); ctx.shadowBlur=0; ctx.globalAlpha=1;
      ctx.restore(); continue;
    }
    const pulse=0.55+0.45*Math.sin(m.t/(m.armed?180:420));
    const alertRate=m.armed ? 240 : 700;
    const blink=Math.floor(now/alertRate)%2===0;
    // Faint detection ring
    ctx.globalAlpha=0.12+0.06*pulse;
    ctx.beginPath(); ctx.arc(0,0,MINE_TRIGGER_R,0,Math.PI*2);
    ctx.strokeStyle='#ff2200'; ctx.lineWidth=1; ctx.stroke();
    ctx.globalAlpha=1;
    // Body
    ctx.shadowBlur=m.armed?18*pulse:8;
    ctx.shadowColor=m.armed?'#ff2200':'#884400';
    // Outer ring
    ctx.beginPath(); ctx.arc(0,0,12,0,Math.PI*2);
    ctx.strokeStyle=m.armed?(blink?'#ff2200':'#ff6600'):'#885500';
    ctx.lineWidth=2.5; ctx.stroke();
    // Inner disc
    ctx.beginPath(); ctx.arc(0,0,7.5,0,Math.PI*2);
    ctx.fillStyle=m.armed?(blink?'rgba(255,34,0,0.85)':'rgba(120,20,0,0.9)'):'rgba(60,30,0,0.9)';
    ctx.fill();
    // Cross-hair ticks
    ctx.strokeStyle=m.armed?(blink?'#ff4400':'#ff8800'):'#664400';
    ctx.lineWidth=1.5;
    [[0,12,0,8],[0,-12,0,-8],[12,0,8,0],[-12,0,-8,0]].forEach(([x1,y1,x2,y2])=>{
      ctx.beginPath(); ctx.moveTo(x1,y1); ctx.lineTo(x2,y2); ctx.stroke();
    });
    // Armed indicator dot (pulsing center)
    if(m.armed){
      ctx.beginPath(); ctx.arc(0,0,3,0,Math.PI*2);
      ctx.fillStyle=blink?'#ff2200':'#ff6600';
      ctx.shadowBlur=10; ctx.shadowColor='#ff2200'; ctx.fill(); ctx.shadowBlur=0;
    }
    ctx.restore();
  }
}
function tickFaradayCages(dt){
  for(let ci=faradayCages.length-1;ci>=0;ci--){
    const c=faradayCages[ci];
    if(c.blasting){c.blastT-=dt*1000;if(c.blastT<=0)faradayCages.splice(ci,1);continue;}
    if(!c.armed){c.armMs-=dt*1000;if(c.armMs<=0)c.armed=true;continue;}
    c.life-=dt*1000;
    if(c.life<=0){
      for(const e of c.trapped){delete e.anchorX;delete e.anchorY;}
      faradayCages.splice(ci,1);
      continue;
    }
    if(c.trapped.length>=2)continue;
    for(let ei=0;ei<enemies.length;ei++){
      const e=enemies[ei];
      if(e.anchorX!==undefined)continue;
      if(dist2(c.x,c.y,e.x,e.y)<FARADAY_TRIGGER_R*FARADAY_TRIGGER_R){
        e.anchorX=c.x;e.anchorY=c.y;
        c.trapped.push(e);
        spawnParts(c.x,c.y,'#88ffcc',_pCount(14),3,5,420);
        SFX.shield();
        if(c.trapped.length>=2)break;
      }
    }
  }
}
function drawFaradayCages(){
  const now=Date.now();
  for(const c of faradayCages){
    const sx=c.x-camX,sy=c.y-camY;
    if(sx<-200||sx>canvas.width+200||sy<-200||sy>canvas.height+200)continue;
    ctx.save();ctx.translate(sx,sy);
    if(c.blasting){
      const prog=1-(c.blastT/400);
      ctx.globalAlpha=Math.max(0,(1-prog)*0.7);
      ctx.beginPath();ctx.arc(0,0,FARADAY_TRIGGER_R*prog*1.1,0,Math.PI*2);
      ctx.strokeStyle='#88ffcc';ctx.lineWidth=2;ctx.shadowBlur=20;ctx.shadowColor='#88ffcc';
      ctx.stroke();ctx.shadowBlur=0;ctx.globalAlpha=1;ctx.restore();continue;
    }
    const full=c.trapped.length>=2;
    const col=full?'#ffcc00':c.armed?'#88ffcc':'#336655';
    const pulse=0.6+0.4*Math.sin(now/220);
    ctx.globalAlpha=0.1+0.05*pulse;
    ctx.beginPath();ctx.arc(0,0,FARADAY_TRIGGER_R,0,Math.PI*2);
    ctx.strokeStyle=col;ctx.lineWidth=1;ctx.stroke();ctx.globalAlpha=1;
    ctx.shadowBlur=c.armed?16*pulse:6;ctx.shadowColor=col;
    ctx.beginPath();
    for(let i=0;i<6;i++){
      const a=Math.PI/6+i*Math.PI/3;
      const r=16+(c.armed?2*Math.sin(now/180+i):0);
      i===0?ctx.moveTo(Math.cos(a)*r,Math.sin(a)*r):ctx.lineTo(Math.cos(a)*r,Math.sin(a)*r);
    }
    ctx.closePath();ctx.strokeStyle=col;ctx.lineWidth=2;ctx.stroke();ctx.shadowBlur=0;
    ctx.globalAlpha=0.3;
    for(let i=0;i<6;i++){
      const a=Math.PI/6+i*Math.PI/3;
      ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(Math.cos(a)*14,Math.sin(a)*14);
      ctx.strokeStyle=col;ctx.lineWidth=1;ctx.stroke();
    }
    ctx.globalAlpha=1;
    if(c.armed){
      ctx.font='bold 10px "Courier New"';ctx.fillStyle=col;ctx.textAlign='center';
      ctx.fillText(`${c.trapped.length}/2`,0,22);
    }
    ctx.restore();
  }
}
// ─── ROCKETS ─────────────────────────────────────────────────────
function tickRockets(dt){
  const step=dt*60;
  const DMG=ROCKET_DMG*(P.overchargeMs>0?2.3:1);
  for(let ri=rockets.length-1;ri>=0;ri--){
    const r=rockets[ri];
    r.life-=dt*1000;
    if(r.life<=0){rockets.splice(ri,1);continue;}
    // Store trail point every few pixels of movement
    if(r.trail.length===0||dist(r.x,r.y,r.trail[r.trail.length-1].x,r.trail[r.trail.length-1].y)>14){
      r.trail.push({x:r.x,y:r.y,age:0});
    }
    // Age trail points — remove after 1800ms
    for(let ti=r.trail.length-1;ti>=0;ti--){
      r.trail[ti].age+=dt*1000;
      if(r.trail[ti].age>1800) r.trail.splice(ti,1);
    }
    // Move — PIERCES obstacles and world bounds (no stopping)
    r.x+=r.vx*step;
    r.y+=r.vy*step;
    // Clamp to world but keep going (wrap against walls)
    r.x=clamp(r.x,0,WORLD_W); r.y=clamp(r.y,0,WORLD_H);
    // If it hits a wall boundary, remove it
    if(r.x<=0||r.x>=WORLD_W||r.y<=0||r.y>=WORLD_H){rockets.splice(ri,1);continue;}
    // Damage any enemy within blast radius — can hit each enemy only once per rocket
    for(let ei=enemies.length-1;ei>=0;ei--){
      if(r.hitEnemies.has(ei)) continue;
      if(dist2(r.x,r.y,enemies[ei].x,enemies[ei].y)<(enemies[ei].size+10)**2){
        r.hitEnemies.add(ei);
        const e=enemies[ei];
        e.hp-=DMG;
        spawnParts(r.x,r.y,ROCKET_COL,_pCount(10),3.5,5,380);
        score+=10;
        if(e.hp<=0){SFX.boom();killEnemy(ei);}
      }
    }
    // Smoke/fire trail particles
    if(Math.random()<0.55) spawnParts(r.x-r.vx*2,r.y-r.vy*2,'#ff4400',_pCount(1),0.8,2.5,320);
    if(Math.random()<0.25) spawnParts(r.x-r.vx*3,r.y-r.vy*3,'#888888',_pCount(1),0.4,1.8,480);
  }
}
function drawRockets(){
  const now=Date.now()/1000;
  for(const r of rockets){
    // Draw burn trail (scorch marks through obstacles)
    for(let ti=0;ti<r.trail.length;ti++){
      const tp=r.trail[ti];
      const sx=tp.x-camX, sy=tp.y-camY;
      if(sx<-20||sx>canvas.width+20||sy<-20||sy>canvas.height+20) continue;
      const age=tp.age/1800; // 0→1
      const alpha=Math.max(0,(1-age)*0.65);
      const rad=4*(1-age*0.5)+1;
      // Scorch disc — dark red/orange
      ctx.beginPath();ctx.arc(sx,sy,rad,0,Math.PI*2);
      ctx.fillStyle=`rgba(200,60,0,${alpha*0.7})`;ctx.fill();
      // Outer ember ring
      ctx.beginPath();ctx.arc(sx,sy,rad*1.8,0,Math.PI*2);
      ctx.fillStyle=`rgba(255,120,0,${alpha*0.25})`;ctx.fill();
    }
    // Draw rocket body (seeker-style teardrop)
    const sx=r.x-camX, sy=r.y-camY;
    if(sx<-40||sx>canvas.width+40||sy<-40||sy>canvas.height+40) continue;
    ctx.save();ctx.translate(sx,sy);ctx.rotate(r.angle);
    ctx.shadowBlur=16;ctx.shadowColor=ROCKET_COL;
    // Body
    ctx.beginPath();
    ctx.moveTo(12,0);      // nose
    ctx.lineTo(-5,5);      // rear-right fin
    ctx.lineTo(-9,0);      // tail
    ctx.lineTo(-5,-5);     // rear-left fin
    ctx.closePath();
    ctx.fillStyle=ROCKET_COL;ctx.fill();
    // Tail fins
    ctx.beginPath();
    ctx.moveTo(-6,3);ctx.lineTo(-11,6);ctx.lineTo(-9,0);ctx.closePath();
    ctx.fillStyle='#cc3300';ctx.fill();
    ctx.beginPath();
    ctx.moveTo(-6,-3);ctx.lineTo(-11,-6);ctx.lineTo(-9,0);ctx.closePath();
    ctx.fillStyle='#cc3300';ctx.fill();
    // Engine glow
    const glow=0.6+0.4*Math.sin(now*18);
    ctx.beginPath();ctx.arc(-9,0,4*glow,0,Math.PI*2);
    ctx.fillStyle=`rgba(255,${Math.round(160+80*glow)},60,${glow*0.95})`;
    ctx.shadowBlur=14;ctx.shadowColor='#ff8800';ctx.fill();ctx.shadowBlur=0;
    ctx.restore();
  }
}
// ─── /ROCKETS ────────────────────────────────────────────────────
// ─── GRENADES ────────────────────────────────────────────────────
function _detonateGrenade(gi){
  const g=grenades[gi];
  g.blasting=true;g.blastT=480;
  spawnParts(g.x,g.y,'#ffaa22',_pCount(35),8,9,850);
  spawnParts(g.x,g.y,'#ffffff',_pCount(12),4,5,500);
  spawnParts(g.x,g.y,'#ff6600',_pCount(20),6,7,650);
  if(settings.screenShake)shake=Math.max(shake,18);
  SFX.minedet();
  const DMG=GRENADE_BLAST_DMG*(P.overchargeMs>0?2:1);
  for(let ei=enemies.length-1;ei>=0;ei--){
    const e=enemies[ei];
    if(dist2(g.x,g.y,e.x,e.y)>GRENADE_BLAST_R*GRENADE_BLAST_R)continue;
    e.hp-=DMG;
    spawnParts(e.x,e.y,e.color,_pCount(8),3,4.5,300);
    if(e.hp<=0){SFX.boom();killEnemy(ei);}
  }
}
function tickGrenades(dt){
  const step=dt*60;
  for(let gi=grenades.length-1;gi>=0;gi--){
    const g=grenades[gi];
    if(g.blasting){g.blastT-=dt*1000;if(g.blastT<=0)grenades.splice(gi,1);continue;}
    g.life-=dt*1000;
    g.x+=g.vx*step;g.y+=g.vy*step;
    if(g.x<6){g.x=6;g.vx=Math.abs(g.vx);g.bounces++;spawnParts(g.x,g.y,'#ffaa22',_pCount(2),1.5,2.5,180);}
    else if(g.x>WORLD_W-6){g.x=WORLD_W-6;g.vx=-Math.abs(g.vx);g.bounces++;spawnParts(g.x,g.y,'#ffaa22',_pCount(2),1.5,2.5,180);}
    else if(g.y<6){g.y=6;g.vy=Math.abs(g.vy);g.bounces++;spawnParts(g.x,g.y,'#ffaa22',_pCount(2),1.5,2.5,180);}
    else if(g.y>WORLD_H-6){g.y=WORLD_H-6;g.vy=-Math.abs(g.vy);g.bounces++;spawnParts(g.x,g.y,'#ffaa22',_pCount(2),1.5,2.5,180);}
    const proxy={x:g.x,y:g.y,vx:g.vx,vy:g.vy,bSz:6};
    if(reflectRicoVsObs(proxy)){
      g.x=proxy.x;g.y=proxy.y;g.vx=proxy.vx;g.vy=proxy.vy;
      g.bounces++;
      spawnParts(g.x,g.y,'#ffaa22',_pCount(3),1.5,2.5,180);
    }
    let detonate=g.bounces>=GRENADE_MAX_BOUNCES||g.life<=0;
    if(!detonate){
      for(const e of enemies){if(dist2(g.x,g.y,e.x,e.y)<GRENADE_PROX_R*GRENADE_PROX_R){detonate=true;break;}}
    }
    if(detonate){_detonateGrenade(gi);}
    else if(Math.random()<0.4){spawnParts(g.x,g.y,'#888844',_pCount(1),0.5,1.5,300);}
  }
}
function drawGrenades(){
  const now=Date.now();
  for(const g of grenades){
    const sx=g.x-camX,sy=g.y-camY;
    if(sx<-80||sx>canvas.width+80||sy<-80||sy>canvas.height+80)continue;
    ctx.save();ctx.translate(sx,sy);
    if(g.blasting){
      const prog=1-(g.blastT/480);
      const br=GRENADE_BLAST_R*prog*1.05;
      ctx.globalAlpha=Math.max(0,(1-prog)*0.72);
      ctx.beginPath();ctx.arc(0,0,br,0,Math.PI*2);
      ctx.fillStyle='rgba(255,140,0,0.28)';ctx.fill();
      ctx.strokeStyle='#ffaa22';ctx.lineWidth=3*(1-prog)+1;
      ctx.shadowBlur=26;ctx.shadowColor='#ff6600';
      ctx.stroke();ctx.shadowBlur=0;ctx.globalAlpha=1;
      ctx.restore();continue;
    }
    const pulse=0.7+0.3*Math.sin(now/120);
    ctx.shadowBlur=12*pulse;ctx.shadowColor='#ffaa22';
    ctx.beginPath();ctx.arc(0,0,6,0,Math.PI*2);
    ctx.fillStyle='#ddaa00';ctx.fill();
    ctx.strokeStyle='#ffcc44';ctx.lineWidth=1.5;ctx.stroke();
    ctx.beginPath();ctx.arc(0,-6,2.5,0,Math.PI*2);
    ctx.fillStyle=`rgba(255,${80+Math.floor(120*pulse)},0,${0.8*pulse})`;ctx.fill();
    ctx.shadowBlur=0;ctx.restore();
  }
}
// ─── /GRENADES ───────────────────────────────────────────────────
function tickGravityWells(dt){
  for(let wi=gravityWells.length-1;wi>=0;wi--){
    const gw=gravityWells[wi];
    if(gw.blasting){gw.blastT-=dt*1000;if(gw.blastT<=0)gravityWells.splice(wi,1);continue;}
    gw.life-=dt*1000;
    if(gw.life<=0){
      gw.blasting=true;gw.blastT=600;
      spawnParts(gw.x,gw.y,'#cc44ff',_pCount(30),7,8,700);
      spawnParts(gw.x,gw.y,'#ffffff',_pCount(10),3,4,400);
      if(settings.screenShake)shake=Math.max(shake,10);
      continue;
    }
    for(let ei=0;ei<enemies.length;ei++){
      const e=enemies[ei];
      const dx=gw.x-e.x,dy=gw.y-e.y;
      const d2=dx*dx+dy*dy;
      if(d2>GRAVWELL_R*GRAVWELL_R)continue;
      const d=Math.sqrt(d2)||1;
      const pullStr=GRAVWELL_PULL*(1-d/GRAVWELL_R)*dt*60;
      e.vx+=(dx/d)*pullStr;
      e.vy+=(dy/d)*pullStr;
      if(d2<GRAVWELL_CRUSH_R*GRAVWELL_CRUSH_R){
        e.hp-=GRAVWELL_DPS*dt;
        if(e.hp<=0){SFX.boom();killEnemy(ei);ei--;}
      }
    }
  }
}
function drawGravityWells(){
  const t=Date.now()/1000;
  for(const gw of gravityWells){
    const sx=gw.x-camX,sy=gw.y-camY;
    if(sx<-260||sx>canvas.width+260||sy<-260||sy>canvas.height+260)continue;
    ctx.save();ctx.translate(sx,sy);
    if(gw.blasting){
      const prog=1-(gw.blastT/600);
      ctx.globalAlpha=Math.max(0,(1-prog)*0.65);
      ctx.beginPath();ctx.arc(0,0,GRAVWELL_R*prog*0.8,0,Math.PI*2);
      ctx.strokeStyle='#cc44ff';ctx.lineWidth=2;ctx.shadowBlur=24;ctx.shadowColor='#cc44ff';ctx.stroke();
      ctx.shadowBlur=0;ctx.globalAlpha=1;ctx.restore();continue;
    }
    const lifeFrac=gw.life/GRAVWELL_LIFE;
    ctx.globalAlpha=0.08+0.04*Math.sin(t*2);
    ctx.beginPath();ctx.arc(0,0,GRAVWELL_R,0,Math.PI*2);
    ctx.strokeStyle='#cc44ff';ctx.lineWidth=1;ctx.stroke();ctx.globalAlpha=1;
    ctx.save();ctx.rotate(t*1.8);
    ctx.beginPath();ctx.ellipse(0,0,GRAVWELL_CRUSH_R*1.4,GRAVWELL_CRUSH_R*0.55,0,0,Math.PI*2);
    ctx.strokeStyle=`rgba(200,68,255,${0.35+0.2*Math.sin(t*3)})`;ctx.lineWidth=1.5;
    ctx.shadowBlur=10;ctx.shadowColor='#cc44ff';ctx.stroke();ctx.shadowBlur=0;
    ctx.restore();
    const coreR=8+4*Math.sin(t*4)*lifeFrac;
    ctx.beginPath();ctx.arc(0,0,coreR,0,Math.PI*2);
    const grad=ctx.createRadialGradient(0,0,0,0,0,coreR);
    grad.addColorStop(0,'rgba(255,255,255,0.9)');
    grad.addColorStop(0.4,'rgba(200,68,255,0.7)');
    grad.addColorStop(1,'rgba(60,0,100,0)');
    ctx.fillStyle=grad;ctx.fill();
    ctx.restore();
  }
}
// ─── /MINES ──────────────────────────────────────────────────────
function tickSeekers(dt,now){
  const step=dt*60;
  for(let si=seekers.length-1;si>=0;si--){
    const s=seekers[si];
    // Blasting phase — countdown then remove
    if(s.blasting){
      s.blastT-=dt*1000;
      if(s.blastT<=0) seekers.splice(si,1);
      continue;
    }
    s.life-=dt*1000;
    if(s.life<=0){seekers.splice(si,1);continue;}

    // Reacquire target if lost
    if(s.target===null||s.target>=enemies.length){
      s.target=null;
      let bestDist=SEEKR_DET;
      for(let i=0;i<enemies.length;i++){
        const d=dist(s.x,s.y,enemies[i].x,enemies[i].y);
        if(d<bestDist){bestDist=d;s.target=i;}
      }
    }

    // Steering — gradually turn toward target
    let desiredAngle;
    if(s.target!==null&&s.target<enemies.length){
      const e=enemies[s.target];
      desiredAngle=Math.atan2(e.y-s.y,e.x-s.x);
    } else {
      // No target — keep current heading
      desiredAngle=Math.atan2(s.vy,s.vx);
    }
    let curAngle=Math.atan2(s.vy,s.vx);
    // Shortest-path angle delta
    let delta=desiredAngle-curAngle;
    while(delta>Math.PI)delta-=Math.PI*2;
    while(delta<-Math.PI)delta+=Math.PI*2;
    curAngle+=Math.sign(delta)*Math.min(Math.abs(delta),SEEKR_TURN);

    // Obstacle avoidance nudge — if the nose is about to hit, deflect perpendicular
    const nose=5;
    const nx=s.x+Math.cos(curAngle)*nose,ny=s.y+Math.sin(curAngle)*nose;
    if(circleVsObs(nx,ny,5)){
      curAngle+=Math.PI*0.35*(Math.random()<0.5?1:-1);
    }

    s.vx=Math.cos(curAngle)*SEEKR_SPD;
    s.vy=Math.sin(curAngle)*SEEKR_SPD;
    s.x+=s.vx*step;
    s.y+=s.vy*step;
    s.x=clamp(s.x,0,WORLD_W);s.y=clamp(s.y,0,WORLD_H);

    // Exhaust trail
    if(Math.random()<0.6) spawnParts(s.x-s.vx*2,s.y-s.vy*2,SEEKR_COL,_pCount(1),0.6,2.5,320);

    // Detonate on enemy contact
    if(s.target!==null&&s.target<enemies.length){
      const e=enemies[s.target];
      if(dist2(s.x,s.y,e.x,e.y)<(e.size+5)**2){
        // 30% of enemy maxHp damage (+20% from 25%)
        const dmg=e.maxHp*0.30;
        e.hp-=dmg;
        spawnParts(s.x,s.y,SEEKR_COL,_pCount(18),5,7,480);
        spawnParts(s.x,s.y,'#ffffff',_pCount(8),3,4,320);
        if(settings.screenShake)shake=8; SFX.seekboom();
        if(e.hp<=0){SFX.boom();killEnemy(s.target);}
        s.blasting=true;s.blastT=380;
      }
    }
  }
}
function drawTractorBeam(){
  const w=WEAPONS[P.weaponIdx];
  const shooting=mouse.down||K['Space'];
  if(w.id!=='tractor'||!shooting||(P.stocks['tractor']||0)<=0||!P.alive) return;
  const TRACTOR_R=320, CONE_HALF=Math.PI*0.22;
  const psx=P.x-camX, psy=P.y-camY;
  const now=Date.now()/1000;
  const pulse=0.55+0.45*Math.sin(now*14);
  // Draw cone sweep
  const coneA1=P.aim-CONE_HALF, coneA2=P.aim+CONE_HALF;
  // Outer glow fill
  ctx.beginPath();
  ctx.moveTo(psx,psy);
  ctx.arc(psx,psy,TRACTOR_R,coneA1,coneA2);
  ctx.closePath();
  ctx.fillStyle=`rgba(68,170,255,${0.06*pulse})`;ctx.fill();
  // Two cone edge lines
  ctx.strokeStyle=`rgba(68,170,255,${0.3*pulse})`;ctx.lineWidth=1.5;ctx.shadowBlur=0;
  ctx.beginPath();ctx.moveTo(psx,psy);ctx.lineTo(psx+Math.cos(coneA1)*TRACTOR_R,psy+Math.sin(coneA1)*TRACTOR_R);ctx.stroke();
  ctx.beginPath();ctx.moveTo(psx,psy);ctx.lineTo(psx+Math.cos(coneA2)*TRACTOR_R,psy+Math.sin(coneA2)*TRACTOR_R);ctx.stroke();
  // Arc edge
  ctx.beginPath();ctx.arc(psx,psy,TRACTOR_R,coneA1,coneA2);
  ctx.strokeStyle=`rgba(68,170,255,${0.2+0.1*pulse})`;ctx.lineWidth=1.2;ctx.stroke();
  // Animated sweep lines (3 radiating from player inside cone)
  for(let li=0;li<3;li++){
    const lAngle=P.aim+(li-1)*CONE_HALF*0.55+Math.sin(now*4+li*2.1)*CONE_HALF*0.3;
    const lLen=TRACTOR_R*(0.5+0.5*Math.sin(now*7+li*1.7));
    ctx.beginPath();ctx.moveTo(psx,psy);
    ctx.lineTo(psx+Math.cos(lAngle)*lLen,psy+Math.sin(lAngle)*lLen);
    ctx.strokeStyle=`rgba(120,200,255,${0.25*pulse})`;ctx.lineWidth=1;ctx.stroke();
  }
  // Beams and rings for enemies inside cone
  const inRange=enemies.filter(e=>{
    if(dist(P.x,P.y,e.x,e.y)>=TRACTOR_R) return false;
    let diff=Math.atan2(e.y-P.y,e.x-P.x)-P.aim;
    while(diff>Math.PI) diff-=Math.PI*2;
    while(diff<-Math.PI) diff+=Math.PI*2;
    return Math.abs(diff)<=CONE_HALF;
  }).sort((a,b)=>dist(P.x,P.y,a.x,a.y)-dist(P.x,P.y,b.x,b.y)).slice(0,2);
  for(const e of inRange){
    const esx=e.x-camX, esy=e.y-camY;
    ctx.beginPath();ctx.moveTo(psx,psy);ctx.lineTo(esx,esy);
    ctx.strokeStyle=`rgba(68,170,255,${0.2*pulse})`;ctx.lineWidth=12;ctx.shadowBlur=0;ctx.stroke();
    ctx.beginPath();ctx.moveTo(psx,psy);ctx.lineTo(esx,esy);
    ctx.strokeStyle=`rgba(120,210,255,${0.5*pulse})`;ctx.lineWidth=3;ctx.stroke();
    ctx.beginPath();ctx.moveTo(psx,psy);ctx.lineTo(esx,esy);
    ctx.strokeStyle=`rgba(220,240,255,${0.75+0.25*pulse})`;ctx.lineWidth=1.5;
    ctx.shadowBlur=12;ctx.shadowColor='#44aaff';ctx.stroke();ctx.shadowBlur=0;
    ctx.beginPath();ctx.arc(esx,esy,e.size*1.8+4*pulse,0,Math.PI*2);
    ctx.strokeStyle=`rgba(68,170,255,${0.6*pulse})`;ctx.lineWidth=2;
    ctx.shadowBlur=10;ctx.shadowColor='#44aaff';ctx.stroke();ctx.shadowBlur=0;
  }
}
function drawSeekers(){
  for(const s of seekers){
    const sx=s.x-camX,sy=s.y-camY;
    if(sx<-60||sx>canvas.width+60||sy<-60||sy>canvas.height+60) continue;
    if(s.blasting){
      const prog=1-(s.blastT/380);
      const br=SEEKR_BLAST_R*prog;
      ctx.save();ctx.translate(sx,sy);
      ctx.globalAlpha=Math.max(0,(1-prog)*0.8);
      ctx.beginPath();ctx.arc(0,0,br,0,Math.PI*2);
      ctx.fillStyle='rgba(255,160,0,0.3)';ctx.fill();
      ctx.strokeStyle=SEEKR_COL;ctx.lineWidth=2*(1-prog)+1;
      ctx.shadowBlur=22;ctx.shadowColor=SEEKR_COL;
      ctx.stroke();ctx.shadowBlur=0;ctx.globalAlpha=1;
      ctx.restore();
      continue;
    }
    const angle=Math.atan2(s.vy,s.vx);
    ctx.save();ctx.translate(sx,sy);ctx.rotate(angle);
    ctx.shadowBlur=14;ctx.shadowColor=SEEKR_COL;
    // Missile body — pointed teardrop
    ctx.beginPath();
    ctx.moveTo(9,0);          // nose
    ctx.lineTo(-4,4);         // rear-right
    ctx.lineTo(-7,0);         // tail
    ctx.lineTo(-4,-4);        // rear-left
    ctx.closePath();
    ctx.fillStyle=SEEKR_COL;ctx.fill();
    // Engine glow at tail
    const glow=0.6+0.4*Math.sin(Date.now()/80);
    ctx.fillStyle=`rgba(255,220,100,${glow*0.9})`;
    ctx.beginPath();ctx.arc(-7,0,3*glow,0,Math.PI*2);ctx.fill();
    ctx.shadowBlur=0;ctx.restore();
  }
}
// ─── BOOMERANGS ───────────────────────────────────────────────────
const BOOMR_SPD=26;
const BOOMR_RETURN_SPD=22.5; // slightly faster on return
function tickBoomerangs(dt){
  const step=dt*60;
  for(let bi=boomerangs.length-1;bi>=0;bi--){
    const b=boomerangs[bi];
    b.rot+=dt*(b.phase==='out'?12:-14); // spin direction flips on return

    if(b.phase==='out'){
      b.x+=b.vx*step; b.y+=b.vy*step;
      // Check: hit screen edge (camera viewport) or obstacle → flip to return
      const sx=b.x-camX, sy=b.y-camY;
      const atEdge = sx<4 || sx>canvas.width-4 || sy<4 || sy>canvas.height-4;
      const hitObs = circleVsObs(b.x,b.y,6);
      if(atEdge||hitObs){
        b.phase='return';
        spawnParts(b.x,b.y,b.color,_pCount(6),2.5,3.5,260);
        // Push back inside world slightly if at obstacle
        if(hitObs){ b.x-=b.vx*step*2; b.y-=b.vy*step*2; }
      }
    } else {
      // Home toward player
      const dx=P.x-b.x, dy=P.y-b.y;
      const d=Math.sqrt(dx*dx+dy*dy)||1;
      b.vx=dx/d*BOOMR_RETURN_SPD; b.vy=dy/d*BOOMR_RETURN_SPD;
      b.x+=b.vx*step; b.y+=b.vy*step;
      // Caught by player → remove
      if(d<P.size+8){ boomerangs.splice(bi,1); continue; }
    }

    // Trail particles on outbound
    if(b.phase==='out'&&Math.random()<0.55)
      spawnParts(b.x,b.y,b.color,_pCount(1),1.2,2,150);

    // Hit enemies in either phase (each enemy only once per throw)
    for(let ei=enemies.length-1;ei>=0;ei--){
      const e=enemies[ei];
      if(b.hitEnemies.has(ei)) continue;
      if(dist2(b.x,b.y,e.x,e.y)<(e.size+6)**2){
        b.hitEnemies.add(ei);
        e.hp-=b.dmg;
        spawnParts(b.x,b.y,b.color,_pCount(8),3,4.5,300);
        if(settings.screenShake)shake=Math.max(shake,6);
        if(e.hp<=0){ SFX.boom(); killEnemy(ei);
          // Remap hit set indices above ei
          const updated=new Set();
          for(const idx of b.hitEnemies) updated.add(idx>ei?idx-1:idx);
          b.hitEnemies=updated;
        }
      }
    }
  }
}
function drawBoomerangs(){
  for(const b of boomerangs){
    const sx=b.x-camX, sy=b.y-camY;
    if(sx<-40||sx>canvas.width+40||sy<-40||sy>canvas.height+40) continue;
    ctx.save(); ctx.translate(sx,sy); ctx.rotate(b.rot);
    ctx.shadowBlur=16; ctx.shadowColor=b.color;
    // Slender blade — elongated thin ellipse
    ctx.beginPath(); ctx.ellipse(0,0,18,3.5,0,0,Math.PI*2);
    ctx.fillStyle=b.color; ctx.fill();
    // Bright white edge highlight
    ctx.beginPath(); ctx.ellipse(0,0,18,1.5,0,0,Math.PI*2);
    ctx.fillStyle='rgba(255,255,255,0.75)'; ctx.fill();
    // Phase indicator dot at centre
    ctx.beginPath(); ctx.arc(0,0,3,0,Math.PI*2);
    ctx.fillStyle=b.phase==='out'?'#ffffff':b.color; ctx.fill();
    ctx.shadowBlur=0; ctx.restore();
  }
}
// ─── /BOOMERANGS ─────────────────────────────────────────────────

// ─── FRACTAL FUSION ──────────────────────────────────────────────
function tickFractals(dt){
  for(let i=fractals.length-1;i>=0;i--){
    const f=fractals[i];
    f.life-=dt*1000;
    if(f.life<=0){fractals.splice(i,1);continue;}
    // Move origin with inherited ship velocity — tree travels with craft
    f.ox+=f.vx*dt*60;
    f.oy+=f.vy*dt*60;
    // Dampen inherited velocity toward zero (tree decelerates naturally)
    f.vx*=0.94; f.vy*=0.94;
    // Continuous hit detection — check all segment endpoints in world space
    for(const seg of f.segs){
      const wx=f.ox+seg.x2, wy=f.oy+seg.y2;
      for(let ei=enemies.length-1;ei>=0;ei--){
        if(f.hitSet.has(ei)) continue;
        if(dist2(wx,wy,enemies[ei].x,enemies[ei].y)<38*38){
          f.hitSet.add(ei);
          enemies[ei].hp-=f.dmg;
          spawnParts(enemies[ei].x,enemies[ei].y,'#ff9900',_pCount(4),2,3.5,220);
          if(enemies[ei].hp<=0){SFX.boom();killEnemy(ei);}
        }
      }
    }
  }
}
function drawFractals(){
  for(const f of fractals){
    const pct=f.life/f.maxLife;            // 1→0
    const alpha=Math.min(1, pct * 2.5);   // fast fade
    const reveal=1-pct;                    // 0→1 progressive reveal
    const vibrating=f.life<130;            // final 130ms: vibration
    for(const seg of f.segs){
      const genDelay=seg.gen*0.14;
      const segAlpha=Math.max(0, Math.min(1,(reveal-genDelay)*5));
      if(segAlpha<=0) continue;
      // Vibration: jitter endpoint positions
      let vib=0;
      if(vibrating) vib=(Math.random()-0.5)*(1-pct/0.186)*7;
      const sx1=f.ox+seg.x1-camX, sy1=f.oy+seg.y1-camY;
      const sx2=f.ox+seg.x2+vib-camX, sy2=f.oy+seg.y2+vib-camY;
      const W=seg.gen===0?3.2:seg.gen===1?2.5:seg.gen===2?1.8:seg.gen===3?1.2:0.7;
      ctx.save();
      ctx.globalAlpha=segAlpha*alpha;
      ctx.lineCap='round';
      // Outer glow — wider and brighter
      ctx.beginPath(); ctx.moveTo(sx1,sy1); ctx.lineTo(sx2,sy2);
      ctx.strokeStyle='#ff6600'; ctx.lineWidth=W+4;
      ctx.shadowBlur=22; ctx.shadowColor='#ffaa00';
      ctx.stroke(); ctx.shadowBlur=0;
      // Mid layer
      ctx.beginPath(); ctx.moveTo(sx1,sy1); ctx.lineTo(sx2,sy2);
      ctx.strokeStyle='#ffcc44'; ctx.lineWidth=W+1;
      ctx.stroke();
      // Bright core
      ctx.beginPath(); ctx.moveTo(sx1,sy1); ctx.lineTo(sx2,sy2);
      ctx.strokeStyle=seg.gen===0?'#ffffff':seg.gen===1?'#ffffaa':seg.gen===2?'#ffee66':seg.gen===3?'#ffcc44':'#ff9900';
      ctx.lineWidth=W*0.45;
      ctx.stroke();
      ctx.restore();
    }
  }
}

function checkCollisions(){
  outer:for(let bi=pBullets.length-1;bi>=0;bi--){
    const b=pBullets[bi];
    for(let ei=enemies.length-1;ei>=0;ei--){
      const e=enemies[ei];
      // infected enemies can still be shot and killed by the player
      if(dist2(b.x,b.y,e.x,e.y)<e.size*e.size*1.21){
        if(b.isGrapple){
          e.anchorX=b.x;
          e.anchorY=b.y;
          const anchDmg=e.maxHp*0.2;
          e.hp-=anchDmg;
          spawnParts(b.x,b.y,'#44ddff',_pCount(10),3,4.5,350);
          spawnParts(b.x,b.y,'#ffffff',_pCount(5),2,3,250);
          if(e.hp<=0){SFX.boom();killEnemy(ei);}
          pBullets.splice(bi,1);
          continue outer;
        }
        if(b.stun){
          e.stunMoveMs=Math.max(e.stunMoveMs,1000);
          e.stunFireMs=Math.max(e.stunFireMs,2000);
          e.vx*=0.1;e.vy*=0.1;
          spawnParts(b.x,b.y,'#aaff44',_pCount(10),3,4,280);
          spawnParts(b.x,b.y,'#ffffff',_pCount(5),2,3,200);
        } else {
          score+=10;
          const dmgMult=(e.type==='dreadnought'&&e.phase===2)?2.0:1.0;
          e.hp-=b.dmg*dmgMult; spawnParts(b.x,b.y,e.color,_pCount(6),2.8,3.5,230);
          // Alert: enemy detects player on hit regardless of range (except turret stays put)
          if(e.type!=='turret'&&e.state==='patrol'){
            e.state=dist(P.x,P.y,e.x,e.y)<e.atk?'attack':'chase';
          } else if(e.type==='turret'){
            e.state='attack'; // turret snaps to attack mode instantly
          }
          // Immediate retaliatory shot (with small random spread, not while stunned)
          if(e.stunMs<=0&&e.stunFireMs<=0&&P.alive&&e.type!=='phantom'){
            const retSpread=(Math.random()-0.5)*0.22;
            fireEBullet(e.x,e.y,e.aim+retSpread,7.5,e.dmg);
            e.lastFired=performance.now(); // reset fire cooldown
          }
          if(e.hp<=0){
            SFX.boom();
            // Digital Infection: spread on kill
            if(b.dinf) _spreadInfection(e.type, ei);
            else killEnemy(ei);
          }
        }
        pBullets.splice(bi,1); continue outer;
      }
    }
  }
  if(P.iframes<=0&&P.alive){
    for(let bi=eBullets.length-1;bi>=0;bi--){
      const b=eBullets[bi];
      // miniMe intercepts bullets (checked before player)
      if(miniMe.active&&miniMe.iframes<=0&&dist2(b.x,b.y,miniMe.x,miniMe.y)<MM_SIZE*MM_SIZE){
        miniMe.hp-=b.dmg;miniMe.iframes=500;
        spawnParts(b.x,b.y,MM_COL,_pCount(8),2.5,3.5,260);
        eBullets.splice(bi,1);
        if(miniMe.hp<=0){
          miniMe.active=false;miniMe.lost=true;
          spawnParts(miniMe.x,miniMe.y,MM_COL,_pCount(20),4.5,6,700);
          spawnParts(miniMe.x,miniMe.y,'#ffffff',_pCount(8),3,4,400);
          if(settings.screenShake)shake=10;SFX.mmdead();
        }
        continue;
      }
      // Carrier drones intercept enemy bullets
      if(carrierDrones.length){
        let droneHit=false;
        for(let d=0;d<carrierDrones.length;d++){
          const dr=carrierDrones[d];
          if(dr.hp<=0) continue;
          if(dist2(b.x,b.y,dr.x,dr.y)<10*10){
            dr.hp-=b.dmg;
            spawnParts(b.x,b.y,'#00aaff',_pCount(5),2,3,200);
            eBullets.splice(bi,1);
            if(dr.hp<=0){dr.hp=0;dr.respawnMs=10000;spawnParts(dr.x,dr.y,'#00aaff',_pCount(12),3,5,500);if(settings.screenShake)shake=6;}
            droneHit=true;break;
          }
        }
        if(droneHit) continue;
      }
      const hitR=P.size+(b.isBrute?b.bSz*0.6:0);
      if(b.fromInfected) continue; // infected ally bullets never hurt player
      if(b.isBomb) continue; // bombs detonate via tickBullets, not player collision
      if(dist2(b.x,b.y,P.x,P.y)<hitR*hitR){
        // Invincibility: deflect bullet back outward
        if(P.invincMs>0){
          const ang=Math.atan2(b.y-P.y,b.x-P.x);
          b.vx=Math.cos(ang)*Math.sqrt(b.vx*b.vx+b.vy*b.vy);
          b.vy=Math.sin(ang)*Math.sqrt(b.vx*b.vx+b.vy*b.vy);
          spawnParts(b.x,b.y,'#ffffff',_pCount(5),2.5,3.5,180);
          continue;
        }
        if(P.shieldMs>0){P.shieldMs=0;if(settings.screenShake)shake=9;spawnParts(P.x,P.y,'#44aaff',_pCount(24),5,6,500);SFX.shbreak();P.iframes=400;}
        else{const dmg=b.dmg*P.damageMult;P.hp-=dmg;P.iframes=700;if(settings.screenShake)shake=16;SFX.hit();Music.onHit();spawnParts(b.x,b.y,'#00eeff',_pCount(10),3,4,380);if(P.hp<=0)P.alive=false;}
        eBullets.splice(bi,1);
      }
    }
  }
  for(let pi=pickups.length-1;pi>=0;pi--){
    const pk=pickups[pi];
    const hitR=(pk.hidden?36:48)**2;
    const playerHit=dist2(pk.x,pk.y,P.x,P.y)<hitR;
    const jrHit=miniMe.active&&dist2(pk.x,pk.y,miniMe.x,miniMe.y)<(MM_SIZE+18)**2;
    if(playerHit||jrHit){
      if(jrHit&&!playerHit) spawnParts(pk.x,pk.y,'#44ffcc',_pCount(8),2.5,4,300); // J R pickup sparkle
      switch(pk.type){
        case'battery':P.bat=Math.min(P.maxBat,P.bat+58);score+=50;spawnParts(pk.x,pk.y,'#00ff88',_pCount(14),3.5,5,420);SFX.pickup();weaponFlash={prefix:'COLLECTED',name:'BATTERY PACK +58',ms:1400};break;
        case'health':P.hp=Math.min(P.maxHp,P.hp+48);if(miniMe.active)miniMe.hp=Math.min(MM_HP,miniMe.hp+24);score+=50;spawnParts(pk.x,pk.y,'#ff4466',_pCount(14),3.5,5,420);SFX.pickup();weaponFlash={prefix:'COLLECTED',name:'CRAFT REPAIR +48',ms:1400};break;
        case'medkit':P.hp=Math.min(P.maxHp,P.hp+22);if(miniMe.active)miniMe.hp=Math.min(MM_HP,miniMe.hp+11);score+=30;spawnParts(pk.x,pk.y,'#44ffdd',_pCount(12),3,4.5,380);SFX.pickup();weaponFlash={prefix:'COLLECTED',name:'MED KIT +22',ms:1400};break;
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
              weaponFlash={prefix:'UNLOCKED',name:`${WEAPONS[next].name} - ARSENAL FULL`,ms:3000};
            }
            score+=100;
            spawnParts(pk.x,pk.y,WEAPONS[next].color,_pCount(22),5,7,550); SFX.weapon();
            if(WEAPONS[next].id==='mine') P.mineStock=enemies.length;
            if(WEAPONS[next].id==='seekr'){const half=Math.ceil(enemies.length/2);P.seekStock=half+Math.floor(Math.random()*(enemies.length*3-half+1));}
          } else {
            // All weapons unlocked — refill +10 ammo for the first depleted stocked weapon
            const depleted=[...P.unlockedW].filter(idx=>{
              const w=WEAPONS[idx];
              if(w.id==='mine') return P.mineStock<=0;
              if(w.id==='seekr') return P.seekStock<=0;
              return w.stock!==null&&(P.stocks[w.id]||0)<=0;
            });
            if(depleted.length>0){
              const idx=depleted[Math.floor(Math.random()*depleted.length)];
              const w=WEAPONS[idx];
              if(w.id==='mine') P.mineStock+=10;
              else if(w.id==='seekr') P.seekStock+=10;
              else P.stocks[w.id]=(P.stocks[w.id]||0)+10;
              weaponFlash={prefix:'COLLECTED',name:`AMMO +10 ${w.name}`,ms:2200};
              spawnParts(pk.x,pk.y,w.color,_pCount(14),3.5,5,380); SFX.weapon();
            } else {
              // Nothing depleted — fall back to battery
              P.bat=Math.min(P.maxBat,P.bat+40); SFX.pickup();
            }
          }
          break;
        case'shield':P.shieldMs=4800;score+=10;spawnParts(pk.x,pk.y,'#44aaff',_pCount(18),4,6,480);SFX.shield();break;
        case'emp':triggerEMP();break;
        case'overcharge':P.overchargeMs=7000;P.bat=Math.min(P.maxBat,P.bat+50);spawnParts(pk.x,pk.y,'#ff9900',_pCount(22),5.5,7.5,580);SFX.overchg();break;
        case'points':score+=250;spawnParts(pk.x,pk.y,'#ffd700',_pCount(18),4,6,480);SFX.pickup();break;
        case'ammo':{
          const w=WEAPONS.find(w2=>w2.id===pk.weaponId);
          if(w){
            if(pk.weaponId==='mine') P.mineStock+=pk.ammoAmt;
            else if(pk.weaponId==='seekr') P.seekStock+=pk.ammoAmt;
            else P.stocks[pk.weaponId]=(P.stocks[pk.weaponId]||0)+pk.ammoAmt;
            weaponFlash={prefix:'COLLECTED',name:`AMMO +${pk.ammoAmt} ${pk.weaponName}`,ms:2400};
            spawnParts(pk.x,pk.y,pk.weaponColor||'#ccddff',_pCount(16),4,5.5,480);SFX.weapon();
          }
          break;
        }
        case'invincibility':
          P.invincMs=5000;
          spawnParts(pk.x,pk.y,'#ffffff',_pCount(28),5,7,600);
          weaponFlash={prefix:'COLLECTED',name:'INVINCIBLE 5s',ms:2400};
          SFX.shield();break;
        case'cloak':
          P.cloakMs=5000;
          spawnParts(pk.x,pk.y,'#88ffee',_pCount(24),4,6,550);
          weaponFlash={prefix:'COLLECTED',name:'CLOAK 5s',ms:2400};
          SFX.shield();break;
        case'portal':
          _activatePortal(pk.x,pk.y);
          spawnParts(pk.x,pk.y,'#ff8800',_pCount(22),5,7,500);
          SFX.emp();break;
        case'nuke_key':
          P.nukeKeys.add(pk.bombId);
          weaponFlash={prefix:'COLLECTED',name:`${NUKE_NAMES[pk.bombId]} KEY`,ms:2800};
          spawnParts(pk.x,pk.y,NUKE_COLORS[pk.bombId],_pCount(20),4,6,500);SFX.weapon();break;
      }
      pickups.splice(pi,1);
    }
  }
}

// ─── WORLD ───────────────────────────────────────────────────────
function drawWorld(){
  ctx.fillStyle='#060c18';ctx.fillRect(0,0,canvas.width,canvas.height);
  const gs=85,ox=camX%gs,oy=camY%gs;ctx.strokeStyle='rgba(0,140,210,0.1)';ctx.lineWidth=1;
  for(let x=-ox;x<canvas.width+gs;x+=gs){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x,canvas.height);ctx.stroke();}
  for(let y=-oy;y<canvas.height+gs;y+=gs){ctx.beginPath();ctx.moveTo(0,y);ctx.lineTo(canvas.width,y);ctx.stroke();}
  ctx.shadowBlur=14;ctx.shadowColor='#0099ff';ctx.strokeStyle='rgba(0,180,255,0.35)';ctx.lineWidth=3;ctx.strokeRect(-camX,-camY,WORLD_W,WORLD_H);ctx.shadowBlur=0;
}

// ─── HUD ─────────────────────────────────────────────────────────
function hudBar(x,y,w,h,val,max,color,label){
  ctx.fillStyle='rgba(0,0,0,0.55)';ctx.fillRect(x,y,w,h);ctx.fillStyle='#0a111e';ctx.fillRect(x+1,y+1,w-2,h-2);
  ctx.fillStyle=color;ctx.shadowBlur=8;ctx.shadowColor=color;ctx.fillRect(x+1,y+1,(w-2)*clamp(val/max,0,1),h-2);ctx.shadowBlur=0;
  ctx.fillStyle='rgba(200,230,255,0.85)';ctx.font=`${IS_TOUCH?'8':'10'}px "Courier New"`;ctx.textAlign='left';ctx.fillText(label,x+4,y+h-2);
}
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
      ctx.font=`bold ${T?8:10}px "Courier New"`;
      ctx.fillStyle=P.mineStock>0?(act?'#ff2200':'rgba(180,60,30,0.8)'):'rgba(100,50,40,0.6)';
      ctx.fillText(`×${P.mineStock}`,x+bw/2,by+bh-(T?2:4));
    } else if(w.id==='seekr'){
      ctx.font=`bold ${T?8:10}px "Courier New"`;
      ctx.fillStyle=P.seekStock>0?(act?SEEKR_COL:'rgba(180,130,30,0.8)'):'rgba(100,50,40,0.6)';
      ctx.fillText(`×${P.seekStock}`,x+bw/2,by+bh-(T?2:4));
    } else if(w.id==='tractor'){
      const secLeft=(P.stocks['tractor']||0)/1000;
      ctx.font=`bold ${T?8:10}px "Courier New"`;
      ctx.fillStyle=secLeft>0?(act?'#44aaff':'rgba(60,130,200,0.8)'):'rgba(60,80,120,0.5)';
      ctx.fillText(secLeft>0?`${Math.ceil(secLeft)}s`:'OUT',x+bw/2,by+bh-(T?2:4));
    } else if(wStock!==null){
      ctx.font=`bold ${T?8:10}px "Courier New"`;
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
      ctx.font=`bold ${T?8:10}px "Courier New"`;ctx.fillStyle='rgba(200,60,60,0.8)';
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
  ctx.fillText(cw.name+mineLabel+seekrLabel+mmLabel+stockLabel,canvas.width/2,by-(T?8:14));ctx.shadowBlur=0;
  if(weaponFlash.ms>0){
    const a=Math.min(1,weaponFlash.ms/700);ctx.globalAlpha=a;
    ctx.font=`bold ${T?11:16}px "Courier New"`;ctx.fillStyle=cw.color;ctx.shadowBlur=T?14:24;ctx.shadowColor=cw.color;
    ctx.fillText(`${weaponFlash.prefix??'⬆ WEAPON:'} ${weaponFlash.name}`,canvas.width/2,by-(T?22:38));ctx.shadowBlur=0;ctx.globalAlpha=1;
  }
  ctx.textAlign='left';
}
function drawHUD(){
  const T=IS_TOUCH;
  const pad=T?10:18;
  const hpPct=P.hp/P.maxHp;
  // Bars: narrower and shorter on touch
  const bW=T?130:216, bH=T?12:20, bGap=T?14:25;
  hudBar(pad,pad,bW,bH,P.hp,P.maxHp,hpPct>0.5?'#22ee88':hpPct>0.25?'#ffaa00':'#ff3333',`CRAFT ${Math.ceil(P.hp)}%`);
  hudBar(pad,pad+bGap,bW,bH,P.bat,P.maxBat,P.bat>25?'#ffee00':'#ff5500',`BATT ${Math.ceil(P.bat)}%`);
  // Craft badge
  const c=CRAFTS[P.craftIdx];
  const badgeSz=T?8:10, badgeY=T?pad+34:pad+62;
  ctx.fillStyle=P.color;ctx.shadowBlur=6;ctx.shadowColor=P.color;
  ctx.font=`bold ${badgeSz}px "Courier New"`;ctx.textAlign='left';
  ctx.fillText(`${c.name} · ${c.sub}`,pad,badgeY);ctx.shadowBlur=0;
  let sRow=T?pad+44:pad+76;
  const statusSz=T?8:11, statusGap=T?12:16;
  if(P.shieldMs>0){ctx.fillStyle='#44aaff';ctx.shadowBlur=8;ctx.shadowColor='#44aaff';ctx.font=`bold ${statusSz}px "Courier New"`;ctx.fillText(`SHIELD ${(P.shieldMs/1000).toFixed(1)}s`,pad,sRow);sRow+=statusGap;ctx.shadowBlur=0;}
  if(P.overchargeMs>0){ctx.fillStyle='#ff9900';ctx.shadowBlur=8;ctx.shadowColor='#ff9900';ctx.font=`bold ${statusSz}px "Courier New"`;ctx.fillText(`OVR ${(P.overchargeMs/1000).toFixed(1)}s`,pad,sRow);sRow+=statusGap;ctx.shadowBlur=0;}
  if(P.invincMs>0){ctx.fillStyle='#ffffff';ctx.shadowBlur=10;ctx.shadowColor='#aaddff';ctx.font=`bold ${statusSz}px "Courier New"`;ctx.fillText(`INVINCIBLE ${(P.invincMs/1000).toFixed(1)}s`,pad,sRow);sRow+=statusGap;ctx.shadowBlur=0;}
  if(P.cloakMs>0){ctx.fillStyle='#88ffee';ctx.shadowBlur=8;ctx.shadowColor='#44ffcc';ctx.font=`bold ${statusSz}px "Courier New"`;ctx.fillText(`CLOAKED ${(P.cloakMs/1000).toFixed(1)}s`,pad,sRow);sRow+=statusGap;ctx.shadowBlur=0;}
  if(CRAFTS[P.craftIdx].id==='sniper'&&deadEyeMs>0){
    const mult=Math.min(3.0,1.0+(deadEyeMs/2000)*2.0);
    const col=mult>=2.8?'#ff4444':mult>=2.0?'#ffaa00':'#44ffcc';
    ctx.fillStyle=col;ctx.shadowBlur=8;ctx.shadowColor=col;
    ctx.font=`bold ${statusSz}px "Courier New"`;
    ctx.fillText(`DEAD EYE  ×${mult.toFixed(1)}`,pad,sRow);sRow+=statusGap;ctx.shadowBlur=0;
  }
  if(!T){ctx.font='11px "Courier New"';ctx.fillStyle='rgba(90,140,200,0.7)';ctx.fillText(`KILLS: ${P.kills}  |  WEAPONS: ${P.unlockedW.size}/${WEAPONS.length}`,pad,sRow);}
  // Score — label on top, number below, flush top-right
  const {scoreY,labelY}=trLayout();
  ctx.textAlign='right';
  const scoreX=canvas.width-14;
  ctx.font=`${T?9:12}px "Courier New"`;ctx.fillStyle='rgba(140,100,30,0.75)';
  ctx.fillText('SCORE',scoreX,T?labelY+8:labelY+10);
  if(T){
    ctx.font='bold 18px "Courier New"';ctx.fillStyle='#ffdd00';ctx.shadowBlur=12;ctx.shadowColor='#ffaa00';
    ctx.fillText(String(score).padStart(8,'0'),scoreX,scoreY+20);ctx.shadowBlur=0;
  } else {
    ctx.font='bold 32px "Courier New"';ctx.fillStyle='#ffdd00';ctx.shadowBlur=18;ctx.shadowColor='#ffaa00';
    ctx.fillText(String(score).padStart(8,'0'),scoreX,scoreY+26);ctx.shadowBlur=0;
  }
  // Wave counter (battle) or TT timer or CT round indicator
  ctx.textAlign='center';
  if(gameMode==='combattraining'){
    drawCTHUD();
  } else if(gameMode==='timetrial'){
    if(ttLevel===2){
      drawNukeHUD();
    } else if(ttLevel===4){
      drawJRRHUD();
    } else if(ttLevel===5){
      drawTNGHUD();
    } else {
      // Level 1 — Ghost Run timer + distance
      const ms=ttFinished?ttElapsed:(performance.now()-ttStartTime);
      const timeStr=formatTTTime(ms);
      ctx.font=`bold ${T?15:22}px "Courier New"`;
      ctx.fillStyle='#00ccff';ctx.shadowBlur=14;ctx.shadowColor='#00aaff';
      ctx.fillText(timeStr,canvas.width/2,pad+(T?14:22));ctx.shadowBlur=0;
      const distM=Math.max(0,(ttLevel===3?DBD_FINISH_X:TT_FINISH_X)-P.x);
      ctx.font=`${T?8:11}px "Courier New"`;
      ctx.fillStyle=distM<1000?'#ffdd44':'rgba(100,180,255,0.7)';
      ctx.fillText(`▶▶ ${Math.round(distM)}m TO FINISH`,canvas.width/2,pad+(T?26:40));
    }
  } else if(T){
    ctx.font='bold 11px "Courier New"';ctx.fillStyle='#00ccff';ctx.shadowBlur=8;ctx.shadowColor='#00ccff';
    ctx.fillText(`W${wave}/${TOTAL_WAVES}`,canvas.width/2,pad+14);ctx.shadowBlur=0;
    ctx.font='9px "Courier New"';ctx.fillStyle=enemies.length>0?'#ff6666':'#00ff88';
    ctx.fillText(`${enemies.length}✕`,canvas.width/2,pad+25);
  } else {
    ctx.font='bold 16px "Courier New"';ctx.fillStyle='#00ccff';ctx.shadowBlur=12;ctx.shadowColor='#00ccff';
    ctx.fillText(`WAVE ${wave} / ${TOTAL_WAVES}`,canvas.width/2,pad+20);ctx.shadowBlur=0;
    ctx.font='12px "Courier New"';ctx.fillStyle=enemies.length>0?'#ff6666':'#00ff88';
    ctx.fillText(`${enemies.length} HOSTILE${enemies.length!==1?'S':''}`,canvas.width/2,pad+38);
  }
  // Warnings
  const warnY=canvas.height-(T?145:102), hullY=canvas.height-(T?158:120);
  if(P.bat<22&&Math.floor(Date.now()/280)%2===0){ctx.font=`bold ${T?11:16}px "Courier New"`;ctx.fillStyle='#ff6600';ctx.shadowBlur=18;ctx.shadowColor='#ff4400';ctx.fillText(T?'⚡ LOW BATTERY':'⚡ LOW BATTERY — FIND POWER CELL',canvas.width/2,warnY);ctx.shadowBlur=0;}
  if(P.hp<25&&Math.floor(Date.now()/220)%2===0){ctx.font=`bold ${T?10:14}px "Courier New"`;ctx.fillStyle='#ff2244';ctx.shadowBlur=16;ctx.shadowColor='#ff0033';ctx.fillText('⚠ CRITICAL CRAFT DAMAGE',canvas.width/2,hullY);ctx.shadowBlur=0;}
  // Controls hint
  const hintDuration=T?14000:9000;
  if(Date.now()-gameStartTime<hintDuration){
    ctx.font='9px "Courier New"';ctx.fillStyle='rgba(100,160,215,0.5)';
    const hy=T?weaponBarY()-14:canvas.height-14;
    ctx.fillText(T?'L·Move  R·Aim&Fire  Tap·Switch':'WASD·Move  Mouse·Aim  F·Rotate  Click/Space·Fire  Shift·Boost  |  1-6/Q/E·Weapon',canvas.width/2,hy);
  }
  ctx.textAlign='left';drawWeaponBar();
  drawPauseBtn();drawMuteBtn();
}
function pauseBtnRect(){
  const{pbX,pbY,pbW,pbH}=mlLayout();
  return{x:pbX,y:pbY,w:pbW,h:pbH};
}
function muteBtnRect(){
  const{muteX,muteY,muteW,muteH}=mlLayout();
  return{x:muteX,y:muteY,w:muteW,h:muteH};
}
function drawPauseBtn(){
  const{x,y,w,h}=pauseBtnRect();
  const hov=mouse.x>x&&mouse.x<x+w&&mouse.y>y&&mouse.y<y+h;
  ctx.fillStyle=hov?'rgba(0,180,255,0.18)':'rgba(0,0,0,0.45)';
  roundRect(ctx,x,y,w,h,6);ctx.fill();
  ctx.strokeStyle=hov?'rgba(0,200,255,0.7)':'rgba(0,120,200,0.35)';
  ctx.lineWidth=1.3; roundRect(ctx,x,y,w,h,6); ctx.stroke();
  // Two pause bars
  const bx=x+Math.round(w*0.28),bw2=Math.max(4,Math.round(w*0.14)),bh2=Math.round(h*0.55),by2=y+Math.round(h*0.22);
  ctx.fillStyle=hov?'#00ddff':'rgba(100,170,230,0.75)';
  ctx.fillRect(bx,by2,bw2,bh2);
  ctx.fillRect(bx+bw2+3,by2,bw2,bh2);
}
function drawMuteBtn(){
  const{x,y,w,h}=muteBtnRect();
  const hov=mouse.x>x&&mouse.x<x+w&&mouse.y>y&&mouse.y<y+h;
  const muted=Music.isMuted();
  ctx.fillStyle=hov?(muted?'rgba(255,100,50,0.18)':'rgba(0,180,255,0.18)'):'rgba(0,0,0,0.45)';
  roundRect(ctx,x,y,w,h,6);ctx.fill();
  ctx.strokeStyle=muted?(hov?'rgba(255,120,60,0.9)':'rgba(180,60,30,0.55)'):(hov?'rgba(0,200,255,0.7)':'rgba(0,120,200,0.35)');
  ctx.lineWidth=1.3; roundRect(ctx,x,y,w,h,6); ctx.stroke();
  // Speaker icon
  const cx2=x+w/2,cy2=y+h/2;
  ctx.fillStyle=muted?'rgba(220,80,50,0.85)':'rgba(100,170,230,0.75)';
  ctx.save();ctx.translate(cx2,cy2);
  // Speaker body
  ctx.fillRect(-5,-3,5,6);
  ctx.beginPath();ctx.moveTo(0,-5);ctx.lineTo(5,-2);ctx.lineTo(5,2);ctx.lineTo(0,5);ctx.closePath();ctx.fill();
  if(!muted){
    // Sound waves
    ctx.strokeStyle='rgba(100,170,230,0.75)';ctx.lineWidth=1.2;
    ctx.beginPath();ctx.arc(5,0,4,-(Math.PI*0.5),Math.PI*0.5);ctx.stroke();
  } else {
    // X mark
    ctx.strokeStyle='rgba(220,80,50,0.9)';ctx.lineWidth=1.5;
    ctx.beginPath();ctx.moveTo(7,-4);ctx.lineTo(11,4);ctx.stroke();
    ctx.beginPath();ctx.moveTo(11,-4);ctx.lineTo(7,4);ctx.stroke();
  }
  ctx.restore();
}
function drawPauseScreen(){
  // Frosted dark overlay
  ctx.fillStyle='rgba(4,10,26,0.72)';
  ctx.fillRect(0,0,canvas.width,canvas.height);

  // Scanline texture
  for(let sy=0;sy<canvas.height;sy+=4){
    ctx.fillStyle='rgba(0,0,0,0.12)';
    ctx.fillRect(0,sy,canvas.width,2);
  }

  const cx=canvas.width/2, cy=canvas.height/2;
  const now=Date.now()/1000;

  // Outer ring
  ctx.beginPath(); ctx.arc(cx,cy,118,0,Math.PI*2);
  ctx.strokeStyle='rgba(0,180,255,0.12)'; ctx.lineWidth=1; ctx.stroke();
  ctx.beginPath(); ctx.arc(cx,cy,120,0,Math.PI*2);
  ctx.strokeStyle='rgba(0,180,255,0.22)'; ctx.lineWidth=2; ctx.stroke();

  // PAUSED title
  ctx.textAlign='center';
  ctx.font='bold 68px "Courier New"';
  ctx.shadowBlur=45; ctx.shadowColor='#00aaff'; ctx.fillStyle='#00ccff';
  ctx.fillText('PAUSED',cx,cy-28);
  ctx.shadowBlur=0;

  // Subtitle
  ctx.font='12px "Courier New"';
  ctx.fillStyle='rgba(80,140,200,0.65)';
  ctx.fillText(`WAVE ${wave} / ${TOTAL_WAVES}  ·  SCORE  ${String(score).padStart(8,'0')}  ·  KILLS ${P.kills}`,cx,cy+16);

  // Resume button
  const bw=290,bh=50,bx=cx-bw/2,by=cy+48;
  const bhov=mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh;
  ctx.shadowBlur=bhov?30:14; ctx.shadowColor='#00ff88';
  ctx.fillStyle=bhov?'#00ff88':'rgba(0,0,0,0.75)';
  roundRect(ctx,bx,by,bw,bh,10); ctx.fill();
  ctx.strokeStyle='#00ff88'; ctx.lineWidth=2.2;
  roundRect(ctx,bx,by,bw,bh,10); ctx.stroke();
  ctx.shadowBlur=0;
  ctx.font='bold 16px "Courier New"';
  ctx.fillStyle=bhov?'#000':'#00ff88';
  ctx.fillText('▶  RETURN TO FLIGHT',cx,by+32);

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
  ctx.fillText('MODIFY WEAPONS',cx,my2+mh2/2+5);

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

  ctx.textAlign='left';
}
function drawLoadoutEdit(){
  const cx=canvas.width/2,W=canvas.width,H=canvas.height;
  const isHangar=loadoutEditFrom==='hangar';
  const c=isHangar?CRAFTS[hangarCraft]:CRAFTS[P.craftIdx];
  const lo=isHangar?hangarLoadout:P.loadout;
  ctx.fillStyle='rgba(4,10,26,0.88)';ctx.fillRect(0,0,W,H);
  ctx.textAlign='center';
  ctx.font='bold 24px "Courier New"';ctx.fillStyle='#00ccff';ctx.shadowBlur=20;ctx.shadowColor='#00aaff';
  ctx.fillText('WEAPONS ARSENAL',cx,50);ctx.shadowBlur=0;
  ctx.font='12px "Courier New"';ctx.fillStyle='rgba(100,180,255,0.7)';
  ctx.fillText(`${c.name}  —  ${lo.length}/${c.maxSlots} SLOTS`,cx,72);
  const GLYPHS=['•','►','»','↩','∿','↯','|','↪','⊙','‖','⊸','◈','◎','⊞','⊛','⇝','⬆','⊕','⌬','◉','⊗','≋','※'];
  const cardW=140,cardH=38,cardGap=8;
  ctx.font='bold 12px "Courier New"';ctx.fillStyle='rgba(0,255,136,0.7)';
  ctx.fillText('▼  LOADED',cx,100);
  const loadedY=114;
  const loadedTotalW=c.maxSlots*(cardW+cardGap)-cardGap;
  const loadedStartX=cx-loadedTotalW/2;
  for(let i=0;i<c.maxSlots;i++){
    const x=loadedStartX+i*(cardW+cardGap),y=loadedY;
    if(i<lo.length){
      const wIdx=lo[i],w=WEAPONS[wIdx];
      const hov=mouse.x>x&&mouse.x<x+cardW&&mouse.y>y&&mouse.y<y+cardH;
      ctx.fillStyle=hov?'rgba(0,80,40,0.7)':'rgba(0,40,20,0.5)';
      roundRect(ctx,x,y,cardW,cardH,6);ctx.fill();
      ctx.strokeStyle=hov?'#00ff88':w.color;ctx.lineWidth=hov?2:1;
      roundRect(ctx,x,y,cardW,cardH,6);ctx.stroke();
      ctx.font='14px "Courier New"';ctx.fillStyle=w.color;
      ctx.textAlign='center';ctx.fillText(GLYPHS[wIdx]||'?',x+16,y+cardH/2+5);
      ctx.font='9px "Courier New"';ctx.fillStyle=hov?'#ffffff':'rgba(180,220,255,0.85)';
      ctx.textAlign='left';ctx.fillText(w.name,x+30,y+cardH/2+4);ctx.textAlign='center';
    } else {
      ctx.strokeStyle='rgba(60,100,140,0.3)';ctx.lineWidth=1;ctx.setLineDash([4,4]);
      roundRect(ctx,x,y,cardW,cardH,6);ctx.stroke();ctx.setLineDash([]);
    }
  }
  const available=isHangar
    ?WEAPONS.map((_,i)=>i).filter(i=>!lo.includes(i))
    :[...P.unlockedW].filter(i=>!lo.includes(i)).sort((a,b)=>a-b);
  const availY=loadedY+cardH+40;
  ctx.font='bold 12px "Courier New"';ctx.fillStyle='rgba(150,180,220,0.6)';
  ctx.textAlign='center';ctx.fillText(`▼  AVAILABLE  (${available.length})`,cx,availY-8);
  const availCols=Math.max(1,Math.min(available.length,Math.floor((W-40)/(cardW+cardGap))));
  const availTotalW=Math.min(available.length,availCols)*(cardW+cardGap)-cardGap;
  const availStartX=cx-availTotalW/2;
  for(let i=0;i<available.length;i++){
    const col=i%availCols,row=Math.floor(i/availCols);
    const x=availStartX+col*(cardW+cardGap),y=availY+row*(cardH+cardGap);
    const wIdx=available[i],w=WEAPONS[wIdx];
    const hov=mouse.x>x&&mouse.x<x+cardW&&mouse.y>y&&mouse.y<y+cardH;
    const full=lo.length>=c.maxSlots;
    ctx.fillStyle=hov&&!full?'rgba(0,40,80,0.7)':'rgba(0,20,50,0.4)';
    roundRect(ctx,x,y,cardW,cardH,6);ctx.fill();
    ctx.strokeStyle=full?'rgba(60,80,100,0.3)':hov?'#00ccff':w.color;ctx.lineWidth=hov&&!full?2:1;
    roundRect(ctx,x,y,cardW,cardH,6);ctx.stroke();
    ctx.font='14px "Courier New"';ctx.fillStyle=full?'rgba(80,100,120,0.5)':w.color;
    ctx.textAlign='center';ctx.fillText(GLYPHS[wIdx]||'?',x+16,y+cardH/2+5);
    ctx.font='9px "Courier New"';ctx.fillStyle=full?'rgba(80,100,120,0.5)':hov?'#ffffff':'rgba(150,180,210,0.75)';
    ctx.textAlign='left';ctx.fillText(w.name,x+30,y+cardH/2+4);ctx.textAlign='center';
  }
  const dbw=200,dbh=44,dbx=cx-dbw/2,dby=H-80;
  const dhov=mouse.x>dbx&&mouse.x<dbx+dbw&&mouse.y>dby&&mouse.y<dby+dbh;
  ctx.shadowBlur=dhov?24:10;ctx.shadowColor='#00ff88';
  ctx.fillStyle=dhov?'#00ff88':'rgba(0,0,0,0.7)';
  roundRect(ctx,dbx,dby,dbw,dbh,8);ctx.fill();
  ctx.strokeStyle='#00ff88';ctx.lineWidth=2;
  roundRect(ctx,dbx,dby,dbw,dbh,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 14px "Courier New"';ctx.fillStyle=dhov?'#000':'#00ff88';
  ctx.fillText('DONE',cx,dby+dbh/2+5);
  ctx.textAlign='left';
}
function drawMinimap(){
  const mw=160,mh=108,mx=canvas.width-mw-18,my=trLayout().mmY,scx=mw/WORLD_W,scy=mh/WORLD_H;
  ctx.fillStyle='rgba(4,10,22,0.80)';ctx.fillRect(mx,my,mw,mh);ctx.strokeStyle='rgba(0,140,210,0.35)';ctx.lineWidth=1;ctx.strokeRect(mx,my,mw,mh);
  for(const o of obstacles){ctx.fillStyle='rgba(25,70,150,0.45)';if(o.type==='pillar')ctx.fillRect(mx+o.x*scx-2,my+o.y*scy-2,4,4);else ctx.fillRect(mx+o.x*scx,my+o.y*scy,Math.max(2,o.w*scx),Math.max(2,o.h*scy));}
  for(const p of pickups){ctx.fillStyle=PTYPES[p.type].color+(p.hidden?'55':'bb');ctx.fillRect(mx+p.x*scx-1,my+p.y*scy-1,2.5,2.5);}
  for(const e of enemies){ctx.fillStyle=e.color;ctx.fillRect(mx+e.x*scx-2,my+e.y*scy-2,4,4);}
  ctx.fillStyle=P.color;ctx.shadowBlur=8;ctx.shadowColor=P.color;ctx.fillRect(mx+P.x*scx-2.5,my+P.y*scy-2.5,5,5);ctx.shadowBlur=0;
  // Portal markers
  if(portalActive){
    portalPositions.forEach((pos,i)=>{
      const pulse=0.6+0.4*Math.sin(Date.now()/200+i);
      const selected=(i===portalSelected);
      ctx.fillStyle=selected?`rgba(255,180,0,${0.95*pulse})`:`rgba(220,120,0,${0.85*pulse})`;
      ctx.shadowBlur=selected?10:5;ctx.shadowColor='#ff8800';
      ctx.beginPath();ctx.arc(mx+pos.x*scx,my+pos.y*scy,4,0,Math.PI*2);ctx.fill();
      // Ring on selected
      if(selected){
        ctx.strokeStyle=`rgba(255,220,80,${0.8*pulse})`;ctx.lineWidth=1.2;
        ctx.beginPath();ctx.arc(mx+pos.x*scx,my+pos.y*scy,6.5,0,Math.PI*2);ctx.stroke();
      }
      ctx.shadowBlur=0;
    });
  }
  ctx.strokeStyle='rgba(0,200,255,0.22)';ctx.lineWidth=0.8;ctx.strokeRect(mx+camX*scx,my+camY*scy,canvas.width*scx,canvas.height*scy);
  // Nuke markers (level 2)
  if(ttLevel===2&&nukes.length) drawNukeMinimap(mx,my,mw,mh);
  if(ttLevel===4&&jrCaptives.length) drawJRRMinimap(mx,my,mw,mh);
  if(ttLevel===5&&tngPads.length) drawTNGMinimap(mx,my,mw,mh);
  ctx.fillStyle='rgba(0,150,220,0.5)';ctx.font='7px "Courier New"';ctx.textAlign='left';ctx.fillText('TACTICAL MAP',mx+3,my+8);
}
function drawCrosshair(){
  if(IS_TOUCH) return;
  if(WEAPONS[P.weaponIdx].id==='sawtooth'){
    // Draw rotating arc around player to indicate auto-aim mode
    const sx=P.x-camX, sy=P.y-camY;
    const r=P.size*2.2;
    const now=Date.now();
    ctx.save();
    ctx.strokeStyle='#ff6600';ctx.lineWidth=1.5;ctx.shadowBlur=10;ctx.shadowColor='#ff6600';
    // Spinning dashed arc — 3 evenly spaced arcs
    for(let i=0;i<3;i++){
      const base=P.sawtoothAngle+i*(Math.PI*2/3);
      ctx.beginPath();ctx.arc(sx,sy,r,base,base+Math.PI*0.55);ctx.stroke();
    }
    // Arrow tip at current aim direction
    const ax=sx+Math.cos(P.aim)*r, ay=sy+Math.sin(P.aim)*r;
    ctx.fillStyle='#ff6600';ctx.beginPath();ctx.arc(ax,ay,3,0,Math.PI*2);ctx.fill();
    ctx.restore();
    return;
  }
  const x=mouse.x,y=mouse.y,r=11;
  ctx.strokeStyle=P.color;ctx.lineWidth=1.5;ctx.shadowBlur=7;ctx.shadowColor=P.color;
  ctx.beginPath();ctx.arc(x,y,r,0,Math.PI*2);ctx.stroke();
  ctx.beginPath();ctx.moveTo(x-r-5,y);ctx.lineTo(x-r+4,y);ctx.moveTo(x+r-4,y);ctx.lineTo(x+r+5,y);ctx.moveTo(x,y-r-5);ctx.lineTo(x,y-r+4);ctx.moveTo(x,y+r-4);ctx.lineTo(x,y+r+5);ctx.stroke();ctx.shadowBlur=0;
  ctx.fillStyle=P.color;ctx.beginPath();ctx.arc(x,y,2,0,Math.PI*2);ctx.fill();
}
function drawTouchSticks(){
  if(!IS_TOUCH) return;
  const now=Date.now();
  // Default ghost positions when inactive — sit below weapon bar
  const stickY = weaponBarY() + (IS_TOUCH?24:38) + STICK_R + 8;
  const defaults=[
    {x:STICK_R+28, y:stickY},
    {x:canvas.width-STICK_R-28, y:stickY}
  ];
  const stickList=[touchSticks.L, touchSticks.R];
  stickList.forEach((st,i)=>{
    const cx = st.active ? st.ox : defaults[i].x;
    const cy = st.active ? st.oy : defaults[i].y;
    const kx = cx + st.dx;
    const ky = cy + st.dy;
    const alpha = st.active ? 0.82 : 0.18;
    const col = P.color;
    ctx.save();
    ctx.globalAlpha = alpha;
    ctx.shadowBlur = st.active ? 14 : 0;
    ctx.shadowColor = col;
    // Outer ring
    ctx.beginPath(); ctx.arc(cx,cy,STICK_R,0,Math.PI*2);
    ctx.strokeStyle=col; ctx.lineWidth=2.2; ctx.stroke();
    // Deadzone inner ring (faint)
    ctx.beginPath(); ctx.arc(cx,cy,STICK_R*STICK_DEAD,0,Math.PI*2);
    ctx.strokeStyle=col; ctx.lineWidth=0.8; ctx.globalAlpha=alpha*0.35; ctx.stroke();
    ctx.globalAlpha=alpha;
    // Knob fill
    ctx.beginPath(); ctx.arc(kx,ky,STICK_NUB,0,Math.PI*2);
    ctx.fillStyle=col+'28'; ctx.fill();
    ctx.strokeStyle=col; ctx.lineWidth=2; ctx.stroke();
    // Direction icon inside knob
    ctx.fillStyle=col; ctx.font=`${st.active?'bold ':''}11px "Courier New"`;
    ctx.textAlign='center';
    ctx.fillText(i===0?'✛':'◎',kx,ky+4);
    // Label
    ctx.font='9px "Courier New"'; ctx.fillStyle=col;
    ctx.globalAlpha=alpha*0.65;
    ctx.fillText(i===0?'MOVE':'AIM',cx,cy+STICK_R+14);
    ctx.shadowBlur=0; ctx.globalAlpha=1; ctx.textAlign='left';
    ctx.restore();
  });
}
function tickLaserFlash(dt){if(laserFlash)laserFlash.life-=dt*1000;}
function drawLaserFlash(){
  if(!laserFlash||laserFlash.life<=0){laserFlash=null;return;}
  const f=laserFlash,frac=f.life/f.maxLife; // 1→0 as it fades
  const sx1=f.x1-camX,sy1=f.y1-camY,sx2=f.x2-camX,sy2=f.y2-camY;
  const len=Math.sqrt((sx2-sx1)**2+(sy2-sy1)**2)||1;
  const cos=(sx2-sx1)/len,sin=(sy2-sy1)/len;
  ctx.save();
  // Outer glow pass — wide, very transparent
  ctx.strokeStyle=`rgba(255,80,255,${frac*0.25})`;
  ctx.lineWidth=14;ctx.lineCap='round';ctx.shadowBlur=0;
  ctx.beginPath();ctx.moveTo(sx1,sy1);ctx.lineTo(sx2,sy2);ctx.stroke();
  // Mid glow
  ctx.strokeStyle=`rgba(255,140,255,${frac*0.55})`;
  ctx.lineWidth=5;
  ctx.beginPath();ctx.moveTo(sx1,sy1);ctx.lineTo(sx2,sy2);ctx.stroke();
  // Jagged core — rebuild jitter path from stored forks
  ctx.shadowBlur=22;ctx.shadowColor='#ff66ff';
  ctx.strokeStyle=`rgba(255,180,255,${frac*0.9})`;
  ctx.lineWidth=2.2;
  ctx.beginPath();ctx.moveTo(sx1,sy1);
  const mainForks=f.forks.filter(k=>!k.branch).sort((a,b)=>a.t-b.t);
  for(const k of mainForks){
    const px=sx1+cos*len*k.t+(-sin)*k.jx-cos*k.jy;
    const py=sy1+sin*len*k.t+cos*k.jx+(-sin)*k.jy;
    ctx.lineTo(px,py);
  }
  ctx.lineTo(sx2,sy2);ctx.stroke();
  // White hot core
  ctx.strokeStyle=`rgba(255,255,255,${frac*0.85})`;
  ctx.lineWidth=1;ctx.shadowBlur=0;
  ctx.beginPath();ctx.moveTo(sx1,sy1);ctx.lineTo(sx2,sy2);ctx.stroke();
  // Branch forks
  ctx.shadowBlur=10;ctx.shadowColor='#ff88ff';
  for(const k of f.forks.filter(k=>k.branch)){
    const bx1=k.x1-camX,by1=k.y1-camY,bx2=k.x2-camX,by2=k.y2-camY;
    ctx.strokeStyle=`rgba(255,120,255,${frac*0.65})`;ctx.lineWidth=1.4;
    ctx.beginPath();ctx.moveTo(bx1,by1);ctx.lineTo(bx2,by2);ctx.stroke();
  }
  // Origin muzzle flare
  ctx.fillStyle=`rgba(255,220,255,${frac*0.9})`;
  ctx.beginPath();ctx.arc(sx1,sy1,6*frac,0,Math.PI*2);ctx.fill();
  ctx.restore();
}
function drawEMPFlash(){if(empFlash<=0)return;ctx.fillStyle=`rgba(140,40,220,${(empFlash/750)*0.48})`;ctx.fillRect(0,0,canvas.width,canvas.height);}
function tickLeechFlash(dt){
  if(leechFlash.active){leechFlash.ms-=dt*1000;if(leechFlash.ms<=0)leechFlash.active=false;}
}
function drawLeechFlash(){
  if(!leechFlash.active)return;
  const alpha=Math.min(1,leechFlash.ms/200);
  ctx.save();
  ctx.globalAlpha=alpha*0.85;
  ctx.beginPath();
  ctx.moveTo(P.x-camX,P.y-camY);
  ctx.lineTo(leechFlash.tx-camX,leechFlash.ty-camY);
  ctx.strokeStyle='#00ff88';ctx.lineWidth=3+4*alpha;
  ctx.shadowBlur=22;ctx.shadowColor='#00ff88';
  ctx.stroke();ctx.shadowBlur=0;
  ctx.globalAlpha=alpha*0.4;
  ctx.lineWidth=1.5;ctx.strokeStyle='#ffffff';
  ctx.stroke();
  ctx.globalAlpha=1;ctx.restore();
}
function tickShockwaveFlash(dt){
  if(shockwaveFlash.ms>0)shockwaveFlash.ms-=dt*1000;
}
function drawShockwaveFlash(){
  if(shockwaveFlash.ms<=0)return;
  const prog=1-(shockwaveFlash.ms/600);
  const r=SHOCKWAVE_R*prog;
  const alpha=Math.max(0,(1-prog)*0.7);
  const sx=P.x-camX,sy=P.y-camY;
  ctx.save();
  ctx.globalAlpha=alpha;
  ctx.beginPath();ctx.arc(sx,sy,r,0,Math.PI*2);
  ctx.strokeStyle='#ff8844';ctx.lineWidth=4*(1-prog)+1;
  ctx.shadowBlur=20;ctx.shadowColor='#ff8844';ctx.stroke();ctx.shadowBlur=0;
  ctx.globalAlpha=alpha*0.18;
  ctx.beginPath();ctx.arc(sx,sy,r,0,Math.PI*2);
  ctx.fillStyle='#ff8844';ctx.fill();
  ctx.globalAlpha=1;ctx.restore();
}
function drawBossWarning(dt){
  if(bossWarning<=0)return;bossWarning-=dt*1000;
  ctx.globalAlpha=Math.min(1,bossWarning/800);ctx.textAlign='center';ctx.font='bold 36px "Courier New"';ctx.fillStyle='#ff0055';ctx.shadowBlur=35;ctx.shadowColor='#ff0055';
  if(Math.floor(Date.now()/250)%2===0)ctx.fillText('⚠  BOSS INCOMING  ⚠',canvas.width/2,canvas.height/2-60);
  ctx.shadowBlur=0;ctx.globalAlpha=1;ctx.textAlign='left';
}

// ═══════════════════════════════════════════════════════════════
//  SELECTION SCREENS
// ═══════════════════════════════════════════════════════════════

function drawStatBar(x,y,w,h,val,max,col){
  ctx.fillStyle='rgba(0,0,0,0.5)';ctx.fillRect(x,y,w,h);
  ctx.fillStyle=col;ctx.shadowBlur=5;ctx.shadowColor=col;ctx.fillRect(x,y,w*(val/max),h);ctx.shadowBlur=0;
}

function drawCraftCard(craft,cx,cy,idx,hov,sel){
  const t=Date.now()/1000;
  const CW=200,CH=340;
  const x=cx-CW/2,y=cy-CH/2;
  const isActive=hov===idx||sel===idx;
  const accCol=isActive?craft.defaultColor:'rgba(30,55,90,0.7)';
  const bgAlpha=isActive?0.92:0.6;

  // Card shadow glow
  if(isActive){ctx.shadowBlur=32;ctx.shadowColor=craft.defaultColor;}
  ctx.fillStyle=`rgba(6,14,34,${bgAlpha})`;
  roundRect(ctx,x,y,CW,CH,10);ctx.fill();
  ctx.shadowBlur=0;

  // Border
  ctx.strokeStyle=isActive?craft.defaultColor:'rgba(30,60,100,0.4)';
  ctx.lineWidth=isActive?2.2:1;
  roundRect(ctx,x,y,CW,CH,10);ctx.stroke();

  // Top accent bar
  if(isActive){
    ctx.fillStyle=craft.defaultColor;ctx.shadowBlur=10;ctx.shadowColor=craft.defaultColor;
    ctx.fillRect(x+10,y+2,CW-20,3);ctx.shadowBlur=0;
  }

  // Preview drone
  const previewY=cy-CH/2+80;
  const spin=t*(idx%2===0?8:-9);
  if(craft.id==='phantom')drawPhantom(cx,previewY,Math.PI*0.85+Math.sin(t*0.7)*0.25,isActive?22:18,craft.defaultColor,lighten(craft.defaultColor),spin);
  else if(craft.id==='viper')drawViper(cx,previewY,Math.PI*0.85+Math.sin(t*0.7)*0.25,isActive?22:18,craft.defaultColor,lighten(craft.defaultColor),spin);
  else if(craft.id==='titan')drawTitan(cx,previewY,Math.PI*0.85+Math.sin(t*0.7)*0.25,isActive?22:18,craft.defaultColor,lighten(craft.defaultColor),spin);
  else if(craft.id==='specter')drawSpecter(cx,previewY,Math.PI*0.85+Math.sin(t*0.7)*0.25,isActive?22:18,craft.defaultColor,lighten(craft.defaultColor),spin);
  else if(craft.id==='sniper')drawSniper(cx,previewY,Math.PI*0.85+Math.sin(t*0.7)*0.25,isActive?22:18,craft.defaultColor,lighten(craft.defaultColor),spin);
  else if(craft.id==='carrier')drawCarrier(cx,previewY,Math.PI*0.85+Math.sin(t*0.7)*0.25,isActive?22:18,craft.defaultColor,lighten(craft.defaultColor),spin);
  else if(craft.id==='skirmisher')drawSkirmisher(cx,previewY,Math.PI*0.85+Math.sin(t*0.7)*0.25,isActive?22:18,craft.defaultColor,lighten(craft.defaultColor),spin);

  // Name
  ctx.textAlign='center';ctx.font=`bold 16px "Courier New"`;
  ctx.fillStyle=isActive?craft.defaultColor:'rgba(140,180,220,0.8)';
  ctx.shadowBlur=isActive?14:0;ctx.shadowColor=craft.defaultColor;
  ctx.fillText(craft.name,cx,y+148);ctx.shadowBlur=0;
  ctx.font='9px "Courier New"';ctx.fillStyle='rgba(100,140,180,0.7)';
  ctx.fillText(craft.sub,cx,y+162);

  // Stat bars
  const statLabels=[['SPD',craft.stats.speed],['ARM',craft.stats.armor],['FIRE',craft.stats.fire],['BATT',craft.stats.battery]];
  const barX=x+14,barW=CW-28;
  statLabels.forEach(([label,val],si)=>{
    const by=y+176+si*22;
    ctx.font='8px "Courier New"';ctx.textAlign='left';ctx.fillStyle='rgba(80,120,165,0.7)';ctx.fillText(label,barX,by+8);
    drawStatBar(barX+32,by,barW-32,8,val,5,isActive?craft.defaultColor:'rgba(40,80,130,0.7)');
  });

  // Ability
  ctx.font='7.5px "Courier New"';ctx.textAlign='center';ctx.fillStyle=isActive?'rgba(200,230,255,0.75)':'rgba(80,110,145,0.6)';
  const words=craft.ability.split('  —  ');
  ctx.fillText(words[0],cx,y+274);ctx.fillText(words[1]||'',cx,y+286);

  // Selected tick
  if(sel===idx){
    ctx.fillStyle=craft.defaultColor;ctx.shadowBlur=18;ctx.shadowColor=craft.defaultColor;
    ctx.font='bold 12px "Courier New"';ctx.fillText('✔ SELECTED',cx,y+CH-14);ctx.shadowBlur=0;
  }
}

function roundRect(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.lineTo(x+w-r,y);c.quadraticCurveTo(x+w,y,x+w,y+r);c.lineTo(x+w,y+h-r);c.quadraticCurveTo(x+w,y+h,x+w-r,y+h);c.lineTo(x+r,y+h);c.quadraticCurveTo(x,y+h,x,y+h-r);c.lineTo(x,y+r);c.quadraticCurveTo(x,y,x+r,y);c.closePath();}

function getCardCenters(){
  const total=CRAFTS.length,spacing=220,startX=canvas.width/2-(total-1)*spacing/2,cy=canvas.height/2+20;
  return CRAFTS.map((_,i)=>({cx:startX+i*spacing,cy}));
}

function drawDroneSelectScreen(){
  // BG
  ctx.fillStyle='#050d1a';ctx.fillRect(0,0,canvas.width,canvas.height);
  const t=Date.now()/1000;
  // Subtle grid
  ctx.strokeStyle='rgba(0,80,160,0.08)';ctx.lineWidth=1;
  for(let x=0;x<canvas.width;x+=70){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x,canvas.height);ctx.stroke();}
  for(let y=0;y<canvas.height;y+=70){ctx.beginPath();ctx.moveTo(0,y);ctx.lineTo(canvas.width,y);ctx.stroke();}

  // Title
  ctx.textAlign='center';
  ctx.font='bold 42px "Courier New"';ctx.fillStyle='#00ccff';ctx.shadowBlur=30;ctx.shadowColor='#0088cc';
  ctx.fillText('HANGAR — SELECT CRAFT',canvas.width/2,64);ctx.shadowBlur=0;
  ctx.font='11px "Courier New"';ctx.fillStyle='rgba(60,120,180,0.6)';
  ctx.fillText('Click a craft to select  ·  Press SPACE or click DEPLOY to continue',canvas.width/2,88);

  // Cards
  const centers=getCardCenters();
  centers.forEach(({cx,cy},i)=>drawCraftCard(CRAFTS[i],cx,cy,i,hoverCard,selectedCraft));

  // BACK button — left of CHOOSE COLOR
  const btnW=220,btnH=44,btnX=canvas.width/2-btnW/2,btnY=canvas.height-78;
  const backW=120,backH=btnH,backX=btnX-backW-14,backY=btnY;
  const backHov=mouse.x>backX&&mouse.x<backX+backW&&mouse.y>backY&&mouse.y<backY+backH;
  ctx.shadowBlur=backHov?18:6;ctx.shadowColor='rgba(0,180,255,0.7)';
  ctx.fillStyle=backHov?'rgba(0,180,255,0.18)':'rgba(0,0,0,0.7)';
  roundRect(ctx,backX,backY,backW,backH,8);ctx.fill();
  ctx.strokeStyle=backHov?'#00ccff':'rgba(0,140,220,0.65)';ctx.lineWidth=backHov?2:1;
  roundRect(ctx,backX,backY,backW,backH,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 14px "Courier New"';ctx.fillStyle=backHov?'#00eeff':'rgba(150,205,255,0.85)';
  ctx.textAlign='center';ctx.fillText('◀  BACK',backX+backW/2,backY+28);

  // CHOOSE COLOR button
  const btnHov=mouse.x>btnX&&mouse.x<btnX+btnW&&mouse.y>btnY&&mouse.y<btnY+btnH;
  ctx.shadowBlur=btnHov?22:10;ctx.shadowColor=CRAFTS[selectedCraft].defaultColor;
  ctx.fillStyle=btnHov?CRAFTS[selectedCraft].defaultColor:'rgba(0,0,0,0.7)';
  roundRect(ctx,btnX,btnY,btnW,btnH,8);ctx.fill();
  ctx.strokeStyle=CRAFTS[selectedCraft].defaultColor;ctx.lineWidth=2;
  roundRect(ctx,btnX,btnY,btnW,btnH,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 14px "Courier New"';ctx.fillStyle=btnHov?'#000':'#fff';
  ctx.fillText('▶  CHOOSE COLOR',canvas.width/2,btnY+28);
  ctx.textAlign='left';
}

function drawColorSelectScreen(){
  ctx.fillStyle='#050d1a';ctx.fillRect(0,0,canvas.width,canvas.height);
  ctx.strokeStyle='rgba(0,80,160,0.08)';ctx.lineWidth=1;
  for(let x=0;x<canvas.width;x+=70){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x,canvas.height);ctx.stroke();}
  for(let y=0;y<canvas.height;y+=70){ctx.beginPath();ctx.moveTo(0,y);ctx.lineTo(canvas.width,y);ctx.stroke();}

  const t=Date.now()/1000;
  const cx=canvas.width/2,cy=canvas.height/2;
  const craft=CRAFTS[selectedCraft];

  // Header
  ctx.textAlign='center';ctx.font='bold 36px "Courier New"';ctx.fillStyle='#00ccff';ctx.shadowBlur=22;ctx.shadowColor='#0088cc';
  ctx.fillText('FORGE — CHOOSE COLOR',cx,56);ctx.shadowBlur=0;
  ctx.font='12px "Courier New"';ctx.fillStyle='rgba(80,140,200,0.6)';
  ctx.fillText(`${craft.name} · ${craft.sub}`,cx,80);

  // Big live preview
  const previewY=cy-30;
  const spin=t*10;
  const previewSize=52;
  // Draw halo
  const[r,g,b]=hexToRgb(selectedColor);
  ctx.beginPath();ctx.arc(cx,previewY,previewSize*2.2,0,Math.PI*2);
  ctx.fillStyle=`rgba(${r},${g},${b},0.04)`;ctx.fill();
  ctx.beginPath();ctx.arc(cx,previewY,previewSize*1.7,0,Math.PI*2);
  ctx.fillStyle=`rgba(${r},${g},${b},0.06)`;ctx.fill();

  if(craft.id==='phantom')drawPhantom(cx,previewY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,selectedColor,lighten(selectedColor),spin);
  else if(craft.id==='viper')drawViper(cx,previewY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,selectedColor,lighten(selectedColor),spin);
  else if(craft.id==='titan')drawTitan(cx,previewY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,selectedColor,lighten(selectedColor),spin);
  else if(craft.id==='specter')drawSpecter(cx,previewY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,selectedColor,lighten(selectedColor),spin);
  else if(craft.id==='sniper')drawSniper(cx,previewY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,selectedColor,lighten(selectedColor),spin);
  else if(craft.id==='carrier')drawCarrier(cx,previewY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,selectedColor,lighten(selectedColor),spin);
  else if(craft.id==='skirmisher')drawSkirmisher(cx,previewY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,selectedColor,lighten(selectedColor),spin);

  // Swatch label
  ctx.font='11px "Courier New"';ctx.fillStyle='rgba(80,130,185,0.7)';ctx.textAlign='center';
  ctx.fillText('PRESET COLORS',cx,cy+80);

  // Swatches grid 5x2
  const swatchR=22,gapX=62,gapY=58;
  const sw_startX=cx-(4*gapX+swatchR)/1,sw_startY=cy+98;
  SWATCHES.forEach((col,i)=>{
    const sx=cx+(i%5-2)*gapX+(i<5?0:0);
    const sy=sw_startY+(Math.floor(i/5))*gapY;
    const hov=hoverSwatch===i,isSel=selectedColor===col;
    ctx.beginPath();ctx.arc(sx,sy,hov?swatchR+4:swatchR,0,Math.PI*2);
    ctx.fillStyle=col;ctx.shadowBlur=hov||isSel?20:6;ctx.shadowColor=col;ctx.fill();ctx.shadowBlur=0;
    if(isSel){ctx.strokeStyle='#ffffff';ctx.lineWidth=3;ctx.beginPath();ctx.arc(sx,sy,swatchR+6,0,Math.PI*2);ctx.stroke();}
    else if(hov){ctx.strokeStyle=col;ctx.lineWidth=2;ctx.beginPath();ctx.arc(sx,sy,swatchR+2,0,Math.PI*2);ctx.stroke();}
  });

  // Custom color button
  const cbX=cx,cbY=cy+220;
  const cbHov=dist(mouse.x,mouse.y,cbX,cbY)<30;
  ctx.beginPath();ctx.arc(cbX,cbY,28,0,Math.PI*2);
  ctx.fillStyle=cbHov?'rgba(40,80,140,0.9)':'rgba(20,40,80,0.7)';ctx.fill();
  ctx.strokeStyle='rgba(80,140,220,0.7)';ctx.lineWidth=1.5;ctx.stroke();
  ctx.font='10px "Courier New"';ctx.fillStyle='rgba(180,210,255,0.8)';ctx.textAlign='center';
  ctx.fillText('CUSTOM',cbX,cbY-3);ctx.fillText('🎨',cbX,cbY+13);

  // Current hex label
  ctx.font='bold 14px "Courier New"';ctx.fillStyle=selectedColor;ctx.shadowBlur=12;ctx.shadowColor=selectedColor;
  ctx.fillText(selectedColor.toUpperCase(),cx,cbY+50);ctx.shadowBlur=0;

  // Back button
  const backW=130,backH=38,backX=cx-260-backW/2,backY=canvas.height-70;
  const backHov=mouse.x>backX&&mouse.x<backX+backW&&mouse.y>backY&&mouse.y<backY+backH;
  ctx.shadowBlur=backHov?12:4;ctx.shadowColor='rgba(80,140,220,0.5)';
  ctx.fillStyle=backHov?'rgba(30,60,110,0.9)':'rgba(10,20,50,0.7)';roundRect(ctx,backX,backY,backW,backH,7);ctx.fill();
  ctx.strokeStyle='rgba(80,140,220,0.5)';ctx.lineWidth=1.5;roundRect(ctx,backX,backY,backW,backH,7);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='12px "Courier New"';ctx.fillStyle='rgba(150,190,230,0.8)';ctx.fillText('◀ BACK',backX+backW/2,backY+24);

  // Deploy button
  const depW=220,depH=44,depX=cx+80,depY=canvas.height-78;
  const depHov=mouse.x>depX&&mouse.x<depX+depW&&mouse.y>depY&&mouse.y<depY+depH;
  ctx.shadowBlur=depHov?28:14;ctx.shadowColor=selectedColor;
  ctx.fillStyle=depHov?selectedColor:'rgba(0,0,0,0.8)';roundRect(ctx,depX,depY,depW,depH,8);ctx.fill();
  ctx.strokeStyle=selectedColor;ctx.lineWidth=2.2;roundRect(ctx,depX,depY,depW,depH,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 15px "Courier New"';ctx.fillStyle=depHov?'#000':'#fff';
  ctx.fillText('▶  DEPLOY PILOT',depX+depW/2,depY+28);
  ctx.textAlign='left';
}

// ─── AIRCRAFT HANGAR SCREEN ──────────────────────────────────────
function _hangarLayout(){
  const W=canvas.width, H=canvas.height, cx=W/2;
  const btnH=44, btnY=H-72;
  const headerBottom=72, previewTopPad=24, previewBotPad=44;
  const cardBotPad=44, swatchZoneH=54, cardH=340;
  const fixedH=previewTopPad+previewBotPad+cardH+cardBotPad+swatchZoneH+32+btnH;
  const previewAvail=Math.max(60, Math.min(H-headerBottom-fixedH, 140));
  const previewSize=Math.min(42,(previewAvail-24)/2);
  const previewCY=headerBottom+previewTopPad+previewAvail/2;
  const cardsCY=previewCY+previewAvail/2+previewBotPad+cardH/2;
  const swatchLabelY=cardsCY+cardH/2+cardBotPad;
  const swatchR=Math.min(18,Math.max(12,W*0.018));
  const rowW=Math.min(W*0.82,11*(swatchR*2+10)-10);
  const itemStep=rowW/10;
  const rowStartX=cx-rowW/2;
  const swatchZoneTop=swatchLabelY+18, swatchZoneBot=btnY-btnH-10;
  const swatchCY=swatchZoneTop+(swatchZoneBot-swatchZoneTop)/2;
  const spacing=Math.min(220,W*0.22);
  const startX=cx-(HANGAR_VISIBLE-1)*spacing/2;
  const arrowW=36,arrowH=cardH;
  const arrowLX=startX-100-arrowW-12, arrowRX=startX+(HANGAR_VISIBLE-1)*spacing+spacing/2+12;
  const cancelW=Math.min(160,W*0.22), saveW=Math.min(220,W*0.3);
  const totalBtnW=cancelW+saveW+36;
  const cancelX=cx-totalBtnW/2, saveX=cancelX+cancelW+36;
  return {W,H,cx,btnH,btnY,previewSize,previewCY,cardsCY,swatchLabelY,swatchR,rowW,itemStep,rowStartX,swatchCY,spacing,startX,arrowW,arrowH,arrowLX,arrowRX,cancelW,saveW,cancelX,saveX};
}
function drawHangarScreen(){
  const t=Date.now()/1000;
  const {W,H,cx,btnH,btnY,previewSize,previewCY,cardsCY,swatchLabelY,swatchR,rowW,itemStep,rowStartX,swatchCY,spacing,startX,arrowW,arrowH,arrowLX,arrowRX,cancelW,saveW,cancelX,saveX}=_hangarLayout();
  const previewGlow=previewSize*1.9;

  // Background + grid
  ctx.fillStyle='#050d1a';ctx.fillRect(0,0,W,H);
  ctx.strokeStyle='rgba(0,80,160,0.08)';ctx.lineWidth=1;
  for(let x=0;x<W;x+=70){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x,H);ctx.stroke();}
  for(let y=0;y<H;y+=70){ctx.beginPath();ctx.moveTo(0,y);ctx.lineTo(W,y);ctx.stroke();}

  // ── Header ───────────────────────────────────────────────────
  ctx.textAlign='center';
  const titleSz=Math.min(36,W*0.055);
  ctx.font=`bold ${titleSz}px "Courier New"`;ctx.fillStyle='#ff8800';ctx.shadowBlur=24;ctx.shadowColor='#cc5500';
  ctx.fillText('AIRCRAFT HANGAR',cx,40);ctx.shadowBlur=0;
  ctx.font='10px "Courier New"';ctx.fillStyle='rgba(180,120,60,0.6)';
  ctx.fillText('Choose your craft and color — changes saved to your profile',cx,58);

  // ── Live preview (selected craft in chosen color) ────────────
  const craft=CRAFTS[hangarCraft];
  const spin=t*10;
  const[r,g,b]=hexToRgb(hangarColor);
  ctx.beginPath();ctx.arc(cx,previewCY,previewGlow,0,Math.PI*2);
  ctx.fillStyle=`rgba(${r},${g},${b},0.07)`;ctx.fill();
  if(craft.id==='phantom')drawPhantom(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
  else if(craft.id==='viper')drawViper(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
  else if(craft.id==='titan')drawTitan(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
  else if(craft.id==='specter')drawSpecter(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
  else if(craft.id==='sniper')drawSniper(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
  else if(craft.id==='carrier')drawCarrier(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
  else if(craft.id==='skirmisher')drawSkirmisher(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
  else drawEnemyDrone(cx,previewCY,Math.PI*0.75+Math.sin(t*0.6)*0.3,previewSize,hangarColor,lighten(hangarColor),spin);
  // Hex label below preview
  ctx.font=`bold ${Math.min(11,previewSize*0.35)}px "Courier New"`;ctx.fillStyle=hangarColor;ctx.shadowBlur=6;ctx.shadowColor=hangarColor;
  ctx.fillText(hangarColor.toUpperCase(),cx,previewCY+previewSize+14);ctx.shadowBlur=0;

  // ── Craft cards row ──────────────────────────────────────────
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
  const dotY=cardsCY+170+16;
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

  // ── Color forge — single row ─────────────────────────────────
  ctx.font='bold 10px "Courier New"';ctx.fillStyle='rgba(180,120,50,0.65)';ctx.textAlign='center';
  ctx.fillText('╴ COLOR FORGE ╶',cx,swatchLabelY+12);

  const totalItems=11;

  SWATCHES.forEach((col,i)=>{
    const sx=rowStartX+i*itemStep;
    const sy=swatchCY;
    const hov=hoverSwatch===i, isSel=hangarColor===col;
    ctx.beginPath();ctx.arc(sx,sy,hov?swatchR+3:swatchR,0,Math.PI*2);
    ctx.fillStyle=col;ctx.shadowBlur=hov||isSel?18:4;ctx.shadowColor=col;ctx.fill();ctx.shadowBlur=0;
    if(isSel){ctx.strokeStyle='#ffffff';ctx.lineWidth=2.5;ctx.beginPath();ctx.arc(sx,sy,swatchR+5,0,Math.PI*2);ctx.stroke();}
    else if(hov){ctx.strokeStyle=col;ctx.lineWidth=1.5;ctx.beginPath();ctx.arc(sx,sy,swatchR+2,0,Math.PI*2);ctx.stroke();}
  });

  // Custom color picker button — last item in the row
  const cbX=rowStartX+10*itemStep, cbY=swatchCY;
  const cbHov=dist(mouse.x,mouse.y,cbX,cbY)<swatchR+4;
  ctx.beginPath();ctx.arc(cbX,cbY,swatchR,0,Math.PI*2);
  ctx.fillStyle=cbHov?'rgba(40,80,140,0.9)':'rgba(20,40,80,0.7)';ctx.fill();
  ctx.strokeStyle='rgba(80,140,220,0.6)';ctx.lineWidth=1.3;ctx.stroke();
  ctx.font=`${Math.max(7,swatchR*0.55)}px "Courier New"`;ctx.fillStyle='rgba(180,210,255,0.8)';ctx.textAlign='center';
  ctx.fillText('🎨',cbX,cbY+5);

  // Edit Loadout button
  const elW=200,elH=38,elX=cx-elW/2,elY=btnY-54;
  const elHov=mouse.x>elX&&mouse.x<elX+elW&&mouse.y>elY&&mouse.y<elY+elH;
  ctx.shadowBlur=elHov?20:8;ctx.shadowColor='#00ccff';
  ctx.fillStyle=elHov?'rgba(0,140,200,0.85)':'rgba(0,0,0,0.65)';
  roundRect(ctx,elX,elY,elW,elH,6);ctx.fill();
  ctx.strokeStyle=elHov?'#00eeff':'rgba(0,140,220,0.6)';ctx.lineWidth=1.5;
  roundRect(ctx,elX,elY,elW,elH,6);ctx.stroke();ctx.shadowBlur=0;
  ctx.textAlign='center';ctx.font='bold 12px "Courier New"';
  ctx.fillStyle=elHov?'#000':'rgba(100,200,255,0.9)';
  ctx.fillText('EDIT ARSENAL',cx,elY+elH/2+4);

  // ── Bottom buttons ───────────────────────────────────────────
  const cancelHov=mouse.x>cancelX&&mouse.x<cancelX+cancelW&&mouse.y>btnY&&mouse.y<btnY+btnH;
  ctx.fillStyle=cancelHov?'rgba(180,50,30,0.22)':'rgba(0,0,0,0.6)';
  roundRect(ctx,cancelX,btnY,cancelW,btnH,8);ctx.fill();
  ctx.strokeStyle=cancelHov?'rgba(220,80,50,0.8)':'rgba(130,50,30,0.5)';ctx.lineWidth=1.8;
  roundRect(ctx,cancelX,btnY,cancelW,btnH,8);ctx.stroke();
  ctx.font='bold 13px "Courier New"';ctx.fillStyle=cancelHov?'#ff8866':'rgba(200,100,70,0.8)';
  ctx.textAlign='center';ctx.fillText('✕  CANCEL',cancelX+cancelW/2,btnY+btnH/2+5);

  const saveHov=mouse.x>saveX&&mouse.x<saveX+saveW&&mouse.y>btnY&&mouse.y<btnY+btnH;
  ctx.shadowBlur=saveHov?22:8;ctx.shadowColor='#ff8800';
  ctx.fillStyle=saveHov?'#ff8800':'rgba(0,0,0,0.7)';
  roundRect(ctx,saveX,btnY,saveW,btnH,8);ctx.fill();
  ctx.strokeStyle='#ff8800';ctx.lineWidth=2;roundRect(ctx,saveX,btnY,saveW,btnH,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 14px "Courier New"';ctx.fillStyle=saveHov?'#000':'#ff8800';
  ctx.textAlign='center';ctx.fillText('✔  SAVE CHANGES',saveX+saveW/2,btnY+btnH/2+5);

  ctx.textAlign='left';
}

// ─── GAME SCREENS ────────────────────────────────────────────────
function overlay(a){ctx.fillStyle=`rgba(6,12,24,${a})`;ctx.fillRect(0,0,canvas.width,canvas.height);}
// ─── TITLE MENU BUTTONS ──────────────────────────────────────────
const MENU_ITEMS=[
  {label:'Battle Waves',   dim:false},
  {label:'Time Trials',    dim:false},
  {label:'Combat Training',dim:false},
  {label:'Level Designer', dim:false},
  {label:'Aircraft Hangar',dim:false},
  {label:'Hall of Fame',   dim:false},
  {label:'Setup',          dim:false},
];
// Only Battle Waves has a click action currently; rest render as available but inert
const MENU_ACTIVE=['Battle Waves'];
let menuHover=-1;
let soundToggleHover=false;
let hofClearStep=0,hofClearResetAt=0,hofClearFlashMs=0;
let setupSliderDrag=null;
function getMenuRects(){
  const W=canvas.width,H=canvas.height;
  const bw=Math.max(200,Math.min(W*0.72,380));
  const bh=Math.max(32,Math.min(H*0.062,52));
  const gap=Math.max(6,Math.min(H*0.014,14));
  const n=MENU_ITEMS.length;
  const totalH=n*(bh+gap)-gap;
  // Title block ends ~45% down; menu starts just below, clamped so it fits
  const titleEnd=Math.min(H*0.46, H-totalH-20);
  const startY=Math.max(titleEnd, (H-totalH)/2+H*0.08);
  const x=W/2-bw/2;
  return MENU_ITEMS.map((m,i)=>({x,y:startY+i*(bh+gap),w:bw,h:bh,item:m}));
}
function drawStartScreen(){
  const W=canvas.width,H=canvas.height;
  ctx.fillStyle='#060c18';ctx.fillRect(0,0,W,H);
  // Grid
  const gs=Math.max(50,Math.min(80,W/14));
  ctx.strokeStyle='rgba(0,80,180,0.09)';ctx.lineWidth=1;
  for(let x=0;x<W;x+=gs){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x,H);ctx.stroke();}
  for(let y=0;y<H;y+=gs){ctx.beginPath();ctx.moveTo(0,y);ctx.lineTo(W,y);ctx.stroke();}

  const t=Date.now()/1000,cx=W/2;
  // Title font scales with width, clamped
  const titleSz=Math.max(28,Math.min(W*0.115,82));
  const titleY=Math.max(titleSz+8, H*0.14);
  ctx.textAlign='center';
  ctx.font=`bold ${titleSz}px "Courier New"`;
  ctx.shadowBlur=40;ctx.shadowColor='#00aaff';ctx.fillStyle='#00ccff';
  const _nameParts=GAME_NAME.split(' ');
  ctx.fillText(_nameParts[0],cx,titleY);
  ctx.fillStyle='#ffffff';ctx.shadowColor='#aaddff';ctx.shadowBlur=25;
  ctx.fillText(_nameParts.slice(1).join(' '),cx,titleY+titleSz*1.1);ctx.shadowBlur=0;

  // Tagline — only show if there's room
  const tagY=titleY+titleSz*2.1;
  if(tagY < H*0.42){
    const tagSz=Math.max(8,Math.min(11,W/90));
    ctx.font=`bold ${tagSz}px "Courier New"`;ctx.fillStyle='rgba(0,160,220,0.5)';
    ctx.fillText('▸ v3  ·  4 CRAFT CLASSES  ·  5-WAVE SURVIVAL  ·  WEAPONS · OBSTACLES · POWER-UPS ◂',cx,tagY);
  }

  // Animated drones — orbit around top-centre, scale with screen
  const dr=Math.min(W,H)*0.18;
  const dcy=titleY+titleSz*0.5;
  const dscale=Math.max(0.4,Math.min(W/900,1.1));
  drawPhantom(cx+Math.cos(t*0.55)*dr*0.95,    dcy-dr*0.55+Math.sin(t*0.75)*12,   t*0.38,        26*dscale,'#00ccff',lighten('#00ccff'),t*8);
  drawViper(  cx+Math.cos(t*0.55+Math.PI)*dr*0.70, dcy-dr*0.50+Math.sin(t*0.75+Math.PI)*9,t*0.38+Math.PI,19*dscale,'#ff4400',lighten('#ff4400'),t*11);
  drawTitan(  cx+Math.cos(t*0.55+Math.PI*1.5)*dr*0.40,dcy-dr*0.70+Math.sin(t*0.75+Math.PI*1.5)*7,t*0.38,16*dscale,'#ff8800',lighten('#ff8800'),t*7);
  drawSpecter(cx+Math.cos(t*0.55+Math.PI*0.5)*dr*0.50,dcy-dr*0.42+Math.sin(t*0.75+Math.PI*0.5)*11,t*0.38,17*dscale,'#aa44ff',lighten('#aa44ff'),t*10);

  // Menu buttons
  const rects=getMenuRects();
  const btnSz=Math.max(10,Math.min(rects[0].h*0.36,15));
  for(let i=0;i<rects.length;i++){
    const {x,y,w,h,item}=rects[i];
    const hov=(i===menuHover);
    const dim=item.dim;
    // BG
    ctx.fillStyle=hov&&!dim?'rgba(0,180,255,0.18)':hov&&dim?'rgba(30,55,90,0.55)':!dim?'rgba(0,55,115,0.55)':'rgba(10,18,38,0.45)';
    ctx.fillRect(x,y,w,h);
    // Border
    ctx.strokeStyle=hov&&!dim?'#00ccff':hov&&dim?'rgba(80,130,180,0.85)':!dim?'rgba(0,140,220,0.75)':'rgba(35,60,95,0.6)';
    ctx.lineWidth=hov?2:1;
    ctx.shadowBlur=hov&&!dim?20:hov&&dim?10:0;ctx.shadowColor=hov&&dim?'rgba(80,140,200,0.6)':'#00ccff';
    ctx.strokeRect(x,y,w,h);ctx.shadowBlur=0;
    // Left accent bar on non-dim hover
    if(hov&&!dim){ctx.fillStyle='#00ccff';ctx.fillRect(x,y,3,h);}
    // Label
    ctx.textAlign='center';
    ctx.font=`bold ${btnSz}px "Courier New"`;
    ctx.fillStyle=hov&&!dim?'#00eeff':hov&&dim?'rgba(130,175,220,0.85)':!dim?'rgba(150,205,255,0.92)':'rgba(55,85,125,0.55)';
    ctx.shadowBlur=hov&&!dim?14:hov&&dim?6:0;ctx.shadowColor=hov&&dim?'rgba(100,160,220,0.5)':'#00ccff';
    ctx.fillText(item.label,x+w/2,y+h/2+btnSz*0.36);
    ctx.shadowBlur=0;
  }
  ctx.textAlign='left';
  // Sound toggle — top-right corner
  const _stPad=Math.max(14,W*0.02);
  const _stW=Math.max(80,W*0.075),_stH=Math.max(28,H*0.042);
  const _stX=W-_stPad-_stW,_stY=_stPad;
  const _stMuted=Music.isMuted();
  const _stHov=soundToggleHover;
  ctx.fillStyle=_stHov?'rgba(0,180,255,0.18)':'rgba(0,55,115,0.55)';
  ctx.fillRect(_stX,_stY,_stW,_stH);
  ctx.strokeStyle=_stHov?'#00ccff':'rgba(0,140,220,0.75)';
  ctx.lineWidth=_stHov?2:1;
  ctx.shadowBlur=_stHov?20:0;ctx.shadowColor='#00ccff';
  ctx.strokeRect(_stX,_stY,_stW,_stH);ctx.shadowBlur=0;
  const _stSz=Math.max(9,Math.min(_stH*0.38,13));
  ctx.font=`bold ${_stSz}px "Courier New"`;
  ctx.textAlign='center';
  ctx.fillStyle=_stHov?'#00eeff':'rgba(150,205,255,0.92)';
  ctx.fillText(_stMuted?'✕ SOUND OFF':'♪ SOUND ON',_stX+_stW/2,_stY+_stH/2+_stSz*0.36);
  ctx.textAlign='left';
}
function _roundRect(x,y,w,h,r){
  ctx.beginPath();
  ctx.moveTo(x+r,y);ctx.lineTo(x+w-r,y);
  ctx.arcTo(x+w,y,x+w,y+r,r);ctx.lineTo(x+w,y+h-r);
  ctx.arcTo(x+w,y+h,x+w-r,y+h,r);ctx.lineTo(x+r,y+h);
  ctx.arcTo(x,y+h,x,y+h-r,r);ctx.lineTo(x,y+r);
  ctx.arcTo(x,y,x+r,y,r);ctx.closePath();
}
function _getSetupLayout(W,H){
  const cx=W/2;
  const trackW=Math.max(200,Math.min(W*0.55,520));
  const trackX=cx-trackW/2;
  const labelSz=Math.max(9,Math.min(12,W/90));
  const rowH=Math.max(36,H*0.065);
  const sectionGap=Math.max(18,H*0.032);
  const btnH=Math.max(30,H*0.052);
  const tog3W=Math.max(70,W*0.1);
  const tog2W=Math.max(80,W*0.11);
  const togH=Math.max(26,H*0.045);
  const titleH=Math.max(32,H*0.1);
  let y=titleH+Math.max(20,H*0.04);
  const audioHeaderY=y;y+=labelSz*2+8;
  const sliders=[
    {key:'musicVol',label:'MUSIC VOLUME',y},
    {key:'sfxVol',label:'EFFECTS VOLUME',y:y+rowH},
    {key:'uiVol',label:'INTERFACE VOLUME',y:y+rowH*2},
  ];
  y+=rowH*3+sectionGap;
  const displayHeaderY=y;y+=labelSz*2+8;
  const particleY=y;y+=rowH+sectionGap;
  const gameplayHeaderY=y;y+=labelSz*2+8;
  const shakeY=y;y+=rowH+sectionGap;
  const dataHeaderY=y;y+=labelSz*2+8;
  const hofBtnY=y;
  const hofBtnW=Math.max(220,W*0.28),hofBtnH=btnH;
  const backPad=Math.max(20,W*0.03);
  const backH=Math.max(30,H*0.052),backW=Math.max(90,W*0.1);
  const backBtn={x:backPad,y:H-backPad-backH,w:backW,h:backH};
  return{W,H,cx,trackW,trackX,labelSz,rowH,btnH,tog3W,tog2W,togH,
    audioHeaderY,sliders,trackThumbR:8,
    displayHeaderY,particleY,
    gameplayHeaderY,shakeY,
    dataHeaderY,hofBtnY,hofBtnW,hofBtnH,
    backBtn};
}
function _particleBtnRects(L){
  const opts=['full','reduced','off'],totalW=opts.length*L.tog3W+(opts.length-1)*8;
  const startX=L.cx-totalW/2;
  return opts.map((v,i)=>({x:startX+i*(L.tog3W+8),y:L.particleY,val:v}));
}
function _shakeBtnRects(L){
  const labels=['ON','OFF'],vals=[true,false],totalW=2*L.tog2W+8;
  const startX=L.cx-totalW/2;
  return vals.map((v,i)=>({x:startX+i*(L.tog2W+8),y:L.shakeY,val:v,label:labels[i]}));
}
function _drawSlider(L,key,label,cy){
  const val=settings[key];
  const isDragging=setupSliderDrag&&setupSliderDrag.key===key;
  const thumbX=L.trackX+val*L.trackW;
  ctx.font=`bold ${L.labelSz}px "Courier New"`;
  ctx.textAlign='right';
  ctx.fillStyle='rgba(150,205,255,0.85)';
  ctx.fillText(label,L.trackX-14,cy+L.labelSz*0.4);
  ctx.fillStyle='rgba(0,40,90,0.7)';
  _roundRect(L.trackX,cy-5,L.trackW,10,5);ctx.fill();
  ctx.strokeStyle='rgba(0,100,180,0.5)';ctx.lineWidth=1;
  _roundRect(L.trackX,cy-5,L.trackW,10,5);ctx.stroke();
  if(val>0){ctx.fillStyle='rgba(0,180,255,0.5)';_roundRect(L.trackX,cy-5,val*L.trackW,10,5);ctx.fill();}
  ctx.beginPath();ctx.arc(thumbX,cy,isDragging?10:L.trackThumbR,0,Math.PI*2);
  ctx.fillStyle=isDragging?'#00eeff':'#00ccff';
  ctx.shadowBlur=isDragging?18:10;ctx.shadowColor='#00aaff';
  ctx.fill();ctx.shadowBlur=0;
  ctx.textAlign='left';
  ctx.fillStyle='rgba(0,200,255,0.7)';
  ctx.font=`bold ${L.labelSz}px "Courier New"`;
  ctx.fillText(Math.round(val*100)+'%',L.trackX+L.trackW+14,cy+L.labelSz*0.4);
  ctx.textAlign='center';
}
function _drawToggle3(L,key,labels,vals,rects,rowLabel){
  ctx.font=`bold ${L.labelSz}px "Courier New"`;
  ctx.textAlign='right';
  ctx.fillStyle='rgba(150,205,255,0.85)';
  ctx.fillText(rowLabel,rects[0].x-14,rects[0].y+L.togH/2+L.labelSz*0.4);
  for(let i=0;i<rects.length;i++){
    const{x,y,val}=rects[i];const active=settings[key]===val;
    ctx.fillStyle=active?'rgba(0,160,255,0.85)':'rgba(0,40,90,0.7)';
    ctx.fillRect(x,y,L.tog3W,L.togH);
    ctx.strokeStyle=active?'#00ccff':'rgba(0,100,180,0.5)';ctx.lineWidth=active?2:1;
    ctx.shadowBlur=active?12:0;ctx.shadowColor='#00ccff';
    ctx.strokeRect(x,y,L.tog3W,L.togH);ctx.shadowBlur=0;
    ctx.font=`bold ${L.labelSz*0.9}px "Courier New"`;ctx.textAlign='center';
    ctx.fillStyle=active?'#060c18':'rgba(100,170,230,0.7)';
    ctx.fillText(labels[i],x+L.tog3W/2,y+L.togH/2+L.labelSz*0.36);
  }
  ctx.textAlign='center';
}
function _drawToggle2(L,key,rects,rowLabel){
  ctx.font=`bold ${L.labelSz}px "Courier New"`;
  ctx.textAlign='right';
  ctx.fillStyle='rgba(150,205,255,0.85)';
  ctx.fillText(rowLabel,rects[0].x-14,rects[0].y+L.togH/2+L.labelSz*0.4);
  for(let i=0;i<rects.length;i++){
    const{x,y,val,label}=rects[i];const active=settings[key]===val;
    ctx.fillStyle=active?'rgba(0,160,255,0.85)':'rgba(0,40,90,0.7)';
    ctx.fillRect(x,y,L.tog2W,L.togH);
    ctx.strokeStyle=active?'#00ccff':'rgba(0,100,180,0.5)';ctx.lineWidth=active?2:1;
    ctx.shadowBlur=active?12:0;ctx.shadowColor='#00ccff';
    ctx.strokeRect(x,y,L.tog2W,L.togH);ctx.shadowBlur=0;
    ctx.font=`bold ${L.labelSz*0.9}px "Courier New"`;ctx.textAlign='center';
    ctx.fillStyle=active?'#060c18':'rgba(100,170,230,0.7)';
    ctx.fillText(label,x+L.tog2W/2,y+L.togH/2+L.labelSz*0.36);
  }
  ctx.textAlign='center';
}
function _drawHofClearBtn(L,now){
  if(hofClearStep===1&&now>hofClearResetAt)hofClearStep=0;
  const hx=L.cx-L.hofBtnW/2;
  const label=hofClearStep===1?'CONFIRM CLEAR — CLICK AGAIN':'CLEAR HALL OF FAME';
  const hov=mouse.x>=hx&&mouse.x<=hx+L.hofBtnW&&mouse.y>=L.hofBtnY&&mouse.y<=L.hofBtnY+L.hofBtnH;
  ctx.fillStyle=hofClearStep===1?'rgba(120,0,0,0.7)':hov?'rgba(0,180,255,0.18)':'rgba(0,55,115,0.55)';
  ctx.fillRect(hx,L.hofBtnY,L.hofBtnW,L.hofBtnH);
  ctx.strokeStyle=hofClearStep===1?'#ff3333':hov?'#00ccff':'rgba(0,140,220,0.75)';
  ctx.lineWidth=(hov||hofClearStep===1)?2:1;
  ctx.shadowBlur=(hov||hofClearStep===1)?16:0;ctx.shadowColor=hofClearStep===1?'#ff3333':'#00ccff';
  ctx.strokeRect(hx,L.hofBtnY,L.hofBtnW,L.hofBtnH);ctx.shadowBlur=0;
  const bSz=Math.max(9,Math.min(L.hofBtnH*0.36,12));
  ctx.font=`bold ${bSz}px "Courier New"`;
  ctx.fillStyle=hofClearStep===1?'#ff8888':hov?'#00eeff':'rgba(150,205,255,0.92)';
  ctx.textAlign='center';
  ctx.fillText(label,L.cx,L.hofBtnY+L.hofBtnH/2+bSz*0.36);
}
function drawSetupScreen(){
  const W=canvas.width,H=canvas.height;
  ctx.fillStyle='#060c18';ctx.fillRect(0,0,W,H);
  const gs=Math.max(50,Math.min(80,W/14));
  ctx.strokeStyle='rgba(0,80,180,0.09)';ctx.lineWidth=1;
  for(let x=0;x<W;x+=gs){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x,H);ctx.stroke();}
  for(let y=0;y<H;y+=gs){ctx.beginPath();ctx.moveTo(0,y);ctx.lineTo(W,y);ctx.stroke();}
  const L=_getSetupLayout(W,H);
  const now=Date.now();
  ctx.textAlign='center';
  const titleSz=Math.max(20,Math.min(W*0.055,36));
  ctx.font=`bold ${titleSz}px "Courier New"`;
  ctx.shadowBlur=30;ctx.shadowColor='#00aaff';ctx.fillStyle='#00ccff';
  ctx.fillText('SETUP',W/2,Math.max(titleSz+10,H*0.07));
  ctx.shadowBlur=0;
  function _sh(label,y){
    ctx.font=`bold ${L.labelSz*0.85}px "Courier New"`;
    ctx.fillStyle='rgba(0,140,220,0.55)';
    ctx.fillText(label,L.cx,y+L.labelSz);
    ctx.strokeStyle='rgba(0,100,180,0.3)';ctx.lineWidth=1;
    const lw=Math.max(100,W*0.35);
    ctx.beginPath();ctx.moveTo(L.cx-lw/2,y+L.labelSz+5);ctx.lineTo(L.cx+lw/2,y+L.labelSz+5);ctx.stroke();
  }
  _sh('AUDIO',L.audioHeaderY);
  for(const s of L.sliders)_drawSlider(L,s.key,s.label,s.y);
  _sh('DISPLAY',L.displayHeaderY);
  _drawToggle3(L,'particles',['FULL','REDUCED','OFF'],['full','reduced','off'],_particleBtnRects(L),'Particle Intensity');
  _sh('GAMEPLAY',L.gameplayHeaderY);
  _drawToggle2(L,'screenShake',_shakeBtnRects(L),'Screen Shake');
  _sh('DATA',L.dataHeaderY);
  _drawHofClearBtn(L,now);
  if(hofClearFlashMs>0){
    ctx.font=`bold ${L.labelSz*1.1}px "Courier New"`;
    ctx.fillStyle=`rgba(0,220,120,${Math.min(1,hofClearFlashMs/400)})`;
    ctx.fillText('CLEARED',L.cx,L.hofBtnY+L.hofBtnH+L.labelSz*2);
    hofClearFlashMs-=16;
  }
  const{x,y,w,h}=L.backBtn;
  const backHov=mouse.x>=x&&mouse.x<=x+w&&mouse.y>=y&&mouse.y<=y+h;
  ctx.fillStyle=backHov?'rgba(0,180,255,0.18)':'rgba(0,55,115,0.55)';
  ctx.fillRect(x,y,w,h);
  ctx.strokeStyle=backHov?'#00ccff':'rgba(0,140,220,0.75)';
  ctx.lineWidth=backHov?2:1;
  ctx.shadowBlur=backHov?20:0;ctx.shadowColor='#00ccff';
  ctx.strokeRect(x,y,w,h);ctx.shadowBlur=0;
  const bSz=Math.max(9,Math.min(h*0.38,13));
  ctx.font=`bold ${bSz}px "Courier New"`;
  ctx.fillStyle=backHov?'#00eeff':'rgba(150,205,255,0.92)';
  ctx.fillText('◀ BACK',x+w/2,y+h/2+bSz*0.36);
  ctx.textAlign='left';
}
function drawWaveClearScreen(){
  const cx=canvas.width/2,cy=canvas.height/2;
  ctx.textAlign='center';ctx.font='bold 58px "Courier New"';ctx.shadowBlur=30;ctx.shadowColor='#00ff88';ctx.fillStyle='#00ff88';ctx.fillText('WAVE CLEAR',cx,cy-18);ctx.shadowBlur=0;
  ctx.font='22px "Courier New"';ctx.fillStyle='#aaffcc';ctx.shadowBlur=14;ctx.shadowColor='#aaffcc';ctx.fillText(`SCORE: ${String(score).padStart(8,'0')}`,cx,cy+36);ctx.shadowBlur=0;
  if(wave<=TOTAL_WAVES){ctx.font='14px "Courier New"';ctx.fillStyle='#77aacc';ctx.fillText(`WAVE ${wave} INCOMING`,cx,cy+70);if(Math.floor(Date.now()/550)%2===0){ctx.font='12px "Courier New"';ctx.fillStyle='rgba(100,150,200,0.65)';ctx.fillText('PRESS SPACE TO CONTINUE',cx,cy+90);}}
  ctx.textAlign='left';
}
function drawGameoverScreen(){
  overlay(0.78);const cx=canvas.width/2,cy=canvas.height/2,t=Date.now()/1000;
  ctx.textAlign='center';ctx.font='bold 62px "Courier New"';ctx.shadowBlur=32;ctx.shadowColor='#ff2244';ctx.fillStyle='#ff2244';ctx.fillText('DRONE DESTROYED',cx,cy-75);ctx.shadowBlur=0;
  ctx.font='27px "Courier New"';ctx.fillStyle='#ffcc00';ctx.shadowBlur=16;ctx.shadowColor='#ffcc00';ctx.fillText(`FINAL SCORE: ${String(score).padStart(8,'0')}`,cx,cy-10);ctx.shadowBlur=0;
  ctx.font='14px "Courier New"';ctx.fillStyle='#aaccff';ctx.fillText(`CRAFT: ${CRAFTS[P.craftIdx].name}  ·  WAVE: ${wave}/${TOTAL_WAVES}  ·  KILLS: ${P.kills}`,cx,cy+28);
  if(Math.floor(t*1.7)%2===0){ctx.font='bold 17px "Courier New"';ctx.fillStyle='#00ccff';ctx.shadowBlur=14;ctx.shadowColor='#00ccff';ctx.fillText('[ SPACE — RETURN TO HANGAR ]',cx,cy+90);ctx.shadowBlur=0;}
  ctx.textAlign='left';
}
function drawVictoryScreen(){
  overlay(0.78);const cx=canvas.width/2,cy=canvas.height/2,t=Date.now()/1000;
  ctx.textAlign='center';ctx.font='bold 60px "Courier New"';ctx.shadowBlur=35;ctx.shadowColor='#ffdd00';ctx.fillStyle='#ffdd00';ctx.fillText('MISSION COMPLETE',cx,cy-75);ctx.shadowBlur=0;
  ctx.font='26px "Courier New"';ctx.fillStyle='#00ff88';ctx.shadowBlur=18;ctx.shadowColor='#00ff88';ctx.fillText(`FINAL SCORE: ${String(score).padStart(8,'0')}`,cx,cy-10);ctx.shadowBlur=0;
  ctx.font='16px "Courier New"';ctx.fillStyle='#aaccff';ctx.fillText(`${CRAFTS[P.craftIdx].name}  ·  ALL ${TOTAL_WAVES} WAVES  ·  KILLS: ${P.kills}`,cx,cy+28);
  if(Math.floor(t*1.7)%2===0){ctx.font='bold 17px "Courier New"';ctx.fillStyle='#00ccff';ctx.shadowBlur=14;ctx.shadowColor='#00ccff';ctx.fillText('[ SPACE — PLAY AGAIN ]',cx,cy+90);ctx.shadowBlur=0;}
  ctx.textAlign='left';
}

// ─── GAME INIT ────────────────────────────────────────────────────
function spawnWave(n){
  pBullets.length=0;eBullets.length=0;mines.length=0;seekers.length=0;rockets.length=0;boomerangs.length=0;fractals.length=0;hazards.length=0;grenades.length=0;gravityWells.length=0;faradayCages.length=0;
  waveStartTime=Date.now();
  // JR persists through waves — only reset if not currently active
  if(!miniMe.active){miniMe.lost=false;}
  generateObstacles();spawnHiddenPickups();spawnWaveEnemies(n);SFX.wave();
  // Hazard obstacles — scale with wave
  if(n>=2) spawnHazardZaps(n>=4?4:2, 200,WORLD_W-200, 200,WORLD_H-200, 80,160);
  if(n>=3) spawnHazardMines(n>=4?5:3, 200,WORLD_W-200, 200,WORLD_H-200, WORLD_W/2,WORLD_H/2, 350);
  if(P.unlockedW.has(WEAPONS.findIndex(w=>w.id==='mine'))){
    P.mineStock=enemies.length;
  }
  if(P.unlockedW.has(WEAPONS.findIndex(w=>w.id==='seekr'))){
    const half=Math.ceil(enemies.length/2);
    P.seekStock=half+Math.floor(Math.random()*(enemies.length*3-half+1));
  }
}
// ─── TIME TRIAL FUNCTIONS ─────────────────────────────────────────
const TT_WORLD_W=20800; // ~8x battle world width
const TT_FINISH_X=TT_WORLD_W-160; // finish line x position

function generateTTObstacles(){
  obstacles=[];
  const spawnX=120, spawnY=WORLD_H/2, finX=TT_FINISH_X;
  const H=WORLD_H;
  const SPAWN_CLEAR=340, FINISH_CLEAR=280;

  // Helper — push only if far enough from spawn/finish and not overlapping
  function tryPush(obs,clearR=40){
    const cx=obs.type==='pillar'?obs.x:obs.x+obs.w/2;
    const cy=obs.type==='pillar'?obs.y:obs.y+obs.h/2;
    if(dist(cx,cy,spawnX,spawnY)<SPAWN_CLEAR) return false;
    if(dist(cx,cy,finX,H/2)<FINISH_CLEAR) return false;
    obstacles.push(obs); return true;
  }

  // ── PASS 1: Edge-channel blockers ────────────────────────────────
  // Place tall vertical walls near top/bottom edges at staggered intervals
  // so the player cannot skate along the boundary unimpeded.
  const edgeStep=480;
  for(let x=700; x<finX-300; x+=edgeStep+(rng(-80,80))){
    // Bottom-edge cluster: wall hugging the floor
    const bLen=rng(H*0.22, H*0.38);
    tryPush({type:'wall', x:x-13, y:H-bLen-rng(4,18), w:26, h:bLen});
    // Top-edge cluster: wall hugging the ceiling
    const tLen=rng(H*0.22, H*0.38);
    tryPush({type:'wall', x:x+edgeStep*0.44-13, y:rng(4,18), w:26, h:tLen});
  }

  // ── PASS 2: Choke-point clusters ─────────────────────────────────
  // Every ~1100px, place a dense cluster of pillars across the height,
  // leaving only 1–2 navigable gaps so the player must choose a path.
  const chokeStep=1100;
  for(let x=900; x<finX-400; x+=chokeStep+(rng(-120,120))){
    const numPillars=Math.floor(rng(4,7));
    const ySlots=[];
    for(let s=0;s<numPillars;s++) ySlots.push(rng(50, H-50));
    ySlots.sort((a,b)=>a-b);
    // Ensure no two are so close they form an impassable wall
    for(let s=0;s<ySlots.length;s++){
      const r=rng(28,44);
      const ox=x+rng(-60,60), oy=ySlots[s];
      tryPush({type:'pillar', x:ox, y:oy, r, rot:rng(0,Math.PI)}, r+30);
    }
  }

  // ── PASS 3: Horizontal gauntlet walls ────────────────────────────
  // Long horizontal walls placed in the middle vertical zone at intervals,
  // forcing the player to go above or below them.
  const hGauntletStep=820;
  for(let x=600; x<finX-200; x+=hGauntletStep+(rng(-100,100))){
    const len=rng(H*0.28, H*0.52);
    const yCenter=H*0.5+rng(-H*0.18, H*0.18);
    tryPush({type:'wall', x:x-len/2, y:yCenter-13, w:len, h:26});
  }

  // ── PASS 4: Dense random pillar fill ─────────────────────────────
  // 130 more pillars scattered across the full height (including near edges)
  for(let i=0;i<130;i++){
    let x,y,att=0;
    do{
      x=rng(450, finX-180);
      // Bias toward edges 30% of the time to keep corridors uncomfortable
      y=Math.random()<0.3 ? (Math.random()<0.5 ? rng(18,H*0.22) : rng(H*0.78,H-18)) : rng(18,H-18);
      att++;
    } while(att<60 && circleVsObs(x,y,50));
    tryPush({type:'pillar', x, y, r:rng(20,42), rot:rng(0,Math.PI)});
  }

  // ── PASS 5: Random wall fill ──────────────────────────────────────
  // 60 shorter walls at varied orientations to break up open channels
  for(let i=0;i<60;i++){
    let x,y,att=0;
    const vert=Math.random()<0.5;
    const maxLen=vert ? Math.min(H*0.32,220) : 200;
    const len=rng(70, maxLen);
    do{
      x=rng(500, finX-280);
      y=Math.random()<0.25 ? (Math.random()<0.5 ? rng(10,H*0.25) : rng(H*0.75,H-10)) : rng(10,H-10);
      att++;
    } while(att<50 && (dist(x,y,spawnX,spawnY)<SPAWN_CLEAR || circleVsObs(x,y,28)));
    tryPush({type:'wall', x:x-(vert?13:len/2), y:y-(vert?len/2:13), w:vert?26:len, h:vert?len:26});
  }
}

function spawnTTEnemies(){
  enemies.length=0;
  const placed=[];
  const H=WORLD_H, MARGIN=55;
  // Sections: [xMin, xMax, scouts, guards, turrets, darts, wraiths, brutes, phantoms]
  const sections=[
    [500,   1800,  2,0,0, 0,0,0,0],
    [1800,  3000,  3,1,0, 1,0,0,0],
    [3000,  4200,  2,2,1, 1,0,1,0],
    [4200,  5400,  2,2,2, 2,0,1,0],
    [5400,  6600,  2,2,2, 1,1,0,1],
    [6600,  7800,  2,2,2, 1,1,1,0],
    [7800,  9200,  2,2,2, 2,1,1,1],
    [9200,  10400, 1,2,2, 1,1,0,1],
    [10400, 11600, 2,2,1, 2,1,1,0],
    [11600, 12800, 2,2,2, 2,2,1,1],
    [12800, 14000, 2,3,2, 2,2,1,1],
    [14000, 15200, 2,3,3, 2,2,1,1],
    [15200, 16400, 2,3,3, 2,2,2,1],
    [16400, 17600, 2,3,4, 2,2,2,1],
    [17600, 18800, 1,3,4, 2,2,2,2],
    [18800, TT_FINISH_X-200, 1,3,3, 2,2,2,1],
  ];
  for(const [x1,x2,sc,gu,tu,da,wr,br,ph] of sections){
    const addInBand=(type,count)=>{
      for(let i=0;i<count;i++){
        let x,y,a=0;
        do{x=rng(x1,x2);y=rng(MARGIN,H-MARGIN);a++;}
        while(a<40&&(circleVsObs(x,y,22)||placed.some(p=>dist(x,y,p.x,p.y)<70)));
        const e=mkEnemy(type,x,y); enemies.push(e); placed.push({x,y});
      }
    };
    addInBand('scout',sc);addInBand('guard',gu);addInBand('turret',tu);
    addInBand('dart',da);addInBand('wraith',wr);addInBand('brute',br);addInBand('phantom',ph);
  }
  // Hazards scattered along the corridor
  spawnHazardZaps(22, 800, TT_WORLD_W-400, MARGIN+10, H-MARGIN-10, 70,140);
  spawnHazardMines(18, 800, TT_WORLD_W-400, MARGIN+10, H-MARGIN-10, P.x, P.y, 500);
  // Boss near finish line (Ghost Run L1)
  {
    const _bPool=['dreadnought','harbinger'];
    const _bType=_bPool[Math.floor(Math.random()*_bPool.length)];
    const _bE=mkEnemy(_bType,TT_FINISH_X-600,WORLD_H/2);
    enemies.push(_bE);
    if(_bType==='harbinger') harbingerRef=_bE;
  }
}

function formatTTTime(ms){
  const min=Math.floor(ms/60000);
  const sec=Math.floor((ms%60000)/1000);
  const cs=Math.floor((ms%1000)/10);
  return `${String(min).padStart(2,'0')}:${String(sec).padStart(2,'0')}:${String(cs).padStart(2,'0')}`;
}

function computeTTFinalScore(){
  const timeSec=ttElapsed/1000;
  const timeBase=Math.max(0,(600-timeSec)*500); // up to 300,000 at t=0
  const hpFactor=0.4+0.6*(P.hp/P.maxHp);
  const batFactor=0.4+0.6*(P.bat/P.maxBat);
  const survivalFactor=(hpFactor+batFactor)/2;
  const pointMultiplier=1+(score/50000);
  const killPct=ttTotalEnemies>0?P.kills/ttTotalEnemies:0;
  const killFactor=0.5+0.5*killPct; // 0.5 at 0 kills → 1.0 at 100% kills
  ttFinalScore=Math.floor(timeBase*survivalFactor*pointMultiplier*killFactor);
}

function drawFinishLine(){
  const sx=TT_FINISH_X-camX;
  if(sx<-20||sx>canvas.width+20)return;
  const now=Date.now()/1000,pulse=0.7+0.3*Math.sin(now*3);
  // Checkerboard columns
  const checkH=28,numChecks=Math.ceil(canvas.height/checkH);
  for(let i=0;i<numChecks;i++){
    const sy=i*checkH;
    ctx.fillStyle=i%2===0?'rgba(255,255,255,0.55)':'rgba(10,10,10,0.45)';
    ctx.fillRect(sx-8,sy,8,checkH);
    ctx.fillStyle=i%2===0?'rgba(10,10,10,0.45)':'rgba(255,255,255,0.55)';
    ctx.fillRect(sx,sy,8,checkH);
  }
  // Glow line
  ctx.shadowBlur=24*pulse;ctx.shadowColor='#ffdd00';
  ctx.strokeStyle=`rgba(255,220,0,${0.88*pulse})`;ctx.lineWidth=3;
  ctx.beginPath();ctx.moveTo(sx,0);ctx.lineTo(sx,canvas.height);ctx.stroke();
  ctx.shadowBlur=0;
  // Vertical label
  ctx.save();ctx.translate(sx+26,canvas.height/2);ctx.rotate(-Math.PI/2);
  ctx.font='bold 20px "Courier New"';
  ctx.fillStyle=`rgba(255,220,50,${0.95*pulse})`;ctx.shadowBlur=12;ctx.shadowColor='#ffaa00';
  ctx.textAlign='center';ctx.fillText('◇  FINISH LINE  ◇',0,0);
  ctx.shadowBlur=0;ctx.restore();
}

function drawTimeTrialResult(){
  ctx.fillStyle='rgba(4,10,26,0.90)';ctx.fillRect(0,0,canvas.width,canvas.height);
  const cx=canvas.width/2,cy=canvas.height/2;
  // Header
  ctx.textAlign='center';
  ctx.font='bold 52px "Courier New"';
  ctx.fillStyle='#ffdd00';ctx.shadowBlur=40;ctx.shadowColor='#ffaa00';
  ctx.fillText(ttLevel===2?'BOMBS DISARMED':ttLevel===4?'ALL J R SAFE':ttLevel===5?'TOUCH N GO — COMPLETE':'TRIAL COMPLETE',cx,cy-128);ctx.shadowBlur=0;
  // Time
  ctx.font='bold 38px "Courier New"';
  ctx.fillStyle='#00eeff';ctx.shadowBlur=22;ctx.shadowColor='#00aaff';
  ctx.fillText(formatTTTime(ttElapsed),cx,cy-66);ctx.shadowBlur=0;
  ctx.font='12px "Courier New"';ctx.fillStyle='rgba(80,140,200,0.6)';
  ctx.fillText('COMPLETION TIME',cx,cy-46);
  // Stat line
  const hpPct=Math.round(P.hp/P.maxHp*100);
  const batPct=Math.round(P.bat/P.maxBat*100);
  const killPct=ttTotalEnemies>0?Math.round(P.kills/ttTotalEnemies*100):0;
  ctx.font='13px "Courier New"';ctx.fillStyle='rgba(120,200,255,0.75)';
  ctx.fillText(`CRAFT ${hpPct}%  ·  BATTERY ${batPct}%  ·  KILLS ${P.kills}/${ttTotalEnemies} (${killPct}%)  ·  SCORE ${score.toLocaleString()}`,cx,cy+2);
  // Score formula hint
  ctx.font='10px "Courier New"';ctx.fillStyle='rgba(80,120,180,0.5)';
  ctx.fillText('TIME BONUS × SURVIVAL × KILL FACTOR × POINT MULTIPLIER',cx,cy+22);
  // Final score
  ctx.font='bold 30px "Courier New"';
  ctx.fillStyle='#00ff88';ctx.shadowBlur=20;ctx.shadowColor='#00cc66';
  ctx.fillText(`FINAL SCORE  ${ttFinalScore.toLocaleString()}`,cx,cy+68);ctx.shadowBlur=0;
  // Return button
  const bw=260,bh=46,bx=cx-bw/2,by=cy+104;
  const bhov=mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh;
  ctx.fillStyle=bhov?'rgba(0,180,255,0.22)':'rgba(0,0,0,0.6)';
  roundRect(ctx,bx,by,bw,bh,10);ctx.fill();
  ctx.strokeStyle=bhov?'#00ccff':'rgba(0,140,220,0.6)';ctx.lineWidth=2;
  roundRect(ctx,bx,by,bw,bh,10);ctx.stroke();
  ctx.font='bold 14px "Courier New"';
  ctx.fillStyle=bhov?'#00eeff':'rgba(100,180,255,0.9)';
  ctx.fillText('RETURN TO BASE',cx,by+bh/2+5);
  ctx.textAlign='left';
}

// ═══════════════════════════════════════════════════════════════
// TIME TRIAL LEVEL 2 — NUCLEAR DISARM
// ═══════════════════════════════════════════════════════════════
function generateNukeObstacles(){
  obstacles.length=0;
  const W=WORLD_W,H=WORLD_H,MARGIN=80;
  // Dense random pillars
  for(let i=0;i<80;i++){
    let x,y,a=0;
    do{x=rng(MARGIN,W-MARGIN);y=rng(MARGIN,H-MARGIN);a++;}
    while(a<50&&(nukes.some(n=>dist(x,y,n.x,n.y)<220)||obstacles.some(o=>o.type==='pillar'&&dist(x,y,o.x,o.y)<90)));
    obstacles.push({type:'pillar',x,y,r:rng(18,32)});
  }
  // Horizontal walls creating corridors
  for(let i=0;i<22;i++){
    let x,y,a=0;
    do{x=rng(MARGIN,W-500);y=rng(MARGIN,H-MARGIN);a++;}
    while(a<40&&nukes.some(n=>dist(x+200,y,n.x,n.y)<240));
    obstacles.push({type:'wall',x,y,w:rng(200,520),h:rng(18,26)});
  }
  // Vertical wall segments
  for(let i=0;i<18;i++){
    let x,y,a=0;
    do{x=rng(MARGIN,W-MARGIN);y=rng(MARGIN,H-400);a++;}
    while(a<40&&nukes.some(n=>dist(x,y+160,n.x,n.y)<240));
    obstacles.push({type:'wall',x,y,w:rng(18,26),h:rng(180,360)});
  }
}

function spawnNukeEnemies(){
  enemies.length=0;
  const placed=[];
  const W=WORLD_W,H=WORLD_H,MARGIN=60;
  // Guard each nuke with a lighter cluster (was 2 guards+1 brute+2 turrets+1 wraith = 6)
  for(const n of nukes){
    for(let i=0;i<1;i++){let x,y,a=0;do{x=n.x+rng(-300,300);y=n.y+rng(-300,300);a++;}while(a<30&&(circleVsObs(x,y,22)||placed.some(p=>dist(x,y,p.x,p.y)<70)));enemies.push(mkEnemy('guard',x,y));placed.push({x,y});}
    for(let i=0;i<1;i++){let x,y,a=0;do{x=n.x+rng(-220,220);y=n.y+rng(-220,220);a++;}while(a<30&&(circleVsObs(x,y,22)||placed.some(p=>dist(x,y,p.x,p.y)<70)));enemies.push(mkEnemy('brute',x,y));placed.push({x,y});}
    for(let i=0;i<1;i++){let x,y,a=0;do{x=n.x+rng(-220,220);y=n.y+rng(-220,220);a++;}while(a<30&&(circleVsObs(x,y,22)||placed.some(p=>dist(x,y,p.x,p.y)<70)));enemies.push(mkEnemy('turret',x,y));placed.push({x,y});}
    // One wraith per bomb site
    let wx,wy,wa=0;do{wx=n.x+rng(-280,280);wy=n.y+rng(-280,280);wa++;}while(wa<30&&(circleVsObs(wx,wy,22)||placed.some(p=>dist(wx,wy,p.x,p.y)<70)));
    enemies.push(mkEnemy('wraith',wx,wy));placed.push({x:wx,y:wy});
  }
  // Roaming patrols — reduced to 19
  const patrolTypes=['scout','scout','guard','dart','dart','phantom'];
  for(let i=0;i<19;i++){
    let x,y,a=0;do{x=rng(MARGIN,W-MARGIN);y=rng(MARGIN,H-MARGIN);a++;}while(a<40&&(circleVsObs(x,y,22)||placed.some(p=>dist(x,y,p.x,p.y)<70)));
    const t=patrolTypes[i%patrolTypes.length];
    enemies.push(mkEnemy(t,x,y));placed.push({x,y});
  }
  // Hazards scattered around the open world
  spawnHazardZaps(12, MARGIN+20, W-MARGIN-20, MARGIN+20, H-MARGIN-20, 80,150);
  spawnHazardMines(10, MARGIN+20, W-MARGIN-20, MARGIN+20, H-MARGIN-20, W/2, H-120, 400);
}

function spawnNukeKeys(){
  // Place one key pickup per bomb, spread away from the corresponding bomb
  for(let id=0;id<4;id++){
    const n=nukes[id];
    let x,y,a=0;
    // Put it in the opposite quadrant from the bomb for tension
    const qx=n.x<WORLD_W/2?WORLD_W*0.55:WORLD_W*0.1;
    const qy=n.y<WORLD_H/2?WORLD_H*0.55:WORLD_H*0.1;
    do{
      x=qx+rng(-WORLD_W*0.18,WORLD_W*0.18);
      y=qy+rng(-WORLD_H*0.18,WORLD_H*0.18);
      a++;
    }while(a<60&&(circleVsObs(x,y,22)||dist(x,y,P.x,P.y)<280));
    pickups.push({x,y,type:'nuke_key',bombId:id,nukeColor:NUKE_COLORS[id],t:rng(0,Math.PI*2),hidden:false,dropTimer:null});
  }
}

function placeNukes(){
  nukes=[];
  const W=WORLD_W,H=WORLD_H;
  // Four bombs in distinct quadrants, well away from spawn
  const quadrants=[
    [W*0.15,W*0.45, H*0.12,H*0.45],
    [W*0.55,W*0.85, H*0.12,H*0.45],
    [W*0.15,W*0.45, H*0.55,H*0.88],
    [W*0.55,W*0.85, H*0.55,H*0.88],
  ];
  for(let id=0;id<4;id++){
    const [x1,x2,y1,y2]=quadrants[id];
    let x,y,a=0;
    do{x=rng(x1,x2);y=rng(y1,y2);a++;}while(a<60&&circleVsObs(x,y,50));
    nukes.push({x,y,id,armed:true,disarmProgress:0,color:NUKE_COLORS[id],name:NUKE_NAMES[id]});
  }
}

function tickNukes(dt){
  for(const n of nukes){
    if(!n.armed) continue;
    const d=dist(P.x,P.y,n.x,n.y);
    if(d<NUKE_DISARM_RANGE&&P.nukeKeys.has(n.id)){
      n.disarmProgress+=dt*1000;
      if(n.disarmProgress>=NUKE_DISARM_TIME){
        n.armed=false;n.disarmProgress=0;
        spawnParts(n.x,n.y,n.color,_pCount(30),5,8,800);
        spawnParts(n.x,n.y,'#ffffff',_pCount(12),3,5,500);
        SFX.confirm();
        weaponFlash={name:`✔ ${n.name} DISARMED`,ms:3500};
        // Check all disarmed
        if(nukes.every(nb=>!nb.armed)){
          ttFinished=true;ttElapsed=performance.now()-ttStartTime;
          computeNukeFinalScore();saveHighScore('timetrial_2',ttFinalScore,ttElapsed);SFX.wave();
          setTimeout(()=>{gameState='timeTrialResult';},800);
        }
      }
    } else {
      // Decay at half the gain rate — brief interruptions don't reset progress
      n.disarmProgress=Math.max(0,n.disarmProgress-dt*500);
    }
  }
}

function drawNukes(){
  const t=Date.now()/1000;
  for(const n of nukes){
    const sx=n.x-camX, sy=n.y-camY;
    if(sx<-80||sx>canvas.width+80||sy<-80||sy>canvas.height+80) continue;
    const col=n.color;
    ctx.save();ctx.translate(sx,sy);
    if(n.armed){
      // Outer warning pulse
      const pulse=0.5+0.5*Math.sin(t*4);
      ctx.beginPath();ctx.arc(0,0,36+pulse*6,0,Math.PI*2);
      ctx.strokeStyle=col+'55';ctx.lineWidth=2;ctx.shadowBlur=0;ctx.stroke();
      // Body — cylindrical bomb silhouette
      ctx.fillStyle='rgba(20,20,30,0.92)';ctx.strokeStyle=col;ctx.lineWidth=2.5;ctx.shadowBlur=22;ctx.shadowColor=col;
      roundRect(ctx,-18,-26,36,52,6);ctx.fill();ctx.stroke();ctx.shadowBlur=0;
      // Fins
      ctx.fillStyle=col+'aa';
      ctx.fillRect(-22,14,6,12);ctx.fillRect(16,14,6,12);
      // Warning stripes
      ctx.strokeStyle=col;ctx.lineWidth=1.5;
      for(let i=0;i<3;i++){ctx.beginPath();ctx.moveTo(-16,-16+i*10);ctx.lineTo(16,-16+i*10);ctx.stroke();}
      // Label
      ctx.font='bold 9px "Courier New"';ctx.fillStyle=col;ctx.textAlign='center';
      ctx.fillText(n.name,-2,2);
      // Disarm progress bar
      if(n.disarmProgress>0){
        const pct=n.disarmProgress/NUKE_DISARM_TIME;
        ctx.fillStyle='rgba(0,0,0,0.6)';ctx.fillRect(-20,30,40,6);
        ctx.fillStyle=col;ctx.shadowBlur=8;ctx.shadowColor=col;
        ctx.fillRect(-20,30,40*pct,6);ctx.shadowBlur=0;
      }
      // Key required indicator — show lock if key not held
      if(!P.nukeKeys.has(n.id)){
        ctx.font='13px "Courier New"';ctx.fillStyle=col+'88';ctx.fillText('🔒',-2,52);
      } else {
        ctx.font='9px "Courier New"';ctx.fillStyle='#00ff88';
        ctx.fillText('HOLD TO DISARM',-2,52);
      }
    } else {
      // Disarmed — dim greyed-out
      ctx.globalAlpha=0.3;
      ctx.fillStyle='#223322';ctx.strokeStyle='#447744';ctx.lineWidth=1.5;
      roundRect(ctx,-18,-26,36,52,6);ctx.fill();ctx.stroke();
      ctx.font='8px "Courier New"';ctx.fillStyle='#44aa44';ctx.textAlign='center';
      ctx.fillText('SAFE',-2,2);
      ctx.globalAlpha=1;
    }
    ctx.restore();
  }
}

function drawNukeMinimap(mx,my,mw,mh){
  const scx=mw/WORLD_W, scy=mh/WORLD_H;
  for(const n of nukes){
    ctx.beginPath();ctx.arc(mx+n.x*scx,my+n.y*scy,n.armed?5:3,0,Math.PI*2);
    ctx.fillStyle=n.armed?n.color:'#334433';
    ctx.shadowBlur=n.armed?8:0;ctx.shadowColor=n.color;
    ctx.fill();ctx.shadowBlur=0;
    // Key pickup dots
  }
}

function drawNukeHUD(){
  // 4 bomb status icons — top-center
  const T=IS_TOUCH, pad=T?10:18;
  const cx=canvas.width/2;
  const ms=ttFinished?ttElapsed:(performance.now()-ttStartTime);
  ctx.textAlign='center';
  ctx.font=`bold ${T?13:18}px "Courier New"`;ctx.fillStyle='#00ccff';ctx.shadowBlur=10;ctx.shadowColor='#0088cc';
  ctx.fillText(formatTTTime(ms),cx,pad+(T?14:20));ctx.shadowBlur=0;
  // Bomb icons row
  const iconW=T?32:42, iconH=T?22:28, gap=T?8:12;
  const totalW=4*iconW+3*gap;
  const startX=cx-totalW/2;
  const iconY=pad+(T?26:40);
  for(let i=0;i<4;i++){
    const n=nukes[i];
    if(!n) continue;
    const ix=startX+i*(iconW+gap), iy=iconY;
    const hasKey=P.nukeKeys.has(i);
    const isDisarming=n.armed&&hasKey&&n.disarmProgress>0;
    // Background
    ctx.fillStyle=n.armed?(hasKey?n.color+'33':'rgba(40,10,10,0.8)'):'rgba(10,40,10,0.85)';
    roundRect(ctx,ix,iy,iconW,iconH,4);ctx.fill();
    // Border
    ctx.strokeStyle=n.armed?(hasKey?n.color:'#662222'):'#44aa44';
    ctx.lineWidth=isDisarming?2.5:1.5;
    ctx.shadowBlur=isDisarming?10:0;ctx.shadowColor=n.color;
    roundRect(ctx,ix,iy,iconW,iconH,4);ctx.stroke();ctx.shadowBlur=0;
    // Disarm progress fill
    if(isDisarming){
      const pct=n.disarmProgress/NUKE_DISARM_TIME;
      ctx.fillStyle=n.color+'88';
      ctx.save();ctx.beginPath();roundRect(ctx,ix,iy,iconW*pct,iconH,4);ctx.fill();ctx.restore();
    }
    // Label
    ctx.font=`bold ${T?7:9}px "Courier New"`;
    ctx.textAlign='center';
    if(!n.armed){
      ctx.fillStyle='#44ff88';ctx.shadowBlur=6;ctx.shadowColor='#44ff88';
      ctx.fillText('✔ SAFE',ix+iconW/2,iy+iconH/2+3);ctx.shadowBlur=0;
    } else if(isDisarming){
      const pct=Math.round(n.disarmProgress/NUKE_DISARM_TIME*100);
      ctx.fillStyle='#ffffff';
      ctx.fillText(`${pct}%`,ix+iconW/2,iy+iconH/2+3);
    } else if(hasKey){
      ctx.fillStyle=n.color;ctx.fillText('ARMED',ix+iconW/2,iy+iconH/2+3);
    } else {
      ctx.fillStyle='#884444';ctx.fillText('🔒',ix+iconW/2,iy+iconH/2+4);
    }
  }
  // Disarmed count summary below badges
  const disarmedCount=nukes.filter(n=>!n.armed).length;
  if(disarmedCount>0){
    ctx.font=`${T?7:9}px "Courier New"`;
    ctx.fillStyle='#44ff88';ctx.shadowBlur=4;ctx.shadowColor='#44ff88';
    ctx.fillText(`${disarmedCount}/4 DISARMED`,cx,iconY+iconH+(T?10:13));
    ctx.shadowBlur=0;
  }
}

function computeNukeFinalScore(){
  const timeSec=ttElapsed/1000;
  const timeBase=Math.max(0,(900-timeSec)*600);
  const hpFactor=0.4+0.6*(P.hp/P.maxHp);
  const batFactor=0.4+0.6*(P.bat/P.maxBat);
  const survivalFactor=(hpFactor+batFactor)/2;
  const pointMultiplier=1+(score/50000);
  const killPct=ttTotalEnemies>0?P.kills/ttTotalEnemies:0;
  const killFactor=0.5+0.5*killPct;
  ttFinalScore=Math.floor(timeBase*survivalFactor*pointMultiplier*killFactor);
}

function startNukeDisarm(){
  _hideAllAds();
  WORLD_W=TT_WORLD_W2;WORLD_H=TT_WORLD_H2;
  score=0;wave=1;bossWarning=0;empFlash=0;weaponFlash={name:'',ms:0};
  leechFlash={active:false,tx:0,ty:0,ms:0};shockwaveFlash={ms:0};
  portalActive=false;portalPositions=[];nukes=[];
  particles.length=0;pickups.length=0;pBullets.length=0;eBullets.length=0;mines.length=0;seekers.length=0;rockets.length=0;boomerangs.length=0;fractals.length=0;hazards.length=0;grenades.length=0;gravityWells.length=0;faradayCages.length=0;
  miniMe.active=false;miniMe.lost=false;miniMe.hp=MM_HP;miniMe.iframes=0;
  resetPlayer();
  P.x=WORLD_W/2;P.y=WORLD_H-120;camX=P.x-canvas.width/2;camY=P.y-canvas.height/2;
  placeNukes();
  generateNukeObstacles();
  spawnHiddenPickups();
  spawnNukeKeys();
  spawnNukeEnemies();
  ttTotalEnemies=enemies.length;
  if(P.unlockedW.has(WEAPONS.findIndex(w=>w.id==='seekr'))){
    const half=Math.ceil(enemies.length/2);
    P.seekStock=half+Math.floor(Math.random()*(enemies.length*3-half+1));
  }
  ttStartTime=performance.now();ttElapsed=0;ttFinished=false;ttFinalScore=0;
  gameStartTime=Date.now();gameState='playing';_snapMouseToPlayer();
}

// ─── TIME TRIAL LEVEL SELECT SCREEN ──────────────────────────────
function drawTTLevelSelect(){
  ctx.fillStyle='#050d1a';ctx.fillRect(0,0,canvas.width,canvas.height);
  ctx.strokeStyle='rgba(0,80,160,0.08)';ctx.lineWidth=1;
  const W=canvas.width,H=canvas.height,cx=W/2;
  for(let x=0;x<W;x+=70){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x,H);ctx.stroke();}
  for(let y=0;y<H;y+=70){ctx.beginPath();ctx.moveTo(0,y);ctx.lineTo(W,y);ctx.stroke();}
  ctx.textAlign='center';
  ctx.font='bold 36px "Courier New"';ctx.fillStyle='#00ccff';ctx.shadowBlur=26;ctx.shadowColor='#0088cc';
  ctx.fillText('TIME TRIALS',cx,46);ctx.shadowBlur=0;
  ctx.font='11px "Courier New"';ctx.fillStyle='rgba(80,140,200,0.55)';
  ctx.fillText('SELECT YOUR MISSION',cx,68);

  const headerH=88,backH=52,padV=12;
  const availH=H-headerH-backH-padV*3;
  const rowGap=Math.max(10,Math.min(20,availH*0.04));
  const cardH=Math.max(110,Math.min(240,Math.floor((availH-rowGap)/2)));
  const cardW=Math.max(140,Math.min(240,Math.floor((W-80)/3)-12));
  const gap=Math.max(8,Math.min(20,Math.floor((W-cardW*3-80)/2)));
  const totalW=cardW*3+gap*2;
  const row1Y=headerH;
  const c1X=cx-totalW/2,c2X=c1X+cardW+gap,c3X=c2X+cardW+gap;
  const now=Date.now()/1000;
  const iconH=Math.min(44,cardH*0.32); // height reserved for icon at top of card

  function _card(bx,ry,col,hcol,title,lines,iconFn){
    const hov=mouse.x>bx&&mouse.x<bx+cardW&&mouse.y>ry&&mouse.y<ry+cardH;
    ctx.fillStyle=hov?`rgba(${col},0.12)`:'rgba(6,14,34,0.80)';
    roundRect(ctx,bx,ry,cardW,cardH,8);ctx.fill();
    ctx.strokeStyle=hov?hcol:`rgba(${col},0.35)`;ctx.lineWidth=hov?2:1;
    ctx.shadowBlur=hov?16:0;ctx.shadowColor=hcol;
    roundRect(ctx,bx,ry,cardW,cardH,8);ctx.stroke();ctx.shadowBlur=0;
    const pad=Math.max(10,cardW*0.07);
    const ccx=bx+cardW/2;
    // Icon area — centred in top portion with padding
    const iconAreaH=Math.min(50,cardH*0.35);
    ctx.save();ctx.translate(ccx,ry+pad+iconAreaH*0.52);
    iconFn(iconAreaH,hov,hcol);
    ctx.restore();
    // Divider below icon
    const divY=ry+pad+iconAreaH+pad*0.6;
    ctx.strokeStyle=`rgba(${col},0.2)`;ctx.lineWidth=1;
    ctx.beginPath();ctx.moveTo(bx+pad,divY);ctx.lineTo(bx+cardW-pad,divY);ctx.stroke();
    // Title
    const titleSz=Math.min(13,cardW*0.065);
    ctx.font=`bold ${titleSz}px "Courier New"`;ctx.fillStyle=hov?hcol:`rgba(${col},0.9)`;
    ctx.fillText(title,ccx,divY+titleSz+pad*0.6);
    // Detail lines — evenly spaced to fill remaining space above bottom pad
    const lineSz=Math.min(8.5,cardW*0.042);
    const textStart=divY+titleSz+pad*0.6+pad*0.5;
    const textEnd=ry+cardH-pad;
    const lineCount=lines.length;
    const lineStep=lineCount>1?(textEnd-textStart-lineSz)/(lineCount-1):0;
    ctx.font=`${lineSz}px "Courier New"`;ctx.fillStyle=`rgba(${col},0.6)`;
    lines.forEach((l,i)=>ctx.fillText(l,ccx,textStart+lineSz+i*(lineStep||lineSz+5)));
  }

  // ── Ghost Run icon: speed arrow streaking through a finish line ──
  _card(c1X,row1Y,'0,160,220','#00ccff','GHOST RUN',
    ['Race to the finish','20,800m corridor','Enemy gauntlet','Hazards throughout'],
    (sz,hov,col)=>{
      const s=sz*0.38;
      ctx.shadowBlur=hov?14:8;ctx.shadowColor=col;
      // Motion trail lines
      for(let i=0;i<4;i++){
        ctx.strokeStyle=`rgba(0,200,255,${0.15+i*0.12})`;ctx.lineWidth=1.5-i*0.3;
        ctx.beginPath();ctx.moveTo(-s*(1.1+i*0.28),s*(-0.3+i*0.2));ctx.lineTo(-s*(0.4+i*0.18),s*(-0.3+i*0.2));ctx.stroke();
      }
      // Arrow body
      ctx.fillStyle=col;ctx.strokeStyle=col;ctx.lineWidth=2;
      ctx.beginPath();ctx.moveTo(s*1.1,0);ctx.lineTo(-s*0.2,s*0.55);ctx.lineTo(-s*0.2,-s*0.55);ctx.closePath();ctx.fill();
      // Finish post
      ctx.strokeStyle=`rgba(0,200,255,0.55)`;ctx.lineWidth=2;
      ctx.beginPath();ctx.moveTo(s*1.3,-s*0.8);ctx.lineTo(s*1.3,s*0.8);ctx.stroke();
      ctx.shadowBlur=0;
    });

  // ── Nuclear Disarm icon: stylised bomb with fuse and warning tick ──
  _card(c2X,row1Y,'255,80,40','#ff6644','NUCLEAR DISARM',
    ['Locate & disarm 4 bombs','Collect keys first','Open exploration',`${TT_WORLD_W2}×${TT_WORLD_H2}m`],
    (sz,hov,col)=>{
      const s=sz*0.36;
      ctx.shadowBlur=hov?14:8;ctx.shadowColor=col;
      // Bomb body
      ctx.beginPath();ctx.arc(0,s*0.15,s*0.75,0,Math.PI*2);
      ctx.fillStyle='rgba(20,6,0,0.9)';ctx.fill();
      ctx.strokeStyle=col;ctx.lineWidth=2.2;ctx.stroke();
      // Fuse
      const fusePhase=Math.sin(now*4)*0.2;
      ctx.strokeStyle='#ffaa44';ctx.lineWidth=1.5;
      ctx.beginPath();ctx.moveTo(s*0.15,-s*0.6);ctx.quadraticCurveTo(s*(0.55+fusePhase),-s*1.1,s*0.35,-s*1.45);ctx.stroke();
      // Fuse spark
      ctx.beginPath();ctx.arc(s*0.35,-s*1.45,s*0.18,0,Math.PI*2);
      ctx.fillStyle=`rgba(255,${160+Math.round(Math.sin(now*8)*80)},0,${0.7+Math.sin(now*8)*0.3})`;
      ctx.shadowBlur=12;ctx.shadowColor='#ffaa00';ctx.fill();ctx.shadowBlur=0;
      // Warning X
      ctx.strokeStyle=`rgba(255,80,40,0.7)`;ctx.lineWidth=1.8;
      const xs=s*0.3;
      ctx.beginPath();ctx.moveTo(-xs,s*(-0.05)-xs);ctx.lineTo(xs,s*(-0.05)+xs);ctx.stroke();
      ctx.beginPath();ctx.moveTo(xs,s*(-0.05)-xs);ctx.lineTo(-xs,s*(-0.05)+xs);ctx.stroke();
      ctx.shadowBlur=0;
    });

  // ── Dance Birdie Dance icon: drone weaving between zap pylon arcs ──
  _card(c3X,row1Y,'160,255,80','#aaff44','DANCE BIRDIE',
    ['Ghost Run corridor','Half hostiles replaced','Zap pylons & mines','Hazard gauntlet'],
    (sz,hov,col)=>{
      const s=sz*0.36;
      ctx.shadowBlur=hov?12:6;ctx.shadowColor=col;
      // Two pylon circles
      const pylonX=s*0.95;
      for(const px of [-pylonX,pylonX]){
        ctx.beginPath();ctx.arc(px,0,s*0.25,0,Math.PI*2);
        ctx.fillStyle='rgba(10,4,0,0.9)';ctx.fill();
        ctx.strokeStyle='rgb(255,60,0)';ctx.lineWidth=1.8;ctx.stroke();
        const xo=s*0.11;
        ctx.strokeStyle='rgba(255,60,0,0.8)';ctx.lineWidth=1.2;
        ctx.beginPath();ctx.moveTo(px-xo,-xo);ctx.lineTo(px+xo,xo);ctx.stroke();
        ctx.beginPath();ctx.moveTo(px+xo,-xo);ctx.lineTo(px-xo,xo);ctx.stroke();
      }
      // Electric arc between pylons (crackle)
      const seed=Math.floor(now*12);
      ctx.strokeStyle=`rgba(255,${Math.round(60+Math.abs(Math.sin(now*10))*80)},0,0.9)`;ctx.lineWidth=1.5;
      ctx.beginPath();ctx.moveTo(-pylonX+s*0.25,0);
      for(let i=1;i<=5;i++){
        const tx=-pylonX+s*0.25+(pylonX*2-s*0.5)*i/5;
        const jitter=(Math.sin(seed*2.1+i*4.3)-0.5)*s*0.45;
        ctx.lineTo(tx,jitter);
      }
      ctx.lineTo(pylonX-s*0.25,0);ctx.stroke();
      // Small drone weaving above arc
      const weaveX=Math.sin(now*2.8)*s*0.6;
      const weaveY=-s*0.55+Math.sin(now*5.5)*s*0.15;
      ctx.save();ctx.translate(weaveX,weaveY);
      ctx.fillStyle=col;ctx.shadowBlur=8;ctx.shadowColor=col;
      ctx.beginPath();ctx.moveTo(s*0.18,0);ctx.lineTo(-s*0.1,s*0.12);ctx.lineTo(-s*0.1,-s*0.12);ctx.closePath();ctx.fill();
      ctx.restore();
      ctx.shadowBlur=0;
    });

  // Row 2 — JR Rescue (left) and Touch N Go (right)
  const row2Y=row1Y+cardH+rowGap;
  const row2TotalW=cardW*2+gap;
  const c4X=cx-row2TotalW/2;
  const c5X=c4X+cardW+gap;

  // ── J R Rescue icon ──
  _card(c4X,row2Y,'68,255,204','#44ffcc','J R RESCUE',
    ['Rescue 3 captive J R crafts','Kill guards to free them','Carry each back to base','Open world — no finish'],
    (sz,hov,col)=>{
      const s=sz*0.36;
      ctx.shadowBlur=hov?12:6;ctx.shadowColor=col;
      for(let r=1;r<=3;r++){
        const rr=s*(0.3+r*0.28);
        const pulse=0.25+0.25*Math.abs(Math.sin(now*2.5-r*0.8));
        ctx.beginPath();ctx.arc(s*0.75,0,rr,0,Math.PI*2);
        ctx.strokeStyle=`rgba(0,200,255,${pulse})`;ctx.lineWidth=1;ctx.stroke();
      }
      ctx.beginPath();ctx.arc(s*0.75,0,s*0.2,0,Math.PI*2);
      ctx.fillStyle='rgba(0,200,255,0.7)';ctx.shadowBlur=10;ctx.shadowColor='#00ccff';ctx.fill();ctx.shadowBlur=0;
      ctx.strokeStyle=`rgba(68,255,204,${0.35+0.2*Math.sin(now*6)})`;ctx.lineWidth=1.5;
      ctx.setLineDash([4,4]);ctx.beginPath();ctx.moveTo(-s*0.65,0);ctx.lineTo(s*0.55,0);ctx.stroke();ctx.setLineDash([]);
      ctx.save();ctx.translate(-s*0.65,0);
      const bs=s*0.22;
      ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*bs,Math.sin(a)*bs):ctx.lineTo(Math.cos(a)*bs,Math.sin(a)*bs);}ctx.closePath();
      ctx.fillStyle='rgba(4,12,24,0.9)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.2;ctx.stroke();
      ctx.restore();
      ctx.save();ctx.translate(-s*0.65,0);
      const cr=s*0.38;
      ctx.strokeStyle='rgba(255,60,60,0.5)';ctx.lineWidth=1;
      for(let b=0;b<4;b++){const ba=(b/4)*Math.PI*2;ctx.beginPath();ctx.moveTo(Math.cos(ba)*bs,Math.sin(ba)*bs);ctx.lineTo(Math.cos(ba)*cr,Math.sin(ba)*cr);ctx.stroke();}
      ctx.restore();
      ctx.shadowBlur=0;
    });

  // ── Touch N Go icon: 5 numbered pads with a flight path weaving between them ──
  _card(c5X,row2Y,'255,220,0','#ffdd00','TOUCH N GO',
    ['5 hidden landing pads','Reveal numbers by flying over','Touch in order 1 → 5','Wrong order resets sequence'],
    (sz,hov,col)=>{
      const s=sz*0.36;
      ctx.shadowBlur=hov?12:6;ctx.shadowColor=col;
      // 5 mini pads in a rough scatter
      const padPos=[[-s*0.82,-s*0.55],[s*0.7,-s*0.7],[0,0],[-s*0.5,s*0.65],[s*0.75,s*0.4]];
      const padNums=[1,2,3,4,5];
      for(let i=0;i<5;i++){
        const [px,py]=padPos[i];
        const done=i<2&&Math.abs(Math.sin(now*1.2+i))>0.3;
        const c2=i===2?'#ffdd00':'#446688';
        ctx.beginPath();ctx.arc(px,py,s*0.22,0,Math.PI*2);
        ctx.fillStyle='rgba(8,16,36,0.9)';ctx.fill();
        ctx.strokeStyle=c2;ctx.lineWidth=1.2;ctx.stroke();
        ctx.font=`bold ${Math.round(s*0.22)}px "Courier New"`;
        ctx.fillStyle=c2;ctx.textAlign='center';ctx.textBaseline='middle';
        ctx.fillText(String(padNums[i]),px,py+0.5);ctx.textBaseline='alphabetic';
      }
      // Dashed flight path curve through pads
      ctx.strokeStyle=`rgba(255,220,0,${0.3+0.15*Math.sin(now*3)})`;ctx.lineWidth=1;
      ctx.setLineDash([3,5]);
      ctx.beginPath();ctx.moveTo(padPos[0][0],padPos[0][1]);
      for(let i=1;i<5;i++) ctx.lineTo(padPos[i][0],padPos[i][1]);
      ctx.stroke();ctx.setLineDash([]);
      // Small craft
      const tPos=padPos[Math.floor(now*0.8)%5];
      ctx.save();ctx.translate(tPos[0],tPos[1]-s*0.38);
      ctx.fillStyle=col;ctx.shadowBlur=8;ctx.shadowColor=col;
      ctx.beginPath();ctx.moveTo(s*0.16,0);ctx.lineTo(-s*0.09,s*0.1);ctx.lineTo(-s*0.09,-s*0.1);ctx.closePath();ctx.fill();
      ctx.restore();
      ctx.shadowBlur=0;
    });

  // Back button — left-anchored, _briefBtn style
  const bw=140,bh=36,bx=Math.max(20,W*0.03),by=H-backH+8;
  _briefBtn(bx,by,bw,bh,'◀  BACK','#aaccff',false);
  ctx.textAlign='left';
}

function spawnDBDEnemies(){
  // Half the enemy count of Ghost Run, replaced with heavy hazard presence
  enemies.length=0;
  const placed=[];
  const H=WORLD_H, MARGIN=55;
  // Reduced enemy sections — roughly half the hostiles of Ghost Run
  const sections=[
    [500,   2800,  1,0,0, 1,0,0,0],
    [2800,  5200,  1,1,0, 1,0,0,0],
    [5200,  7600,  1,1,1, 1,0,0,0],
    [7600,  10400, 1,1,1, 1,0,1,0],
    [10400, 13200, 1,1,1, 2,1,0,1],
    [13200, 16400, 1,2,1, 1,1,1,1],
    [16400, DBD_FINISH_X-200, 1,2,2, 2,1,1,1],
  ];
  for(const [x1,x2,sc,gu,tu,da,wr,br,ph] of sections){
    const addInBand=(type,count)=>{
      for(let i=0;i<count;i++){
        let x,y,a=0;
        do{x=rng(x1,x2);y=rng(MARGIN,H-MARGIN);a++;}
        while(a<40&&(circleVsObs(x,y,22)||placed.some(p=>dist(x,y,p.x,p.y)<70)));
        const e=mkEnemy(type,x,y); enemies.push(e); placed.push({x,y});
      }
    };
    addInBand('scout',sc);addInBand('guard',gu);addInBand('turret',tu);
    addInBand('dart',da);addInBand('wraith',wr);addInBand('brute',br);addInBand('phantom',ph);
  }
  // Heavy hazard presence — replacing the missing hostiles with environmental danger
  spawnHazardZaps(38, 600, DBD_WORLD_W-400, MARGIN+10, H-MARGIN-10, 70,150);
  spawnHazardMines(28, 600, DBD_WORLD_W-400, MARGIN+10, H-MARGIN-10, P.x, P.y, 500);
  // Boss near finish line (Dance Birdie Dance L3)
  {
    const _bPool=['dreadnought','harbinger'];
    const _bType=_bPool[Math.floor(Math.random()*_bPool.length)];
    const _bE=mkEnemy(_bType,DBD_FINISH_X-600,WORLD_H/2);
    enemies.push(_bE);
    if(_bType==='harbinger') harbingerRef=_bE;
  }
}

function startDanceBirdie(){
  _hideAllAds();
  ttLevel=3; nukes=[];
  WORLD_W=DBD_WORLD_W; WORLD_H=canvas.height;
  score=0;wave=1;bossWarning=0;empFlash=0;weaponFlash={name:'DANCE BIRDIE DANCE',ms:3000};
  leechFlash={active:false,tx:0,ty:0,ms:0};shockwaveFlash={ms:0};
  harbingerRef=null;
  portalActive=false;portalPositions=[];
  particles.length=0;pickups.length=0;pBullets.length=0;eBullets.length=0;mines.length=0;seekers.length=0;rockets.length=0;boomerangs.length=0;fractals.length=0;hazards.length=0;grenades.length=0;gravityWells.length=0;faradayCages.length=0;
  miniMe.active=false;miniMe.lost=false;miniMe.hp=MM_HP;miniMe.iframes=0;
  resetPlayer();
  P.x=P.size+80; P.y=WORLD_H/2; camX=0; camY=0;
  generateTTObstacles();
  spawnHiddenPickups(); spawnHiddenPickups(true);
  spawnDBDEnemies();
  ttTotalEnemies=enemies.length;
  if(P.unlockedW.has(WEAPONS.findIndex(w=>w.id==='mine'))) P.mineStock=enemies.length;
  if(P.unlockedW.has(WEAPONS.findIndex(w=>w.id==='seekr'))){
    const half=Math.ceil(enemies.length/2);
    P.seekStock=half+Math.floor(Math.random()*(enemies.length*3-half+1));
  }
  ttStartTime=performance.now(); ttElapsed=0; ttFinished=false; ttFinalScore=0;
  gameStartTime=Date.now(); gameState='playing'; _snapMouseToPlayer();
}


// ═══════════════════════════════════════════════════════════════
// COMBAT TRAINING MODE
// ═══════════════════════════════════════════════════════════════
function generateCTObstacles(){
  obstacles.length=0;
  const W=WORLD_W, H=WORLD_H;
  for(let i=0;i<7;i++){
    let x,y,att=0;
    do{ x=rng(W*0.12,W*0.88); y=rng(H*0.12,H*0.88); att++; }
    while(att<60&&(dist(x,y,W/2,H/2)<160||circleVsObs(x,y,50)));
    obstacles.push({type:'pillar',x,y,r:rng(22,38),rot:rng(0,Math.PI)});
  }
}

function _ctSpawnEnemy(){
  const type=CT_SEQUENCE[ctLevel];
  const W=WORLD_W, H=WORLD_H;
  let ex=W*0.82, ey=H/2, att=0;
  while(att<60&&(circleVsObs(ex,ey,ETYPES[type].size+16)||dist(ex,ey,P.x,P.y)<200)){
    ex=rng(W*0.1,W*0.9); ey=rng(H*0.1,H*0.9); att++;
  }
  enemies.length=0;
  enemies.push(mkEnemy(type,ex,ey));
}

function _ctSchedulePickup(){ctNextPickupMs=25000+Math.random()*20000;}

function _ctSpawnPickup(){
  // One pickup per round: smart heal/battery if needed, else weapon unlock or random
  const W=WORLD_W, H=WORLD_H;
  const allUnlocked=_allWeaponsUnlocked();
  let type;
  if(P.hp<P.maxHp*0.4){type='health';}
  else if(P.hp<P.maxHp*0.7){type='medkit';}
  else if(P.bat<P.maxBat*0.35){type='battery';}
  else{type=allUnlocked?(Math.random()<0.5?'battery':'health'):(Math.random()<0.55?'weapon':(Math.random()<0.5?'battery':'health'));}
  // Place in the middle zone, away from both player and enemy
  let px,py,att=0;
  const ene=enemies[0];
  do{
    px=rng(W*0.25,W*0.75); py=rng(H*0.2,H*0.8); att++;
  }while(att<60&&(circleVsObs(px,py,24)||dist(px,py,P.x,P.y)<120||(ene&&dist(px,py,ene.x,ene.y)<120)));
  spawnPickup(px,py,type,false);
}

function startCombatTraining(){
  _hideAllAds();
  gameMode='combattraining';
  ttLevel=1; nukes=[];
  WORLD_W=canvas.width; WORLD_H=canvas.height;
  ctLevel=0; ctTotalScore=0; ctFinalScore=0; ctLevelUpMs=0;
  score=0; wave=1; bossWarning=0; empFlash=0; weaponFlash={name:'COMBAT TRAINING',ms:2500}; lastHullBeepMs=0;
  leechFlash={active:false,tx:0,ty:0,ms:0};shockwaveFlash={ms:0};
  harbingerRef=null;
  portalActive=false; portalPositions=[];
  particles.length=0; pickups.length=0; pBullets.length=0; eBullets.length=0;
  mines.length=0; seekers.length=0; boomerangs.length=0; fractals.length=0; hazards.length=0;
  miniMe.active=false; miniMe.lost=false; miniMe.hp=MM_HP; miniMe.iframes=0;
  hangarScroll=0; resetPlayer();
  P.x=WORLD_W*0.18; P.y=WORLD_H/2; camX=0; camY=0;
  generateCTObstacles();
  _ctSpawnEnemy();
  _ctSpawnPickup();
  _ctSchedulePickup();
  ctStartTime=performance.now();
  gameStartTime=Date.now();
  gameState='playing';
}

function ctKillAndAdvance(){
  const elapsed=(performance.now()-ctStartTime)/1000;
  const timeBonus=Math.max(0,Math.round(600*(1-Math.min(elapsed,60)/60)));
  ctTotalScore+=score+timeBonus;
  ctLevelUpName=CT_SEQUENCE[ctLevel].toUpperCase();
  ctLevel++;
  if(ctLevel>=CT_SEQUENCE.length){
    ctFinalScore=ctTotalScore;
    saveHighScore('combattraining',ctFinalScore,Date.now()-gameStartTime);
    gameState='ctResult';
    SFX.confirm();
  } else {
    gameState='ctLevelUp';
    ctLevelUpMs=2200;
    SFX.wave();
  }
}

function tickCTLevelUp(dt){
  ctLevelUpMs-=dt*1000;
  if(ctLevelUpMs<=0){
    pBullets.length=0; eBullets.length=0; mines.length=0;
    seekers.length=0; boomerangs.length=0; fractals.length=0;
    particles.length=0; pickups.length=0;
    P.iframes=800; score=0;
    WORLD_W=canvas.width; WORLD_H=canvas.height;
    generateCTObstacles();
    _ctSpawnEnemy();
    _ctSpawnPickup();
    _ctSchedulePickup();
    ctStartTime=performance.now();
    gameState='playing';
    weaponFlash={name:`ROUND ${ctLevel+1} — ${CT_SEQUENCE[ctLevel].toUpperCase()}`,ms:2200};
  }
}

function drawCTLevelUp(){
  ctx.fillStyle='rgba(4,10,26,0.82)';ctx.fillRect(0,0,canvas.width,canvas.height);
  const cx=canvas.width/2,cy=canvas.height/2;
  ctx.textAlign='center';
  ctx.font='bold 52px "Courier New"';ctx.fillStyle='#00ff88';ctx.shadowBlur=36;ctx.shadowColor='#00cc66';
  ctx.fillText('TARGET DOWN',cx,cy-40);ctx.shadowBlur=0;
  ctx.font='bold 22px "Courier New"';ctx.fillStyle='#00eeff';ctx.shadowBlur=14;ctx.shadowColor='#0088cc';
  ctx.fillText(ctLevelUpName+' ELIMINATED',cx,cy+8);ctx.shadowBlur=0;
  if(ctLevel<CT_SEQUENCE.length){
    ctx.font='14px "Courier New"';ctx.fillStyle='rgba(100,200,255,0.7)';
    ctx.fillText(`NEXT TARGET: ${CT_SEQUENCE[ctLevel].toUpperCase()}`,cx,cy+40);
  }
  const pct=Math.max(0,ctLevelUpMs/2200);
  const bw=180,bh=4,bx=cx-bw/2,by=cy+68;
  ctx.fillStyle='rgba(0,40,80,0.8)';ctx.fillRect(bx,by,bw,bh);
  ctx.fillStyle='rgba(0,200,120,0.8)';ctx.fillRect(bx,by,bw*pct,bh);
  ctx.textAlign='left';
}

function drawCTResult(){
  ctx.fillStyle='rgba(4,10,26,0.92)';ctx.fillRect(0,0,canvas.width,canvas.height);
  const cx=canvas.width/2,cy=canvas.height/2;
  ctx.textAlign='center';
  ctx.font='bold 44px "Courier New"';ctx.fillStyle='#ffdd00';ctx.shadowBlur=36;ctx.shadowColor='#ffaa00';
  ctx.fillText('TRAINING COMPLETE',cx,cy-120);ctx.shadowBlur=0;
  ctx.font='13px "Courier New"';ctx.fillStyle='rgba(180,220,255,0.7)';
  ctx.fillText(`ALL ${CT_SEQUENCE.length} HOSTILE TYPES DEFEATED`,cx,cy-82);
  CT_SEQUENCE.forEach((type,i)=>{
    const col=ETYPES[type]?.color||'#ffffff';
    const rx=cx+(i-(CT_SEQUENCE.length-1)/2)*38;
    ctx.beginPath();ctx.arc(rx,cy-48,9,0,Math.PI*2);
    ctx.fillStyle=col;ctx.shadowBlur=8;ctx.shadowColor=col;ctx.fill();ctx.shadowBlur=0;
    ctx.font='6px "Courier New"';ctx.fillStyle=col;
    ctx.fillText(type.toUpperCase().slice(0,5),rx,cy-32);
  });
  ctx.font='bold 34px "Courier New"';ctx.fillStyle='#00ff88';ctx.shadowBlur=22;ctx.shadowColor='#00cc66';
  ctx.fillText(`FINAL SCORE  ${ctFinalScore.toLocaleString()}`,cx,cy+24);ctx.shadowBlur=0;
  ctx.font='11px "Courier New"';ctx.fillStyle='rgba(80,140,200,0.5)';
  ctx.fillText('SCORE + TIME EFFICIENCY BONUS PER ROUND',cx,cy+48);
  const bw=240,bh=44,bx=cx-bw/2,by=cy+74;
  const bhov=mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh;
  ctx.fillStyle=bhov?'rgba(0,200,120,0.18)':'rgba(0,0,0,0.6)';
  roundRect(ctx,bx,by,bw,bh,8);ctx.fill();
  ctx.strokeStyle=bhov?'#00ff88':'rgba(0,160,100,0.5)';ctx.lineWidth=1.8;
  roundRect(ctx,bx,by,bw,bh,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 14px "Courier New"';ctx.fillStyle=bhov?'#00ff88':'rgba(0,220,140,0.8)';
  ctx.fillText('◀  RETURN TO BASE',cx,by+bh/2+5);
  ctx.textAlign='left';
}

function drawCTHUD(){
  const T=IS_TOUCH, pad=T?10:18, cx=canvas.width/2;
  ctx.textAlign='center';
  ctx.font=`bold ${T?13:18}px "Courier New"`;ctx.fillStyle='#ffdd00';ctx.shadowBlur=10;ctx.shadowColor='#ffaa00';
  ctx.fillText(`ROUND ${ctLevel+1} / ${CT_SEQUENCE.length}`,cx,pad+(T?14:20));ctx.shadowBlur=0;
  const type=CT_SEQUENCE[ctLevel];
  const col=ETYPES[type]?.color||'#ffffff';
  ctx.font=`${T?9:12}px "Courier New"`;ctx.fillStyle=col;ctx.shadowBlur=6;ctx.shadowColor=col;
  ctx.fillText(`TARGET: ${type.toUpperCase()}`,cx,pad+(T?26:40));ctx.shadowBlur=0;
  if(enemies.length===1){
    const e=enemies[0];
    const pct=e.hp/e.maxHp;
    const bw=T?120:200,bh=T?5:7,bx=cx-bw/2,by=pad+(T?34:54);
    ctx.fillStyle='rgba(0,0,0,0.6)';ctx.fillRect(bx-1,by-1,bw+2,bh+2);
    ctx.fillStyle='rgba(30,30,30,0.7)';ctx.fillRect(bx,by,bw,bh);
    ctx.fillStyle=pct>0.5?'#ff4444':pct>0.25?'#ff8800':'#ff2200';
    ctx.shadowBlur=6;ctx.shadowColor=ctx.fillStyle;
    ctx.fillRect(bx,by,bw*pct,bh);ctx.shadowBlur=0;
    ctx.font=`${T?7:9}px "Courier New"`;ctx.fillStyle='rgba(200,100,100,0.7)';
    ctx.fillText('HOSTILE',cx,by+bh+(T?9:11));
  }
  ctx.textAlign='left';
}

// ═══════════════════════════════════════════════════════════════
// HALL OF FAME  — high score persistence + screen
// ═══════════════════════════════════════════════════════════════
const GAME_NAME='PATROL WING'; // ← change here to rename the game everywhere
const HOF_KEY='pw_hof_scores';
const HOF_MAX=20;
let hofTab=0; // 0=This Device, 1=Best Globally

const MODE_LABELS={
  'battle':        'BATTLE WAVES',
  'timetrial_1':   'TT GHOST RUN',
  'timetrial_2':   'TT NUCLEAR DISARM',
  'timetrial_3':   'TT DANCE BIRDIE',
  'timetrial_4':   'TT J R RESCUE',
  'timetrial_5':   'TT TOUCH N GO',
  'combattraining':'COMBAT TRAINING',
};

function _hofLoad(){
  try{ return JSON.parse(localStorage.getItem(HOF_KEY)||'[]'); }
  catch(e){ return []; }
}
function _hofSave(arr){
  try{ localStorage.setItem(HOF_KEY, JSON.stringify(arr)); } catch(e){}
}

function saveHighScore(modeKey, pts, durationMs){
  if(!pts||pts<=0) return;
  const arr=_hofLoad();
  arr.push({
    mode: modeKey,
    score: Math.round(pts),
    duration: Math.round(durationMs),
    date: new Date().toISOString(),
  });
  arr.sort((a,b)=>b.score-a.score);
  _hofSave(arr.slice(0,HOF_MAX));
}

function _fmtDur(ms){
  if(!ms||ms<=0) return '--:--';
  const s=Math.floor(ms/1000);
  const m=Math.floor(s/60), ss=s%60;
  return `${String(m).padStart(2,'0')}:${String(ss).padStart(2,'0')}`;
}
function _fmtDate(iso){
  try{
    const d=new Date(iso);
    return `${d.getMonth()+1}/${d.getDate()}/${String(d.getFullYear()).slice(-2)} ${d.getHours()}:${String(d.getMinutes()).padStart(2,'0')}`;
  }catch(e){ return ''; }
}

// Placeholder global leaderboard data
const HOF_GLOBAL=[
  {name:'AETHER_X',    country:'🇯🇵',score:2841200,time:'04:12'},
  {name:'VORTEX99',    country:'🇺🇸',score:2740850,time:'04:28'},
  {name:'NEONPILOT',   country:'🇰🇷',score:2618400,time:'04:51'},
  {name:'STARHOUND',   country:'🇬🇧',score:2502700,time:'05:03'},
  {name:'CRASHZONE',   country:'🇩🇪',score:2388900,time:'05:22'},
  {name:'HYPERDRIFT',  country:'🇧🇷',score:2210600,time:'05:41'},
  {name:'IRONWING',    country:'🇨🇦',score:2198300,time:'05:44'},
  {name:'AXIOM_7',     country:'🇫🇷',score:2084100,time:'06:01'},
  {name:'DUSKRUNNER',  country:'🇦🇺',score:1966500,time:'06:18'},
  {name:'STATIC_ACE',  country:'🇸🇪',score:1854200,time:'06:35'},
  {name:'PEREGRINE',   country:'🇳🇱',score:1740800,time:'06:58'},
  {name:'HIVENODE',    country:'🇮🇳',score:1622300,time:'07:14'},
  {name:'LUNARFOX',    country:'🇲🇽',score:1511900,time:'07:32'},
  {name:'GHOSTBYTE',   country:'🇸🇬',score:1408700,time:'07:51'},
  {name:'OVERCLOCKED', country:'🇵🇱',score:1302400,time:'08:09'},
  {name:'DAWNSTRIKE',  country:'🇳🇴',score:1198100,time:'08:28'},
  {name:'PIXEL_ACE',   country:'🇿🇦',score:1092600,time:'08:44'},
  {name:'TURBOFANG',   country:'🇮🇹',score:988300, time:'09:02'},
  {name:'COLDWAVE',    country:'🇦🇷',score:881700, time:'09:21'},
  {name:'STRIKEZONE',  country:'🇵🇹',score:774200, time:'09:40'},
];

// ═══════════════════════════════════════════════════════════════
// PRE-GAME BRIEFING SCREENS
// ═══════════════════════════════════════════════════════════════
const BRIEFINGS={
  brief_battle:{
    title:'BATTLE WAVES',
    sub:'STANDARD ENGAGEMENT',
    color:'#00ccff',
    objective:'Survive five waves of escalating hostile forces. Clear every wave to claim victory.',
    hint:'Tip: Conserve battery — boost is powerful but drains fast. Hazards in later waves can work in your favor.',
    launchFn: ()=>{ gameMode='battle'; startGame(); },
    iconFn:(cx,col,now)=>{
      // Five stacked wave bars — each taller than the last, pulsing left to right
      const barW=10,gap=7,totalW=5*(barW+gap)-gap;
      const heights=[18,28,38,48,60];
      for(let i=0;i<5;i++){
        const phase=(now*2+i*0.5)%(Math.PI*2);
        const bh=heights[i]*(0.72+0.28*Math.sin(phase));
        const bx=cx-totalW/2+i*(barW+gap);
        const alpha=0.45+0.35*Math.sin(phase);
        ctx.fillStyle=`rgba(0,200,255,${alpha})`;
        ctx.shadowBlur=8;ctx.shadowColor=col;
        ctx.fillRect(bx,-bh/2,barW,bh);
        ctx.shadowBlur=0;
      }
      // "WAVE 5" label hint
      ctx.font='bold 9px "Courier New"';ctx.fillStyle=`rgba(0,200,255,0.45)`;
      ctx.textAlign='center';ctx.fillText('WAVE 5',cx,46);
    },
  },
  brief_ct:{
    title:'COMBAT TRAINING',
    sub:'ONE ON ONE',
    color:'#ffdd00',
    objective:'Face each hostile type in single combat. Eight rounds — easiest to hardest. Fastest kills earn the highest scores.',
    hint:'Tip: A power-up spawns each round. Grab it early — some fights are easier with the right weapon.',
    launchFn: ()=>{ _loadHangar(); startCombatTraining(); },
    iconFn:(cx,col,now)=>{
      // Two drones facing off — player (cyan) left, enemy (red) right
      const sep=54;
      const pulse=0.5+0.5*Math.sin(now*3);
      // Player craft (simple arrow)
      ctx.save();ctx.translate(cx-sep,0);
      ctx.fillStyle='#00ddff';ctx.shadowBlur=10;ctx.shadowColor='#00ddff';
      ctx.beginPath();ctx.moveTo(16,0);ctx.lineTo(-8,10);ctx.lineTo(-8,-10);ctx.closePath();ctx.fill();
      ctx.shadowBlur=0;ctx.restore();
      // Enemy craft (arrow pointing left)
      ctx.save();ctx.translate(cx+sep,0);
      ctx.fillStyle='#ff2244';ctx.shadowBlur=10;ctx.shadowColor='#ff2244';
      ctx.beginPath();ctx.moveTo(-16,0);ctx.lineTo(8,10);ctx.lineTo(8,-10);ctx.closePath();ctx.fill();
      ctx.shadowBlur=0;ctx.restore();
      // VS spark in centre
      ctx.font=`bold ${14+Math.round(pulse*3)}px "Courier New"`;
      ctx.fillStyle=`rgba(255,220,0,${0.7+0.3*pulse})`;
      ctx.shadowBlur=12*pulse;ctx.shadowColor='#ffdd00';
      ctx.textAlign='center';ctx.fillText('VS',cx,5);ctx.shadowBlur=0;
    },
  },
  brief_tt1:{
    title:'GHOST RUN',
    sub:'TIME TRIAL — LEVEL 1',
    color:'#00ff88',
    objective:'Reach the finish line 20,800m ahead as fast as possible. Enemies and hazards block the corridor. Your time is your score.',
    hint:'Tip: You don\'t need to kill everything — but more kills multiply your final score. Hazards damage enemies too.',
    launchFn: ()=>{ ttLevel=1; _loadHangar(); startGame(); },
  },
  brief_tt2:{
    title:'NUCLEAR DISARM',
    sub:'TIME TRIAL — LEVEL 2',
    color:'#ff4422',
    objective:'Locate four nuclear devices hidden in a 4,200 × 3,200m world. Collect each matching key, then hold position at the bomb to disarm it.',
    hint:'Tip: Keys are placed in the opposite quadrant from their bomb. Check the minimap — disarmed bombs turn green.',
    launchFn: ()=>{ ttLevel=2; _loadHangar(); startNukeDisarm(); },
  },
  brief_tt3:{
    title:'DANCE BIRDIE DANCE',
    sub:'TIME TRIAL — LEVEL 3',
    color:'#aaff44',
    objective:'Race the full 20,800m corridor — but half the hostiles have been replaced with zap pylons and floor mines. Read the field.',
    hint:'Tip: Floor mines explode on proximity and damage nearby enemies too. Threading between zap pylons takes practice.',
    launchFn: ()=>{ ttLevel=3; _loadHangar(); startDanceBirdie(); },
  },
  brief_tt4:{
    title:'J R RESCUE',
    sub:'TIME TRIAL — LEVEL 4',
    color:'#44ffcc',
    objective:'Three J R companion crafts are being held captive by hostile forces at different locations. Kill their guards to free each one, fly close to grab it, and escort it back to the central base. Rescue all three to complete the mission.',
    hint:'Tip: You can only carry one J R at a time. Check the minimap for captive positions. Guards respawn as roaming patrols — clear the area before grabbing.',
    launchFn: ()=>{ ttLevel=4; _loadHangar(); startJRRescue(); },
  },
  brief_tt5:{
    title:'TOUCH N GO',
    sub:'TIME TRIAL — LEVEL 5',
    color:'#ffdd00',
    objective:'Five landing pads are hidden across an open world. Fly over each one to reveal its number. Then land on them in order — 1 through 5. Touch a pad out of order and the sequence resets to 1. All this while avoiding hostiles and hazards.',
    hint:'Tip: Explore first to reveal all pad numbers before committing to the sequence. The minimap marks discovered pads. Hover over a pad for 1.2s to register a touch.',
    launchFn: ()=>{ ttLevel=5; _loadHangar(); startTouchNGo(); },
  },
};
let activeBriefing=null; // key into BRIEFINGS

function drawBriefingScreen(){
  const b=BRIEFINGS[activeBriefing];
  if(!b) return;
  const W=canvas.width,H=canvas.height,cx=W/2;
  ctx.fillStyle='#050d1a';ctx.fillRect(0,0,W,H);
  ctx.strokeStyle='rgba(0,80,160,0.07)';ctx.lineWidth=1;
  for(let x=0;x<W;x+=70){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x,H);ctx.stroke();}
  for(let y=0;y<H;y+=70){ctx.beginPath();ctx.moveTo(0,y);ctx.lineTo(W,y);ctx.stroke();}
  const scan=(Date.now()/14)%H;
  ctx.fillStyle='rgba(0,180,255,0.035)';ctx.fillRect(0,scan,W,3);
  ctx.textAlign='center';
  const hasIcon=!!b.iconFn;
  const now=Date.now()/1000;

  // Buttons anchored to bottom — calculated first so content doesn't overlap
  const btnW=Math.min(220,W*0.28),btnH=46,btnGap=20;
  const btnY=H-btnH-Math.max(28,H*0.04);
  const backX=Math.max(20,W*0.03), launchX=W-Math.max(20,W*0.03)-btnW;
  _briefBtn(backX,btnY,btnW,btnH,'◀  BACK','#aaccff',false);
  _briefBtn(launchX,btnY,btnW,btnH,'▶  TAKE FLIGHT',b.color,true);

  // Top-down content layout
  let y=Math.max(24,H*0.04);

  // Sub-label
  ctx.font='11px "Courier New"';ctx.fillStyle=b.color+'77';
  ctx.letterSpacing='3px';ctx.fillText(b.sub,cx,y+11);ctx.letterSpacing='0px';
  y+=11+Math.max(20,H*0.032);

  // Title
  const titleSz=Math.min(46,Math.max(28,W*0.046));
  ctx.font=`bold ${titleSz}px "Courier New"`;ctx.fillStyle=b.color;
  ctx.shadowBlur=26;ctx.shadowColor=b.color;
  ctx.fillText(b.title,cx,y+titleSz);ctx.shadowBlur=0;
  y+=titleSz+Math.max(24,H*0.04);

  // Icon
  if(hasIcon){
    const iconSlot=Math.max(64,H*0.1);
    ctx.save();ctx.translate(cx,y+iconSlot*0.5);
    b.iconFn(0,b.color,now);
    ctx.restore();
    y+=iconSlot+Math.max(20,H*0.032);
  }

  // Divider
  ctx.strokeStyle=b.color+'33';ctx.lineWidth=1;
  ctx.beginPath();ctx.moveTo(cx-200,y);ctx.lineTo(cx+200,y);ctx.stroke();
  y+=Math.max(22,H*0.034);

  // MISSION OBJECTIVE label
  ctx.font='bold 11px "Courier New"';ctx.fillStyle='rgba(150,200,255,0.6)';
  ctx.letterSpacing='2px';ctx.fillText('MISSION OBJECTIVE',cx,y+11);ctx.letterSpacing='0px';
  y+=11+Math.max(14,H*0.022);

  // Objective body
  const objSz=Math.min(14,Math.max(11,W*0.016));
  const objLineH=objSz+8;
  _wrapText(b.objective,cx,y,Math.min(W*0.70,620),objLineH,'rgba(215,230,255,0.92)',`${objSz}px "Courier New"`);
  const estLines=Math.max(2,Math.ceil(b.objective.length/(Math.min(W*0.70,620)/objSz*1.7)));
  y+=estLines*objLineH+Math.max(20,H*0.032);

  // Hint — only draw if it fits above the buttons
  const hintLineH=15;
  const hintLines=Math.max(1,Math.ceil(b.hint.length/(Math.min(W*0.62,540)/7*1.7)));
  if(y+hintLines*hintLineH+20<btnY){
    _wrapText(b.hint,cx,y,Math.min(W*0.62,540),hintLineH,b.color+'77','11px "Courier New"');
  }

  ctx.textAlign='left';
}
function _briefBtn(x,y,w,h,label,col,primary){
  const hov=mouse.x>x&&mouse.x<x+w&&mouse.y>y&&mouse.y<y+h;
  ctx.fillStyle=hov?col+'22':'rgba(0,0,0,0.55)';
  roundRect(ctx,x,y,w,h,8);ctx.fill();
  ctx.strokeStyle=hov?col:col+'55';ctx.lineWidth=hov?2:1.2;
  ctx.shadowBlur=hov?16:0;ctx.shadowColor=col;
  roundRect(ctx,x,y,w,h,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font=`${primary?'bold ':''} 13px "Courier New"`;
  ctx.fillStyle=hov?col:col+'bb';
  ctx.fillText(label,x+w/2,y+h/2+5);
}
function _wrapText(text,cx,y,maxW,lineH,color,font){
  ctx.font=font;ctx.fillStyle=color;
  const words=text.split(' ');let line='';
  for(const word of words){
    const test=line?line+' '+word:word;
    if(ctx.measureText(test).width>maxW&&line){
      ctx.fillText(line,cx,y);y+=lineH;line=word;
    } else { line=test; }
  }
  if(line) ctx.fillText(line,cx,y);
}

// ═══════════════════════════════════════════════════════════════
// UNIFIED DEATH SCREEN  (replaces drawGameoverScreen)
// ═══════════════════════════════════════════════════════════════
let deathScreenEnteredAt=0; // timestamp when gameover state was set

function _modeLabel(){
  if(gameMode==='combattraining') return 'COMBAT TRAINING';
  if(gameMode==='timetrial'){
    if(ttLevel===2) return 'NUCLEAR DISARM';
    if(ttLevel===3) return 'DANCE BIRDIE DANCE';
    if(ttLevel===4) return 'J R RESCUE';
    if(ttLevel===5) return 'TOUCH N GO';
    return 'GHOST RUN';
  }
  return `BATTLE WAVES  W${wave}/${TOTAL_WAVES}`;
}

function drawDeathScreen(){
  if(!deathScreenEnteredAt) deathScreenEnteredAt=Date.now();
  const elapsed=(Date.now()-deathScreenEnteredAt)/1000;
  const countdown=Math.max(0,5-elapsed);
  const W=canvas.width,H=canvas.height,cx=W/2,cy=H/2;
  // Dark overlay with red vignette
  ctx.fillStyle='rgba(4,0,8,0.86)';ctx.fillRect(0,0,W,H);
  const vig=ctx.createRadialGradient(cx,cy,H*0.2,cx,cy,H*0.75);
  vig.addColorStop(0,'rgba(0,0,0,0)');vig.addColorStop(1,'rgba(60,0,0,0.55)');
  ctx.fillStyle=vig;ctx.fillRect(0,0,W,H);
  ctx.textAlign='center';
  // Flicker effect on header
  const flicker=elapsed<1?Math.random()>0.3:1;
  if(flicker){
    ctx.font='bold 64px "Courier New"';ctx.fillStyle='#ff1133';
    ctx.shadowBlur=44;ctx.shadowColor='#ff0022';
    ctx.fillText('SYSTEMS CRITICAL',cx,cy-120);ctx.shadowBlur=0;
  }
  // Mode
  ctx.font='13px "Courier New"';ctx.fillStyle='rgba(180,100,100,0.7)';
  ctx.fillText(_modeLabel(),cx,cy-72);
  // Divider
  ctx.strokeStyle='rgba(200,0,0,0.3)';ctx.lineWidth=1;
  ctx.beginPath();ctx.moveTo(cx-200,cy-54);ctx.lineTo(cx+200,cy-54);ctx.stroke();
  // Score
  ctx.font='bold 38px "Courier New"';ctx.fillStyle='#ffdd00';
  ctx.shadowBlur=20;ctx.shadowColor='#ffaa00';
  ctx.fillText(String(gameEndScore).padStart(8,'0'),cx,cy-18);ctx.shadowBlur=0;
  ctx.font='11px "Courier New"';ctx.fillStyle='rgba(180,160,100,0.7)';
  ctx.fillText('SCORE',cx,cy+4);
  // Time elapsed
  const dur=gameEndDurationMs;
  ctx.font='20px "Courier New"';ctx.fillStyle='rgba(180,200,220,0.85)';
  ctx.fillText(_fmtDur(dur),cx,cy+42);
  ctx.font='10px "Courier New"';ctx.fillStyle='rgba(120,140,170,0.6)';
  ctx.fillText('TIME IN FIELD',cx,cy+58);
  // Craft + kills
  ctx.font='12px "Courier New"';ctx.fillStyle='rgba(120,150,200,0.65)';
  ctx.fillText(`${CRAFTS[P.craftIdx].name}  ·  ${P.kills} HOSTILE${P.kills!==1?'S':''} ELIMINATED`,cx,cy+84);
  // Main menu button
  const bw=220,bh=44,bx=cx-bw/2,by=cy+112;
  const bhov=mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh;
  ctx.fillStyle=bhov?'rgba(0,140,200,0.18)':'rgba(0,0,0,0.6)';
  roundRect(ctx,bx,by,bw,bh,8);ctx.fill();
  ctx.strokeStyle=bhov?'#00ccff':'rgba(0,100,160,0.5)';ctx.lineWidth=bhov?2:1.3;
  ctx.shadowBlur=bhov?14:0;ctx.shadowColor='#00ccff';
  roundRect(ctx,bx,by,bw,bh,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 13px "Courier New"';ctx.fillStyle=bhov?'#00ccff':'rgba(100,160,220,0.8)';
  ctx.fillText('◀  MAIN MENU',cx,by+bh/2+5);
  // Countdown
  ctx.font='11px "Courier New"';ctx.fillStyle='rgba(100,120,160,0.6)';
  ctx.fillText(`Auto-returning in ${Math.ceil(countdown)}s`,cx,by+bh+22);
  // Auto-advance
  if(countdown<=0){ _returnToStart(); }
  ctx.textAlign='left';
}

function _returnToStart(){
  deathScreenEnteredAt=0;
  jrCaptives=[];jrCarrying=-1;tngPads=[];tngSeq=1;tngOnPad=-1;tngHoldMs=0;
  WORLD_W=2600;WORLD_H=1700;ttLevel=1;nukes=[];gameMode='battle';gameState='start';
}

function drawHallOfFame(){
  const W=canvas.width,H=canvas.height,cx=W/2,cy=H/2;
  ctx.fillStyle='#050d1a';ctx.fillRect(0,0,W,H);
  ctx.strokeStyle='rgba(0,80,160,0.07)';ctx.lineWidth=1;
  for(let x=0;x<W;x+=70){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x,H);ctx.stroke();}
  for(let y=0;y<H;y+=70){ctx.beginPath();ctx.moveTo(0,y);ctx.lineTo(W,y);ctx.stroke();}
  ctx.textAlign='center';
  ctx.font='bold 36px "Courier New"';ctx.fillStyle='#ffdd00';ctx.shadowBlur=28;ctx.shadowColor='#ffaa00';
  ctx.fillText('HALL OF FAME',cx,46);ctx.shadowBlur=0;
  // Tabs
  const tabW=Math.min(200,W*0.28),tabH=36,tabGap=12;
  const tab1X=cx-tabW-tabGap/2, tab2X=cx+tabGap/2, tabY=68;
  const tab1Hov=mouse.x>tab1X&&mouse.x<tab1X+tabW&&mouse.y>tabY&&mouse.y<tabY+tabH;
  const tab2Hov=mouse.x>tab2X&&mouse.x<tab2X+tabW&&mouse.y>tabY&&mouse.y<tabY+tabH;
  [[tab1X,'THIS DEVICE',0],[tab2X,'BEST GLOBALLY',1]].forEach(([tx,label,idx])=>{
    const act=hofTab===idx;
    ctx.fillStyle=act?'rgba(0,180,255,0.18)':'rgba(0,0,0,0.5)';
    roundRect(ctx,tx,tabY,tabW,tabH,6);ctx.fill();
    ctx.strokeStyle=act?'#00ccff':'rgba(0,100,160,0.4)';ctx.lineWidth=act?2:1;
    ctx.shadowBlur=act?12:0;ctx.shadowColor='#00ccff';
    roundRect(ctx,tx,tabY,tabW,tabH,6);ctx.stroke();ctx.shadowBlur=0;
    ctx.font=`${act?'bold ':''}12px "Courier New"`;
    ctx.fillStyle=act?'#00eeff':'rgba(80,140,200,0.7)';
    ctx.fillText(label,tx+tabW/2,tabY+tabH/2+5);
  });
  const listY=tabY+tabH+14, listH=H-listY-58;
  ctx.fillStyle='rgba(0,0,0,0.35)';ctx.fillRect(cx-W*0.46,listY,W*0.92,listH);
  ctx.strokeStyle='rgba(0,80,160,0.25)';ctx.lineWidth=1;ctx.strokeRect(cx-W*0.46,listY,W*0.92,listH);
  if(hofTab===0){
    // ── THIS DEVICE ──
    const scores=_hofLoad();
    if(scores.length===0){
      ctx.font='13px "Courier New"';ctx.fillStyle='rgba(80,120,180,0.6)';
      ctx.fillText('NO SCORES RECORDED YET — COMPLETE A MISSION TO APPEAR HERE',cx,listY+listH/2);
    } else {
      const rowH=Math.min(32, listH/(scores.length+1));
      const colX={rank:cx-W*0.44,mode:cx-W*0.30,score:cx+W*0.04,dur:cx+W*0.22,date:cx+W*0.38};
      ctx.font='bold 9px "Courier New"';ctx.fillStyle='rgba(0,180,255,0.55)';
      ctx.textAlign='left';
      ctx.fillText('#',   colX.rank, listY+14);
      ctx.fillText('MODE',colX.mode, listY+14);
      ctx.fillText('SCORE',colX.score,listY+14);
      ctx.fillText('TIME', colX.dur,  listY+14);
      ctx.fillText('DATE', colX.date, listY+14);
      ctx.strokeStyle='rgba(0,100,160,0.3)';ctx.lineWidth=1;
      ctx.beginPath();ctx.moveTo(cx-W*0.44,listY+18);ctx.lineTo(cx+W*0.46,listY+18);ctx.stroke();
      scores.forEach((s,i)=>{
        const ry=listY+26+i*rowH;
        if(ry+rowH>listY+listH) return;
        const gold=i===0,silver=i===1,bronze=i===2;
        ctx.font=`${gold?'bold ':''}${Math.min(11,rowH*0.38)}px "Courier New"`;
        ctx.fillStyle=gold?'#ffdd44':silver?'#cccccc':bronze?'#cc8844':'rgba(140,180,220,0.85)';
        ctx.textAlign='left';
        ctx.fillText(`${i+1}`,colX.rank,ry);
        ctx.fillText(MODE_LABELS[s.mode]||s.mode,colX.mode,ry);
        ctx.textAlign='right';
        ctx.fillText(s.score.toLocaleString(),colX.score+60,ry);
        ctx.textAlign='left';
        ctx.fillText(_fmtDur(s.duration),colX.dur,ry);
        ctx.fillText(_fmtDate(s.date),colX.date,ry);
      });
    }
  } else {
    // ── BEST GLOBALLY (placeholder) ──
    ctx.font='9px "Courier New"';ctx.fillStyle='rgba(255,180,40,0.45)';
    ctx.fillText('— LIVE LEADERBOARD COMING SOON — PLACEHOLDER DATA SHOWN —',cx,listY+12);
    const rowH=Math.min(28, (listH-24)/(HOF_GLOBAL.length+1));
    const colX={rank:cx-W*0.44,name:cx-W*0.36,country:cx+W*0.04,score:cx+W*0.16,time:cx+W*0.32};
    ctx.font='bold 9px "Courier New"';ctx.fillStyle='rgba(0,180,255,0.55)';
    ctx.textAlign='left';
    ctx.fillText('#',      colX.rank,   listY+28);
    ctx.fillText('HANDLE', colX.name,   listY+28);
    ctx.fillText('COUNTRY',colX.country,listY+28);
    ctx.fillText('SCORE',  colX.score,  listY+28);
    ctx.fillText('BEST TIME',colX.time, listY+28);
    ctx.strokeStyle='rgba(0,100,160,0.3)';ctx.lineWidth=1;
    ctx.beginPath();ctx.moveTo(cx-W*0.44,listY+33);ctx.lineTo(cx+W*0.46,listY+33);ctx.stroke();
    HOF_GLOBAL.forEach((g,i)=>{
      const ry=listY+44+i*rowH;
      if(ry+rowH>listY+listH) return;
      const gold=i===0,silver=i===1,bronze=i===2;
      ctx.font=`${gold?'bold ':''}${Math.min(11,rowH*0.38)}px "Courier New"`;
      ctx.fillStyle=gold?'#ffdd44':silver?'#cccccc':bronze?'#cc8844':'rgba(140,180,220,0.85)';
      ctx.textAlign='left';
      ctx.fillText(`${i+1}`,colX.rank,ry);
      ctx.fillText(g.name,  colX.name,ry);
      ctx.fillText(g.country,colX.country,ry);
      ctx.textAlign='right';
      ctx.fillText(g.score.toLocaleString(),colX.score+60,ry);
      ctx.textAlign='left';
      ctx.fillText(g.time,  colX.time,ry);
    });
  }
  // Back button
  const bw=160,bh=38,bx=Math.max(20,W*0.03),by=H-50;
  _briefBtn(bx,by,bw,bh,'◀  BACK','#aaccff',false);
  ctx.textAlign='left';
}

// ═══════════════════════════════════════════════════════════════
// JR RESCUE — Time Trial Level 4
// Three captive J R crafts guarded by hostiles. Kill the guards,
// grab the J R by flying close, return it to the central base.
// ═══════════════════════════════════════════════════════════════
const JRR_GRAB_R   = 65;   // px — how close to pick up a free JR
const JRR_BASE_R   = 90;   // px — delivery zone at base
const JRR_CARRY_SPD= 0.78; // speed multiplier while carrying

function _jrrPlaceCaptives(){
  jrCaptives=[];
  jrCarrying=-1;
  const W=JRR_WORLD_W, H=JRR_WORLD_H;
  jrBase={x:W/2, y:H/2};
  // Three captives in distinct areas, away from base and player spawn
  const zones=[
    {x:W*0.18, y:H*0.22},
    {x:W*0.82, y:H*0.22},
    {x:W*0.50, y:H*0.82},
  ];
  for(let i=0;i<3;i++){
    let cx=zones[i].x+rng(-120,120), cy=zones[i].y+rng(-120,120);
    cx=clamp(cx,120,W-120); cy=clamp(cy,120,H-120);
    jrCaptives.push({x:cx, y:cy, state:'captive', guards:[], rotor:rng(0,Math.PI*2), t:0});
  }
}

function _jrrSpawnGuards(){
  const placed=[];
  for(let ci=0;ci<jrCaptives.length;ci++){
    const c=jrCaptives[ci];
    c.guards=[];
    const types=['guard','guard','turret','wraith'];
    for(let i=0;i<4;i++){
      let ex,ey,a=0;
      do{
        ex=c.x+rng(-260,260); ey=c.y+rng(-260,260); a++;
      }while(a<40&&(circleVsObs(ex,ey,22)||placed.some(p=>dist(ex,ey,p.x,p.y)<70)||dist(ex,ey,c.x,c.y)<60));
      ex=clamp(ex,30,JRR_WORLD_W-30); ey=clamp(ey,30,JRR_WORLD_H-30);
      const e=mkEnemy(types[i],ex,ey);
      e._jrrCaptive=ci; // which captive this guard protects
      enemies.push(e);
      placed.push({x:ex,y:ey});
      c.guards.push(e); // hold reference
    }
  }
  // Roaming patrols
  for(let i=0;i<14;i++){
    let ex,ey,a=0;
    do{ex=rng(60,JRR_WORLD_W-60);ey=rng(60,JRR_WORLD_H-60);a++;}
    while(a<40&&(circleVsObs(ex,ey,22)||placed.some(p=>dist(ex,ey,p.x,p.y)<80)));
    const t=['scout','scout','guard','dart','phantom'][i%5];
    enemies.push(mkEnemy(t,ex,ey));placed.push({x:ex,y:ey});
  }
}

function tickJRRescue(dt){
  if(!jrCaptives.length) return;
  const now=Date.now()/1000;
  for(let ci=0;ci<jrCaptives.length;ci++){
    const c=jrCaptives[ci];
    c.t+=dt;c.rotor+=dt*12;
    if(c.state==='captive'){
      // Check if all assigned guards are dead (not in enemies array)
      const guardsAlive=c.guards.some(g=>enemies.includes(g));
      if(!guardsAlive) c.state='free';
    }
    if(c.state==='free'&&jrCarrying===-1){
      if(dist(P.x,P.y,c.x,c.y)<JRR_GRAB_R){
        c.state='carried'; jrCarrying=ci;
        weaponFlash={name:'J R RESCUED — RETURN TO BASE',ms:3000};
        spawnParts(c.x,c.y,MM_COL,_pCount(20),4,6,600); SFX.confirm();
      }
    }
    if(c.state==='carried'){
      // JR follows player closely
      c.x=P.x+Math.cos(P.aim+Math.PI)*28;
      c.y=P.y+Math.sin(P.aim+Math.PI)*28;
      // Check delivery at base
      if(dist(P.x,P.y,jrBase.x,jrBase.y)<JRR_BASE_R){
        c.state='rescued'; jrCarrying=-1;
        spawnParts(c.x,c.y,MM_COL,_pCount(28),5,7,800);
        spawnParts(jrBase.x,jrBase.y,'#ffffff',_pCount(12),3,5,600);
        SFX.confirm(); if(settings.screenShake)shake=10;
        const rescued=jrCaptives.filter(j=>j.state==='rescued').length;
        weaponFlash={name:`J R SAFE! ${rescued}/3 RESCUED`,ms:3000};
        score+=1000;
        // All three rescued?
        if(jrCaptives.every(j=>j.state==='rescued')){
          ttFinished=true; ttElapsed=performance.now()-ttStartTime;
          computeJRRFinalScore(); SFX.wave();
          setTimeout(()=>{gameState='timeTrialResult';},800);
        }
      }
    }
  }
  // Speed penalty while carrying
  if(jrCarrying>=0) P.spd=CRAFTS[P.craftIdx].spd*JRR_CARRY_SPD;
  else P.spd=CRAFTS[P.craftIdx].spd;
}

function computeJRRFinalScore(){
  const timeSec=ttElapsed/1000;
  const timeBase=Math.max(0,(900-timeSec)*700);
  const hpFactor=0.4+0.6*(P.hp/P.maxHp);
  const survivalFactor=(hpFactor+0.4+0.6*(P.bat/P.maxBat))/2;
  const killPct=ttTotalEnemies>0?P.kills/ttTotalEnemies:0;
  const killFactor=0.5+0.5*killPct;
  ttFinalScore=Math.floor((timeBase+score)*survivalFactor*killFactor);
}

function drawJRRescue(){
  const now=Date.now()/1000;
  // Draw base
  const bsx=jrBase.x-camX, bsy=jrBase.y-camY;
  const pulse=0.5+0.5*Math.sin(now*3);
  // Base landing pad rings
  ctx.save();ctx.translate(bsx,bsy);
  ctx.beginPath();ctx.arc(0,0,JRR_BASE_R,0,Math.PI*2);
  ctx.fillStyle=`rgba(0,200,255,${0.05+0.04*pulse})`;ctx.fill();
  ctx.strokeStyle=`rgba(0,200,255,${0.35+0.2*pulse})`;ctx.lineWidth=2;
  ctx.shadowBlur=16;ctx.shadowColor='#00ccff';ctx.stroke();ctx.shadowBlur=0;
  ctx.beginPath();ctx.arc(0,0,JRR_BASE_R*0.55,0,Math.PI*2);
  ctx.strokeStyle=`rgba(0,200,255,${0.2*pulse})`;ctx.lineWidth=1;ctx.stroke();
  // Cross hairs
  ctx.strokeStyle=`rgba(0,200,255,${0.25})`;ctx.lineWidth=1;
  ctx.beginPath();ctx.moveTo(-JRR_BASE_R*0.7,0);ctx.lineTo(JRR_BASE_R*0.7,0);ctx.stroke();
  ctx.beginPath();ctx.moveTo(0,-JRR_BASE_R*0.7);ctx.lineTo(0,JRR_BASE_R*0.7);ctx.stroke();
  // Count rescued
  const rescuedCount=jrCaptives.filter(j=>j.state==='rescued').length;
  ctx.font='bold 13px "Courier New"';ctx.fillStyle='#00ccff';ctx.textAlign='center';
  ctx.shadowBlur=8;ctx.shadowColor='#00ccff';
  ctx.fillText(`BASE  ${rescuedCount}/3`,0,JRR_BASE_R+18);ctx.shadowBlur=0;
  ctx.restore();
  // Draw rescued JRs at base
  let rIdx=0;
  for(const c of jrCaptives){
    if(c.state!=='rescued') continue;
    const angle=(rIdx/3)*Math.PI*2+now*0.5;
    const ox=jrBase.x+Math.cos(angle)*28, oy=jrBase.y+Math.sin(angle)*28;
    _drawJRCaptive(ox-camX,oy-camY,c.rotor,1.0,'#44ffcc');
    rIdx++;
  }
  // Draw captives in world
  for(let ci=0;ci<jrCaptives.length;ci++){
    const c=jrCaptives[ci];
    if(c.state==='rescued') continue;
    const sx=c.x-camX, sy=c.y-camY;
    if(sx<-80||sx>canvas.width+80||sy<-80||sy>canvas.height+80) continue;
    const col=c.state==='captive'?'#ff4466':c.state==='free'?'#ffdd00':'#44ffcc';
    if(c.state==='free'){
      // Pulsing grab ring
      const gp=0.5+0.5*Math.sin(now*6);
      ctx.beginPath();ctx.arc(sx,sy,JRR_GRAB_R,0,Math.PI*2);
      ctx.strokeStyle=`rgba(255,220,0,${0.35*gp})`;ctx.lineWidth=1.5;ctx.stroke();
    }
    if(c.state==='captive'){
      // Cage bars around captive
      ctx.save();ctx.translate(sx,sy);
      const cageR=22;
      ctx.strokeStyle='rgba(255,60,60,0.5)';ctx.lineWidth=1.2;
      for(let b=0;b<6;b++){
        const ba=(b/6)*Math.PI*2;
        ctx.beginPath();ctx.moveTo(Math.cos(ba)*10,Math.sin(ba)*10);
        ctx.lineTo(Math.cos(ba)*cageR,Math.sin(ba)*cageR);ctx.stroke();
      }
      ctx.beginPath();ctx.arc(0,0,cageR,0,Math.PI*2);
      ctx.strokeStyle='rgba(255,60,60,0.4)';ctx.stroke();
      ctx.restore();
    }
    _drawJRCaptive(sx,sy,c.rotor,c.state==='captive'?0.7:1.0,col);
    ctx.font='9px "Courier New"';ctx.fillStyle=col;ctx.textAlign='center';
    const label=c.state==='captive'?'CAPTIVE':c.state==='free'?'▼ GRAB':'CARRYING';
    ctx.fillText(label,sx,sy-26);
  }
}

function drawJRRMinimap(mx,my,mw,mh){
  const scx=mw/WORLD_W, scy=mh/WORLD_H;
  // Base
  ctx.beginPath();ctx.arc(mx+jrBase.x*scx,my+jrBase.y*scy,5,0,Math.PI*2);
  ctx.fillStyle='#00ccff';ctx.shadowBlur=6;ctx.shadowColor='#00ccff';ctx.fill();ctx.shadowBlur=0;
  // Captives
  for(const c of jrCaptives){
    if(c.state==='rescued') continue;
    const col=c.state==='captive'?'#ff4466':c.state==='free'?'#ffdd00':'#44ffcc';
    ctx.beginPath();ctx.arc(mx+c.x*scx,my+c.y*scy,4,0,Math.PI*2);
    ctx.fillStyle=col;ctx.shadowBlur=5;ctx.shadowColor=col;ctx.fill();ctx.shadowBlur=0;
  }
}

function drawJRRHUD(){
  const T=IS_TOUCH, pad=T?10:18, cx=canvas.width/2;
  ctx.textAlign='center';
  const ms=ttFinished?ttElapsed:(performance.now()-ttStartTime);
  ctx.font=`bold ${T?13:18}px "Courier New"`;ctx.fillStyle='#44ffcc';ctx.shadowBlur=10;ctx.shadowColor='#00ffaa';
  ctx.fillText(formatTTTime(ms),cx,pad+(T?14:20));ctx.shadowBlur=0;
  // Status badges for 3 JRs
  const iconW=T?30:42,iconH=T?20:26,gap=T?8:12;
  const totalW=3*iconW+2*gap, startX=cx-totalW/2, iconY=pad+(T?26:38);
  for(let i=0;i<3;i++){
    if(!jrCaptives[i]) continue;
    const c=jrCaptives[i];
    const ix=startX+i*(iconW+gap);
    const col=c.state==='captive'?'#ff4466':c.state==='free'?'#ffdd00':c.state==='carried'?'#44ffcc':'#44ff88';
    ctx.fillStyle=c.state==='captive'?'rgba(50,10,20,0.8)':`${col}22`;
    roundRect(ctx,ix,iconY,iconW,iconH,4);ctx.fill();
    ctx.strokeStyle=col;ctx.lineWidth=c.state==='carried'?2.5:1.5;
    ctx.shadowBlur=c.state==='carried'?10:0;ctx.shadowColor=col;
    roundRect(ctx,ix,iconY,iconW,iconH,4);ctx.stroke();ctx.shadowBlur=0;
    ctx.font=`bold ${T?7:9}px "Courier New"`;ctx.fillStyle=col;
    const lbl=c.state==='captive'?'CAPTIVE':c.state==='free'?'FREE':c.state==='carried'?'▶ GO!':'✔ SAFE';
    ctx.fillText(lbl,ix+iconW/2,iconY+iconH/2+3);
  }
  // Carrying indicator
  if(jrCarrying>=0){
    // Compute direction from player to base and pick closest cardinal arrow
    const _dx=jrBase.x-P.x, _dy=jrBase.y-P.y;
    const _ang=Math.atan2(_dy,_dx);
    const _arrows=['→','↘','↓','↙','←','↖','↑','↗'];
    const _dir=_arrows[((Math.round(_ang/(Math.PI/4))%8)+8)%8];
    ctx.font=`${T?8:10}px "Courier New"`;ctx.fillStyle='#44ffcc';ctx.shadowBlur=6;ctx.shadowColor='#44ffcc';
    ctx.fillText(`${_dir}  RETURN TO BASE  ${_dir}`,cx,iconY+iconH+(T?10:14));ctx.shadowBlur=0;
  }
  ctx.textAlign='left';
}

function _drawJRCaptive(x,y,rotor,alpha,col){
  ctx.save();ctx.translate(x,y);ctx.globalAlpha=alpha;
  const sz=MM_SIZE*0.9;
  ctx.shadowBlur=12;ctx.shadowColor=col;
  const ARMS=[Math.PI/4,-Math.PI/4,3*Math.PI/4,-3*Math.PI/4];
  ctx.strokeStyle=col;ctx.lineWidth=1.2;
  for(const a of ARMS){
    const ax=Math.cos(a)*sz*1.25,ay=Math.sin(a)*sz*1.25;
    ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(ax,ay);ctx.stroke();
    ctx.beginPath();ctx.arc(ax,ay,2,0,Math.PI*2);ctx.fillStyle=col;ctx.fill();
    ctx.save();ctx.translate(ax,ay);ctx.rotate(rotor);
    ctx.beginPath();ctx.ellipse(0,0,sz*0.6,sz*0.15,0,0,Math.PI*2);
    ctx.strokeStyle='#aaffee';ctx.lineWidth=1;ctx.globalAlpha=0.5;ctx.stroke();
    ctx.globalAlpha=alpha;ctx.restore();
  }
  const bs=sz*0.55;
  ctx.beginPath();for(let i=0;i<6;i++){const a=(Math.PI/3)*i;i===0?ctx.moveTo(Math.cos(a)*bs,Math.sin(a)*bs):ctx.lineTo(Math.cos(a)*bs,Math.sin(a)*bs);}ctx.closePath();
  ctx.fillStyle='rgba(4,12,24,0.95)';ctx.fill();ctx.strokeStyle=col;ctx.lineWidth=1.4;ctx.stroke();
  const pulse=0.6+0.4*Math.sin(Date.now()/250);
  ctx.beginPath();ctx.arc(0,0,2.5*pulse,0,Math.PI*2);ctx.fillStyle=col;ctx.shadowColor=col;ctx.shadowBlur=8;ctx.fill();ctx.shadowBlur=0;
  ctx.globalAlpha=1;ctx.restore();
}

function startJRRescue(){
  _hideAllAds();
  ttLevel=4; nukes=[];
  WORLD_W=JRR_WORLD_W; WORLD_H=JRR_WORLD_H;
  score=0;wave=1;bossWarning=0;empFlash=0;weaponFlash={name:'',ms:0};lastHullBeepMs=0;
  leechFlash={active:false,tx:0,ty:0,ms:0};shockwaveFlash={ms:0};
  portalActive=false;portalPositions=[];
  particles.length=0;pickups.length=0;pBullets.length=0;eBullets.length=0;
  mines.length=0;seekers.length=0;rockets.length=0;boomerangs.length=0;fractals.length=0;hazards.length=0;grenades.length=0;gravityWells.length=0;faradayCages.length=0;
  miniMe.active=false;miniMe.lost=false;miniMe.hp=MM_HP;miniMe.iframes=0;
  resetPlayer();
  P.x=JRR_WORLD_W/2; P.y=JRR_WORLD_H-150;
  camX=P.x-canvas.width/2; camY=P.y-canvas.height/2;
  _jrrPlaceCaptives();
  generateNukeObstacles(); // reuse open-world obstacle generator
  spawnHiddenPickups();
  _jrrSpawnGuards();
  ttTotalEnemies=enemies.length;
  if(P.unlockedW.has(WEAPONS.findIndex(w=>w.id==='seekr'))){
    const half=Math.ceil(enemies.length/2);
    P.seekStock=half+Math.floor(Math.random()*(enemies.length*3-half+1));
  }
  ttStartTime=performance.now();ttElapsed=0;ttFinished=false;ttFinalScore=0;
  gameStartTime=Date.now();gameState='playing';_snapMouseToPlayer();
}

// ═══════════════════════════════════════════════════════════════
// TOUCH N GO — Time Trial Level 5
// 5 numbered landing pads scattered across the world. Numbers are
// hidden until first touch. Land on them in order 1→5. Wrong order
// resets sequence to 1. Best time wins.
// ═══════════════════════════════════════════════════════════════

function _tngPlacePads(){
  tngPads=[];tngSeq=1;tngOnPad=-1;tngHoldMs=0;
  const W=TNG_WORLD_W,H=TNG_WORLD_H,MARGIN=160;
  // Five zones spread across world — top, upper-mid, centre, lower-mid, bottom
  const zones=[
    {x:W*0.22,y:H*0.12},{x:W*0.75,y:H*0.26},
    {x:W*0.40,y:H*0.50},{x:W*0.18,y:H*0.72},
    {x:W*0.68,y:H*0.88},
  ];
  // Shuffle numbers 1-5 so player can't guess positions from pattern
  const nums=[1,2,3,4,5];
  for(let i=nums.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[nums[i],nums[j]]=[nums[j],nums[i]];}
  for(let i=0;i<5;i++){
    let px=zones[i].x+rng(-160,160),py=zones[i].y+rng(-160,160);
    px=clamp(px,MARGIN,W-MARGIN);py=clamp(py,MARGIN,H-MARGIN);
    tngPads.push({x:px,y:py,num:nums[i],revealed:false,done:false,t:0});
  }
}

function _tngSpawnEnemies(){
  enemies.length=[];const placed=[];
  const W=TNG_WORLD_W,H=TNG_WORLD_H,MARGIN=80;
  const types=['scout','scout','guard','guard','dart','dart','phantom','wraith','brute','turret'];
  for(let i=0;i<22;i++){
    let x,y,a=0;
    do{x=rng(MARGIN,W-MARGIN);y=rng(MARGIN,H-MARGIN);a++;}
    while(a<50&&(circleVsObs(x,y,22)||placed.some(p=>dist(x,y,p.x,p.y)<80)||tngPads.some(p=>dist(x,y,p.x,p.y)<TNG_PAD_R+80)));
    enemies.push(mkEnemy(types[i%types.length],x,y));placed.push({x,y});
  }
  spawnHazardZaps(10,MARGIN,W-MARGIN,MARGIN,H-MARGIN,80,150);
  spawnHazardMines(8,MARGIN,W-MARGIN,MARGIN,H-MARGIN,W/2,H-200,400);
}

function tickTNG(dt){
  if(!tngPads.length||ttFinished) return;
  for(const p of tngPads) p.t+=dt;
  const px=P.x,py=P.y;
  let nearIdx=-1;
  for(let i=0;i<tngPads.length;i++){
    if(!tngPads[i].done&&dist(px,py,tngPads[i].x,tngPads[i].y)<TNG_TOUCH_R){nearIdx=i;break;}
  }
  if(nearIdx!==-1){
    const pad=tngPads[nearIdx];
    // Reveal number on first approach
    if(!pad.revealed){pad.revealed=true;spawnParts(pad.x,pad.y,'#ffdd00',_pCount(10),3,5,500);}
    if(nearIdx!==tngOnPad){tngOnPad=nearIdx;tngHoldMs=0;}
    tngHoldMs+=dt*1000;
    if(tngHoldMs>=TNG_HOLD_MS){
      // Evaluate
      if(pad.num===tngSeq){
        pad.done=true;tngSeq++;score+=500;
        spawnParts(pad.x,pad.y,'#44ff88',_pCount(22),5,7,700);
        spawnParts(pad.x,pad.y,'#ffffff',_pCount(10),3,5,500);
        SFX.confirm();if(settings.screenShake)shake=8;
        weaponFlash={name:`PAD ${pad.num} ✔  —  ${tngSeq<=5?'FIND PAD '+tngSeq:'ALL PADS CLEARED!'}`,ms:2500};
        if(tngSeq>5){
          ttFinished=true;ttElapsed=performance.now()-ttStartTime;
          computeTNGFinalScore();SFX.wave();
          setTimeout(()=>{gameState='timeTrialResult';},800);
        }
      } else {
        // Wrong order — reset sequence
        const wrongNum=pad.num;
        tngSeq=1;
        // Un-done all pads but keep them revealed
        for(const p of tngPads) p.done=false;
        spawnParts(pad.x,pad.y,'#ff2244',_pCount(18),4,6,600);
        SFX.boom();if(settings.screenShake)shake=14;
        weaponFlash={name:`WRONG ORDER — RESTART FROM PAD 1`,ms:3000};
      }
      tngOnPad=-1;tngHoldMs=0;
    }
  } else {
    tngOnPad=-1;tngHoldMs=0;
  }
}

function computeTNGFinalScore(){
  const timeSec=ttElapsed/1000;
  const timeBase=Math.max(0,(600-timeSec)*900);
  const hpFac=0.4+0.6*(P.hp/P.maxHp);
  const batFac=0.4+0.6*(P.bat/P.maxBat);
  ttFinalScore=Math.floor((timeBase+score)*((hpFac+batFac)/2));
}

function drawTNG(){
  if(!tngPads.length) return;
  const now=Date.now()/1000;
  for(let i=0;i<tngPads.length;i++){
    const pad=tngPads[i];
    const sx=pad.x-camX,sy=pad.y-camY;
    if(sx<-120||sx>canvas.width+120||sy<-120||sy>canvas.height+120) continue;
    const isNext=pad.num===tngSeq&&!pad.done;
    const isDone=pad.done;
    const col=isDone?'#44ff88':isNext?'#ffdd00':pad.revealed?'#aaccff':'#4466aa';
    const pulse=0.5+0.5*Math.sin(now*(isNext?4:2)+i);
    ctx.save();ctx.translate(sx,sy);
    // Outer glow ring
    ctx.beginPath();ctx.arc(0,0,TNG_PAD_R+4,0,Math.PI*2);
    ctx.strokeStyle=`rgba(${isDone?'68,255,136':isNext?'255,220,0':'68,130,255'},${0.2+0.15*pulse})`;
    ctx.lineWidth=3;ctx.shadowBlur=16;ctx.shadowColor=col;ctx.stroke();ctx.shadowBlur=0;
    // Pad surface
    ctx.beginPath();ctx.arc(0,0,TNG_PAD_R,0,Math.PI*2);
    ctx.fillStyle=isDone?'rgba(10,40,20,0.85)':isNext?'rgba(40,35,0,0.85)':'rgba(8,16,40,0.85)';ctx.fill();
    ctx.strokeStyle=col;ctx.lineWidth=2;ctx.stroke();
    // Inner ring
    ctx.beginPath();ctx.arc(0,0,TNG_PAD_R*0.62,0,Math.PI*2);
    ctx.strokeStyle=col+'55';ctx.lineWidth=1;ctx.stroke();
    // Cross hairs
    ctx.strokeStyle=col+'44';ctx.lineWidth=1;
    ctx.beginPath();ctx.moveTo(-TNG_PAD_R*0.85,0);ctx.lineTo(TNG_PAD_R*0.85,0);ctx.stroke();
    ctx.beginPath();ctx.moveTo(0,-TNG_PAD_R*0.85);ctx.lineTo(0,TNG_PAD_R*0.85);ctx.stroke();
    // Number label — only if revealed or done
    if(pad.revealed||isDone){
      ctx.font=`bold ${Math.round(TNG_PAD_R*0.68)}px "Courier New"`;
      ctx.fillStyle=col;ctx.shadowBlur=10;ctx.shadowColor=col;
      ctx.textAlign='center';ctx.textBaseline='middle';
      ctx.fillText(String(pad.num),0,1);
      ctx.textBaseline='alphabetic';ctx.shadowBlur=0;
    } else {
      // Unrevealed — show ?
      ctx.font=`bold ${Math.round(TNG_PAD_R*0.62)}px "Courier New"`;
      ctx.fillStyle=`rgba(80,120,200,${0.4+0.2*pulse})`;
      ctx.textAlign='center';ctx.textBaseline='middle';
      ctx.fillText('?',0,1);ctx.textBaseline='alphabetic';
    }
    // Done checkmark overlay
    if(isDone){
      ctx.font=`bold 11px "Courier New"`;ctx.fillStyle='#44ff88aa';
      ctx.textAlign='center';ctx.fillText('✔',0,TNG_PAD_R+14);
    }
    // Approach prompt
    const dToPlayer=dist(P.x,P.y,pad.x,pad.y);
    if(dToPlayer<TNG_TOUCH_R+20&&!pad.done){
      const pct=Math.min(1,tngHoldMs/TNG_HOLD_MS);
      // Hold progress arc
      ctx.beginPath();ctx.arc(0,0,TNG_PAD_R+10,-Math.PI/2,-Math.PI/2+Math.PI*2*pct);
      ctx.strokeStyle=pad.num===tngSeq?'#ffdd00':'#ff4444';ctx.lineWidth=3;
      ctx.shadowBlur=10;ctx.shadowColor=pad.num===tngSeq?'#ffdd00':'#ff4444';
      ctx.stroke();ctx.shadowBlur=0;
    }
    ctx.restore();
  }
}

function drawTNGMinimap(mx,my,mw,mh){
  const scx=mw/WORLD_W,scy=mh/WORLD_H;
  for(const pad of tngPads){
    const col=pad.done?'#44ff88':pad.revealed?'#aaccff':'#334488';
    ctx.beginPath();ctx.arc(mx+pad.x*scx,my+pad.y*scy,5,0,Math.PI*2);
    ctx.fillStyle=col;ctx.shadowBlur=5;ctx.shadowColor=col;ctx.fill();ctx.shadowBlur=0;
    if(pad.revealed||pad.done){
      ctx.font='bold 6px "Courier New"';ctx.fillStyle='#000';
      ctx.textAlign='center';ctx.textBaseline='middle';
      ctx.fillText(String(pad.num),mx+pad.x*scx,my+pad.y*scy+0.5);
      ctx.textBaseline='alphabetic';
    }
  }
}

function drawTNGHUD(){
  const T=IS_TOUCH,cx=canvas.width/2,pad=T?10:18;
  ctx.textAlign='center';
  // Timer
  const ms=ttFinished?ttElapsed:(performance.now()-ttStartTime);
  ctx.font=`bold ${T?13:18}px "Courier New"`;ctx.fillStyle='#ffdd00';ctx.shadowBlur=10;ctx.shadowColor='#ffaa00';
  ctx.fillText(formatTTTime(ms),cx,pad+(T?14:20));ctx.shadowBlur=0;
  // Sequence strip — 5 badges
  const bw=T?26:36,bh=T?18:24,bgap=T?6:8;
  const totalW=5*bw+4*bgap,startX=cx-totalW/2,stripY=pad+(T?26:36);
  for(let n=1;n<=5;n++){
    const bx=startX+(n-1)*(bw+bgap);
    const isDone=tngPads.some(p=>p.num===n&&p.done);
    const isNext=n===tngSeq&&!isDone;
    const col=isDone?'#44ff88':isNext?'#ffdd00':'rgba(80,100,140,0.6)';
    ctx.fillStyle=isDone?'rgba(10,40,20,0.8)':isNext?'rgba(40,35,0,0.8)':'rgba(6,12,28,0.8)';
    roundRect(ctx,bx,stripY,bw,bh,3);ctx.fill();
    ctx.strokeStyle=col;ctx.lineWidth=isNext?2:1;
    ctx.shadowBlur=isNext?8:0;ctx.shadowColor='#ffdd00';
    roundRect(ctx,bx,stripY,bw,bh,3);ctx.stroke();ctx.shadowBlur=0;
    ctx.font=`bold ${T?8:10}px "Courier New"`;ctx.fillStyle=col;
    ctx.fillText(isDone?'✔':String(n),bx+bw/2,stripY+bh/2+(T?3:4));
  }
  // Approach hint
  if(tngOnPad>=0&&!tngPads[tngOnPad]?.done){
    const pad2=tngPads[tngOnPad];
    const isRight=pad2.num===tngSeq;
    ctx.font=`${T?8:10}px "Courier New"`;
    ctx.fillStyle=isRight?'#ffdd00':'#ff4444';
    ctx.shadowBlur=6;ctx.shadowColor=isRight?'#ffdd00':'#ff4444';
    const msg=isRight?`HOLD — PAD ${pad2.num} ✔`:`PAD ${pad2.num} — NEED PAD ${tngSeq} FIRST`;
    ctx.fillText(msg,cx,stripY+bh+(T?10:14));ctx.shadowBlur=0;
  }
  ctx.textAlign='left';
}

function startTouchNGo(){
  _hideAllAds();
  ttLevel=5;nukes=[];jrCaptives=[];jrCarrying=-1;tngPads=[];tngSeq=1;tngOnPad=-1;tngHoldMs=0;
  WORLD_W=TNG_WORLD_W;WORLD_H=TNG_WORLD_H;
  score=0;wave=1;bossWarning=0;empFlash=0;weaponFlash={name:'FIND THE PADS — TOUCH IN ORDER 1 to 5',ms:4000};lastHullBeepMs=0;
  leechFlash={active:false,tx:0,ty:0,ms:0};shockwaveFlash={ms:0};
  portalActive=false;portalPositions=[];
  particles.length=0;pickups.length=0;pBullets.length=0;eBullets.length=0;
  mines.length=0;seekers.length=0;rockets.length=0;boomerangs.length=0;fractals.length=0;hazards.length=0;grenades.length=0;gravityWells.length=0;faradayCages.length=0;
  miniMe.active=false;miniMe.lost=false;miniMe.hp=MM_HP;miniMe.iframes=0;
  resetPlayer();
  P.x=TNG_WORLD_W/2;P.y=TNG_WORLD_H-150;
  camX=P.x-canvas.width/2;camY=P.y-canvas.height/2;
  _tngPlacePads();
  generateNukeObstacles();
  spawnHiddenPickups();
  _tngSpawnEnemies();
  ttTotalEnemies=enemies.length;
  if(P.unlockedW.has(WEAPONS.findIndex(w=>w.id==='seekr'))){
    const half=Math.ceil(enemies.length/2);
    P.seekStock=half+Math.floor(Math.random()*(enemies.length*3-half+1));
  }
  ttStartTime=performance.now();ttElapsed=0;ttFinished=false;ttFinalScore=0;
  gameStartTime=Date.now();gameState='playing';_snapMouseToPlayer();
}

function loadCustomLevel(levelData){
  _hideAllAds();
  gameMode='custom';
  WORLD_W=Math.min(4500,Math.max(canvas.width,levelData.worldW||2600));
  WORLD_H=Math.min(4500,Math.max(canvas.height,levelData.worldH||1700));
  score=0;wave=1;bossWarning=0;empFlash=0;
  weaponFlash={name:levelData.name||'CUSTOM LEVEL',ms:2500};
  customTransitionMs=0;customTransitionText='';
  leechFlash={active:false,tx:0,ty:0,ms:0};shockwaveFlash={ms:0};
  harbingerRef=null;
  portalActive=false;portalPositions=[];
  particles.length=0;pickups.length=0;pBullets.length=0;eBullets.length=0;
  mines.length=0;seekers.length=0;rockets.length=0;boomerangs.length=0;fractals.length=0;
  hazards.length=0;grenades.length=0;gravityWells.length=0;faradayCages.length=0;enemies.length=0;
  miniMe.active=false;miniMe.lost=false;miniMe.hp=MM_HP;miniMe.iframes=0;
  lastHullBeepMs=0;
  resetPlayer();
  P.x=levelData.spawnX||WORLD_W/2;
  P.y=levelData.spawnY||WORLD_H/2;
  camX=clamp(P.x-canvas.width/2,0,Math.max(0,WORLD_W-canvas.width));
  camY=clamp(P.y-canvas.height/2,0,Math.max(0,WORLD_H-canvas.height));
  obstacles=[];
  if(levelData.obstacles){
    for(const o of levelData.obstacles){
      if(o.type==='pillar') obstacles.push({type:'pillar',x:o.x,y:o.y,r:o.r||35,rot:Math.random()*Math.PI});
      else if(o.type==='wall') obstacles.push({type:'wall',x:o.x,y:o.y,w:o.w||26,h:o.h||100});
    }
  }
  if(levelData.enemies){
    for(const en of levelData.enemies){
      const e=mkEnemy(en.type,en.x,en.y);
      if(e){enemies.push(e);if(en.type==='harbinger') harbingerRef=e;}
    }
  }
  if(levelData.pickups){
    for(const p of levelData.pickups) spawnPickup(p.x,p.y,p.type,!!p.hidden);
  }
  if(levelData.hazards){
    for(const h of levelData.hazards){
      if(h.type==='zap_pylon') spawnZapPylonPair(h.x,h.y,h.angle||0,h.gap||120);
      else if(h.type==='floor_mine') spawnFloorMine(h.x,h.y);
    }
  }
  customWinCondition=levelData.winCondition||'killAll';
  customWinParams=levelData.winParams||{};
  customObjectives=levelData.objectives||[];
  customSurviveMs=(customWinCondition==='survive'&&customWinParams.seconds)?customWinParams.seconds*1000:0;
  customKeysCollected=0;
  customKeysTotal=customObjectives.filter(o=>o.type==='key').length;
  customItemHeld=false;
  customFinishX=0;customFinishY=0;
  customGoalX=0;customGoalY=0;
  for(const obj of customObjectives){
    if(obj.type==='finish'){customFinishX=obj.x;customFinishY=obj.y;}
    if(obj.type==='goal'){customGoalX=obj.x;customGoalY=obj.y;}
  }
  gameStartTime=Date.now();
  gameState='playing';
}
function customLevelWin(){
  customWinCondition='';// prevent re-entry from tick
  if(customPack&&customPack.currentIdx<customPack.levels.length-1){
    customPack.currentIdx++;
    customTransitionMs=3000;customTransitionText='LEVEL COMPLETE';
    SFX.wave();
    setTimeout(()=>loadCustomLevel(customPack.levels[customPack.currentIdx]),3000);
  } else {
    gameState='customResult';
    SFX.confirm();
  }
}
function tickCustomTransition(dt){
  if(customTransitionMs>0) customTransitionMs-=dt*1000;
}
function tickCustomWinCondition(dt){
  if(gameMode!=='custom'||!P.alive)return;
  if(customWinCondition==='killAll'){
    if(enemies.length===0) customLevelWin();
  } else if(customWinCondition==='reachFinish'){
    if(dist(P.x,P.y,customFinishX,customFinishY)<50) customLevelWin();
  } else if(customWinCondition==='survive'){
    customSurviveMs-=dt*1000;
    if(customSurviveMs<=0) customLevelWin();
  } else if(customWinCondition==='retrieve'){
    if(customItemHeld&&dist(P.x,P.y,customGoalX,customGoalY)<50) customLevelWin();
  } else if(customWinCondition==='collectAll'){
    if(customKeysTotal>0&&customKeysCollected>=customKeysTotal) customLevelWin();
  }
}
function tickCustomObjectivePickup(){
  if(gameMode!=='custom')return;
  for(const obj of customObjectives){
    if(obj.collected)continue;
    if(obj.type==='key'&&dist(P.x,P.y,obj.x,obj.y)<40){
      obj.collected=true;customKeysCollected++;
      spawnParts(obj.x,obj.y,'#ffdd00',_pCount(14),3,5,400);SFX.pickup();
      weaponFlash={prefix:'COLLECTED',name:`KEY ${customKeysCollected}/${customKeysTotal}`,ms:1800};
    }
    if(obj.type==='item'&&!customItemHeld&&dist(P.x,P.y,obj.x,obj.y)<40){
      obj.collected=true;customItemHeld=true;
      spawnParts(obj.x,obj.y,'#ff8800',_pCount(14),3,5,400);SFX.pickup();
      weaponFlash={prefix:'COLLECTED',name:'ITEM - RETURN TO GOAL',ms:2200};
    }
  }
}
function drawCustomObjectives(){
  if(gameMode!=='custom')return;
  const t=Date.now()/1000;
  for(const obj of customObjectives){
    const sx=obj.x-camX,sy=obj.y-camY;
    if(sx<-60||sx>canvas.width+60||sy<-60||sy>canvas.height+60)continue;
    if(obj.type==='finish'){
      const pulse=0.7+0.3*Math.sin(t*3);
      ctx.shadowBlur=24*pulse;ctx.shadowColor='#ffdd00';
      ctx.strokeStyle=`rgba(255,220,0,${0.88*pulse})`;ctx.lineWidth=3;
      ctx.beginPath();ctx.moveTo(sx,0);ctx.lineTo(sx,canvas.height);ctx.stroke();ctx.shadowBlur=0;
    } else if(obj.type==='key'&&!obj.collected){
      const pulse=0.6+0.4*Math.sin(t*4+obj.x);
      ctx.save();ctx.translate(sx,sy);ctx.rotate(t*2);
      ctx.shadowBlur=14*pulse;ctx.shadowColor='#ffdd00';
      ctx.font='bold 18px "Courier New"';ctx.fillStyle=`rgba(255,220,0,${0.7+0.3*pulse})`;
      ctx.textAlign='center';ctx.fillText('K',0,6);
      ctx.shadowBlur=0;ctx.restore();
    } else if(obj.type==='item'&&!customItemHeld){
      const pulse=0.6+0.4*Math.sin(t*3);
      ctx.save();ctx.translate(sx,sy);
      ctx.shadowBlur=16*pulse;ctx.shadowColor='#ff8800';
      ctx.beginPath();ctx.moveTo(0,-12);ctx.lineTo(10,0);ctx.lineTo(0,12);ctx.lineTo(-10,0);ctx.closePath();
      ctx.fillStyle=`rgba(255,136,0,${0.75+0.25*pulse})`;ctx.fill();
      ctx.strokeStyle='#ffcc44';ctx.lineWidth=2;ctx.stroke();
      ctx.shadowBlur=0;ctx.restore();
    } else if(obj.type==='goal'){
      const pulse=0.5+0.5*Math.sin(t*2);
      const r=40+6*pulse;
      ctx.beginPath();ctx.arc(sx,sy,r,0,Math.PI*2);
      ctx.strokeStyle=`rgba(0,255,136,${0.4+0.3*pulse})`;ctx.lineWidth=3;
      ctx.shadowBlur=18*pulse;ctx.shadowColor='#00ff88';ctx.stroke();ctx.shadowBlur=0;
      ctx.font='10px "Courier New"';ctx.fillStyle='rgba(0,255,136,0.7)';ctx.textAlign='center';
      ctx.fillText(customWinCondition==='retrieve'?'GOAL':'ZONE',sx,sy+r+14);
    }
  }
  // Objective HUD text
  ctx.textAlign='center';
  if(customWinCondition==='killAll'){
    ctx.font='bold 14px "Courier New"';ctx.fillStyle='#ff4444';ctx.shadowBlur=8;ctx.shadowColor='#ff2200';
    ctx.fillText(`ELIMINATE ALL HOSTILES  (${enemies.length} remaining)`,canvas.width/2,80);ctx.shadowBlur=0;
  }
  if(customWinCondition==='reachFinish'){
    ctx.font='bold 14px "Courier New"';ctx.fillStyle='#ffdd00';ctx.shadowBlur=8;ctx.shadowColor='#ffaa00';
    const dToFinish=Math.round(dist(P.x,P.y,customFinishX,customFinishY));
    ctx.fillText(`REACH THE FINISH LINE  (${dToFinish}m)`,canvas.width/2,80);ctx.shadowBlur=0;
  }
  if(customWinCondition==='survive'&&customSurviveMs>0){
    const sec=Math.ceil(customSurviveMs/1000);
    ctx.font='bold 24px "Courier New"';
    ctx.fillStyle=sec<=10?'#ff4400':'#00ccff';ctx.shadowBlur=14;ctx.shadowColor=sec<=10?'#ff2200':'#00aaff';
    ctx.fillText(`SURVIVE  ${sec}s`,canvas.width/2,80);ctx.shadowBlur=0;
  }
  if(customWinCondition==='collectAll'){
    ctx.font='bold 14px "Courier New"';
    ctx.fillStyle='#ffdd00';ctx.shadowBlur=8;ctx.shadowColor='#ffaa00';
    ctx.fillText(`KEYS  ${customKeysCollected}/${customKeysTotal}`,canvas.width/2,80);ctx.shadowBlur=0;
  }
  if(customWinCondition==='retrieve'){
    ctx.font='bold 14px "Courier New"';
    ctx.fillStyle=customItemHeld?'#00ff88':'#ff8800';ctx.shadowBlur=8;ctx.shadowColor=customItemHeld?'#00ff88':'#ff8800';
    ctx.fillText(customItemHeld?'ITEM HELD - REACH GOAL':'FIND THE ITEM',canvas.width/2,80);ctx.shadowBlur=0;
  }
  // Pack progress + level name
  if(customPack&&customPack.levels.length>1){
    ctx.font='11px "Courier New"';ctx.fillStyle='rgba(100,180,255,0.55)';
    const lvl=customPack.levels[customPack.currentIdx];
    ctx.fillText(`${customPack.packName} - ${lvl?lvl.name:''}  (${customPack.currentIdx+1}/${customPack.levels.length})`,canvas.width/2,58);
  }
  ctx.textAlign='left';
}

function drawCustomTransition(){
  if(customTransitionMs<=0)return;
  const alpha=Math.min(1,customTransitionMs/500);
  const cx=canvas.width/2,cy=canvas.height/2;
  ctx.fillStyle=`rgba(0,0,0,${0.5*alpha})`;
  ctx.fillRect(0,cy-60,canvas.width,120);
  ctx.textAlign='center';
  ctx.font='bold 42px "Courier New"';
  ctx.fillStyle=`rgba(0,255,136,${alpha})`;
  ctx.shadowBlur=30*alpha;ctx.shadowColor='#00ff88';
  ctx.fillText(customTransitionText,cx,cy+8);
  ctx.shadowBlur=0;
  ctx.font='14px "Courier New"';
  ctx.fillStyle=`rgba(100,200,255,${0.7*alpha})`;
  ctx.fillText('ADVANCING...',cx,cy+36);
  ctx.textAlign='left';
}
function drawCustomResult(){
  const W=canvas.width,H=canvas.height,cx=W/2;
  ctx.fillStyle='#060c18';ctx.fillRect(0,0,W,H);
  ctx.textAlign='center';
  ctx.font='bold 36px "Courier New"';ctx.fillStyle='#00ff88';ctx.shadowBlur=30;ctx.shadowColor='#00ff88';
  ctx.fillText('MISSION COMPLETE',cx,H*0.25);ctx.shadowBlur=0;
  if(customPack){
    ctx.font='16px "Courier New"';ctx.fillStyle='rgba(100,200,255,0.8)';
    ctx.fillText(customPack.packName||'CUSTOM PACK',cx,H*0.25+40);
    ctx.font='14px "Courier New"';ctx.fillStyle='rgba(150,180,220,0.7)';
    const cleared=customPack.currentIdx+1;
    ctx.fillText(`${cleared} LEVEL${cleared>1?'S':''} CLEARED`,cx,H*0.25+64);
  }
  const elapsed=Math.floor((Date.now()-gameStartTime)/1000);
  const mins=Math.floor(elapsed/60),secs=elapsed%60;
  ctx.font='14px "Courier New"';ctx.fillStyle='rgba(150,180,220,0.7)';
  ctx.fillText(`SCORE  ${String(score).padStart(8,'0')}   TIME  ${mins}:${String(secs).padStart(2,'0')}`,cx,H*0.25+96);
  const bw=240,bh=46,bx=cx-bw/2,by=H*0.6;
  const bhov=mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh;
  ctx.shadowBlur=bhov?24:10;ctx.shadowColor='#00ff88';
  ctx.fillStyle=bhov?'#00ff88':'rgba(0,0,0,0.7)';
  roundRect(ctx,bx,by,bw,bh,8);ctx.fill();
  ctx.strokeStyle='#00ff88';ctx.lineWidth=2;
  roundRect(ctx,bx,by,bw,bh,8);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 14px "Courier New"';ctx.fillStyle=bhov?'#000':'#00ff88';
  ctx.fillText('BACK TO MENU',cx,by+bh/2+5);
  ctx.textAlign='left';
}

function drawCustomSelect(){
  const W=canvas.width,H=canvas.height,cx=W/2;
  ctx.fillStyle='#060c18';ctx.fillRect(0,0,W,H);
  ctx.textAlign='center';
  ctx.font='bold 28px "Courier New"';ctx.fillStyle='#00ccff';ctx.shadowBlur=20;ctx.shadowColor='#00aaff';
  ctx.fillText('CUSTOM LEVELS',cx,52);ctx.shadowBlur=0;
  const packs=_loadCustomLevels();
  // CREATE NEW button
  const newW=200,newH=40,newX=cx-newW/2,newY=82;
  const nhov=mouse.x>newX&&mouse.x<newX+newW&&mouse.y>newY&&mouse.y<newY+newH;
  ctx.shadowBlur=nhov?18:6;ctx.shadowColor='#00ff88';
  ctx.fillStyle=nhov?'#00ff88':'rgba(0,40,20,0.6)';
  roundRect(ctx,newX,newY,newW,newH,6);ctx.fill();
  ctx.strokeStyle=nhov?'#00ff88':'rgba(0,180,100,0.5)';ctx.lineWidth=1.5;
  roundRect(ctx,newX,newY,newW,newH,6);ctx.stroke();ctx.shadowBlur=0;
  ctx.textAlign='center';ctx.font='bold 13px "Courier New"';ctx.fillStyle=nhov?'#000':'#00ff88';
  ctx.fillText('+ CREATE NEW',cx,newY+newH/2+5);

  if(packs.length===0){
    ctx.font='14px "Courier New"';ctx.fillStyle='rgba(100,140,180,0.6)';
    ctx.fillText('No custom levels yet',cx,H/2);
  } else {
    const cardW=Math.min(500,W*0.8),cardH=48,subH=36,cardGap=6;
    let cy=140;
    for(let i=0;i<packs.length;i++){
      const pk=packs[i];
      if(cy>H-100)break;
      const expanded=customSelectExpanded===i;
      const phov=mouse.x>cx-cardW/2&&mouse.x<cx+cardW/2&&mouse.y>cy&&mouse.y<cy+cardH;
      // Pack header
      ctx.fillStyle=expanded?'rgba(0,50,100,0.7)':phov?'rgba(0,40,80,0.6)':'rgba(0,25,50,0.5)';
      roundRect(ctx,cx-cardW/2,cy,cardW,cardH,6);ctx.fill();
      ctx.strokeStyle=expanded?'#00ccff':phov?'rgba(0,160,220,0.5)':'rgba(0,100,180,0.3)';ctx.lineWidth=expanded?2:1;
      roundRect(ctx,cx-cardW/2,cy,cardW,cardH,6);ctx.stroke();
      ctx.textAlign='left';ctx.font='bold 13px "Courier New"';ctx.fillStyle=expanded?'#00ccff':'rgba(180,220,255,0.9)';
      ctx.fillText((expanded?'v ':'> ')+(pk.packName||'Unnamed Pack'),cx-cardW/2+14,cy+20);
      ctx.font='10px "Courier New"';ctx.fillStyle='rgba(100,160,220,0.6)';
      ctx.fillText(`${pk.levels?pk.levels.length:0} levels  |  ${pk.author||'Unknown'}`,cx-cardW/2+14,cy+36);
      // Pack buttons: CLONE, DEL
      const pbtnW=48,pbtnH=24,pbtnY=cy+(cardH-pbtnH)/2;
      const delX=cx+cardW/2-pbtnW-8,cloneX=delX-pbtnW-6;
      // DEL
      const dHov=mouse.x>delX&&mouse.x<delX+pbtnW&&mouse.y>pbtnY&&mouse.y<pbtnY+pbtnH;
      ctx.fillStyle=dHov?'rgba(180,30,10,0.8)':'rgba(80,20,10,0.4)';
      roundRect(ctx,delX,pbtnY,pbtnW,pbtnH,3);ctx.fill();
      ctx.textAlign='center';ctx.font='bold 9px "Courier New"';ctx.fillStyle=dHov?'#ffccaa':'rgba(200,80,50,0.7)';
      ctx.fillText('DEL',delX+pbtnW/2,pbtnY+pbtnH/2+3);
      // CLONE
      const cHov=mouse.x>cloneX&&mouse.x<cloneX+pbtnW&&mouse.y>pbtnY&&mouse.y<pbtnY+pbtnH;
      ctx.fillStyle=cHov?'rgba(0,100,60,0.7)':'rgba(0,50,30,0.4)';
      roundRect(ctx,cloneX,pbtnY,pbtnW,pbtnH,3);ctx.fill();
      ctx.fillStyle=cHov?'#00ff88':'rgba(0,180,100,0.6)';
      ctx.fillText('CLONE',cloneX+pbtnW/2,pbtnY+pbtnH/2+3);
      cy+=cardH+cardGap;
      // Expanded: show levels
      if(expanded&&pk.levels){
        for(let j=0;j<pk.levels.length;j++){
          const lv=pk.levels[j];
          if(cy>H-100)break;
          const sel=customSelectSelectedLevel===j;
          const lhov=mouse.x>cx-cardW/2+20&&mouse.x<cx+cardW/2&&mouse.y>cy&&mouse.y<cy+subH;
          ctx.fillStyle=sel?'rgba(0,60,40,0.6)':lhov?'rgba(0,30,60,0.5)':'rgba(0,15,35,0.4)';
          roundRect(ctx,cx-cardW/2+20,cy,cardW-20,subH,4);ctx.fill();
          if(sel){ctx.strokeStyle='#00ff88';ctx.lineWidth=1;roundRect(ctx,cx-cardW/2+20,cy,cardW-20,subH,4);ctx.stroke();}
          ctx.textAlign='left';ctx.font='11px "Courier New"';ctx.fillStyle=sel?'#00ff88':'rgba(160,200,240,0.8)';
          ctx.fillText(lv.name||'Untitled',cx-cardW/2+36,cy+14);
          ctx.font='9px "Courier New"';ctx.fillStyle='rgba(100,150,200,0.5)';
          ctx.fillText(`${lv.winCondition||'killAll'}  |  ${lv.worldW||2600}x${lv.worldH||1700}`,cx-cardW/2+36,cy+28);
          // Level buttons: PLAY, EDIT, COPY, DEL
          const lbW=38,lbH=22,lbY=cy+(subH-lbH)/2;
          const lDelX=cx+cardW/2-lbW-8,lCopyX=lDelX-lbW-4,lEditX=lCopyX-lbW-4,lPlayX=lEditX-lbW-4;
          const btns=[
            {x:lPlayX,label:'PLAY',col:'#00ff88',bg:'rgba(0,80,40,0.6)'},
            {x:lEditX,label:'EDIT',col:'#00ccff',bg:'rgba(0,50,100,0.5)'},
            {x:lCopyX,label:'COPY',col:'#ffaa00',bg:'rgba(80,50,0,0.4)'},
            {x:lDelX,label:'DEL',col:'#ff5533',bg:'rgba(80,20,10,0.4)'},
          ];
          for(const btn of btns){
            const bh2=mouse.x>btn.x&&mouse.x<btn.x+lbW&&mouse.y>lbY&&mouse.y<lbY+lbH;
            ctx.fillStyle=bh2?btn.col+'33':btn.bg;
            roundRect(ctx,btn.x,lbY,lbW,lbH,3);ctx.fill();
            ctx.textAlign='center';ctx.font='bold 8px "Courier New"';ctx.fillStyle=bh2?btn.col:btn.col+'99';
            ctx.fillText(btn.label,btn.x+lbW/2,lbY+lbH/2+3);
          }
          cy+=subH+4;
        }
        // Leaderboard + Awards stubs (when a level is selected)
        if(customSelectSelectedLevel>=0&&customSelectSelectedLevel<pk.levels.length){
          cy+=8;
          ctx.strokeStyle='rgba(0,100,180,0.3)';ctx.lineWidth=1;
          roundRect(ctx,cx-cardW/2+20,cy,cardW-20,50,4);ctx.stroke();
          ctx.textAlign='left';ctx.font='bold 10px "Courier New"';ctx.fillStyle='rgba(100,180,255,0.6)';
          ctx.fillText('LEADERBOARD',cx-cardW/2+32,cy+16);
          ctx.font='9px "Courier New"';ctx.fillStyle='rgba(80,120,160,0.4)';
          ctx.fillText('Multiplayer coming soon',cx-cardW/2+32,cy+34);
          cy+=58;
          roundRect(ctx,cx-cardW/2+20,cy,cardW-20,50,4);ctx.stroke();
          ctx.textAlign='left';ctx.font='bold 10px "Courier New"';ctx.fillStyle='rgba(100,180,255,0.6)';
          ctx.fillText('AWARDS',cx-cardW/2+32,cy+16);
          ctx.font='9px "Courier New"';ctx.fillStyle='rgba(80,120,160,0.4)';
          ctx.fillText('Multiplayer coming soon',cx-cardW/2+32,cy+34);
          cy+=58;
        }
      }
    }
  }
  // Back button
  ctx.textAlign='center';
  const bbw=160,bbh=40,bbx=cx-bbw/2,bby=H-70;
  const bhov=mouse.x>bbx&&mouse.x<bbx+bbw&&mouse.y>bby&&mouse.y<bby+bbh;
  ctx.shadowBlur=bhov?18:6;ctx.shadowColor='#00ccff';
  ctx.fillStyle=bhov?'rgba(0,140,200,0.85)':'rgba(0,0,0,0.65)';
  roundRect(ctx,bbx,bby,bbw,bbh,6);ctx.fill();
  ctx.strokeStyle=bhov?'#00eeff':'rgba(0,140,220,0.6)';ctx.lineWidth=1.5;
  roundRect(ctx,bbx,bby,bbw,bbh,6);ctx.stroke();ctx.shadowBlur=0;
  ctx.font='bold 12px "Courier New"';ctx.fillStyle=bhov?'#000':'rgba(100,200,255,0.9)';
  ctx.fillText('BACK',cx,bby+bbh/2+4);
  ctx.textAlign='left';
}

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

function _getEditorCategories(){
  return [
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
}
function _findToolDef(toolId){
  for(const cat of _getEditorCategories()) for(const item of cat.items) if(item.tool===toolId) return item;
  return null;
}

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
  editorLevel._editPackIdx=packIdx;
  editorLevel._editLevelIdx=levelIdx;
}

function _drawEditorSidebar(sideW,H){
  ctx.fillStyle='rgba(4,10,26,0.95)';ctx.fillRect(0,0,sideW,H);
  ctx.strokeStyle='rgba(0,100,180,0.3)';ctx.lineWidth=1;
  ctx.beginPath();ctx.moveTo(sideW,0);ctx.lineTo(sideW,H);ctx.stroke();
  ctx.textAlign='left';ctx.font='bold 11px "Courier New"';
  const cats=_getEditorCategories();
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

function _editorSave(){
  if(editorPlacedItems.length===0){weaponFlash={name:'Place at least one item first',ms:2000};return;}
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
  editorPack.levels.push(lv);
  editorDirty=false;
  gameState='levelSavePrompt';SFX.confirm();
}

function drawLevelSavePrompt(){
  const W=canvas.width,H=canvas.height,cx=W/2;
  ctx.fillStyle='#060c18';ctx.fillRect(0,0,W,H);
  ctx.textAlign='center';
  ctx.font='bold 24px "Courier New"';ctx.fillStyle='#00ff88';ctx.shadowBlur=20;ctx.shadowColor='#00ff88';
  ctx.fillText('LEVEL SAVED',cx,H*0.2);ctx.shadowBlur=0;
  ctx.font='14px "Courier New"';ctx.fillStyle='rgba(100,200,255,0.8)';
  ctx.fillText(`"${editorLevel?editorLevel.name:''}" added to "${editorPack?editorPack.packName:''}"`,cx,H*0.2+34);
  ctx.font='12px "Courier New"';ctx.fillStyle='rgba(150,180,220,0.6)';
  if(editorLevel){
    const ec=editorPlacedItems.filter(i=>i.cat==='enemy').length;
    ctx.fillText(`${editorLevel.worldW}x${editorLevel.worldH}  |  ${ec} enemies  |  ${editorLevel.winCondition}`,cx,H*0.2+60);
  }
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

function startBattle(){
  _hideAllAds();
  gameMode='battle';
  ttLevel=1; nukes=[];
  WORLD_W=2600; WORLD_H=1700;
  score=0; wave=1; bossWarning=0; empFlash=0; weaponFlash={name:'',ms:0}; lastHullBeepMs=0;
  leechFlash={active:false,tx:0,ty:0,ms:0};shockwaveFlash={ms:0};
  harbingerRef=null;
  portalActive=false; portalPositions=[];
  particles.length=0; pickups.length=0; pBullets.length=0; eBullets.length=0; mines.length=0; seekers.length=0; boomerangs.length=0; fractals.length=0; hazards.length=0;
  miniMe.active=false; miniMe.lost=false; miniMe.hp=MM_HP; miniMe.iframes=0;
  hangarScroll=0; resetPlayer(); camX=P.x-canvas.width/2; camY=P.y-canvas.height/2;
  spawnWave(1); gameStartTime=Date.now(); gameState='playing'; _snapMouseToPlayer();
}

function startTimeTrial(){
  _hideAllAds();
  gameMode='timetrial';
  ttLevel=1; nukes=[];
  WORLD_W=TT_WORLD_W; WORLD_H=canvas.height;
  score=0; wave=1; bossWarning=0; empFlash=0; weaponFlash={name:'',ms:0}; lastHullBeepMs=0;
  leechFlash={active:false,tx:0,ty:0,ms:0};shockwaveFlash={ms:0};
  harbingerRef=null;
  portalActive=false; portalPositions=[];
  particles.length=0; pickups.length=0; pBullets.length=0; eBullets.length=0; mines.length=0; seekers.length=0; boomerangs.length=0; fractals.length=0; hazards.length=0;
  miniMe.active=false; miniMe.lost=false; miniMe.hp=MM_HP; miniMe.iframes=0;
  resetPlayer();
  P.x=P.size+80; P.y=WORLD_H/2; camX=0; camY=0;
  generateTTObstacles();
  spawnHiddenPickups(); spawnHiddenPickups(true);
  spawnTTEnemies();
  ttTotalEnemies=enemies.length;
  if(P.unlockedW.has(WEAPONS.findIndex(w=>w.id==='mine'))) P.mineStock=enemies.length;
  if(P.unlockedW.has(WEAPONS.findIndex(w=>w.id==='seekr'))){
    const half=Math.ceil(enemies.length/2);
    P.seekStock=half+Math.floor(Math.random()*(enemies.length*3-half+1));
  }
  ttStartTime=performance.now(); ttElapsed=0; ttFinished=false; ttFinalScore=0;
  gameStartTime=Date.now(); gameState='playing'; _snapMouseToPlayer();
}

function startGame(){
  _loadHangar();
  lastTime=performance.now();
  if(gameMode==='timetrial') startTimeTrial(); // always level 1 from this path
  else startBattle();
}

// ─── CLICK / TAP HANDLER ─────────────────────────────────────────
// Shared logic for both mouse-click and touch-tap (mouse.x/y set before calling)
function _doClick(){
  if(editorSliderDrag){editorSliderDrag=null;return;}
  // Portal: click/tap resolves to nearest portal or just resolves
  if(portalActive){
    // Check if click lands on a portal
    let hit=-1;
    for(let i=0;i<portalPositions.length;i++){
      const sx=portalPositions[i].x-camX, sy=portalPositions[i].y-camY;
      if(dist(mouse.x,mouse.y,sx,sy)<44){hit=i;break;}
    }
    if(hit>=0){
      portalSelected=hit;
      const prev=portalPositions[portalSelected];
      camX=clamp(prev.x-canvas.width/2,0,Math.max(0,WORLD_W-canvas.width));
      camY=(gameMode==='timetrial'&&(ttLevel===1||ttLevel===3))?0:clamp(prev.y-canvas.height/2,0,Math.max(0,WORLD_H-canvas.height));
    }
    _resolvePortal();
    return;
  }
  if(gameState==='intro'){
    const cx=canvas.width/2,bw=200,bh=36,by=canvas.height-80;
    const bx=introShowSkip?cx+8:cx-bw/2;
    const _sPad=Math.max(14,canvas.width*0.02);
    const _sW=Math.max(80,canvas.width*0.075),_sH=Math.max(28,canvas.height*0.042);
    const _sX=canvas.width-_sPad-_sW,_sY=_sPad;
    if(mouse.x>=_sX&&mouse.x<=_sX+_sW&&mouse.y>=_sY&&mouse.y<=_sY+_sH){initAudio();Music.toggleMute();return;}
    if(introShowSkip){
      const sw=140,sx=cx-8-sw;
      if(mouse.x>sx&&mouse.x<sx+sw&&mouse.y>by&&mouse.y<by+bh){_iSkipIntro();return;}
    }
    if(introMs()>=INTRO_BTN_DELAY&&mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh){advanceIntro();return;}
    return;
  }
  if(gameState==='adBreak'){
    const elapsed2=Date.now()-adBreakStart;
    if(elapsed2<5000) return; // locked for first 5s
    const cx=canvas.width/2, bw=240, bh=42, bx=cx-bw/2, by=canvas.height-76;
    if(mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh){leaveAdBreak();return;}
    return;
  }
  // Pause + Mute buttons (visible during playing)
  if(gameState==='playing'){
    const{x,y,w,h}=pauseBtnRect();
    if(mouse.x>x&&mouse.x<x+w&&mouse.y>y&&mouse.y<y+h){
      mouse.down=false; // prevent shot on same frame
      gameState='paused'; lastTime=performance.now(); screenLockMs=2000; return;
    }
    const mb=muteBtnRect();
    if(mouse.x>mb.x&&mouse.x<mb.x+mb.w&&mouse.y>mb.y&&mouse.y<mb.y+mb.h){
      mouse.down=false; // prevent shot on same frame
      Music.toggleMute(); return;
    }
    // Weapon bar slot tap (touch & mouse)
    const slotW=IS_TOUCH?24:38, slotH=IS_TOUCH?24:38, slotGap=IS_TOUCH?3:5, total=P.loadout.length;
    const barX=(canvas.width-total*(slotW+slotGap)+slotGap)/2, barY=weaponBarY();
    for(let i=0;i<P.loadout.length;i++){
      const sx=barX+i*(slotW+slotGap);
      if(mouse.x>sx&&mouse.x<sx+slotW&&mouse.y>barY&&mouse.y<barY+slotH){P.weaponIdx=P.loadout[i];mouse.down=false;SFX.select();return;}
    }
  }
  if(gameState==='loadoutEdit'){
    const cx=canvas.width/2,W=canvas.width,H=canvas.height;
    const isHangar=loadoutEditFrom==='hangar';
    const c=isHangar?CRAFTS[hangarCraft]:CRAFTS[P.craftIdx];
    const lo=isHangar?hangarLoadout:P.loadout;
    const cardW=140,cardH=38,cardGap=8;
    const loadedTotalW=c.maxSlots*(cardW+cardGap)-cardGap;
    const loadedStartX=cx-loadedTotalW/2;
    const loadedY=114;
    for(let i=0;i<lo.length;i++){
      const x=loadedStartX+i*(cardW+cardGap),y=loadedY;
      if(mouse.x>x&&mouse.x<x+cardW&&mouse.y>y&&mouse.y<y+cardH){
        if(lo.length>1){
          const removed=lo.splice(i,1)[0];
          if(!isHangar&&P.weaponIdx===removed) P.weaponIdx=lo[0];
          SFX.select();
        }
        return;
      }
    }
    const available=isHangar
      ?WEAPONS.map((_,i)=>i).filter(i=>!lo.includes(i))
      :[...P.unlockedW].filter(i=>!lo.includes(i)).sort((a,b)=>a-b);
    const availY=loadedY+cardH+40;
    const availCols=Math.max(1,Math.min(available.length,Math.floor((W-40)/(cardW+cardGap))));
    const availTotalW=Math.min(available.length,availCols)*(cardW+cardGap)-cardGap;
    const availStartX=cx-availTotalW/2;
    for(let i=0;i<available.length;i++){
      const col=i%availCols,row=Math.floor(i/availCols);
      const x=availStartX+col*(cardW+cardGap),y=availY+row*(cardH+cardGap);
      if(mouse.x>x&&mouse.x<x+cardW&&mouse.y>y&&mouse.y<y+cardH){
        if(lo.length<c.maxSlots){
          lo.push(available[i]);
          SFX.select();
        }
        return;
      }
    }
    const dbw=200,dbh=44,dbx=cx-dbw/2,dby=H-80;
    if(mouse.x>dbx&&mouse.x<dbx+dbw&&mouse.y>dby&&mouse.y<dby+dbh){
      if(isHangar){
        _saveLoadout(c.id,hangarLoadout);
        gameState='hangar';
      } else {
        _saveLoadout(c.id,P.loadout);
        gameState='paused';
      }
      SFX.confirm();return;
    }
    return;
  }
  // Resume + Abort buttons on pause screen
  if(gameState==='paused'){
    if(screenLockMs<=0){
      const cx=canvas.width/2,bw=290,bh=50,bx=cx-bw/2,by=canvas.height/2+48;
      if(mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh){
        gameState='playing'; lastTime=performance.now(); return;
      }
      // Modify Weapons button
      const mw2=290,mh2=44,mx2=cx-mw2/2,my2=by+bh+40;
      if(mouse.x>mx2&&mouse.x<mx2+mw2&&mouse.y>my2&&mouse.y<my2+mh2){
        loadoutEditFrom='pause';gameState='loadoutEdit';SFX.select();return;
      }
      // Abort button
      const aw=290,ah=44,ax=cx-aw/2,ay=my2+mh2+30;
      if(mouse.x>ax&&mouse.x<ax+aw&&mouse.y>ay&&mouse.y<ay+ah){
        gameState='start'; releaseLock();
        WORLD_W=2600;WORLD_H=1700;ttLevel=1;nukes=[];jrCaptives=[];jrCarrying=-1;tngPads=[];tngSeq=1;tngOnPad=-1;tngHoldMs=0;
        if(CRAFTS[P.craftIdx]) P.spd=CRAFTS[P.craftIdx].spd;
        particles.length=0;pickups.length=0;pBullets.length=0;eBullets.length=0;
        mines.length=0;seekers.length=0;rockets.length=0;boomerangs.length=0;fractals.length=0;hazards.length=0;grenades.length=0;gravityWells.length=0;faradayCages.length=0;enemies.length=0;
        miniMe.active=false;miniMe.lost=false;
        SFX.select(); return;
      }
    }
    return; // eat all other clicks while paused
  }

  if(gameState==='levelSavePrompt'){
    const cx=canvas.width/2,H=canvas.height;
    const btnW=260,btnH=46,gap=16;
    const addY=H*0.5;
    if(mouse.x>cx-btnW/2&&mouse.x<cx+btnW/2&&mouse.y>addY&&mouse.y<addY+btnH){
      editorLevelName='Untitled Level';editorWorldW=2600;editorWorldH=1700;
      editorWinCondition='killAll';editorWinSeconds=60;
      gameState='levelSetup';SFX.select();return;
    }
    const doneY=addY+btnH+gap;
    if(mouse.x>cx-btnW/2&&mouse.x<cx+btnW/2&&mouse.y>doneY&&mouse.y<doneY+btnH){
      const packs=_loadCustomLevels();
      packs.push(editorPack);
      _saveCustomLevels(packs);
      editorPack=null;editorLevel=null;editorPlacedItems=[];
      gameState='customSelect';SFX.confirm();return;
    }
    return;
  }
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
  if(gameState==='customSelect'){
    const cx=canvas.width/2,W=canvas.width,H=canvas.height;
    // CREATE NEW
    const newW=200,newH=40,newX=cx-newW/2,newY=82;
    if(mouse.x>newX&&mouse.x<newX+newW&&mouse.y>newY&&mouse.y<newY+newH){
      editorPack=null;editorLevelName='Untitled Level';editorWorldW=2600;editorWorldH=1700;
      editorWinCondition='killAll';editorWinSeconds=60;
      gameState='levelSetup';SFX.select();return;
    }
    const packs=_loadCustomLevels();
    const cardW=Math.min(500,W*0.8),cardH=48,subH=36,cardGap=6;
    let cy=140;
    for(let i=0;i<packs.length;i++){
      const pk=packs[i];
      if(cy>H-100)break;
      const expanded=customSelectExpanded===i;
      // Pack-level buttons
      const pbtnW=48,pbtnH=24,pbtnY=cy+(cardH-pbtnH)/2;
      const delX=cx+cardW/2-pbtnW-8,cloneX=delX-pbtnW-6;
      if(mouse.x>delX&&mouse.x<delX+pbtnW&&mouse.y>pbtnY&&mouse.y<pbtnY+pbtnH){
        packs.splice(i,1);_saveCustomLevels(packs);
        if(customSelectExpanded===i){customSelectExpanded=-1;customSelectSelectedLevel=-1;}
        else if(customSelectExpanded>i) customSelectExpanded--;
        SFX.select();return;
      }
      if(mouse.x>cloneX&&mouse.x<cloneX+pbtnW&&mouse.y>pbtnY&&mouse.y<pbtnY+pbtnH){
        const clone=JSON.parse(JSON.stringify(pk));
        clone.packName=(pk.packName||'Pack')+' (Copy)';clone.created=Date.now();
        packs.push(clone);_saveCustomLevels(packs);SFX.select();return;
      }
      // Pack header click (expand/collapse)
      if(mouse.x>cx-cardW/2&&mouse.x<cx+cardW/2-pbtnW*2-20&&mouse.y>cy&&mouse.y<cy+cardH){
        customSelectExpanded=expanded?-1:i;customSelectSelectedLevel=-1;SFX.select();return;
      }
      cy+=cardH+cardGap;
      if(expanded&&pk.levels){
        for(let j=0;j<pk.levels.length;j++){
          if(cy>H-100)break;
          const lbW=38,lbH=22,lbY=cy+(subH-lbH)/2;
          const lDelX=cx+cardW/2-lbW-8,lCopyX=lDelX-lbW-4,lEditX=lCopyX-lbW-4,lPlayX=lEditX-lbW-4;
          // PLAY
          if(mouse.x>lPlayX&&mouse.x<lPlayX+lbW&&mouse.y>lbY&&mouse.y<lbY+lbH){
            customPack={packName:pk.packName,levels:[pk.levels[j]],currentIdx:0};
            loadCustomLevel(pk.levels[j]);SFX.confirm();return;
          }
          // EDIT
          if(mouse.x>lEditX&&mouse.x<lEditX+lbW&&mouse.y>lbY&&mouse.y<lbY+lbH){
            editorPack={packName:pk.packName,author:pk.author,created:pk.created,levels:[]};
            _loadLevelIntoEditor(pk.levels[j],i,j);
            gameState='levelEditor';SFX.select();return;
          }
          // COPY
          if(mouse.x>lCopyX&&mouse.x<lCopyX+lbW&&mouse.y>lbY&&mouse.y<lbY+lbH){
            const lvCopy=JSON.parse(JSON.stringify(pk.levels[j]));
            const newPack={packName:(lvCopy.name||'Level')+' (Copy)',author:'Player',created:Date.now(),levels:[lvCopy]};
            packs.push(newPack);_saveCustomLevels(packs);SFX.select();return;
          }
          // DEL level
          if(mouse.x>lDelX&&mouse.x<lDelX+lbW&&mouse.y>lbY&&mouse.y<lbY+lbH){
            pk.levels.splice(j,1);
            if(pk.levels.length===0){packs.splice(i,1);customSelectExpanded=-1;}
            _saveCustomLevels(packs);customSelectSelectedLevel=-1;SFX.select();return;
          }
          // Level row click (select)
          if(mouse.x>cx-cardW/2+20&&mouse.x<cx+cardW/2-lbW*4-20&&mouse.y>cy&&mouse.y<cy+subH){
            customSelectSelectedLevel=customSelectSelectedLevel===j?-1:j;SFX.select();return;
          }
          cy+=subH+4;
        }
      }
    }
    // Back
    const bbw=160,bbh=40,bbx=cx-bbw/2,bby=H-70;
    if(mouse.x>bbx&&mouse.x<bbx+bbw&&mouse.y>bby&&mouse.y<bby+bbh){
      gameState='start';customSelectExpanded=-1;customSelectSelectedLevel=-1;SFX.select();return;
    }
    return;
  }
  if(gameState==='customResult'){
    const cx=canvas.width/2,bw=240,bh=46,bx=cx-bw/2,by=canvas.height*0.6;
    if(mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh){
      gameState='customSelect';SFX.select();return;
    }
    return;
  }

  if(gameState==='start'){
    // Sound toggle button
    const _stPad2=Math.max(14,canvas.width*0.02);
    const _stW2=Math.max(80,canvas.width*0.075),_stH2=Math.max(28,canvas.height*0.042);
    const _stX2=canvas.width-_stPad2-_stW2,_stY2=_stPad2;
    if(mouse.x>=_stX2&&mouse.x<=_stX2+_stW2&&mouse.y>=_stY2&&mouse.y<=_stY2+_stH2){
      initAudio();Music.toggleMute();return;
    }
    const rects=getMenuRects();
    for(let i=0;i<rects.length;i++){
      const {x,y,w,h,item}=rects[i];
      if(mouse.x>=x&&mouse.x<=x+w&&mouse.y>=y&&mouse.y<=y+h&&!item.dim){
        if(item.label==='Battle Waves'){ activeBriefing='brief_battle'; gameState='briefing'; SFX.select(); }
        if(item.label==='Time Trials'){  gameMode='timetrial'; gameState='ttLevelSelect'; SFX.select(); }
        if(item.label==='Combat Training'){ activeBriefing='brief_ct'; gameState='briefing'; SFX.select(); }
        if(item.label==='Aircraft Hangar'){ hangarCraft=selectedCraft; hangarColor=selectedColor; hangarScroll=Math.max(0,Math.min(hangarCraft,CRAFTS.length-HANGAR_VISIBLE)); gameState='hangar'; SFX.select(); }
        if(item.label==='Hall of Fame'){ hofTab=0; gameState='hallOfFame'; SFX.select(); }
        if(item.label==='Level Designer'){ gameState='customSelect'; SFX.select(); }
        if(item.label==='Setup'){ gameState='setup'; SFX.select(); }
        return;
      }
    }
    return;
  }
  if(gameState==='briefing'){
    const b=BRIEFINGS[activeBriefing];
    if(!b){gameState='start';return;}
    const W=canvas.width,H=canvas.height,cx=W/2;
    const btnW=Math.min(220,W*0.28),btnH=46;
    const btnY=H-btnH-Math.max(28,H*0.04);
    const backX=Math.max(20,W*0.03), launchX=W-Math.max(20,W*0.03)-btnW;
    if(mouse.x>launchX&&mouse.x<launchX+btnW&&mouse.y>btnY&&mouse.y<btnY+btnH){
      SFX.confirm(); b.launchFn(); return;
    }
    if(mouse.x>backX&&mouse.x<backX+btnW&&mouse.y>btnY&&mouse.y<btnY+btnH){
      if(['brief_tt1','brief_tt2','brief_tt3','brief_tt4','brief_tt5'].includes(activeBriefing)){
        gameState='ttLevelSelect';
      } else {
        gameState='start';
      }
      SFX.select(); return;
    }
    return;
  }
  if(gameState==='gameover'){
    const cx=canvas.width/2,bw=220,bh=44,bx=cx-bw/2,by=canvas.height/2+112;
    if(mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh){
      if(gameMode==='custom'&&customPack){loadCustomLevel(customPack.levels[customPack.currentIdx]);return;}
      _returnToStart(); return;
    }
    return;
  }
  if(gameState==='timeTrialResult'||gameState==='victory'){
    const cx=canvas.width/2,cy=canvas.height/2,bw=260,bh=46,bx=cx-bw/2,by=cy+104;
    if(mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh){
      WORLD_W=2600;WORLD_H=1700;ttLevel=1;nukes=[];jrCaptives=[];jrCarrying=-1;tngPads=[];tngSeq=1;tngOnPad=-1;tngHoldMs=0;gameMode='battle';gameState='start';SFX.select();return;
    }
    return;
  }
  if(gameState==='ctResult'){
    const cx=canvas.width/2,cy=canvas.height/2,bw=240,bh=44,bx=cx-bw/2,by=cy+74;
    if(mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh){
      WORLD_W=2600;WORLD_H=1700;ttLevel=1;nukes=[];jrCaptives=[];jrCarrying=-1;tngPads=[];tngSeq=1;tngOnPad=-1;tngHoldMs=0;gameMode='battle';gameState='start';SFX.select();return;
    }
    return;
  }
  if(gameState==='hallOfFame'){
    const W=canvas.width,cx=W/2;
    const tabW=Math.min(200,W*0.28),tabH=36,tabGap=12,tabY=68;
    const tab1X=cx-tabW-tabGap/2, tab2X=cx+tabGap/2;
    if(mouse.x>tab1X&&mouse.x<tab1X+tabW&&mouse.y>tabY&&mouse.y<tabY+tabH){hofTab=0;SFX.select();return;}
    if(mouse.x>tab2X&&mouse.x<tab2X+tabW&&mouse.y>tabY&&mouse.y<tabY+tabH){hofTab=1;SFX.select();return;}
    const bw=160,bh=38,bx=Math.max(20,canvas.width*0.03),by=canvas.height-50;
    if(mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh){gameState='start';SFX.select();return;}
    return;
  }

  if(gameState==='setup'){
    const L=_getSetupLayout(canvas.width,canvas.height);
    for(const b of _particleBtnRects(L)){
      if(mouse.x>=b.x&&mouse.x<=b.x+L.tog3W&&mouse.y>=b.y&&mouse.y<=b.y+L.togH){
        settings.particles=b.val;_saveSettings();SFX.select();return;
      }
    }
    for(const b of _shakeBtnRects(L)){
      if(mouse.x>=b.x&&mouse.x<=b.x+L.tog2W&&mouse.y>=b.y&&mouse.y<=b.y+L.togH){
        settings.screenShake=b.val;_saveSettings();SFX.select();return;
      }
    }
    const hx=L.cx-L.hofBtnW/2;
    if(mouse.x>=hx&&mouse.x<=hx+L.hofBtnW&&mouse.y>=L.hofBtnY&&mouse.y<=L.hofBtnY+L.hofBtnH){
      if(hofClearStep===0){hofClearStep=1;hofClearResetAt=Date.now()+3000;SFX.select();}
      else{try{localStorage.removeItem(HOF_KEY);}catch(e){}hofClearStep=0;hofClearFlashMs=2000;SFX.confirm();}
      return;
    }
    const{x,y,w,h}=L.backBtn;
    if(mouse.x>=x&&mouse.x<=x+w&&mouse.y>=y&&mouse.y<=y+h){gameState='start';SFX.select();return;}
    return;
  }

  if(gameState==='ttLevelSelect'){
    const W=canvas.width,H=canvas.height,cx=W/2;
    const headerH=88,backH=52,padV=12;
    const availH=H-headerH-backH-padV*3;
    const rowGap=Math.max(10,Math.min(20,availH*0.04));
    const cardH=Math.max(100,Math.min(240,Math.floor((availH-rowGap)/2)));
    const cardW=Math.max(140,Math.min(240,Math.floor((W-80)/3)-12));
    const gap=Math.max(8,Math.min(20,Math.floor((W-cardW*3-80)/2)));
    const totalW=cardW*3+gap*2;
    const row1Y=headerH;
    const c1X=cx-totalW/2,c2X=c1X+cardW+gap,c3X=c2X+cardW+gap;
    if(mouse.x>c1X&&mouse.x<c1X+cardW&&mouse.y>row1Y&&mouse.y<row1Y+cardH){
      activeBriefing='brief_tt1'; gameState='briefing'; SFX.select(); return;
    }
    if(mouse.x>c2X&&mouse.x<c2X+cardW&&mouse.y>row1Y&&mouse.y<row1Y+cardH){
      activeBriefing='brief_tt2'; gameState='briefing'; SFX.select(); return;
    }
    if(mouse.x>c3X&&mouse.x<c3X+cardW&&mouse.y>row1Y&&mouse.y<row1Y+cardH){
      activeBriefing='brief_tt3'; gameState='briefing'; SFX.select(); return;
    }
    const row2Y=row1Y+cardH+rowGap;
    const row2TotalW=cardW*2+gap;
    const c4X=cx-row2TotalW/2, c5X=c4X+cardW+gap;
    if(mouse.x>c4X&&mouse.x<c4X+cardW&&mouse.y>row2Y&&mouse.y<row2Y+cardH){
      activeBriefing='brief_tt4'; gameState='briefing'; SFX.select(); return;
    }
    if(mouse.x>c5X&&mouse.x<c5X+cardW&&mouse.y>row2Y&&mouse.y<row2Y+cardH){
      activeBriefing='brief_tt5'; gameState='briefing'; SFX.select(); return;
    }
    const bw=140,bh=36,bx=Math.max(20,H*0.03),by=H-backH+8;
    if(mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh){gameState='start';SFX.select();return;}
    return;
  }

  if(gameState==='hangar'){
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
    for(let i=0;i<SWATCHES.length;i++){
      const sx=rowStartX+i*itemStep;
      if(dist(mouse.x,mouse.y,sx,swatchCY)<swatchR+8){hangarColor=SWATCHES[i];colorPick.value=hangarColor;SFX.select();return;}
    }
    const cbX=rowStartX+10*itemStep;
    if(dist(mouse.x,mouse.y,cbX,swatchCY)<swatchR+8){colorPick.click();return;}
    // Edit Loadout button
    const elW=200,elH=38,elX=canvas.width/2-elW/2,elY=btnY-54;
    if(mouse.x>elX&&mouse.x<elX+elW&&mouse.y>elY&&mouse.y<elY+elH){
      const cId=CRAFTS[hangarCraft].id;
      const maxSl=CRAFTS[hangarCraft].maxSlots;
      hangarLoadout=_loadLoadout(cId,maxSl)||[CRAFTS[hangarCraft].startWeapon||0];
      loadoutEditFrom='hangar';
      gameState='loadoutEdit';
      SFX.select();return;
    }
    if(mouse.x>cancelX&&mouse.x<cancelX+cancelW&&mouse.y>btnY&&mouse.y<btnY+btnH){
      _loadHangar();hangarCraft=selectedCraft;hangarColor=selectedColor;
      gameState='start';SFX.select();return;
    }
    if(mouse.x>saveX&&mouse.x<saveX+saveW&&mouse.y>btnY&&mouse.y<btnY+btnH){
      selectedCraft=hangarCraft;selectedColor=hangarColor;P.craftIdx=selectedCraft;P.color=selectedColor;
      _saveHangar();weaponFlash={name:'HANGAR SAVED',ms:2000};gameState='start';SFX.confirm();return;
    }
    return;
  }

  if(gameState==='droneSelect'){
    // Check craft cards
    const centers=getCardCenters();
    for(let i=0;i<centers.length;i++){
      const{cx,cy}=centers[i];
      if(mouse.x>cx-100&&mouse.x<cx+100&&mouse.y>cy-170&&mouse.y<cy+170){
        selectedCraft=i;selectedColor=CRAFTS[i].defaultColor;colorPick.value=selectedColor;SFX.select();P.craftIdx=i;return;
      }
    }
    // Back btn
    const btnW=220,btnH=44,btnX=canvas.width/2-btnW/2,btnY=canvas.height-78;
    const backW=120,backX=btnX-backW-14;
    if(mouse.x>backX&&mouse.x<backX+backW&&mouse.y>btnY&&mouse.y<btnY+btnH){
      gameState='start';SFX.select();return;
    }
    // Choose Color btn
    if(mouse.x>btnX&&mouse.x<btnX+btnW&&mouse.y>btnY&&mouse.y<btnY+btnH){
      SFX.confirm();gameState='colorSelect';return;
    }
  }

  if(gameState==='colorSelect'){
    // Swatches
    const cx=canvas.width/2,cy=canvas.height/2;
    const sw_startY=cy+98,gapX=62,gapY=58;
    for(let i=0;i<SWATCHES.length;i++){
      const sx=cx+(i%5-2)*gapX,sy=sw_startY+Math.floor(i/5)*gapY;
      if(dist(mouse.x,mouse.y,sx,sy)<28){selectedColor=SWATCHES[i];colorPick.value=selectedColor;SFX.select();return;}
    }
    // Custom btn
    if(dist(mouse.x,mouse.y,cx,cy+220)<30){colorPick.click();return;}
    // Back btn
    const backW=130,backH=38,backX=cx-260-backW/2,backY=canvas.height-70;
    if(mouse.x>backX&&mouse.x<backX+backW&&mouse.y>backY&&mouse.y<backY+backH){gameState='droneSelect';SFX.select();return;}
    // Deploy
    const depW=220,depH=44,depX=cx+80,depY=canvas.height-78;
    if(mouse.x>depX&&mouse.x<depX+depW&&mouse.y>depY&&mouse.y<depY+depH){SFX.confirm();P.craftIdx=selectedCraft;startGame();return;}
  }

  if(gameState==='waveClear'&&screenLockMs<=0){spawnWave(wave);wavePause=0;gameState='playing';return;}
}
// Use mouseup instead of 'click' so right-clicks never trigger _doClick
canvas.addEventListener('mouseup', e=>{ if(e.button!==0) return; _doClick(); });

// ─── TOUCH CONTROLS ──────────────────────────────────────────────
if(IS_TOUCH){
  canvas.addEventListener('touchstart', e=>{
    e.preventDefault(); initAudio();
    for(const t of e.changedTouches){
      // Update mouse position so UI hit-tests in _doClick work
      mouse.x=t.clientX; mouse.y=t.clientY;
      const side = t.clientX < canvas.width/2 ? 'L' : 'R';
      const st = touchSticks[side];
      if(!st.active){
        st.active=true; st.id=t.identifier;
        st.ox=t.clientX; st.oy=t.clientY;
        st.dx=0; st.dy=0;
      }
    }
  },{passive:false});

  canvas.addEventListener('touchmove', e=>{
    e.preventDefault();
    for(const t of e.changedTouches){
      for(const side of ['L','R']){
        const st=touchSticks[side];
        if(st.active && st.id===t.identifier){
          const rdx=t.clientX-st.ox, rdy=t.clientY-st.oy;
          const mag=Math.sqrt(rdx*rdx+rdy*rdy);
          const capped=Math.min(mag,STICK_R);
          st.dx = mag>0 ? rdx/mag*capped : 0;
          st.dy = mag>0 ? rdy/mag*capped : 0;
        }
      }
    }
  },{passive:false});

  canvas.addEventListener('touchend', e=>{
    e.preventDefault();
    for(const t of e.changedTouches){
      for(const side of ['L','R']){
        const st=touchSticks[side];
        if(st.active && st.id===t.identifier){
          // If this was a tap (barely moved), fire click logic
          const wasTap = Math.abs(st.dx)<12 && Math.abs(st.dy)<12;
          if(wasTap){ mouse.x=t.clientX; mouse.y=t.clientY; _doClick(); }
          st.active=false; st.id=null; st.dx=0; st.dy=0;
        }
      }
    }
  },{passive:false});

  canvas.addEventListener('touchcancel', e=>{
    for(const t of e.changedTouches){
      for(const side of ['L','R']){
        const st=touchSticks[side];
        if(st.active && st.id===t.identifier){ st.active=false; st.id=null; st.dx=0; st.dy=0; }
      }
    }
  },{passive:false});
}

// ─── HOVER TRACKING ──────────────────────────────────────────────
function _isOverSetupInteractive(mx,my){
  if(!canvas)return false;
  const L=_getSetupLayout(canvas.width,canvas.height);
  const th=L.trackThumbR*3;
  for(const s of L.sliders){if(mx>=L.trackX&&mx<=L.trackX+L.trackW&&my>=s.y-th&&my<=s.y+th)return true;}
  for(const b of _particleBtnRects(L)){if(mx>=b.x&&mx<=b.x+L.tog3W&&my>=b.y&&my<=b.y+L.togH)return true;}
  for(const b of _shakeBtnRects(L)){if(mx>=b.x&&mx<=b.x+L.tog2W&&my>=b.y&&my<=b.y+L.togH)return true;}
  const hx=L.cx-L.hofBtnW/2;
  if(mx>=hx&&mx<=hx+L.hofBtnW&&my>=L.hofBtnY&&my<=L.hofBtnY+L.hofBtnH)return true;
  const{x,y,w,h}=L.backBtn;
  if(mx>=x&&mx<=x+w&&my>=y&&my<=y+h)return true;
  return false;
}
canvas.addEventListener('mousemove',()=>{
  hoverCard=-1;hoverSwatch=-1;menuHover=-1;
  if(gameState==='start'){
    const rects=getMenuRects();
    for(let i=0;i<rects.length;i++){const {x,y,w,h}=rects[i];if(mouse.x>=x&&mouse.x<=x+w&&mouse.y>=y&&mouse.y<=y+h){menuHover=i;break;}}
  }
  if(gameState==='droneSelect'){
    const centers=getCardCenters();
    for(let i=0;i<centers.length;i++){const{cx,cy}=centers[i];if(mouse.x>cx-100&&mouse.x<cx+100&&mouse.y>cy-170&&mouse.y<cy+170){hoverCard=i;break;}}
  }
  if(gameState==='colorSelect'){
    const cx=canvas.width/2,cy=canvas.height/2,sw_startY=cy+98,gapX=62,gapY=58;
    for(let i=0;i<SWATCHES.length;i++){const sx=cx+(i%5-2)*gapX,sy=sw_startY+Math.floor(i/5)*gapY;if(dist(mouse.x,mouse.y,sx,sy)<32){hoverSwatch=i;break;}}
  }
  if(gameState==='hangar'){
    const {cardsCY,swatchR,rowStartX,itemStep,swatchCY,startX}=_hangarLayout();
    const spacing=Math.min(220,canvas.width*0.22);
    for(let i=hangarScroll;i<Math.min(hangarScroll+HANGAR_VISIBLE,CRAFTS.length);i++){const cardX=startX+(i-hangarScroll)*spacing;if(mouse.x>cardX-100&&mouse.x<cardX+100&&mouse.y>cardsCY-170&&mouse.y<cardsCY+170){hoverCard=i;break;}}
    for(let i=0;i<SWATCHES.length;i++){const sx=rowStartX+i*itemStep;if(dist(mouse.x,mouse.y,sx,swatchCY)<swatchR+8){hoverSwatch=i;break;}}
  }
  // Track sound toggle hover for start screen highlight
  if(gameState==='start'){
    const _sp=Math.max(14,canvas.width*0.02),_sw=Math.max(80,canvas.width*0.075),_sh=Math.max(28,canvas.height*0.042);
    const _sx=canvas.width-_sp-_sw,_sy=_sp;
    soundToggleHover=mouse.x>=_sx&&mouse.x<=_sx+_sw&&mouse.y>=_sy&&mouse.y<=_sy+_sh;
  }
});

// ─── POINTER LOCK ────────────────────────────────────────────────
function requestLock(){ /* pointer lock removed */ }
function releaseLock(){ /* pointer lock removed */ }

// Snap mouse to current player screen position (call after camera jumps)
function _snapMouseToPlayer(){
  mouse.x = P.x - camX;
  mouse.y = P.y - camY;
}

// Free mouse tracking — no pointer lock, no leash
canvas.addEventListener('mousemove',e=>{
  mouse.x = e.clientX; mouse.y = e.clientY;
  if(editorSliderDrag&&gameState==='levelSetup'){
    const W=canvas.width,cx=W/2;
    const panelW=Math.min(500,W*0.8),panelX=cx-panelW/2;
    const pct=clamp((mouse.x-panelX)/panelW,0,1);
    if(editorSliderDrag==='width') editorWorldW=Math.round((400+pct*(4500-400))/100)*100;
    else if(editorSliderDrag==='height') editorWorldH=Math.round((400+pct*(4500-400))/100)*100;
    else if(editorSliderDrag==='seconds') editorWinSeconds=Math.round(10+pct*(180-10));
  }
  if(setupSliderDrag){
    settings[setupSliderDrag.key]=Math.max(0,Math.min(1,(mouse.x-setupSliderDrag.trackX)/setupSliderDrag.trackW));
    _saveSettings();_applyVolumes();
  }
});

// ═══════════════════════════════════════════════════════════════
//  INTRO CUTSCENE SYSTEM  —  7 sequences + ad break before title
// ═══════════════════════════════════════════════════════════════
let introSeq=0, introSeqStart=Date.now();
let adBreakStart=0;
let introShowSkip=false; // true only on seq 0 for returning players
const INTRO_SEQ_COUNT=7;
const AD_BREAK_DURATION=10000;
const INTRO_SEEN_KEY='pw_intro_seen';

// Check if this player has completed the intro before
try{ introShowSkip=!!localStorage.getItem(INTRO_SEEN_KEY); } catch(e){}

function introMs(){ return Date.now()-introSeqStart; }

function advanceIntro(){
  SFX.select();
  // introShowSkip remains true for the whole sequence if the player has seen it before
  introSeq++;
  introSeqStart=Date.now();
  if(introSeq>=INTRO_SEQ_COUNT){
    _markIntroSeen();
    gameState='adBreak'; adBreakStart=Date.now(); _showAllAds();
  }
}
function _markIntroSeen(){
  try{ localStorage.setItem(INTRO_SEEN_KEY,'1'); } catch(e){}
}
function leaveAdBreak(){ _markIntroSeen(); _hideAllAds(); gameState='start'; }
function _hideAllAds(){
  const el=document.getElementById('adSlot1'); if(el)el.style.display='none';
}
function _showAllAds(){
  const el=document.getElementById('adSlot1'); if(el)el.style.display='flex';
}

// ── Shared helpers ────────────────────────────────────────────
function _iGradBg(){
  ctx.fillStyle='#040a14'; ctx.fillRect(0,0,canvas.width,canvas.height);
  const t=Date.now()/1000,gs=90,off=(t*8)%gs;
  ctx.strokeStyle='rgba(0,60,120,0.07)'; ctx.lineWidth=1;
  for(let x=-off;x<canvas.width+gs;x+=gs){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x,canvas.height);ctx.stroke();}
  for(let y=-off;y<canvas.height+gs;y+=gs){ctx.beginPath();ctx.moveTo(0,y);ctx.lineTo(canvas.width,y);ctx.stroke();}
  const scanY=((Date.now()/1000*55)%canvas.height);
  ctx.fillStyle='rgba(0,80,180,0.03)'; ctx.fillRect(0,scanY-1,canvas.width,3);
}
function _iSeqLabel(txt){
  ctx.save(); ctx.textAlign='left'; ctx.font='9px "Courier New"';
  ctx.fillStyle='rgba(0,100,150,0.45)'; ctx.fillText('// '+txt+' //',22,32); ctx.restore();
}
function _iSkipIntro(){
  SFX.select();
  _markIntroSeen();
  gameState='adBreak'; adBreakStart=Date.now(); _showAllAds();
}
const INTRO_BTN_DELAY=5000;
function _iButtons(last){
  const ms=introMs();
  const cx=canvas.width/2;
  const bw=200,bh=36,by=canvas.height-80;
  const bx=introShowSkip ? cx+8 : cx-bw/2;
  if(introShowSkip){
    const sw=140,sx=cx-8-sw,shov=mouse.x>sx&&mouse.x<sx+sw&&mouse.y>by&&mouse.y<by+bh;
    ctx.save();
    roundRect(ctx,sx,by,sw,bh,6); ctx.clip();
    ctx.fillStyle=shov?'rgba(180,60,20,0.85)':'rgba(0,0,0,0.75)'; ctx.fillRect(sx,by,sw,bh);
    ctx.restore();
    ctx.save();
    ctx.strokeStyle=shov?'rgba(255,100,50,0.9)':'rgba(255,80,30,0.45)'; ctx.lineWidth=1.8;
    ctx.shadowBlur=shov?18:4; ctx.shadowColor='#ff5520';
    roundRect(ctx,sx,by,sw,bh,6); ctx.stroke(); ctx.shadowBlur=0;
    ctx.textAlign='center'; ctx.font='bold 12px "Courier New"';
    ctx.fillStyle=shov?'#fff':'rgba(255,120,70,0.75)';
    ctx.fillText('⏭  SKIP INTRO',sx+sw/2,by+24);
    ctx.restore();
  }
  const pct=Math.min(1,ms/INTRO_BTN_DELAY);
  const ready=pct>=1;
  const bhov=ready&&mouse.x>bx&&mouse.x<bx+bw&&mouse.y>by&&mouse.y<by+bh;
  ctx.save();
  roundRect(ctx,bx,by,bw,bh,6); ctx.clip();
  ctx.fillStyle='rgba(0,0,0,0.75)'; ctx.fillRect(bx,by,bw,bh);
  if(!ready){
    ctx.fillStyle='rgba(0,80,40,0.55)'; ctx.fillRect(bx,by,bw*pct,bh);
    const edgeX=bx+bw*pct;
    const grad=ctx.createLinearGradient(edgeX-18,0,edgeX,0);
    grad.addColorStop(0,'rgba(0,255,136,0)');
    grad.addColorStop(1,'rgba(0,255,136,0.18)');
    ctx.fillStyle=grad; ctx.fillRect(Math.max(bx,edgeX-18),by,Math.min(18,bw*pct),bh);
  } else if(bhov){
    ctx.fillStyle='#00ff88'; ctx.fillRect(bx,by,bw,bh);
  }
  ctx.restore();
  ctx.save();
  ctx.strokeStyle=`rgba(0,255,136,${0.2+0.8*pct})`; ctx.lineWidth=1.8;
  ctx.shadowBlur=bhov?22:8*pct; ctx.shadowColor='#00ff88';
  roundRect(ctx,bx,by,bw,bh,6); ctx.stroke(); ctx.shadowBlur=0;
  ctx.textAlign='center'; ctx.font='bold 12px "Courier New"';
  ctx.fillStyle=bhov?'#000':`rgba(0,255,136,${0.18+0.82*pct})`;
  ctx.fillText(last?'▶  FINAL PREPARATIONS':'▶  CONTINUE',bx+bw/2,by+24);
  ctx.restore();
  // Sound toggle — top-right corner
  const W=canvas.width,H=canvas.height;
  const _sPad=Math.max(14,W*0.02);
  const _sW=Math.max(80,W*0.075),_sH=Math.max(28,H*0.042);
  const _sX=W-_sPad-_sW,_sY=_sPad;
  const _sMuted=Music.isMuted();
  const _sHov=mouse.x>=_sX&&mouse.x<=_sX+_sW&&mouse.y>=_sY&&mouse.y<=_sY+_sH;
  ctx.save();
  ctx.fillStyle=_sHov?'rgba(0,180,255,0.18)':'rgba(0,55,115,0.55)';
  ctx.fillRect(_sX,_sY,_sW,_sH);
  ctx.strokeStyle=_sHov?'#00ccff':'rgba(0,140,220,0.75)';
  ctx.lineWidth=_sHov?2:1;
  ctx.shadowBlur=_sHov?20:0;ctx.shadowColor='#00ccff';
  ctx.strokeRect(_sX,_sY,_sW,_sH);ctx.shadowBlur=0;
  const _sSz=Math.max(9,Math.min(_sH*0.38,13));
  ctx.font=`bold ${_sSz}px "Courier New"`;
  ctx.textAlign='center';
  ctx.fillStyle=_sHov?'#00eeff':'rgba(150,205,255,0.92)';
  ctx.fillText(_sMuted?'✕ SOUND OFF':'♪ SOUND ON',_sX+_sW/2,_sY+_sH/2+_sSz*0.36);
  ctx.restore();
}
function _iFadeIn(ms,start,ramp){return Math.min(1,Math.max(0,(ms-start)/ramp));}
function _iNarrative(lines,baseY,alpha){
  if(alpha<=0)return;
  ctx.save(); ctx.textAlign='center';
  const cx=canvas.width/2;
  lines.forEach((l,i)=>{
    const isH=l[0]==='#',isD=l[0]==='~';
    const txt=isH||isD?l.slice(1):l;
    if(isH){ctx.font='bold 20px "Courier New"';ctx.fillStyle='rgba(0,220,255,'+alpha+')';ctx.shadowBlur=18;ctx.shadowColor='#00aaff';}
    else if(isD){ctx.font='10px "Courier New"';ctx.fillStyle='rgba(80,140,195,'+alpha*0.65+')';ctx.shadowBlur=0;}
    else{ctx.font='13px "Courier New"';ctx.fillStyle='rgba(155,205,255,'+alpha+')';ctx.shadowBlur=0;}
    ctx.fillText(txt,cx,baseY+i*23);
    ctx.shadowBlur=0;
  }); ctx.restore();
}

// ── SEQ 0 — ORIGIN ────────────────────────────────────────────
function _drawIntroSeq0(){
  _iGradBg();
  const t=Date.now()/1000,cx=canvas.width/2,cy=canvas.height/2,ms=introMs();
  const hA=_iFadeIn(ms,0,1000);
  ctx.save(); ctx.globalAlpha=hA*0.5; ctx.textAlign='center'; ctx.font='9px "Courier New"';
  ctx.fillStyle='#0099cc'; ctx.fillText(`// CLASSIFIED — ${GAME_NAME} INITIATIVE — MISSION ARCHIVE //`,cx,42); ctx.restore();
  const dA=_iFadeIn(ms,500,1400);
  if(dA>0){
    ctx.save(); ctx.globalAlpha=dA;
    const fy=cy-90,fsp=Math.min(130,canvas.width*0.18);
    const h1=Math.sin(t*0.8)*8,h2=Math.sin(t*0.8+1.1)*8,h3=Math.sin(t*0.8+2.2)*8,h4=Math.sin(t*0.8+3.3)*8;
    drawPhantom(cx-fsp,fy+h1,Math.PI*0.5+Math.sin(t*0.3)*0.12,34,'#00ccff',lighten('#00ccff'),t*8);
    drawViper(cx+fsp,fy+h2,Math.PI*0.5+Math.sin(t*0.3+0.5)*0.12,26,'#ff4400',lighten('#ff4400'),t*11);
    drawTitan(cx-fsp*0.38,fy+105+h3,Math.PI*0.5+Math.sin(t*0.3+1.0)*0.12,28,'#ff8800',lighten('#ff8800'),t*7);
    drawSpecter(cx+fsp*0.38,fy+105+h4,Math.PI*0.5+Math.sin(t*0.3+1.5)*0.12,24,'#aa44ff',lighten('#aa44ff'),t*10);
    ctx.strokeStyle='rgba(0,190,255,0.07)'; ctx.lineWidth=1; ctx.setLineDash([4,10]);
    [[cx-fsp,fy+h1,cx+fsp,fy+h2],[cx-fsp,fy+h1,cx-fsp*0.38,fy+105+h3],[cx+fsp,fy+h2,cx+fsp*0.38,fy+105+h4]].forEach(([ax,ay,bx2,by2])=>{ctx.beginPath();ctx.moveTo(ax,ay);ctx.lineTo(bx2,by2);ctx.stroke();});
    ctx.setLineDash([]);
    const lA=_iFadeIn(ms,1600,700);
    if(lA>0){
      ctx.globalAlpha=dA*lA; ctx.textAlign='center'; ctx.font='8px "Courier New"';
      const lbls=[['PHANTOM','#00ccff',cx-fsp,fy+h1-46],['VIPER','#ff4400',cx+fsp,fy+h2-42],['TITAN','#ff8800',cx-fsp*0.38,fy+105+h3+40],['SPECTER','#aa44ff',cx+fsp*0.38,fy+105+h4+38]];
      lbls.forEach(([n,c,lx,ly])=>{ctx.fillStyle=c;ctx.shadowBlur=8;ctx.shadowColor=c;ctx.fillText(n,lx,ly);ctx.shadowBlur=0;});
    }
    ctx.restore();
  }
  const nA=_iFadeIn(ms,0,900);
  _iNarrative(['#YEAR 2089. THE OLD ORDER HAS COLLAPSED.','Hostile autonomous drone networks have seized the skies.','In the ashes of the conflict, one program was activated.',`~[ ${GAME_NAME} — FOUR CRAFT. ONE MISSION. ]`],canvas.height*0.73,nA);
  _iButtons(false); _iSeqLabel('ORIGIN');
}

// ── SEQ 1 — THE ENEMY ─────────────────────────────────────────
function _drawIntroSeq1(){
  _iGradBg();
  const t=Date.now()/1000,cx=canvas.width/2,cy=canvas.height/2,ms=introMs();
  ctx.fillStyle='rgba(50,0,0,0.07)'; ctx.fillRect(0,0,canvas.width,canvas.height);
  ctx.save(); ctx.globalAlpha=_iFadeIn(ms,0,700)*0.55; ctx.textAlign='center';
  ctx.font='bold 11px "Courier New"'; ctx.fillStyle='rgba(255,50,50,0.7)';
  ctx.fillText('⚠  HOSTILE CONTACT DETECTED  ⚠',cx,42); ctx.restore();
  const eA=_iFadeIn(ms,300,1200);
  if(eA>0){
    ctx.save(); ctx.globalAlpha=eA;
    for(let i=0;i<5;i++){
      const orb=t*0.38+i*(Math.PI*2/5);
      const sx2=cx+Math.cos(orb)*Math.min(180,canvas.width*0.22),sy2=cy-55+Math.sin(orb)*55;
      drawEnemyDrone(sx2,sy2,Math.atan2(0-sy2+cy-55,0),13,'#ff2244','#ff9aaa',t*9*(i%2?1:-1));
    }
    ctx.restore();
  }
  const gA=_iFadeIn(ms,1400,800);
  if(gA>0){
    const offX=Math.min(170,canvas.width*0.22);
    ctx.save(); ctx.globalAlpha=gA;
    drawEnemyDrone(cx-offX,cy+70+Math.sin(t*0.5)*10,Math.PI*1.5,17,'#ff8800','#ffcc44',t*7);
    ctx.textAlign='center'; ctx.font='9px "Courier New"'; ctx.fillStyle='rgba(255,136,0,0.7)';
    ctx.fillText('GUARD',cx-offX,cy+107); ctx.restore();
  }
  const tA=_iFadeIn(ms,2100,800);
  if(tA>0){
    const offX=Math.min(170,canvas.width*0.22);
    ctx.save(); ctx.globalAlpha=tA;
    drawEnemyDrone(cx+offX,cy+70,Math.PI*0.5,22,'#9900ff','#cc55ff',t*5);
    ctx.textAlign='center'; ctx.font='9px "Courier New"'; ctx.fillStyle='rgba(153,0,255,0.7)';
    ctx.fillText('TURRET',cx+offX,cy+110); ctx.restore();
  }
  const nA=_iFadeIn(ms,0,800);
  _iNarrative(['#HOSTILE FORCES — UNKNOWN ORIGIN','Scouts probe your perimeter in constant patrol.','Guards advance and engage. Turrets hold strategic choke points.','~[ WAVE 5 BRINGS SOMETHING FAR MORE DANGEROUS ]'],canvas.height*0.74,nA);
  _iButtons(false); _iSeqLabel('THE ENEMY');
}

// ── SEQ 2 — THE WAR ───────────────────────────────────────────
function _drawIntroSeq2(){
  _iGradBg();
  const t=Date.now()/1000,cx=canvas.width/2,cy=canvas.height/2,ms=introMs();
  ctx.fillStyle='rgba(0,20,50,0.25)'; ctx.fillRect(0,0,canvas.width,canvas.height);
  const mA=_iFadeIn(ms,0,1000);
  ctx.save(); ctx.globalAlpha=mA;
  const offX=Math.min(230,canvas.width*0.28);
  drawPhantom(cx-offX,cy-20+Math.sin(t*0.7)*12,0.08,28,'#00ccff',lighten('#00ccff'),t*9);
  drawViper(cx-offX-70,cy+55+Math.sin(t*0.7+1)*12,-0.28,20,'#ff4400',lighten('#ff4400'),t*12);
  drawEnemyDrone(cx+offX,cy-20+Math.sin(t*0.65+0.5)*12,Math.PI,15,'#ff2244','#ff9aaa',t*9);
  drawEnemyDrone(cx+offX+75,cy+55+Math.sin(t*0.7+0.8)*10,Math.PI*1.12,14,'#ff2244','#ff9aaa',-t*8);
  drawEnemyDrone(cx+offX-30,cy-80+Math.sin(t*0.8)*8,Math.PI*0.92,17,'#ff8800','#ffcc44',t*7);
  for(let i=0;i<10;i++){
    const phase=((t*0.85+i*0.1)%1),fromR=(i%2===0);
    const bxp=fromR?(cx-offX+50+phase*(offX*2-100)):(cx+offX-50-phase*(offX*2-100));
    const byp=cy-10+(i%3)*22+Math.sin(t+i)*6;
    const col=fromR?'#00eeff':'#ffaa22';
    ctx.beginPath(); ctx.arc(bxp,byp,2.2,0,Math.PI*2);
    ctx.fillStyle=col; ctx.shadowBlur=7; ctx.shadowColor=col; ctx.fill(); ctx.shadowBlur=0;
  }
  ctx.strokeStyle='rgba(255,255,255,0.06)'; ctx.lineWidth=1; ctx.setLineDash([6,12]);
  ctx.beginPath(); ctx.moveTo(cx,cy-170); ctx.lineTo(cx,cy+130); ctx.stroke(); ctx.setLineDash([]);
  ctx.textAlign='center'; ctx.font='bold 16px "Courier New"'; ctx.fillStyle='rgba(255,255,255,0.14)';
  ctx.fillText('VS',cx,cy-25); ctx.restore();
  const nA=_iFadeIn(ms,0,900);
  _iNarrative([`#A CONFLICT WITHOUT END`,`For years the ${GAME_NAME} has held the line.`,'Five escalating waves. Every engagement harder than the last.','~[ EACH PILOT A HERO. EACH RUN A NEW BATTLE FOR SURVIVAL. ]'],canvas.height*0.73,nA);
  _iButtons(false); _iSeqLabel('THE WAR');
}

// ── SEQ 3 — WEAPONS ARSENAL ───────────────────────────────────
function _drawIntroSeq3(){
  _iGradBg();
  const t=Date.now()/1000,cx=canvas.width/2,cy=canvas.height/2,ms=introMs();
  const hA=_iFadeIn(ms,0,700);
  ctx.save(); ctx.globalAlpha=hA; ctx.textAlign='center';
  ctx.font='bold 28px "Courier New"'; ctx.shadowBlur=22; ctx.shadowColor='#0088cc';
  ctx.fillStyle='#00ccff'; ctx.fillText('WEAPONS ARSENAL',cx,70); ctx.shadowBlur=0; ctx.restore();
  const wA=_iFadeIn(ms,400,900);
  if(wA>0){
    ctx.save(); ctx.globalAlpha=wA;
    const wData=[{n:'STANDARD',c:'#00eeff',i:'•',d:'28 dmg · steady'},{n:'RAPID FIRE',c:'#ffee00',i:'►',d:'12 dmg · full-auto'},{n:'SPREAD SHOT',c:'#ff8800',i:'»',d:'19 dmg · triple'},{n:'BURST CANNON',c:'#cc55ff',i:'‖',d:'25 dmg · 3-burst'},{n:'PLASMA',c:'#ff44cc',i:'◈',d:'96 dmg · heavy'},{n:'PROX MINE',c:'#ff2200',i:'⊛',d:'Trap · proximity'}];
    const bw2=54,bh2=54,gap=8,totalW=6*(bw2+gap)-gap;
    const startX=Math.max(cx-totalW/2,14);
    const barY=cy-110;
    const selWep=Math.floor(t*0.9)%6;
    wData.forEach((w,i)=>{
      const wx=startX+i*(bw2+gap),wy=barY;
      const a2=_iFadeIn(ms,400+i*160,450);
      ctx.globalAlpha=wA*a2;
      ctx.fillStyle='rgba(0,0,0,0.7)'; ctx.fillRect(wx,wy,bw2,bh2);
      const sel=i===selWep;
      ctx.strokeStyle=sel?w.c:'rgba(0,60,110,0.6)'; ctx.lineWidth=sel?2:1.2;
      ctx.shadowBlur=sel?16:0; ctx.shadowColor=w.c; ctx.strokeRect(wx,wy,bw2,bh2); ctx.shadowBlur=0;
      ctx.font='bold 21px "Courier New"'; ctx.fillStyle=sel?w.c:'rgba(80,110,140,0.7)'; ctx.textAlign='center';
      ctx.fillText(w.i,wx+bw2/2,wy+bh2/2+9);
      ctx.font='7px "Courier New"'; ctx.fillStyle='rgba(100,150,200,0.55)'; ctx.fillText(String(i+1),wx+bw2-7,wy+12);
    });
    ctx.globalAlpha=wA;
    ctx.textAlign='center'; ctx.font='bold 13px "Courier New"'; ctx.fillStyle=wData[selWep].c;
    ctx.shadowBlur=12; ctx.shadowColor=wData[selWep].c; ctx.fillText(wData[selWep].n,cx,barY+bh2+22); ctx.shadowBlur=0;
    ctx.font='11px "Courier New"'; ctx.fillStyle='rgba(155,205,255,0.7)'; ctx.fillText(wData[selWep].d,cx,barY+bh2+40);
    ctx.restore();
  }
  const nA=_iFadeIn(ms,0,800);
  _iNarrative(['#UNLOCK & UPGRADE YOUR ARSENAL','Collect YELLOW ★ pickups to unlock the next weapon.','Press keys 1–6 or cycle with Q / E to switch weapons.','~[ PROX MINE: key [6] — arm delay — detonates on enemy proximity ]'],canvas.height*0.73,nA);
  _iButtons(false); _iSeqLabel('ARSENAL');
}

// ── SEQ 4 — CONTROLS ──────────────────────────────────────────
function _drawIntroSeq4(){
  _iGradBg();
  const t=Date.now()/1000,cx=canvas.width/2,cy=canvas.height/2,ms=introMs();
  const hA=_iFadeIn(ms,0,700);
  ctx.save(); ctx.globalAlpha=hA; ctx.textAlign='center';
  ctx.font='bold 28px "Courier New"'; ctx.shadowBlur=20; ctx.shadowColor='#0088cc';
  ctx.fillStyle='#00ccff'; ctx.fillText('PILOT INTERFACE',cx,70); ctx.shadowBlur=0; ctx.restore();
  const mA=_iFadeIn(ms,500,900);
  ctx.save(); ctx.globalAlpha=mA;
  function drawKey(kx,ky,label,active){
    const kw=36,kh=36;
    ctx.fillStyle=active?'rgba(0,140,200,0.45)':'rgba(0,30,70,0.75)';
    roundRect(ctx,kx-kw/2,ky-kh/2,kw,kh,5); ctx.fill();
    ctx.strokeStyle=active?'#00ccff':'rgba(0,110,180,0.35)'; ctx.lineWidth=1.4;
    roundRect(ctx,kx-kw/2,ky-kh/2,kw,kh,5); ctx.stroke();
    ctx.font='bold 13px "Courier New"'; ctx.fillStyle=active?'#00ccff':'rgba(100,160,215,0.65)';
    ctx.textAlign='center'; ctx.fillText(label,kx,ky+6);
  }
  const kx=Math.max(cx-200,80),ky=cy-100;
  const aW=Math.floor(t*2.2)%4;
  drawKey(kx,ky-40,'↑',aW===0); drawKey(kx-40,ky,'←',aW===1);
  drawKey(kx,ky,'↓',aW===2); drawKey(kx+40,ky,'→',aW===3);
  ctx.textAlign='center'; ctx.font='10px "Courier New"'; ctx.fillStyle='rgba(0,190,255,0.55)';
  ctx.fillText('MOVE',kx,ky+42); ctx.fillText('ARROW KEYS / WASD',kx,ky+55);
  const aimX2=Math.min(cx+30,canvas.width*0.55),aimY2=cy-90;
  const aimT=t*0.9;
  const aimPx=aimX2+Math.cos(aimT)*38,aimPy=aimY2+Math.sin(aimT)*28;
  drawPhantom(aimX2,aimY2,aimT,22,'#00ccff',lighten('#00ccff'),t*9);
  const cr=22;
  ctx.strokeStyle='#00ccff'; ctx.lineWidth=1.4; ctx.shadowBlur=5; ctx.shadowColor='#00ccff';
  ctx.beginPath(); ctx.arc(aimPx,aimPy,cr*0.45,0,Math.PI*2); ctx.stroke();
  [[aimPx-cr,aimPy,aimPx-cr*0.4,aimPy],[aimPx+cr*0.4,aimPy,aimPx+cr,aimPy],[aimPx,aimPy-cr,aimPx,aimPy-cr*0.4],[aimPx,aimPy+cr*0.4,aimPx,aimPy+cr]].forEach(([x1,y1,x2,y2])=>{ctx.beginPath();ctx.moveTo(x1,y1);ctx.lineTo(x2,y2);ctx.stroke();});
  ctx.shadowBlur=0;
  ctx.strokeStyle='rgba(0,190,255,0.2)'; ctx.setLineDash([3,7]);
  ctx.beginPath(); ctx.moveTo(aimX2,aimY2); ctx.lineTo(aimPx,aimPy); ctx.stroke(); ctx.setLineDash([]);
  ctx.textAlign='center'; ctx.font='10px "Courier New"'; ctx.fillStyle='rgba(0,200,255,0.55)';
  ctx.fillText('MOUSE · AIM',aimX2,aimY2+65);
  const shiftX=Math.min(cx+200,canvas.width-80),shiftY=cy-100;
  const shA=Math.floor(t*1.3)%3===0;
  ctx.fillStyle=shA?'rgba(255,200,0,0.35)':'rgba(0,30,70,0.7)';
  roundRect(ctx,shiftX-52,shiftY-18,104,34,5); ctx.fill();
  ctx.strokeStyle=shA?'#ffcc00':'rgba(0,100,180,0.35)'; ctx.lineWidth=1.3;
  roundRect(ctx,shiftX-52,shiftY-18,104,34,5); ctx.stroke();
  ctx.font='bold 10px "Courier New"'; ctx.fillStyle=shA?'#ffcc00':'rgba(110,170,220,0.65)';
  ctx.textAlign='center'; ctx.fillText('SHIFT',shiftX,shiftY+1);
  ctx.font='8px "Courier New"'; ctx.fillStyle='rgba(220,180,80,0.55)'; ctx.fillText('HOLD · BOOST ×2',shiftX,shiftY+18);
  const batY=cy+50,batW=Math.min(220,canvas.width*0.28),batX=cx-batW/2;
  const batPct=0.35+0.35*Math.sin(t*0.55);
  ctx.fillStyle='rgba(0,0,0,0.55)'; ctx.fillRect(batX,batY,batW,16);
  ctx.fillStyle=batPct>0.25?'#ffee00':'#ff5500'; ctx.shadowBlur=5; ctx.shadowColor=ctx.fillStyle;
  ctx.fillRect(batX+1,batY+1,batW*batPct-2,14); ctx.shadowBlur=0;
  ctx.font='8px "Courier New"'; ctx.fillStyle='rgba(200,230,255,0.65)'; ctx.textAlign='left';
  ctx.fillText('BATTERY',batX+4,batY+12);
  ctx.textAlign='center'; ctx.font='9px "Courier New"'; ctx.fillStyle='rgba(140,195,255,0.5)';
  ctx.fillText('Run out of battery → craft takes damage',cx,batY+32);
  const fireFlash=Math.floor(t*3)%2===0;
  ctx.font='bold 11px "Courier New"'; ctx.fillStyle=fireFlash?'#00ff88':'rgba(0,170,90,0.45)';
  ctx.shadowBlur=fireFlash?12:0; ctx.shadowColor='#00ff88';
  ctx.textAlign='center'; ctx.fillText('LEFT CLICK / SPACE  →  FIRE',cx,cy+10); ctx.shadowBlur=0;
  ctx.restore();
  const nA=_iFadeIn(ms,0,800);
  _iNarrative(['#CONTROLS REFERENCE','WASD · Move   |   Mouse · Aim   |   Click / Space · Fire','Hold SHIFT for speed burst  |  Hold F to rotate gun clockwise','~[ P / ESC to pause   ·   Q / E or 1–6 to cycle weapons ]'],canvas.height*0.73,nA);
  _iButtons(false); _iSeqLabel('CONTROLS');
}

// ── SEQ 5 — POWER-UPS & FIELD INTEL ──────────────────────────
function _drawIntroSeq5(){
  _iGradBg();
  const t=Date.now()/1000,cx=canvas.width/2,cy=canvas.height/2,ms=introMs();
  const hA=_iFadeIn(ms,0,700);
  ctx.save(); ctx.globalAlpha=hA; ctx.textAlign='center';
  ctx.font='bold 28px "Courier New"'; ctx.shadowBlur=20; ctx.shadowColor='#0088cc';
  ctx.fillStyle='#00ccff'; ctx.fillText('FIELD POWER-UPS',cx,70); ctx.shadowBlur=0; ctx.restore();
  const pA=_iFadeIn(ms,400,900);
  if(pA>0){
    ctx.save(); ctx.globalAlpha=pA;
    const pData=[{type:'battery',col:'#00ff88',lbl:'BATTERY',dsc:'+58 power'},{type:'health',col:'#ff4466',lbl:'HEALTH',dsc:'+48 craft'},{type:'weapon',col:'#ffee00',lbl:'WEAPON',dsc:'Unlock next'},{type:'shield',col:'#44aaff',lbl:'SHIELD',dsc:'4.8s bubble'},{type:'emp',col:'#cc44ff',lbl:'EMP',dsc:'Stun all'},{type:'overcharge',col:'#ff9900',lbl:'OVERCHARGE',dsc:'×2.3 dmg 7s'}];
    const sp=Math.min(88,canvas.width*0.11),startX=cx-(5*sp)/2;
    pData.forEach((p,i)=>{
      const px=startX+i*sp,py=cy-80;
      const a2=_iFadeIn(ms,400+i*150,450),pulse=0.7+0.3*Math.sin(t*3+i*0.9);
      ctx.globalAlpha=pA*a2;
      ctx.save(); ctx.translate(px,py);
      ctx.shadowBlur=14*pulse; ctx.shadowColor=p.col;
      if(p.type==='battery'){ctx.strokeStyle=p.col;ctx.fillStyle=p.col+'18';ctx.lineWidth=2;ctx.fillRect(-10,-6,20,12);ctx.strokeRect(-10,-6,20,12);ctx.fillStyle=p.col;ctx.fillRect(-8,-4,12,8);ctx.fillRect(10,-2.5,3.5,5);}
      else if(p.type==='health'){ctx.fillStyle=p.col;ctx.fillRect(-2.5,-9,5,18);ctx.fillRect(-9,-2.5,18,5);}
      else if(p.type==='weapon'){ctx.rotate(t*0.85);ctx.beginPath();for(let k=0;k<8;k++){const a=(Math.PI/4)*k,r=k%2===0?13:5;k===0?ctx.moveTo(Math.cos(a)*r,Math.sin(a)*r):ctx.lineTo(Math.cos(a)*r,Math.sin(a)*r);}ctx.closePath();ctx.fillStyle=p.col;ctx.fill();}
      else if(p.type==='shield'){ctx.beginPath();for(let k=0;k<6;k++){const a=(Math.PI/3)*k;k===0?ctx.moveTo(Math.cos(a)*12,Math.sin(a)*12):ctx.lineTo(Math.cos(a)*12,Math.sin(a)*12);}ctx.closePath();ctx.strokeStyle=p.col;ctx.lineWidth=2.5;ctx.stroke();ctx.beginPath();ctx.arc(0,0,5,0,Math.PI*2);ctx.fillStyle=p.col;ctx.fill();}
      else if(p.type==='emp'){ctx.beginPath();ctx.arc(0,0,7,0,Math.PI*2);ctx.fillStyle=p.col;ctx.fill();[10,14].forEach((r,ii)=>{ctx.beginPath();ctx.arc(0,0,r+pulse*3*ii,0,Math.PI*2);ctx.strokeStyle=p.col+(ii===0?'99':'44');ctx.lineWidth=1.5-ii*0.5;ctx.stroke();});}
      else if(p.type==='overcharge'){ctx.fillStyle=p.col;ctx.beginPath();ctx.moveTo(5,-12);ctx.lineTo(-1,0);ctx.lineTo(4,0);ctx.lineTo(-4,12);ctx.lineTo(2,0);ctx.lineTo(-3,0);ctx.closePath();ctx.fill();}
      ctx.shadowBlur=0; ctx.restore();
      ctx.save(); ctx.globalAlpha=pA*a2; ctx.textAlign='center';
      ctx.font='bold 8px "Courier New"'; ctx.fillStyle=p.col; ctx.fillText(p.lbl,px,py+26);
      ctx.font='8px "Courier New"'; ctx.fillStyle='rgba(130,185,225,0.65)'; ctx.fillText(p.dsc,px,py+38); ctx.restore();
    });
    ctx.restore();
  }
  const mmA=_iFadeIn(ms,2000,800);
  if(mmA>0){
    ctx.save(); ctx.globalAlpha=mmA;
    const mmW=128,mmH=82,mmX=cx-mmW/2,mmY=cy+42;
    ctx.fillStyle='rgba(4,10,22,0.85)'; ctx.fillRect(mmX,mmY,mmW,mmH);
    ctx.strokeStyle='rgba(0,130,200,0.4)'; ctx.lineWidth=1; ctx.strokeRect(mmX,mmY,mmW,mmH);
    [{x:0.5,y:0.5,c:'#00ccff'},{x:0.25,y:0.35,c:'#ff2244'},{x:0.75,y:0.28,c:'#ff2244'},{x:0.6,y:0.72,c:'#ff8800'},{x:0.18,y:0.68,c:'#9900ff'}].forEach(d=>{ctx.beginPath();ctx.arc(mmX+d.x*mmW,mmY+d.y*mmH,3,0,Math.PI*2);ctx.fillStyle=d.c;ctx.shadowBlur=5;ctx.shadowColor=d.c;ctx.fill();ctx.shadowBlur=0;});
    ctx.font='7px "Courier New"'; ctx.fillStyle='rgba(0,140,210,0.65)'; ctx.textAlign='center';
    ctx.fillText('TACTICAL MAP',cx,mmY+8);
    ctx.font='9px "Courier New"'; ctx.fillStyle='rgba(90,150,200,0.6)';
    ctx.fillText('Minimap tracks all hostiles & pickups',cx,mmY+mmH+18); ctx.restore();
  }
  const nA=_iFadeIn(ms,0,800);
  _iNarrative(['#FIELD INTELLIGENCE','Power-ups drop on enemy death or scatter across the map.','SHIELD absorbs one hit.  EMP stuns every hostile on screen.','~[ OVERCHARGE doubles your damage — activate before a boss fight ]'],canvas.height*0.75,nA);
  _iButtons(false); _iSeqLabel('TACTICS');
}

// ── SEQ 6 — MOBILIZE (final) ──────────────────────────────────
function _drawIntroSeq6(){
  _iGradBg();
  const t=Date.now()/1000,cx=canvas.width/2,cy=canvas.height/2,ms=introMs();
  ctx.fillStyle='rgba(0,10,30,0.2)'; ctx.fillRect(0,0,canvas.width,canvas.height);
  const hA=_iFadeIn(ms,0,900);
  ctx.save(); ctx.globalAlpha=hA; ctx.textAlign='center';
  ctx.font='bold 40px "Courier New"'; ctx.shadowBlur=40; ctx.shadowColor='#00aaff';
  ctx.fillStyle='#00ccff'; ctx.fillText(GAME_NAME,cx,cy-200); ctx.shadowBlur=0;
  ctx.font='13px "Courier New"'; ctx.fillStyle='rgba(0,160,220,0.5)';
  ctx.fillText('FOUR CRAFT  ·  FIVE WAVES  ·  ONE SURVIVOR',cx,cy-170); ctx.restore();
  const fA=_iFadeIn(ms,400,1300);
  if(fA>0){
    ctx.save(); ctx.globalAlpha=fA;
    const fY=cy-30+Math.sin(t*0.4)*14;
    const sp=Math.min(canvas.width*0.24,190);
    drawPhantom(cx,fY-30,-Math.PI/2+Math.sin(t*0.3)*0.08,34,'#00ccff',lighten('#00ccff'),t*9);
    drawViper(cx+sp*0.52,fY+28,-Math.PI/2+0.13+Math.sin(t*0.3)*0.1,24,'#ff4400',lighten('#ff4400'),t*12);
    drawTitan(cx-sp*0.52,fY+28,-Math.PI/2-0.13+Math.sin(t*0.3)*0.08,28,'#ff8800',lighten('#ff8800'),t*7);
    drawSpecter(cx,fY+90,-Math.PI/2+Math.sin(t*0.3+0.5)*0.1,22,'#aa44ff',lighten('#aa44ff'),t*10);
    [[cx,fY-30,'#00ccff'],[cx+sp*0.52,fY+28,'#ff4400'],[cx-sp*0.52,fY+28,'#ff8800'],[cx,fY+90,'#aa44ff']].forEach(([fx,fy2,c])=>{
      for(let k=0;k<5;k++){ctx.beginPath();ctx.arc(fx,fy2+38+k*13,2.5-k*0.35,0,Math.PI*2);ctx.fillStyle=c;ctx.globalAlpha=fA*(1-k/5)*0.4;ctx.fill();}
    });
    ctx.restore();
  }
  const wA=_iFadeIn(ms,2000,800);
  if(wA>0){
    ctx.save(); ctx.globalAlpha=wA; ctx.textAlign='center';
    const wy=cy+115;
    for(let w=1;w<=5;w++){
      const wx=cx+(w-3)*58,last=w===5;
      ctx.fillStyle=last?'rgba(40,0,0,0.85)':'rgba(0,15,45,0.75)';
      ctx.shadowBlur=last?18:5; ctx.shadowColor=last?'#ff0055':'#0066cc';
      roundRect(ctx,wx-20,wy-18,40,38,5); ctx.fill();
      ctx.strokeStyle=last?'#ff0055':'rgba(0,110,195,0.5)'; ctx.lineWidth=last?2:1;
      roundRect(ctx,wx-20,wy-18,40,38,5); ctx.stroke(); ctx.shadowBlur=0;
      ctx.font=`bold ${last?'12':'11'}px "Courier New"`;
      ctx.fillStyle=last?'#ff0055':'rgba(0,175,255,0.8)'; ctx.fillText('W'+w,wx,wy+2);
      if(last){ctx.font='7px "Courier New"';ctx.fillStyle='rgba(255,80,80,0.7)';ctx.fillText('BOSS',wx,wy+17);}
    }
    ctx.restore();
  }
  const bossA=_iFadeIn(ms,1200,1000);
  if(bossA>0){
    ctx.save(); ctx.globalAlpha=bossA*0.65;
    const pulse=0.4+0.4*Math.sin(t*2.8);
    for(let r=60;r<200;r+=55){ctx.beginPath();ctx.arc(cx,cy+50,r+pulse*12,0,Math.PI*2);ctx.strokeStyle=`rgba(255,0,55,${0.12*(1-r/200)})`;ctx.lineWidth=1.5;ctx.stroke();}
    drawEnemyDrone(cx,cy+50,Math.PI/2+Math.sin(t*0.3)*0.1,48,'#ff0055','#ff66bb',t*3.5,1);
    ctx.textAlign='center'; ctx.font='8px "Courier New"'; ctx.fillStyle='rgba(255,80,100,0.5)';
    ctx.fillText('800 HP · WAVE 5 BOSS',cx,cy+110); ctx.restore();
  }
  const nA=_iFadeIn(ms,0,800);
  _iNarrative(['#MISSION ACTIVE','Choose your craft. Forge your colors. Enter the sector.','Destroy all hostiles across five waves. Eliminate the Boss.',`~[ THE ${GAME_NAME} DOES NOT RETREAT ]`],canvas.height*0.76,nA);
  _iButtons(true); _iSeqLabel('MOBILIZE');
}

// ── AD BREAK SCREEN ───────────────────────────────────────────
function drawAdBreakScreen(){
  _iGradBg();
  const t=Date.now()/1000, cx=canvas.width/2, cy=canvas.height/2;
  const elapsed=Date.now()-adBreakStart;
  const remaining=Math.max(0, AD_BREAK_DURATION-elapsed);

  // Ad slot is centered at cy-80px (matching CSS translateY(-80px))
  // Brand plate sits above it; countdown + button sit below
  const adH=250+22; // 250px box + 22px label above it
  const adCY=cy-80; // vertical centre of the ad block (matching CSS)
  const plateY=adCY - adH/2 - 52; // above the ad

  // Drones flanking the ad box — drawn behind everything else
  ctx.save(); ctx.globalAlpha=0.18;
  drawPhantom(cx-220+Math.sin(t*0.4)*10, adCY+Math.sin(t*0.5)*14, t*0.18, 26,'#00ccff',lighten('#00ccff'),t*7);
  drawViper( cx+220+Math.sin(t*0.4+1)*10, adCY+Math.sin(t*0.5+1)*14, t*0.18+Math.PI, 20,'#ff4400',lighten('#ff4400'),t*10);
  ctx.restore();

  // Brand plate
  ctx.save(); ctx.textAlign='center';
  ctx.strokeStyle='rgba(0,120,180,0.28)'; ctx.lineWidth=1;
  ctx.beginPath(); ctx.moveTo(cx-170,plateY-8); ctx.lineTo(cx+170,plateY-8); ctx.stroke();
  ctx.font='bold 11px "Courier New"'; ctx.fillStyle='rgba(0,180,230,0.5)';
  ctx.fillText('A MESSAGE FROM OUR SPONSORS', cx, plateY+10);
  ctx.font='bold 32px "Courier New"'; ctx.shadowBlur=24; ctx.shadowColor='#00aaff';
  ctx.fillStyle='#00ccff'; ctx.fillText(GAME_NAME, cx, plateY+42); ctx.shadowBlur=0;
  ctx.strokeStyle='rgba(0,120,180,0.28)'; ctx.lineWidth=1;
  ctx.beginPath(); ctx.moveTo(cx-170,plateY+54); ctx.lineTo(cx+170,plateY+54); ctx.stroke();
  ctx.restore();

  // Countdown bar + label — below the ad block
  const belowAd=adCY + adH/2 + 18;
  if(AD_BREAK_DURATION>0){
    const pct=remaining/AD_BREAK_DURATION;
    const bw2=280, bh2=4, bx2=cx-bw2/2, by2=belowAd;
    ctx.fillStyle='rgba(0,40,80,0.7)'; ctx.fillRect(bx2,by2,bw2,bh2);
    ctx.fillStyle='rgba(0,120,180,0.6)'; ctx.shadowBlur=5; ctx.shadowColor='#0088cc';
    ctx.fillRect(bx2,by2,bw2*pct,bh2); ctx.shadowBlur=0;
    ctx.textAlign='center'; ctx.font='9px "Courier New"'; ctx.fillStyle='rgba(0,110,165,0.5)';
    ctx.fillText(`Auto-continue in ${Math.ceil(remaining/1000)}s`, cx, by2+16);
  }

  // CHOOSE YOUR MISSION button — locked for 5s to match intro button UX
  const AD_BTN_DELAY=5000;
  const adPct=Math.min(1,(elapsed)/AD_BTN_DELAY);
  const adReady=adPct>=1;
  const bw3=240, bh3=42, bx3=cx-bw3/2, by3=canvas.height-76;
  const bhov=adReady&&mouse.x>bx3&&mouse.x<bx3+bw3&&mouse.y>by3&&mouse.y<by3+bh3;
  ctx.save();
  roundRect(ctx,bx3,by3,bw3,bh3,8); ctx.clip();
  ctx.fillStyle='rgba(0,0,0,0.80)'; ctx.fillRect(bx3,by3,bw3,bh3);
  if(!adReady){
    // Fill bar
    ctx.fillStyle='rgba(0,80,40,0.55)'; ctx.fillRect(bx3,by3,bw3*adPct,bh3);
    const edgeX=bx3+bw3*adPct;
    const grad=ctx.createLinearGradient(edgeX-18,0,edgeX,0);
    grad.addColorStop(0,'rgba(0,255,136,0)');grad.addColorStop(1,'rgba(0,255,136,0.18)');
    ctx.fillStyle=grad; ctx.fillRect(Math.max(bx3,edgeX-18),by3,Math.min(18,bw3*adPct),bh3);
  } else if(bhov){
    ctx.fillStyle='#00ff88'; ctx.fillRect(bx3,by3,bw3,bh3);
  }
  ctx.restore();
  ctx.save();
  ctx.strokeStyle=`rgba(0,255,136,${0.2+0.8*adPct})`; ctx.lineWidth=2;
  ctx.shadowBlur=bhov?28:8*adPct; ctx.shadowColor='#00ff88';
  roundRect(ctx,bx3,by3,bw3,bh3,8); ctx.stroke(); ctx.shadowBlur=0;
  ctx.textAlign='center'; ctx.font='bold 14px "Courier New"';
  ctx.fillStyle=bhov?'#000':`rgba(0,255,136,${0.18+0.82*adPct})`;
  ctx.fillText('▶  CHOOSE YOUR MISSION', cx, by3+27); ctx.restore();
}

// ── Intro dispatcher ──────────────────────────────────────────
function drawIntro(){
  const fns=[_drawIntroSeq0,_drawIntroSeq1,_drawIntroSeq2,_drawIntroSeq3,_drawIntroSeq4,_drawIntroSeq5,_drawIntroSeq6];
  if(introSeq<fns.length) fns[introSeq]();
}

// ─── PORTAL SYSTEM ───────────────────────────────────────────────
function _activatePortal(px,py){
  portalActive=true;
  portalCountdown=5000;
  portalSelected=0;
  // Divide the world into 3 zones and pick one position from each so portals
  // are guaranteed to be spread across the play field rather than bunched together.
  const positions=[];
  const margin=180;
  const W=WORLD_W, H=WORLD_H;
  // Zone definitions: [xMin, xMax, yMin, yMax]
  const zones=[
    [margin,        W*0.38,        margin, H-margin],  // left third
    [W*0.38,        W*0.68,        margin, H-margin],  // centre third
    [W*0.68,        W-margin,      margin, H-margin],  // right third
  ];
  // Shuffle zones so portal indices don't always map to the same region
  for(let i=zones.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[zones[i],zones[j]]=[zones[j],zones[i]];}
  for(let i=0;i<3;i++){
    const [x1,x2,y1,y2]=zones[i];
    let x,y,att=0;
    do{
      x=rng(x1,x2);
      y=rng(y1,y2);
      att++;
    } while(att<100&&(circleVsObs(x,y,40)||dist(x,y,P.x,P.y)<250||positions.some(p=>dist(x,y,p.x,p.y)<200)));
    positions.push({x,y});
  }
  portalPositions=[{x:P.x,y:P.y},...positions];
}

function tickPortal(dt){
  if(!portalActive) return;
  portalCountdown-=dt*1000;
  if(portalCountdown<=0){
    _resolvePortal();
  }
}

function _resolvePortal(){
  if(!portalActive) return;
  const dest=portalPositions[portalSelected];
  if(dest){
    P.x=dest.x; P.y=dest.y;
    P.vx=0; P.vy=0;
    camX=clamp(P.x-canvas.width/2,0,Math.max(0,WORLD_W-canvas.width));
    camY=(gameMode==='timetrial'&&(ttLevel===1||ttLevel===3))?0:clamp(P.y-canvas.height/2,0,Math.max(0,WORLD_H-canvas.height));
    spawnParts(P.x,P.y,P.color,_pCount(24),5,7,500);
    _snapMouseToPlayer();
  }
  portalActive=false;
  portalPositions=[];
  SFX.confirm();
}

function drawPortals(){
  if(!portalActive) return;
  const now=Date.now()/1000;
  // Draw each portal in world space
  portalPositions.forEach((pos,i)=>{
    const sx=pos.x-camX, sy=pos.y-camY;
    const selected=(i===portalSelected);
    const pulse=0.6+0.4*Math.sin(now*3+i*1.4);
    ctx.save(); ctx.translate(sx,sy);
    // Dark filled backdrop — black core
    ctx.beginPath();ctx.arc(0,0,selected?36:28,0,Math.PI*2);
    ctx.fillStyle='rgba(0,0,0,0.72)';ctx.fill();
    // Outer ring — orange/yellow glow
    ctx.beginPath();ctx.arc(0,0,selected?38:30,0,Math.PI*2);
    ctx.strokeStyle=selected?`rgba(255,180,0,${0.95*pulse})`:`rgba(210,100,0,${0.65*pulse})`;
    ctx.lineWidth=selected?3.5:2;ctx.shadowBlur=selected?28:12;ctx.shadowColor=selected?'#ffaa00':'#ff6600';
    ctx.stroke();ctx.shadowBlur=0;
    // Inner swirl — rotating arcs in amber/yellow
    ctx.rotate(now*(i%2===0?1.1:-0.9));
    for(let r=8;r<=22;r+=7){
      ctx.beginPath();ctx.arc(0,0,r,0,Math.PI*1.6);
      ctx.strokeStyle=selected?`rgba(255,220,60,${0.75*pulse})`:`rgba(220,130,0,${0.45*pulse})`;
      ctx.lineWidth=1.5;ctx.stroke();
    }
    // Center dot — bright yellow-white
    ctx.beginPath();ctx.arc(0,0,4,0,Math.PI*2);
    ctx.fillStyle=selected?'#ffe040':'#ff8800';ctx.shadowBlur=16;ctx.shadowColor=selected?'#ffdd00':'#ff6600';ctx.fill();ctx.shadowBlur=0;
    // Ghost player craft at origin portal (index 0)
    if(i===0){
      ctx.save();
      ctx.globalAlpha=selected?0.55:0.35;
      drawPlayerCraft(0,0,P.aim,P.size*0.9,P.color,lighten(P.color,90),P.rotor,P.hp/P.maxHp);
      ctx.globalAlpha=1;
      ctx.restore();
    }
    ctx.restore();
    // Label
    ctx.save();ctx.textAlign='center';
    ctx.font=`bold ${selected?12:10}px "Courier New"`;
    ctx.fillStyle=selected?'#ffcc00':'rgba(220,140,0,0.75)';
    ctx.shadowBlur=selected?10:0;ctx.shadowColor='#ffaa00';
    ctx.fillText(i===0?'[0] ORIGIN':`[${i}] PORTAL ${i}`,sx,sy-46);
    ctx.shadowBlur=0;ctx.restore();
  });
  // Countdown overlay
  const sec=Math.max(0,portalCountdown/1000);
  ctx.textAlign='center';
  ctx.font='bold 22px "Courier New"';
  ctx.fillStyle=`rgba(255,180,0,${0.88+0.12*Math.sin(Date.now()/180)})`;
  ctx.shadowBlur=18;ctx.shadowColor='#ff8800';
  ctx.fillText(`⬡ PORTAL ACTIVE — TELEPORTING IN ${sec.toFixed(1)}s`,canvas.width/2,canvas.height/2);
  ctx.font='12px "Courier New"';ctx.fillStyle='rgba(255,200,100,0.65)';ctx.shadowBlur=0;
  ctx.fillText('CLICK A PORTAL TO CONFIRM  ·  SPACE / RIGHT-CLICK to cycle',canvas.width/2,canvas.height/2+26);
  ctx.textAlign='left';
}

// ─── HULL ALARM ──────────────────────────────────────────────────
// Short anxious beep that fires faster and higher as HP approaches zero.
// Active when HP < 35% and playing. Respects mute.
function tickHullBeep(now){
  if(gameState!=='playing'||!P.alive||P.hp/P.maxHp>=0.35) return;
  const hpPct=Math.max(0,P.hp/P.maxHp); // 0 → 0.35
  // Interval shrinks from 2000ms at 35% → 220ms near 0%
  const interval=220+Math.round(1780*(hpPct/0.35));
  if(now-lastHullBeepMs<interval) return;
  lastHullBeepMs=now;
  // Pitch rises from ~440 Hz at 35% → ~900 Hz near 0%
  const freq=440+Math.round(460*(1-hpPct/0.35));
  // Volume rises with urgency
  const vol=0.07+0.08*(1-hpPct/0.35);
  // sine wave + long attack + long decay = smooth pulsing beep, no click
  beep(freq,'sine',0.18,vol,0.22);
}

// ─── MAIN LOOP ────────────────────────────────────────────────────
function loop(now){
  requestAnimationFrame(loop);
  const dt=clamp((now-lastTime)/1000,0,0.05);lastTime=now;
  if(screenLockMs>0) screenLockMs-=dt*1000;
  Music.tick();

  // Space key — menu advances + pause resume
  if(K['Space']){
    if(gameState==='intro'){K['Space']=false;if(introMs()>=INTRO_BTN_DELAY)advanceIntro();return;}
    if(gameState==='adBreak'&&Date.now()-adBreakStart>=5000){K['Space']=false;leaveAdBreak();return;}
    // Portal: Space cycles to next portal and snaps camera to preview destination
    if(portalActive){
      K['Space']=false;
      portalSelected=(portalSelected+1)%portalPositions.length;
      const prev=portalPositions[portalSelected];
      camX=clamp(prev.x-canvas.width/2,0,Math.max(0,WORLD_W-canvas.width));
      camY=(gameMode==='timetrial'&&(ttLevel===1||ttLevel===3))?0:clamp(prev.y-canvas.height/2,0,Math.max(0,WORLD_H-canvas.height));
      SFX.select();return;
    }
    if(gameState==='levelEditor'){K['Space']=false;return;}
    if(gameState==='levelSavePrompt'){K['Space']=false;return;}
    if(gameState==='levelSetup'){K['Space']=false;gameState='customSelect';SFX.select();return;}
    if(gameState==='customSelect'){K['Space']=false;gameState='start';SFX.select();return;}
    if(gameState==='customResult'){K['Space']=false;gameState='customSelect';SFX.select();return;}
    if(gameState==='paused'&&screenLockMs<=0){K['Space']=false;gameState='playing';lastTime=performance.now();}
    else if(gameState==='start'){K['Space']=false;gameMode='battle';_loadHangar();startGame();}
    else if(gameState==='ttLevelSelect'){K['Space']=false;gameState='start';SFX.select();}
    else if(gameState==='hangar'){K['Space']=false;gameState='start';SFX.select();}
    else if(gameState==='droneSelect'){K['Space']=false;SFX.confirm();gameState='colorSelect';}
    else if(gameState==='colorSelect'){K['Space']=false;P.craftIdx=selectedCraft;SFX.confirm();startGame();}
    else if(gameState==='waveClear'&&screenLockMs<=0&&gameMode==='battle'){K['Space']=false;spawnWave(wave);wavePause=0;gameState='playing';}
    else if(gameState==='gameover'){K['Space']=false;_returnToStart();}
    else if(gameState==='victory'||gameState==='timeTrialResult'||gameState==='ctResult'){K['Space']=false;WORLD_W=2600;WORLD_H=1700;ttLevel=1;nukes=[];jrCaptives=[];jrCarrying=-1;tngPads=[];tngSeq=1;tngOnPad=-1;tngHoldMs=0;gameMode='battle';gameState='start';}
    else if(gameState==='briefing'){K['Space']=false;const b=BRIEFINGS[activeBriefing];if(b)b.launchFn();}
    else if(gameState==='ctLevelUp'){K['Space']=false;ctLevelUpMs=0;} // skip the countdown
  }

  if(K['Escape']){
    if(gameState==='levelEditor'){K['Escape']=false;gameState='customSelect';SFX.select();return;}
  }

  // Level editor camera panning
  if(gameState==='levelEditor'){
    const panSpd=8;
    if(K['ArrowLeft']||K['KeyA'])editorCamX=Math.max(0,editorCamX-panSpd);
    if(K['ArrowRight']||K['KeyD'])editorCamX=Math.min(Math.max(0,editorWorldW-canvas.width+180),editorCamX+panSpd);
    if(K['ArrowUp']||K['KeyW'])editorCamY=Math.max(0,editorCamY-panSpd);
    if(K['ArrowDown']||K['KeyS'])editorCamY=Math.min(Math.max(0,editorWorldH-canvas.height),editorCamY+panSpd);
  }

  // Cursor: hide during active play only when using Sawtooth (which has its own arc indicator)
  canvas.style.cursor=(gameState==='playing')?'none':'pointer';

  ctx.save();
  if(shake>0){ctx.translate((Math.random()-0.5)*shake,(Math.random()-0.5)*shake);shake*=0.77;if(shake<0.4)shake=0;}

  if(gameState==='intro'){
    drawIntro();
  } else if(gameState==='adBreak'){
    drawAdBreakScreen();
    if(AD_BREAK_DURATION>0 && Date.now()-adBreakStart>=AD_BREAK_DURATION){ leaveAdBreak(); }
  } else if(gameState==='start'){
    drawStartScreen();
  } else if(gameState==='ttLevelSelect'){
    drawTTLevelSelect();
  } else if(gameState==='hangar'){
    drawHangarScreen();
  } else if(gameState==='droneSelect'){
    drawDroneSelectScreen();
  } else if(gameState==='colorSelect'){
    drawColorSelectScreen();
  } else if(gameState==='playing'){
    if(portalActive){
      // Freeze all game ticks — only tick portal countdown and particles
      tickParticles(dt);tickPortal(dt);
      drawWorld();drawObstacles();drawParticles();pickups.forEach(drawPickup);drawMines();drawFaradayCages();drawRockets();drawGrenades();drawGravityWells();drawSeekers();drawBoomerangs();drawTractorBeam();drawHazards();drawFractals();drawBullets();drawEnemies();if(ttLevel===2)drawNukes();if(ttLevel===4)drawJRRescue();if(ttLevel===5)drawTNG();drawPlayer();drawCustomObjectives();
      if(gameMode==='timetrial') drawFinishLine();
      drawHUD();drawMinimap();drawCrosshair();drawTouchSticks();drawMiniMe();drawPortals();drawCustomTransition();
    } else {
    tickPlayer(dt,now);tickCarrierDrones(dt,now);tickEnemies(dt,now);tickMiniMe(dt,now);tickBullets(dt);tickMines(dt);tickFaradayCages(dt);tickRockets(dt);tickGrenades(dt);tickGravityWells(dt);tickSeekers(dt,now);tickBoomerangs(dt);tickHazards(dt,now);tickFractals(dt);tickParticles(dt);tickPickups(dt);tickLaserFlash(dt);tickLeechFlash(dt);tickShockwaveFlash(dt);tickPortal(dt);if(ttLevel===2)tickNukes(dt);if(ttLevel===4)tickJRRescue(dt);if(ttLevel===5)tickTNG(dt);tickHullBeep(now);checkCollisions();tickCustomWinCondition(dt);tickCustomTransition(dt);tickCustomObjectivePickup();
    if(gameMode==='combattraining'){
      ctNextPickupMs-=dt*1000;
      if(ctNextPickupMs<=0){
        _ctSchedulePickup();
        const W=WORLD_W,H=WORLD_H;
        let px,py,att=0;
        do{px=rng(W*0.15,W*0.85);py=rng(W*0.15,H*0.85);att++;}while(att<60&&circleVsObs(px,py,24));
        const types=['battery','health','medkit','shield','weapon'];
        const t=types[Math.floor(Math.random()*types.length)];
        const idx=pickups.length;
        spawnPickup(px,py,t,false);
        if(pickups.length>idx) pickups[pickups.length-1].dropTimer=18000;
      }
    }
    drawWorld();drawObstacles();drawParticles();pickups.forEach(drawPickup);drawMines();drawFaradayCages();drawRockets();drawGrenades();drawGravityWells();drawSeekers();drawBoomerangs();drawTractorBeam();drawHazards();drawFractals();drawBullets();drawEnemies();if(ttLevel===2)drawNukes();if(ttLevel===4)drawJRRescue();if(ttLevel===5)drawTNG();drawPlayer();drawCustomObjectives();drawCarrierDrones();
    if(slipstreamMs>0&&P.alive&&CRAFTS[P.craftIdx].id==='skirmisher'){
      const sx=P.x-camX, sy=P.y-camY;
      const alphaBase=slipstreamMs/400;
      for(let g=1;g<=3;g++){
        ctx.globalAlpha=alphaBase*(0.35-g*0.08);
        drawSkirmisher(sx-Math.cos(P.aim)*g*8,sy-Math.sin(P.aim)*g*8,P.aim,P.size,'#ff44aa',lighten('#ff44aa'),P.rotor,P.hp/P.maxHp);
      }
      ctx.globalAlpha=1;
    }
    if(gameMode==='timetrial') drawFinishLine();
    drawEMPFlash();drawLaserFlash();drawLeechFlash();drawShockwaveFlash();drawPortals();drawHUD();drawMinimap();drawCrosshair();drawTouchSticks();drawMiniMe();drawBossWarning(dt);drawCustomTransition();
    }
    if(!P.alive){
      mines.length=0;boomerangs.length=0;fractals.length=0;hazards.length=0;
      spawnParts(P.x,P.y,P.color,_pCount(35),7.5,9.5,1100);spawnParts(P.x,P.y,'#ffffff',_pCount(15),5,4,700);if(settings.screenShake)shake=30;
      if(jrCarrying>=0){jrCarrying=-1;P.spd=CRAFTS[P.craftIdx].spd;}
      saveHighScore(gameMode==='combattraining'?'combattraining':gameMode==='timetrial'?`timetrial_${ttLevel}`:'battle', score, Date.now()-gameStartTime);
      gameEndScore=(gameMode==='combattraining')?(ctTotalScore+score):score;
      gameEndDurationMs=Date.now()-gameStartTime;
      deathScreenEnteredAt=Date.now();
      gameState='gameover';
    } else if(gameMode==='combattraining'&&enemies.length===0){
      ctKillAndAdvance();
    } else if(gameMode==='battle'&&enemies.length===0){
      // Wave time bonuses
      const elapsed=(Date.now()-waveStartTime)/1000;
      if(elapsed<30){score+=500;weaponFlash={name:'WAVE BONUS +500',ms:2800};}
      else if(elapsed<60){score+=100;weaponFlash={name:'WAVE BONUS +100',ms:2800};}
      wave++;if(wave>TOTAL_WAVES){saveHighScore('battle',score,Date.now()-gameStartTime);gameState='victory';}else{gameState='waveClear';wavePause=3800;screenLockMs=2000;SFX.wave();}
    }
  } else if(gameState==='hallOfFame'){
    drawHallOfFame();
  } else if(gameState==='ctLevelUp'){
    tickCTLevelUp(dt);tickParticles(dt);
    drawWorld();drawObstacles();drawParticles();drawPlayer();
    drawCTLevelUp();
  } else if(gameState==='ctResult'){
    tickParticles(dt);drawWorld();drawParticles();drawCTResult();
  } else if(gameState==='timeTrialResult'){
    tickParticles(dt);drawWorld();drawParticles();drawTimeTrialResult();
  } else if(gameState==='paused'){
    // Frozen battlefield — no ticks, rotors spin via wall-clock
    const wallSpin=Date.now()/80;
    drawWorld(); drawObstacles(); drawParticles();
    pickups.forEach(drawPickup);
    drawBullets();
    for(const e of enemies){
      const sx=e.x-camX,sy=e.y-camY;
      if(sx<-90||sx>canvas.width+90||sy<-90||sy>canvas.height+90)continue;
      drawEnemyDrone(sx,sy,e.aim,e.size,e.color,e.accent,wallSpin*(enemies.indexOf(e)%2===0?1:-1.3),e.hp/e.maxHp);
      const bw=e.size*3.2,bh=5,bx=sx-bw/2,by=sy-e.size*2.4;
      ctx.fillStyle='rgba(0,0,0,0.7)';ctx.fillRect(bx-1,by-1,bw+2,bh+2);ctx.fillStyle='#111';ctx.fillRect(bx,by,bw,bh);
      const pct=e.hp/e.maxHp;ctx.fillStyle=pct>0.5?'#22ee88':pct>0.25?'#ffaa00':'#ff3333';ctx.fillRect(bx,by,bw*pct,bh);
    }
    if(P.alive){
      const sx=P.x-camX,sy=P.y-camY;
      drawPlayerCraft(sx,sy,P.aim,P.size,P.color,lighten(P.color,90),wallSpin,P.hp/P.maxHp);
    }
    drawHUD(); drawMinimap(); drawTouchSticks(); drawMiniMe(); drawLaserFlash();drawLeechFlash();drawShockwaveFlash();
    drawPauseScreen();
  } else if(gameState==='loadoutEdit'){
    const wallSpin=Date.now()/80;
    drawWorld();drawObstacles();drawParticles();pickups.forEach(drawPickup);drawBullets();
    for(const e of enemies){
      const sx=e.x-camX,sy=e.y-camY;
      if(sx<-90||sx>canvas.width+90||sy<-90||sy>canvas.height+90)continue;
      drawEnemyDrone(sx,sy,e.aim,e.size,e.color,e.accent,wallSpin*(enemies.indexOf(e)%2===0?1:-1.3),e.hp/e.maxHp);
    }
    if(P.alive){const sx=P.x-camX,sy=P.y-camY;drawPlayerCraft(sx,sy,P.aim,P.size,P.color,lighten(P.color,90),wallSpin,P.hp/P.maxHp);}
    drawLoadoutEdit();
  } else if(gameState==='waveClear'&&gameMode==='battle'){
    tickParticles(dt);tickPickups(dt);
    drawWorld();drawObstacles();drawParticles();pickups.forEach(drawPickup);drawPlayer();drawHUD();drawMinimap();
    drawWaveClearScreen();
    wavePause-=dt*1000;if(wavePause<=0){spawnWave(wave);gameState='playing';}
  } else if(gameState==='briefing'){
    drawBriefingScreen();
  } else if(gameState==='setup'){
    drawSetupScreen();
  } else if(gameState==='gameover'){
    tickParticles(dt);drawWorld();drawParticles();drawDeathScreen();
  } else if(gameState==='victory'){
    tickParticles(dt);drawWorld();drawParticles();drawVictoryScreen();
  } else if(gameState==='customResult'){
    drawCustomResult();
  } else if(gameState==='customSelect'){
    drawCustomSelect();
  } else if(gameState==='levelSetup'){
    drawLevelSetup();
  } else if(gameState==='levelEditor'){
    drawLevelEditor();
  } else if(gameState==='levelSavePrompt'){
    drawLevelSavePrompt();
  }

  ctx.restore();
  mouse.justDown=false;
}
requestAnimationFrame(loop);
</script>
</body>
</html>
