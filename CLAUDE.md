# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

ระบบคัดกรองโรงเรียนพื้นที่ลักษณะพิเศษ ปีงบประมาณ 2569 — a rewrite of a legacy **PHPRunner** app
(`D:\laragon\www\highland`) as **plain PHP + MySQL (PDO)**, no framework, no Composer in this repo.
It reuses the legacy database `ssrainfo_ssra` and the legacy accounts/passwords. The codebase and most
comments are in **Thai**; match that when editing.

Read [docs/PRD.md](docs/PRD.md) for the full requirements, data model, and scoring formulas. The PRD's
§-references (e.g. "PRD §10") are cited throughout the code for technical-debt and rationale notes.

## Commands

```bash
# Tests (custom harness in tests/lib.php — no PHPUnit/Composer)
php tests/run.php              # all unit + integration
php tests/run.php unit         # unit only (no DB)
php tests/run.php integration  # integration only (hits real DB; SKIPs if DB unreachable)
# Single test: there is no per-test filter — run a file directly after autoloading:
php -r "require 'tests/lib.php'; spl_autoload_register(function($c){if(str_starts_with($c,'App\\'))require 'app/'.str_replace('\\','/',substr($c,4)).'.php';}); require 'tests/unit/ScoreServiceTest.php'; T::summary();"

# Run the app
# Laragon (Apache): http://newhighland.test/  or  http://localhost/newhighland/
php -S 127.0.0.1:8099 index.php   # PHP built-in server for quick checks
```

Integration tests use **sentinel keys** (`sc_id=999999001`, `acadyears=9999`) and delete their own rows,
so they never touch real data. Keep that pattern for any new DB test.

## Architecture

Single front controller → router → controller → model/service → view. All requests funnel through
[index.php](index.php) (route table) via [.htaccess](.htaccess) rewrite; `app/`, `config/`, `database/`,
`docs/` are blocked from direct web access.

- **[app/bootstrap.php](app/bootstrap.php)** — PSR-4-ish autoloader for `App\`, plus `class_alias` so views
  (which run in the global namespace) can call `App::`, `View::`, `Auth::`, `Csrf::`, `Flash::`, `Db::`
  without imports. Loads view helpers and calls `App::boot()`.
- **[app/Core/](app/Core)** — framework primitives:
  - `App` — config access (`App::config('key')`), URL building (`App::url()` / `App::asset()` honor the
    base path so both `newhighland.test/` and `localhost/newhighland/` work), session boot, `App::acadYear()`.
  - `Db` — PDO singleton; **all queries go through here with prepared statements only** (the rewrite exists
    partly to kill the legacy SQL-injection debt). Helpers: `all/one/scalar/exec/lastId`. It deliberately
    runs `SET SESSION sql_mode = ''` because legacy tables have NOT NULL columns without DEFAULTs.
  - `Router` — supports static paths and `{param}` placeholders; unmatched → 404 view.
  - `View` — renders `app/Views/<name>.php` inside `app/Views/layouts/<layout>.php`. Pass `layout: null`
    for partials/AJAX. `View::e()` escapes; `View::json()` emits and exits.
  - `Auth`, `Csrf`, `Flash`, `Request`, `Upload` — auth/session, CSRF tokens, flash messages, request
    accessors, file uploads.
- **[app/Controllers/](app/Controllers)** — thin; one per feature area (`Auth`, `Home`, `Landing`,
  `School`, `Map`, `HighlandEval`, `Island Eval`, `Confirm`).
- **[app/Models/](app/Models)** — static-method data-access classes over legacy tables (no ORM).
- **[app/Services/](app/Services)** — business logic: `ScoreService` / `IslandScoreService` (scoring),
  `SchoolContext` (assembles a school's context), `PdfService` (PDF output).

### The four functional areas (PRD §2)

1. **Highland eval (new)** — `Highland*` controllers/models: pick school → pin on map + measure elevation
   (`Map`) → 16-item form → `ScoreService::calcHighland` → print PDF.
2. **Island eval (new)** — `Island*`: 15-item form → `IslandScoreService` (out of 100) → print PDF.
3. **+4. Continuity certification** — `ConfirmController` + `SchoolConfirm` model + `school_confirm` table:
   school confirms it still exists → district (สพท.) certifies → สพฐ. approves.

### Conventions that span files

- **Roles** (`Auth`): `school`, `sao` (district), `admin` (สพฐ.). Guard with `Auth::require([...roles])`
  and `Auth::canAccessSchool($scId)`. Schools see only their own `sc_id`; districts see schools under their
  `sao_code`; admin sees all.
- **Logical key is `(sc_id, acadyears)`**, not an auto PK. Legacy eval tables lack PRIMARY/UNIQUE keys, so
  models do **explicit upsert** (check `exists()` then insert-or-update) instead of `ON DUPLICATE KEY`.
- **Mass-assignment guard**: models expose allow-lists (e.g. `HighlandEval::FORM_FIELDS`, `SCORE_FIELDS`,
  `CERT_FIELDS`) and only write columns on those lists. Add new form columns there.
- **CSRF**: every POST handler calls `Csrf::verify()`; forms embed `Csrf::field()`.
- **Scoring is verified against real legacy records** (highland `sc_id=1063020130` acadyears 2567 = 68.14;
  island `sc 1091560035` = 71.38). When touching scoring logic, keep `tests/unit/*ScoreServiceTest.php`
  passing against those fixtures — they encode the legacy formula exactly.
- **`acad_year`** is set once in [config/config.php](config/config.php) (currently 2569) — never hardcode the
  year elsewhere; read `App::acadYear()`.

## Config & external deps

[config/config.php](config/config.php) holds DB credentials, the Google Maps API key, the elevation
threshold (500 m → mountain/flat decision), `admin_users`, and **`mpdf_path` / `mpdf_island_path`** which
point into the legacy `../highland` project to reuse its mPDF/FPDF vendor + PDF templates + TIS-620 fonts
(avoids copying ~97 MB). PDF output depends on those legacy paths existing.

## Known technical debt (intentional, see PRD §10)

- Legacy passwords are **plaintext** — `Auth::attempt` compares directly for compatibility (hashing is a
  planned migration, not a bug to "fix" silently).
- `sql_mode = ''` and missing DEFAULT/UNIQUE keys on legacy tables — `database/migration_2569_confirm.sql`
  is the start of tightening these.
