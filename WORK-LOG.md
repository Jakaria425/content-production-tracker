# Work Log

## 2026-09-06 — Focused feature tests for the Projects page

Task: add an empty-state feature test for `/projects`, run the focused tests,
then the full suite, then the type-check. Record every command and result.

### Change made

- `tests/Feature/ProjectsTest.php` — appended one test (existing 5 untouched):
  `an account without projects sees the empty state`. Uses `User::factory()`
  (one user, zero projects), no dependency on the development seeder. The test
  asserts the `projects` prop is an empty array, which drives the Vue empty
  state (`v-if="projects.length === 0"`).

### Commands and results

1. Focused test run

    ```bash
    php artisan test tests/Feature/ProjectsTest.php --compact
    ```

    Result: PASSED — 6 tests, 6 passed, 68 assertions.

2. Code style (dirty files only)

    ```bash
    vendor\bin\pint --dirty --format agent --parallel
    ```

    Result: PASSED — no files required formatting.

3. Complete test suite

    ```bash
    php artisan test --compact
    ```

    Result: PASSED — 90 tests, 87 passed, 3 skipped, 1 risky, 323 assertions
    (the risky/skipped items are the pre-existing `ProbeTmpTest` dump and
    skipped suites; all Day 1 tests still pass).

4. TypeScript type-check

    ```bash
    NODE_OPTIONS=--max-old-space-size=4096 npm run type-check
    ```

    Result: PASSED — `vue-tsc --noEmit` exited cleanly with no errors.
    (The `NODE_OPTIONS` heap bump was required because this machine has a small
    Windows paging file; earlier un-bumped runs of `vue-tsc` aborted with a
    JavaScript heap out-of-memory error.)

### Summary

- All new tests pass.
- All Day 1 tests still pass.
- `npm run type-check` passes.
- Each test checks one clear behavior; ownership protection is proven by two
  separate users; no test depends on the development seeder.

## 2026-09-06 — Fix 8 Larastan static-analysis errors

Task: resolve the 8 `phpstan analyse` (level 7, larastan) errors reported by
`composer types:check`.

### Changes made

| Type   | File                                         | Detail                                                                                                                                                                |
| ------ | -------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| CREATE | `config/seeder.php`                          | Moved the 3 `env()` calls for the demo user here (where `env()` belongs) with safe `.test` demo defaults                                                              |
| EDIT   | `database/seeders/DatabaseSeeder.php`        | Replaced `env(...)` with `config('seeder.user.*')`; `Hash::make((string) config(...))`                                                                                |
| EDIT   | `tests/Unit/SeederCredentialTest.php`        | Repointed assertions: config file must `env()` name/email/password, seeder must read via `config()` (same security intent: no hardcoded credential); 6 assertions now |
| EDIT   | `app/Http/Requests/StoreProjectRequest.php`  | Added `@return array<string, list<string>>` above `rules()`                                                                                                           |
| EDIT   | `app/Http/Requests/UpdateProjectRequest.php` | Added `@return array<string, list<string>>` above `rules()`                                                                                                           |
| EDIT   | `app/Models/Project.php`                     | Added `/** @use HasFactory<ProjectFactory> */` + import; `@return BelongsTo<User, $this>` on `user()` (mirrors `User` model pattern)                                  |

No files were deleted.

### Commands and results

1. Static analysis (initial)

    ```bash
    composer types:check
    ```

    Result: ERROR — PHPStan crashed on its 128M PHP memory limit
    (parallel worker), not a code failure. Rerun with a raised limit:

    ```bash
    vendor\bin\phpstan analyse --memory-limit=1024M
    ```

    Result: PASSED — 0 errors (all 8 original errors gone).

2. Code style (dirty files only)

    ```bash
    vendor\bin\pint --dirty --format agent --parallel
    ```

    Result: FIXED — added missing EOF newline in `config/seeder.php` and
    `tests/Unit/SeederCredentialTest.php`.

3. Focused test

    ```bash
    php artisan test tests\Unit\SeederCredentialTest.php --compact
    ```

    Result: PASSED — 1 test, 1 passed, 6 assertions.

4. Complete test suite

    ```bash
    php artisan test --compact
    ```

    Result: PASSED — 90 tests, 87 passed, 3 skipped, 1 risky, 327 assertions
    (risky/skipped: pre-existing `ProbeTmpTest` dump + skipped suites).

### Summary

- All 8 phpstan errors fixed; `phpstan analyse` now passes (0 errors) with
  `--memory-limit=1024M` (the 128M default is too low on this machine).
- All tests pass, including the updated credential-safety test.
- No `.env`/`.env.example` changes needed; the demo defaults remain the
  IANA-reserved `.test` domain values.





### ---------DAY-3-------

Start time  09:30 - 09/07/2026

## Day 3 baseline verification

php artisan test tests/Feature/ProjectsTest.php

   PASS  Tests\Feature\ProjectsTest
  ✓ guests are redirected to the login page                                        0.23s  
  ✓ authenticated users can open the projects page                                 2.16s  
  ✓ the projects page shows only the authenticated user projects                   2.08s  
  ✓ the projects page does not expose another user projects                        2.08s  
  ✓ projects are ordered from newest to oldest                                     2.08s  
  ✓ an account without projects sees the empty state                               2.07s  

  Tests:    6 passed (68 assertions)
  Duration: 10.84s


npm run type-check && echo TYPE CHECK PASSED

> type-check
> vue-tsc --noEmit

TYPE CHECK PASSED









* Starting branch and commit
git clone https://github.com/Jakaria425/content-production-tracker.git
cd content-production-tracker
git checkout -b feature
git push origin feature

* Setup commands
composer install
npm install
copy .env.example .env  [// configure database ]
php artisan key:generate


## 2026-09-07 — Day 3 Step 4: OpenAI safe configuration

Task: add OPENAI_API_KEY and OPENAI_MODEL to .env.example and config/services.php.
Application code must use config(), never env().

### Changes made

| Type | File | Detail |
|---|---|---|
| EDIT | `.env.example` | Added `# AI api credentials` section with empty OPENAI_API_KEY= and OPENAI_MODEL= placeholders |
| EDIT | `config/services.php` | Added `openai` block: `api_key` and `model` via env(), fixed `timeout` of 30 seconds |
| CREATE | `tests/Unit/OpenAIConfigTest.php` | Verifies .env.example has correct names and no real key; config reads via env(); future service uses config() not env() |

### Commands and results

1. OpenAI config unit tests

    ```bash
    php artisan test tests/Unit/OpenAIConfigTest.php --compact
    ```

    Result: 2 passed, 1 skipped (service not created yet), 5 assertions.

2. Code style

    ```bash
    vendor\bin\pint --dirty --format agent
    ```

    Result: PASSED.

3. Complete test suite

    ```bash
    php artisan test --compact
    ```

    Result: 93 tests, 89 passed, 4 skipped, 1 risky (baseline unchanged).

### Summary

- OPENAI_API_KEY and OPENAI_MODEL are empty placeholders in .env.example.
- Real key lives only in untracked .env.
- config/services.php reads via env() as required.
- Third test skips until OpenAIService is created.


## 2026-09-07 — Day 3 Step 5: ContentGeneration model, migration, factory

Task: create the content_generations table and model.

### Changes made

| Type | File | Detail |
|---|---|---|
| CREATE | `database/migrations/2026_09_07_..._create_content_generations_table.php` | FK to project_id with cascade delete, status, prompt, response (JSON), model, input_tokens, output_tokens, error_code, timestamps |
| CREATE | `app/Models/ContentGeneration.php` | $fillable, response cast to array, belongsTo(Project) |
| CREATE | `database/factories/ContentGenerationFactory.php` | Default completed state with fake structured response; failed() state for testing |
| EDIT | `app/Models/Project.php` | Added contentGenerations() hasMany relationship |

Note: migration failed on first run because old table existed from a previous session. Dropped it manually:

```bash
php artisan tinker --execute "Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS content_generations');"
```

Then `php artisan migrate` succeeded.

### Commands and results

```bash
vendor\bin\pint --dirty --format agent
php artisan test --compact
```

Result: 90 tests, 87 passed, 3 skipped, 1 risky.


## 2026-09-07 — Day 3 Step 6: OpenAI service

Task: build app/Services/OpenAIService.php — one class, return-based errors.

### Design decisions

- Return-based (not throw) so exactly ONE file is needed, no exception class.
- All error codes are safe strings: missing_configuration, provider_error, invalid_response.
- Prompt and response parsing are private methods — never in a controller.
- Config checked FIRST before any network call.
- ConnectionException, non-2xx, missing/invalid JSON, and schema validation failures all return controlled errors.
- Token usage extracted from usage.input_tokens / usage.output_tokens.

### Changes made

| Type | File | Detail |
|---|---|---|
| CREATE | `app/Services/OpenAIService.php` | generate(Project), buildPrompt(Project), schema(), validateResponse(array) |

### Commands and results

```bash
vendor\bin\pint --dirty --format agent
php artisan test tests/Unit/OpenAIConfigTest.php --compact
```

Result: 3 passed, 9 assertions (all three tests now run — service exists and uses config()).

```bash
php artisan test --compact
```

Result: 93 tests, 90 passed, 3 skipped, 1 risky.

### Summary

The service satisfies every requirement from Step 6 with no extensions:
- Receives Project, builds prompt from allowed fields, calls Responses API with JSON schema,
- Validates every required field and nested item, extracts tokens, returns a clearly defined array.
- No retries, no queues, no streaming, no extra files.
