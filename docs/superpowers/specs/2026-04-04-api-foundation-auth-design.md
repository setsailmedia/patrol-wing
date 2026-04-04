# API Foundation + Auth - Design Spec
**Date:** 2026-04-04
**Scope:** Laravel API scaffold, user registration/login with Sanctum tokens, scores table, game events tracking, client-side PW_API module. This is sub-project 1 of the server-side API. Future specs cover: (2) Scores + Analytics, (3) Levels + Sharing, (4) Loadouts + Craft Config, (5) Full Client Integration.

---

## Overview

A Laravel API deployed alongside the existing game client on the same server. Provides user auth (register/login/token), score submission, and event tracking. The game client treats the API as optional -- all calls are fire-and-forget with localStorage fallback. Gameplay never blocks on network requests.

---

## Project Structure

```
F:\PATROL WING\
  index.php              (game client, unchanged)
  api/                   (Laravel 11 app)
    app/
      Http/
        Controllers/
          AuthController.php
          ScoreController.php
          EventController.php
      Models/
        User.php
        Score.php
        GameEvent.php
    config/
    database/
      migrations/
        create_users_table.php        (Laravel default)
        create_personal_access_tokens_table.php  (Sanctum)
        create_scores_table.php
        create_game_events_table.php
    routes/
      api.php
    .env
    artisan
```

Web server routes `/api/*` to the Laravel app. The game client at root is unaffected.

---

## Database Schema

### users (Laravel default + Sanctum)
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
username        VARCHAR(40) UNIQUE NOT NULL
email           VARCHAR(255) UNIQUE NOT NULL
password        VARCHAR(255) NOT NULL
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### personal_access_tokens (Sanctum default)
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
tokenable_type  VARCHAR(255)
tokenable_id    BIGINT UNSIGNED
name            VARCHAR(255)
token           VARCHAR(64) UNIQUE
abilities       TEXT NULLABLE
last_used_at    TIMESTAMP NULLABLE
expires_at      TIMESTAMP NULLABLE
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### scores
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
user_id         BIGINT UNSIGNED NOT NULL (FK users.id)
mode            VARCHAR(30) NOT NULL (battle, timetrial, combattraining, custom)
score           INT UNSIGNED NOT NULL
duration_ms     INT UNSIGNED NOT NULL
wave_reached    TINYINT UNSIGNED DEFAULT 0
craft_id        VARCHAR(30) NOT NULL
level_name      VARCHAR(100) NULLABLE
created_at      TIMESTAMP

INDEX idx_scores_mode_score (mode, score DESC)
INDEX idx_scores_user (user_id)
```

### game_events
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
user_id         BIGINT UNSIGNED NOT NULL (FK users.id)
event_type      VARCHAR(50) NOT NULL
payload         JSON NULLABLE
client_timestamp BIGINT UNSIGNED NULLABLE
created_at      TIMESTAMP

INDEX idx_events_user (user_id)
INDEX idx_events_type (event_type)
```

**Event types:** `game_started`, `player_defeated`, `wave_cleared`, `level_beat`, `level_ended`, `level_ended_early`, `weapon_unlocked`, `enemy_killed`, `craft_selected`, `loadout_changed`, `custom_level_played`, `custom_level_created`

**Payload examples:**
- `game_started`: `{"mode":"battle","craft":"phantom"}`
- `wave_cleared`: `{"wave":3,"score":4200,"enemies_killed":12,"time_elapsed_ms":45000}`
- `enemy_killed`: `{"enemy_type":"scout","weapon":"grapple","wave":2}`
- `level_beat`: `{"level_name":"Ambush Alley","win_condition":"killAll","score":3800}`

### Placeholder tables (created now, populated in future specs)

### custom_levels
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
user_id         BIGINT UNSIGNED NOT NULL (FK users.id)
pack_name       VARCHAR(100) NOT NULL
level_data      JSON NOT NULL
is_public       BOOLEAN DEFAULT FALSE
downloads       INT UNSIGNED DEFAULT 0
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### loadouts
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
user_id         BIGINT UNSIGNED NOT NULL (FK users.id)
craft_id        VARCHAR(30) NOT NULL
weapons         JSON NOT NULL
created_at      TIMESTAMP
updated_at      TIMESTAMP

UNIQUE INDEX idx_loadout_user_craft (user_id, craft_id)
```

---

## API Endpoints

All endpoints prefixed with `/api/v1`.

### Auth

```
POST   /auth/register     { username, email, password, password_confirmation }
  -> 201 { user: { id, username, email }, token: "..." }

POST   /auth/login        { email, password }
  -> 200 { user: { id, username, email }, token: "..." }

POST   /auth/logout       [token required]
  -> 200 { message: "Logged out" }

GET    /auth/me            [token required]
  -> 200 { id, username, email, created_at, scores_count }
```

### Scores

```
POST   /scores            [token required]
  { mode, score, duration_ms, wave_reached, craft_id, level_name? }
  -> 201 { id, ... }

GET    /scores             ?mode=battle&limit=20
  -> 200 [{ id, username, score, duration_ms, craft_id, created_at }, ...]

GET    /scores/me          [token required] ?limit=20
  -> 200 [{ id, mode, score, duration_ms, wave_reached, craft_id, created_at }, ...]
```

### Events

```
POST   /events            [token required]
  { event_type, payload, timestamp? }
  -> 201 { id }

POST   /events/batch      [token required]
  [{ event_type, payload, timestamp }, ...]
  -> 201 { count: N }
```

### Validation Rules

- `username`: required, 3-40 chars, alphanumeric + underscore, unique
- `email`: required, valid email, unique
- `password`: required, min 8 chars, confirmed (register only)
- `mode`: required, in [battle, timetrial, combattraining, custom]
- `score`: required, integer, min 0
- `event_type`: required, max 50 chars
- `payload`: optional, valid JSON, max 2KB
- Batch events: max 100 per request

### Rate Limiting

- Auth endpoints: 10 requests/minute per IP
- Score submission: 30 requests/minute per user
- Event batch: 60 requests/minute per user
- Leaderboard reads: 120 requests/minute per IP

---

## Client-Side Module (PW_API)

Added to `index.php` as a module-level object. All server communication goes through this. The game code never calls `fetch()` directly.

```javascript
const PW_API = {
  token: null,
  user: null,
  baseUrl: '/api/v1',
  eventQueue: [],
  online: false,

  async _req(method, path, body) { ... }, // silent fail, returns null
  async register(username, email, pw) { ... },
  async login(email, pw) { ... },
  async logout() { ... },
  async checkAuth() { ... },
  queueEvent(type, payload) { ... },      // synchronous, no network
  async flushEvents() { ... },            // batch POST, re-queue on fail
  async saveScore(data) { ... },
  async getLeaderboard(mode, limit) { ... },
  async getMyScores(limit) { ... },
};
```

### Principles
- Every async method returns `null` on failure, never throws
- `queueEvent()` is synchronous -- called freely during gameplay
- `flushEvents()` called at natural breakpoints (wave clear, game over, level complete)
- Token persisted in `localStorage` as `pw_api_token`
- `online` flag set after successful `checkAuth()`, used for UI indicators only
- If no token or server unreachable, all methods silently no-op
- Existing localStorage systems (scores, loadouts, levels) continue to work as primary storage. Server is a sync target, not a replacement.

### Event Queue Flush Points
- `spawnWave()` -- flush after wave clear
- `customLevelWin()` -- flush after level beat
- Game over screen entry
- Manual flush on `PW_API.logout()`

---

## Client UI Changes

### Start Screen Account Indicator

Top-right corner of the start screen (below the sound toggle):
- Authenticated: `username (ONLINE)` in green, clickable
- Not authenticated: `OFFLINE` in dim text, clickable
- Clicking either opens `gameState='account'`

### Account Screen (`'account'` state)

**Logged out view:**
- Two tabs: LOGIN / REGISTER
- LOGIN: email + password fields, SUBMIT button
- REGISTER: username + email + password + confirm password fields, SUBMIT button
- Error messages shown inline (red text below the form)
- BACK button returns to start

**Logged in view:**
- Username, email, account created date
- "ONLINE" status indicator
- Scores submitted count
- LOGOUT button (danger variant)
- BACK button returns to start

**Input handling:** Hidden HTML `<input>` elements (text, email, password) positioned over canvas fields when focused. Same pattern as `editorNameInput`. Three inputs reused across forms.

### Event Integration Points

Calls to `PW_API.queueEvent()` added at:
- `startBattle()` / `startCombatTraining()` / `startTimeTrial()` / `loadCustomLevel()`: `game_started`
- `spawnWave()`: `wave_cleared` (with wave number, score, kills, time)
- `killEnemy()`: `enemy_killed` (with enemy type, weapon used)
- Weapon unlock pickup: `weapon_unlocked` (with weapon id)
- Game over: `player_defeated` (with mode, wave, score, time)
- Victory / level complete: `level_beat`
- Abort mission: `level_ended_early`
- Hangar craft selection: `craft_selected`

Score saving: after existing `saveHighScore()` calls, also call `PW_API.saveScore()`.

---

## Offline-First Guarantees

- Game loads and plays identically with no server
- All existing localStorage systems remain the primary data store
- API calls are fire-and-forget -- no loading spinners, no blocking
- Failed API calls silently re-queue or discard (events re-queue, scores are best-effort)
- The account screen is the only place that shows network errors (login/register failures)
- No game feature is gated behind authentication

---

## Unresolved Questions

None.
