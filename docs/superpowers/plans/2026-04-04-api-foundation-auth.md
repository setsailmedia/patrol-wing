# API Foundation + Auth Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stand up a Laravel API with user auth, score submission, event tracking, and a client-side PW_API module that silently syncs game data to the server.

**Architecture:** Laravel 11 in `api/` directory alongside the game. Sanctum for API token auth. MySQL database. Three controllers (Auth, Score, Event). Client-side `PW_API` object in `index.php` wraps all fetch calls with silent-fail behavior. The game is fully playable offline -- the API is an optional sync layer.

**Tech Stack:** Laravel 11, PHP 8.2+, MySQL/MariaDB, Laravel Sanctum, Vanilla JS (fetch API)

---

### Task 1: Laravel project scaffold + Sanctum

**Files:**
- Create: `F:\PATROL WING\api\` (entire Laravel project)

This task creates the Laravel app and configures Sanctum for API token auth.

- [ ] **Step 1: Install Laravel**

```bash
cd "F:\PATROL WING" && composer create-project laravel/laravel api
```

- [ ] **Step 2: Install Sanctum**

```bash
cd "F:\PATROL WING\api" && composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

- [ ] **Step 3: Configure .env**

Edit `api/.env`:
```
APP_URL=http://localhost
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=patrol_wing
DB_USERNAME=root
DB_PASSWORD=
```

- [ ] **Step 4: Add HasApiTokens to User model**

Edit `api/app/Models/User.php`:
```php
<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['username', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
```

- [ ] **Step 5: Add username to users migration**

Edit `api/database/migrations/0001_01_01_000000_create_users_table.php`. In the `up()` method, add `username` after `id`:

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('username', 40)->unique();
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});
```

- [ ] **Step 6: Create database and run migrations**

```bash
cd "F:\PATROL WING\api" && php artisan migrate
```

- [ ] **Step 7: Configure Sanctum middleware**

Edit `api/bootstrap/app.php` to ensure Sanctum middleware is applied to API routes. In Laravel 11, this is handled via `api.php` route file automatically with `auth:sanctum` middleware on protected routes.

- [ ] **Step 8: Commit**

```bash
cd "F:\PATROL WING" && git add api/ && git commit -m "feat: Laravel 11 scaffold with Sanctum, username on users table"
```

---

### Task 2: Database migrations for scores, game_events, custom_levels, loadouts

**Files:**
- Create: `api/database/migrations/xxxx_create_scores_table.php`
- Create: `api/database/migrations/xxxx_create_game_events_table.php`
- Create: `api/database/migrations/xxxx_create_custom_levels_table.php`
- Create: `api/database/migrations/xxxx_create_loadouts_table.php`

- [ ] **Step 1: Create scores migration**

```bash
cd "F:\PATROL WING\api" && php artisan make:migration create_scores_table
```

Edit the generated migration:

```php
public function up(): void
{
    Schema::create('scores', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('mode', 30);
        $table->unsignedInteger('score');
        $table->unsignedInteger('duration_ms');
        $table->unsignedTinyInteger('wave_reached')->default(0);
        $table->string('craft_id', 30);
        $table->string('level_name', 100)->nullable();
        $table->timestamp('created_at')->useCurrent();

        $table->index(['mode', 'score']);
        $table->index('user_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('scores');
}
```

- [ ] **Step 2: Create game_events migration**

```bash
php artisan make:migration create_game_events_table
```

```php
public function up(): void
{
    Schema::create('game_events', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('event_type', 50);
        $table->json('payload')->nullable();
        $table->unsignedBigInteger('client_timestamp')->nullable();
        $table->timestamp('created_at')->useCurrent();

        $table->index('user_id');
        $table->index('event_type');
    });
}

public function down(): void
{
    Schema::dropIfExists('game_events');
}
```

- [ ] **Step 3: Create custom_levels migration (placeholder)**

```bash
php artisan make:migration create_custom_levels_table
```

```php
public function up(): void
{
    Schema::create('custom_levels', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('pack_name', 100);
        $table->json('level_data');
        $table->boolean('is_public')->default(false);
        $table->unsignedInteger('downloads')->default(0);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('custom_levels');
}
```

- [ ] **Step 4: Create loadouts migration (placeholder)**

```bash
php artisan make:migration create_loadouts_table
```

```php
public function up(): void
{
    Schema::create('loadouts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('craft_id', 30);
        $table->json('weapons');
        $table->timestamps();

        $table->unique(['user_id', 'craft_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('loadouts');
}
```

- [ ] **Step 5: Run migrations**

```bash
php artisan migrate
```

- [ ] **Step 6: Commit**

```bash
cd "F:\PATROL WING" && git add api/ && git commit -m "feat: migrations for scores, game_events, custom_levels, loadouts"
```

---

### Task 3: Eloquent models

**Files:**
- Create: `api/app/Models/Score.php`
- Create: `api/app/Models/GameEvent.php`
- Create: `api/app/Models/CustomLevel.php`
- Create: `api/app/Models/Loadout.php`
- Modify: `api/app/Models/User.php`

- [ ] **Step 1: Create Score model**

```bash
cd "F:\PATROL WING\api" && php artisan make:model Score
```

Edit `api/app/Models/Score.php`:

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'mode', 'score', 'duration_ms', 'wave_reached', 'craft_id', 'level_name'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 2: Create GameEvent model**

```bash
php artisan make:model GameEvent
```

Edit `api/app/Models/GameEvent.php`:

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'event_type', 'payload', 'client_timestamp'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 3: Create CustomLevel and Loadout models**

```bash
php artisan make:model CustomLevel
php artisan make:model Loadout
```

Edit `api/app/Models/CustomLevel.php`:
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomLevel extends Model
{
    protected $fillable = ['user_id', 'pack_name', 'level_data', 'is_public', 'downloads'];

    protected function casts(): array
    {
        return ['level_data' => 'array', 'is_public' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Edit `api/app/Models/Loadout.php`:
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loadout extends Model
{
    protected $fillable = ['user_id', 'craft_id', 'weapons'];

    protected function casts(): array
    {
        return ['weapons' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 4: Add relationships to User model**

Add to `api/app/Models/User.php`:

```php
    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function gameEvents(): HasMany
    {
        return $this->hasMany(GameEvent::class);
    }

    public function customLevels(): HasMany
    {
        return $this->hasMany(CustomLevel::class);
    }

    public function loadouts(): HasMany
    {
        return $this->hasMany(Loadout::class);
    }
```

Add `use Illuminate\Database\Eloquent\Relations\HasMany;` to the imports.

- [ ] **Step 5: Commit**

```bash
cd "F:\PATROL WING" && git add api/ && git commit -m "feat: Eloquent models — Score, GameEvent, CustomLevel, Loadout + User relationships"
```

---

### Task 4: Auth controller + routes

**Files:**
- Create: `api/app/Http/Controllers/AuthController.php`
- Modify: `api/routes/api.php`

- [ ] **Step 1: Create AuthController**

```bash
cd "F:\PATROL WING\api" && php artisan make:controller AuthController
```

Edit `api/app/Http/Controllers/AuthController.php`:

```php
<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|min:3|max:40|regex:/^[a-zA-Z0-9_]+$/|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $token = $user->createToken('game')->plainTextToken;

        return response()->json([
            'user' => ['id' => $user->id, 'username' => $user->username, 'email' => $user->email],
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        $token = $user->createToken('game')->plainTextToken;

        return response()->json([
            'user' => ['id' => $user->id, 'username' => $user->username, 'email' => $user->email],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'scores_count' => $user->scores()->count(),
        ]);
    }
}
```

- [ ] **Step 2: Set up API routes**

Replace `api/routes/api.php`:

```php
<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth (rate limited)
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Scores
        Route::post('/scores', [ScoreController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/scores/me', [ScoreController::class, 'mine']);

        // Events
        Route::post('/events', [EventController::class, 'store'])->middleware('throttle:60,1');
        Route::post('/events/batch', [EventController::class, 'batch'])->middleware('throttle:60,1');
    });

    // Public leaderboard
    Route::get('/scores', [ScoreController::class, 'index'])->middleware('throttle:120,1');
});
```

- [ ] **Step 3: Commit**

```bash
cd "F:\PATROL WING" && git add api/ && git commit -m "feat: AuthController — register, login, logout, me + API routes with rate limiting"
```

---

### Task 5: Score and Event controllers

**Files:**
- Create: `api/app/Http/Controllers/ScoreController.php`
- Create: `api/app/Http/Controllers/EventController.php`

- [ ] **Step 1: Create ScoreController**

```bash
cd "F:\PATROL WING\api" && php artisan make:controller ScoreController
```

Edit `api/app/Http/Controllers/ScoreController.php`:

```php
<?php
namespace App\Http\Controllers;

use App\Models\Score;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|string|in:battle,timetrial,combattraining,custom',
            'score' => 'required|integer|min:0',
            'duration_ms' => 'required|integer|min:0',
            'wave_reached' => 'integer|min:0|max:255',
            'craft_id' => 'required|string|max:30',
            'level_name' => 'nullable|string|max:100',
        ]);

        $score = $request->user()->scores()->create($validated);

        return response()->json($score, 201);
    }

    public function index(Request $request)
    {
        $mode = $request->query('mode');
        $limit = min(100, max(1, (int) ($request->query('limit', 20))));

        $query = Score::query()
            ->join('users', 'scores.user_id', '=', 'users.id')
            ->select('scores.id', 'users.username', 'scores.mode', 'scores.score', 'scores.duration_ms', 'scores.craft_id', 'scores.created_at')
            ->orderByDesc('scores.score');

        if ($mode) {
            $query->where('scores.mode', $mode);
        }

        return response()->json($query->limit($limit)->get());
    }

    public function mine(Request $request)
    {
        $limit = min(100, max(1, (int) ($request->query('limit', 20))));

        $scores = $request->user()->scores()
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        return response()->json($scores);
    }
}
```

- [ ] **Step 2: Create EventController**

```bash
php artisan make:controller EventController
```

Edit `api/app/Http/Controllers/EventController.php`:

```php
<?php
namespace App\Http\Controllers;

use App\Models\GameEvent;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_type' => 'required|string|max:50',
            'payload' => 'nullable|array',
            'timestamp' => 'nullable|integer',
        ]);

        $event = $request->user()->gameEvents()->create([
            'event_type' => $validated['event_type'],
            'payload' => $validated['payload'] ?? null,
            'client_timestamp' => $validated['timestamp'] ?? null,
        ]);

        return response()->json(['id' => $event->id], 201);
    }

    public function batch(Request $request)
    {
        $request->validate([
            '*.event_type' => 'required|string|max:50',
            '*.payload' => 'nullable|array',
            '*.timestamp' => 'nullable|integer',
        ]);

        $events = $request->all();
        if (count($events) > 100) {
            return response()->json(['error' => 'Max 100 events per batch'], 422);
        }

        $userId = $request->user()->id;
        $rows = [];
        $now = now();

        foreach ($events as $e) {
            $rows[] = [
                'user_id' => $userId,
                'event_type' => $e['event_type'],
                'payload' => isset($e['payload']) ? json_encode($e['payload']) : null,
                'client_timestamp' => $e['timestamp'] ?? null,
                'created_at' => $now,
            ];
        }

        GameEvent::insert($rows);

        return response()->json(['count' => count($rows)], 201);
    }
}
```

- [ ] **Step 3: Commit**

```bash
cd "F:\PATROL WING" && git add api/ && git commit -m "feat: ScoreController + EventController — store, leaderboard, batch events"
```

---

### Task 6: Client-side PW_API module

**Files:**
- Modify: `F:\PATROL WING\index.php`

- [ ] **Step 1: Add PW_API object**

Find the game's module-level variable declarations (near the top, around line 40). After the `editorNameInput` reference, add the PW_API module:

```javascript
const PW_API={
  token:null,user:null,baseUrl:'/api/v1',eventQueue:[],online:false,
  _init(){try{this.token=localStorage.getItem('pw_api_token');if(this.token)this.checkAuth();}catch(e){}},
  async _req(method,path,body){
    try{
      const h={'Content-Type':'application/json'};
      if(this.token)h['Authorization']='Bearer '+this.token;
      const res=await fetch(this.baseUrl+path,{method,headers:h,body:body?JSON.stringify(body):undefined});
      if(!res.ok)throw new Error(res.status);
      return await res.json();
    }catch(e){return null;}
  },
  async register(username,email,pw,pwConfirm){
    const r=await this._req('POST','/auth/register',{username,email,password:pw,password_confirmation:pwConfirm});
    if(r&&r.token){this.token=r.token;this.user=r.user;this.online=true;try{localStorage.setItem('pw_api_token',r.token);}catch(e){}}
    return r;
  },
  async login(email,pw){
    const r=await this._req('POST','/auth/login',{email,password:pw});
    if(r&&r.token){this.token=r.token;this.user=r.user;this.online=true;try{localStorage.setItem('pw_api_token',r.token);}catch(e){}}
    return r;
  },
  async logout(){
    await this._req('POST','/auth/logout');
    this.token=null;this.user=null;this.online=false;
    try{localStorage.removeItem('pw_api_token');}catch(e){}
    this.flushEvents();
  },
  async checkAuth(){
    const r=await this._req('GET','/auth/me');
    if(r&&r.id){this.user=r;this.online=true;}
    else{this.token=null;this.user=null;this.online=false;try{localStorage.removeItem('pw_api_token');}catch(e){}}
    return r;
  },
  queueEvent(type,payload){
    this.eventQueue.push({event_type:type,payload:payload||{},timestamp:Date.now()});
  },
  async flushEvents(){
    if(!this.token||this.eventQueue.length===0)return;
    const batch=[...this.eventQueue];this.eventQueue=[];
    const r=await this._req('POST','/events/batch',batch);
    if(!r)this.eventQueue.push(...batch);
  },
  async saveScore(data){
    if(!this.token)return null;
    return this._req('POST','/scores',data);
  },
  async getLeaderboard(mode,limit){return this._req('GET',`/scores?mode=${mode}&limit=${limit||20}`);},
  async getMyScores(limit){return this._req('GET',`/scores/me?limit=${limit||20}`);},
};
PW_API._init();
```

- [ ] **Step 2: Add event queue calls at gameplay breakpoints**

Search for `saveHighScore` calls (these are the natural score-save points). After each `saveHighScore` call, add:

```javascript
PW_API.saveScore({mode:...,score:...,duration_ms:...,wave_reached:...,craft_id:CRAFTS[P.craftIdx].id});
PW_API.flushEvents();
```

Read each `saveHighScore` call to determine the correct mode/score/duration values. There should be calls in:
- Battle victory
- Time trial result
- Combat training result
- Custom level result

Also add `PW_API.queueEvent()` calls at key gameplay points:
- `startBattle()`: `PW_API.queueEvent('game_started',{mode:'battle',craft:CRAFTS[P.craftIdx].id});`
- `startCombatTraining()`: same pattern with mode 'combattraining'
- `startTimeTrial()` / `startNukeDisarm()` / `startDanceBirdie()` etc.: mode 'timetrial'
- `loadCustomLevel()`: `PW_API.queueEvent('game_started',{mode:'custom',level:levelData.name});`
- `spawnWave()`: `PW_API.queueEvent('wave_cleared',{wave:n-1,score,kills:P.kills});`
- Game over (player death): `PW_API.queueEvent('player_defeated',{mode:gameMode,wave,score});`

- [ ] **Step 3: Commit**

```bash
cd "F:\PATROL WING" && git add index.php && git commit -m "feat: PW_API client module — auth, scores, event queue, gameplay integration"
```

---

### Task 7: Account screen UI

**Files:**
- Modify: `F:\PATROL WING\index.php`

- [ ] **Step 1: Add hidden HTML inputs for account forms**

Find the `editorNameInput` element (around line 26). After it, add:

```html
<input type="email" id="accountEmail" style="position:absolute;opacity:0;pointer-events:none;width:1px;height:1px;top:0;left:0;" maxlength="255">
<input type="password" id="accountPw" style="position:absolute;opacity:0;pointer-events:none;width:1px;height:1px;top:0;left:0;" maxlength="128">
<input type="text" id="accountUsername" style="position:absolute;opacity:0;pointer-events:none;width:1px;height:1px;top:0;left:0;" maxlength="40">
```

Add JS references after `editorNameInput`:

```javascript
const accountEmail=document.getElementById('accountEmail');
const accountPw=document.getElementById('accountPw');
const accountUsername=document.getElementById('accountUsername');
```

- [ ] **Step 2: Add account state variables**

Near other module-level state variables:

```javascript
let accountTab=0; // 0=login, 1=register
let accountError='';
let accountLoading=false;
```

- [ ] **Step 3: Add account indicator to start screen**

In `drawStartScreen()`, after the sound toggle rendering, add:

```javascript
  // Account indicator
  const aiX=W-_stPad-_stW,aiY=_stPad+_stH+8;
  ctx.textAlign='right';ctx.font='10px "Courier New"';
  if(PW_API.online&&PW_API.user){
    ctx.fillStyle='rgba(0,200,100,0.7)';
    ctx.fillText(`${PW_API.user.username} (ONLINE)`,W-_stPad,aiY+10);
  } else {
    ctx.fillStyle='rgba(100,140,180,0.4)';
    ctx.fillText('OFFLINE — click to log in',W-_stPad,aiY+10);
  }
  ctx.textAlign='left';
```

Add click handler in the start screen click section:

```javascript
    // Account indicator click
    const aiY=_stPad+_stH+8;
    if(mouse.x>W*0.6&&mouse.y>aiY&&mouse.y<aiY+20){
      accountError='';accountTab=0;gameState='account';SFX.select();return;
    }
```

(Use approximate hit area covering the right side of the screen near the indicator.)

- [ ] **Step 4: Add drawAccountScreen() function**

Add after other draw functions. This renders login/register forms when logged out, and account info when logged in. Uses hidden HTML inputs for text fields, `_btn()` for buttons. Shows `accountError` in red below the form. BACK button at bottom-left.

The function is large (~80 lines) and uses the same canvas field + hidden input pattern as `drawLevelSetup()` for the name field. The implementer should read `drawLevelSetup()` for the pattern and replicate it for email, password, and username fields.

Key elements:
- Logged out: two tabs (LOGIN / REGISTER) rendered as `_btn()` toggles
- LOGIN tab: email field, password field, SUBMIT button
- REGISTER tab: username field, email field, password field, confirm password field, SUBMIT button
- Logged in: username, email, created date, scores count, LOGOUT button
- Error text in red below form
- BACK button at bottom-left

- [ ] **Step 5: Add account click handler**

Handle tab switches, field focus (show/position hidden input on click), SUBMIT (call `PW_API.login()` or `PW_API.register()`), LOGOUT, BACK.

On SUBMIT for login:
```javascript
accountLoading=true;accountError='';
const r=await PW_API.login(accountEmail.value,accountPw.value);
accountLoading=false;
if(!r) accountError='Login failed — check credentials';
else gameState='start';
```

On SUBMIT for register:
```javascript
accountLoading=true;accountError='';
const r=await PW_API.register(accountUsername.value,accountEmail.value,accountPw.value,accountPw.value);
accountLoading=false;
if(!r) accountError='Registration failed — try different username/email';
else gameState='start';
```

- [ ] **Step 6: Wire into render loop and key handlers**

Add `} else if(gameState==='account'){ drawAccountScreen(); }` to the render switch.

Add ESC handler: `if(gameState==='account'){K['Escape']=false;gameState='start';SFX.select();return;}`

- [ ] **Step 7: Commit**

```bash
cd "F:\PATROL WING" && git add index.php && git commit -m "feat: account screen — login, register, profile, ONLINE indicator on start screen"
```

---

## Unresolved Questions

None.
