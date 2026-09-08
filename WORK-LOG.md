# Work Log

## Focused feature tests for the Projects page

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

## baseline verification

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


## OpenAI safe configuration

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


## ContentGeneration model, migration, factory

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


## OpenAI service

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


## Generation endpoint and authorization

Task: add an authenticated POST route that generates a plan for a project the
user owns, saves one ContentGeneration record, and flashes a safe message.

### Design decisions

- **One generic failure message for every failure path** (Option A). The
  sentence `The content plan could not be generated. Please try again later.`
  is stored once in a class constant `SAFE_FAILURE_MESSAGE` and reused by both
  the invalid-input guard and the service-failure path through a private
  `failWithoutGeneration()` helper. Per-error-code messages were rejected: to
  stay safe they all collapse to the same sentence anyway, and differentiating
  them starts describing internals to end users. The real diagnostic detail
  lives in `content_generations.error_code`, not in the browser.
- **`Inertia::flash('toast', ...)` instead of `->with(...)`** so the message
  reaches the app's existing toast system (`resources/js/lib/flashToast.ts`).
- **Ownership checked against the database**, never a browser-sent `user_id`:
  `$request->user()->id !== $project->user_id` → `abort(403)`.
- **Input gate runs before the service call**, so an empty title/content_type/
  brief spends no API credit and writes no record.

### Changes made

| Type | File | Detail |
|---|---|---|
| CREATE | `app/Http/Controllers/ProjectContentPlanController.php` | `store(Request, Project)` — ownership check, input gate, `OpenAIService` via constructor injection, single `ContentGeneration::create()`, safe flash, `back()` |
| EDIT | `routes/web.php` | Added `POST projects/{project}/generations` → `projects.generations.store` inside the existing `auth` middleware group |

### Commands and results

1. Code style

    ```bash
    vendor\bin\pint --dirty --format agent
    ```

    Result: PASSED.

2. Route registration and middleware

    ```bash
    php artisan route:list --path=projects -v
    ```

    Result: `POST projects/{project}/generations .. projects.generations.store`
    with middleware `web`, `auth`.

3. Complete test suite

    ```bash
    php artisan test --compact
    ```

    Result: PASSED — 93 tests, 90 passed, 3 skipped, 1 risky, 336 assertions.

4. Static analysis

    ```bash
    vendor\bin\phpstan analyse --memory-limit=1024M
    ```

    Result: PASSED — 0 errors.

### Summary

- Guest blocked by `auth` middleware; cross-user request blocked by `abort(403)`.
- Invalid project input returns before any OpenAI call and writes no record.
- Exactly one `ContentGeneration` row per request, completed or failed.
- No API key, provider body, stack trace, or exception message reaches the browser.
- Endpoint tests are deliberately deferred to Step 9.


## Generate button and latest plan display (Vue)

Task: give the user an in-UI trigger for generation and a read-only view of the
most recently saved successful plan, in TypeScript with no uses of `any`.

### Design decisions

- **Button only on projects that have a `brief`**, matching the assignment's
  `v-if="project.brief"` requirement. Without a brief the button is not rendered.
- **Global latest plan, not per-project** (singlular wording in the assignment).
  `ProjectController` loads `latestGeneration` = the newest `completed` row
  whose project belongs to the authenticated user, checked via
  `whereHas('project', fn => $q->where('user_id', $request->user()->id))`.
  A per-project query would have been the same code for less clarity.
- **Generation kicked off with the generated Wayfinder helper**
  `generatePlan($project)` (`router.post`), matching how the rest of the app
  does XHR calls. `isGenerating` disables the button while the request runs.

### Changes made

| Type | File | Detail |
|---|---|---|
| EDIT | `resources/js/types/projects.ts` | Added `Project.brief`, `ContentPlan`, `ContentPlanOutlineItem`, `LatestGeneration` (flat `response` object). No `any` |
| EDIT | `app/Http/Controllers/ProjectController.php` | Index adds `brief` and `latestGeneration` to the Inertia props |
| EDIT | `resources/js/pages/Projects.vue` | `isGenerating` ref, `generateContentPlan()` via `generatePlan($project)`, Button `v-if="project.brief"`, six read-only sections (suggested_title, content_brief, outline, key_points, production_tasks, risks) |

### Commands and results

1. Wayfinder helpers (run inside `resources/js`)

    ```bash
    php artisan wayfinder:generate --with-form
    ```

    Result: PASSED — emitted `resources/js/routes/projects/generations/index.ts`.

2. TypeScript type-check

    ```bash
    NODE_OPTIONS=--max-old-space-size=4096 npm run type-check
    ```

    Result: PASSED — `vue-tsc --noEmit` clean.

3. Static analysis

    ```bash
    vendor\bin\phpstan analyse --memory-limit=1024M
    ```

    Result: PASSED — 0 errors.

4. Complete test suite

    ```bash
    php artisan test --compact
    ```

    Result: PASSED — 93 tests, 90 passed, 3 skipped, 1 risky, 336 assertions.

### Summary

- Existing `ProjectsTest` assertions use `->has()`/`->where()`, so new props
  (`brief`, `latestGeneration`) did not break them.
- The failure message still comes from the controller's single
  `SAFE_FAILURE_MESSAGE`, so Step 6/7 guarantees were untouched.


## diagnosis: why the button always failed

Symptom: every generation produced a failed row with `error_code =
provider_error` and only the generic safe message in the UI.

### Investigation path

1. Ruled out config: `.env` key is a well-formed dequoted `sk-proj-...`, no
   config cache, model present, prompt length 462 chars saved on the row.
2. Free diagnostic GET `/v1/models` with the key (zero token cost):
   `ConnectionException: cURL error 60 — unable to get local issuer
   certificate`. PHP on this machine had NO CA bundle:
   `curl.cainfo=` and `openssl.cafile=` were both empty in
   `C:\php-8.5.8\php.ini`, and no `cacert.pem` existed on `C:\`.
3. Fix: downloaded the official Mozilla bundle to
   `C:\php-8.5.8\extras\ssl\cacert.pem`, enabled
   `curl.cainfo` and `openssl.cafile` in `php.ini` (path uses forward
   slashes). Retry: `GET /v1/models` → HTTP 200, 133 models. Key valid,
   connectivity restored.
4. Reproduced the exact `POST /v1/responses` payload the service sends
   (same model, same prompt from `buildPrompt`, same JSON schema via
   reflection). Provider answered:

   ```
   HTTP 429  insufficient_quota  credit_balance_exhausted
   "You have no credits remaining. Add credits to continue using the API at
   https://platform.openai.com/settings/organization/billing/"
   ```

### Conclusion

- The original `provider_error` was the TLS failure (step 2), not OpenAI.
- With TLS fixed and the key valid, generation is blocked only by the account
  having **zero API credits** (ChatGPT Plus does not include API credit).
- **No application code changed** — this was purely an environment issue. The
  Step 6/7 design (generic message, `error_code` only) already behaves exactly
  as required for this failure.
- To enable a live test the owner must add credits at
  `platform.openai.com/settings/organization/billing/`.

### Commands and results

```bash
curl.exe -L --fail -o C:\php-8.5.8\extras\ssl\cacert.pem https://curl.se/ca/cacert.pem
```

Result: PASSED — 188,900-byte bundle; `php -r "echo ini_get('curl.cainfo')"`
returns the new path.


## AI feature tests — Step 9

Task: add focused Pest tests for the content-generation model, the OpenAI service,
the generation endpoint/authorization, and the Vue display contract, faking every
provider response and never calling the real OpenAI API.

### Test files created

| File | Scope | Tests |
|---|---|---|
| `tests/Unit/ContentGenerationModelTest.php` | `ContentGeneration` model | 6 |
| `tests/Unit/OpenAIServiceTest.php` | `OpenAIService::generate()` | 9 |
| `tests/Feature/ContentGenerationTest.php` | Endpoint + authorization | 14 |
| `tests/Feature/ContentPlanDisplayTest.php` | Vue Inertia prop contract | 3 |

### Commands and results

1. New AI-feature tests

    ```bash
    php artisan test tests/Unit/ContentGenerationModelTest.php tests/Unit/OpenAIServiceTest.php tests/Feature/ContentGenerationTest.php tests/Feature/ContentPlanDisplayTest.php --compact
    ```

    Result: PASSED — 32 tests, 32 passed.

2. Full test suite

    ```bash
    php artisan test --compact
    ```

    Result: PASSED — 124 tests, 121 passed, 3 skipped, 0 failures, 482 assertions.

3. PHP code style

    ```bash
    vendor\bin\pint --dirty --format agent
    ```

    Result: PASSED — no files required formatting.

4. Static analysis

    ```bash
    vendor\bin\phpstan analyse --memory-limit=1024M
    ```

    Result: PASSED — 0 errors.

5. TypeScript type-check

    ```bash
    NODE_OPTIONS=--max-old-space-size=4096 npm run type-check
    ```

    Result: PASSED — `vue-tsc --noEmit` clean.

### Note on `composer run ci:check`

`composer run ci:check` fails on this machine at the `npm run check` step
(vite-plus frontend formatting) because the `tinypool` worker pool crashes with
a Node.js memory error. The individual component checks (Pest, Pint, PHPStan,
`npm run type-check`) all pass; the bundled script fails for environment/memory
reasons before it reaches the PHP tests.

### Detailed test output

```text
   PASS  Tests\Unit\ContentGenerationModelTest
  ✓ it casts the response column as an array                                                                 0.28s  
  ✓ it persists tracked fields                                                                               0.01s  
  ✓ it belongs to its project                                                                                0.02s  
  ✓ it factory default is a completed generation                                                             0.01s  
  ✓ it factory failed state clears response data                                                             0.01s  
  ✓ it deletes generations when the project is deleted                                                       0.01s  


   PASS  Tests\Unit\OpenAIConfigTest
  ✓ openai credentials come from environment via config, not hardcoded                                       0.01s  
  ✓ services config reads openai values from environment                                                     0.01s  
  ✓ application code does not call env for openai credentials                                                0.01s  

   PASS  Tests\Unit\OpenAIServiceTest
  ✓ it returns missing_configuration when the api key is empty                                               0.02s  
  ✓ it returns missing_configuration when the model is empty                                                 0.01s  
  ✓ it returns provider_error on a non-2xx response                                                          0.05s  
  ✓ it returns provider_error on a connection failure                                                        0.01s  
  ✓ it returns invalid_response when output text is not valid json                                           0.01s  
  ✓ it returns invalid_response when the json misses a required key                                          0.01s  
  ✓ it returns completed with parsed data and token usage                                                    0.01s  
  ✓ it sends a strict json_schema format                                                                     0.01s  
  ✓ it builds the prompt from allowed fields and excludes secrets                                            0.01s  
                                                                  

   PASS  Tests\Feature\Auth\PasswordResetTest
  ✓ reset password link screen can be rendered                                                               0.02s  
  ✓ reset password link can be requested                                                                     0.24s  
  ✓ reset password screen can be rendered                                                                    0.23s  
  ✓ password can be reset with valid token                                                                   0.23s  
  ✓ password cannot be reset with invalid token                                                              0.22s  


   PASS  Tests\Feature\ContentGenerationTest
  ✓ a guest cannot generate a content plan                                                                   0.02s  
  ✓ a user can generate a plan for their own project                                                         0.02s  
  ✓ a user cannot generate a plan for another user project                                                   0.02s  
  ✓ invalid project input prevents the provider request                                                      0.01s  
  ✓ the outgoing request uses the configured model                                                           0.01s  
  ✓ the outgoing prompt includes the allowed project data                                                    0.01s  
  ✓ the outgoing prompt excludes unrelated user data and secrets                                             0.01s  
  ✓ a successful structured response is validated and saved                                                  0.01s  
  ✓ token usage is saved when present                                                                        0.01s  
  ✓ a provider http failure saves a failed generation and shows a safe message                               0.01s  
  ✓ malformed structured output saves a failed generation and shows a safe message                           0.02s  
  ✓ the api key and provider error body do not appear in the browser response                                0.01s  
  ✓ a project with a completed generation reports has_content_plan true                                      0.02s  
  ✓ a project with only a failed generation reports has_content_plan false                                   0.02s  




```

### Summary

- All four requested test areas have dedicated, passing coverage.
- No real OpenAI API request is made by any test (`Http::preventStrayRequests()`
  and `Http::fake()` guard every call).
- Tests set their own `services.openai` config, so they do not skip when
  `OPENAI_API_KEY` is absent from the environment.
