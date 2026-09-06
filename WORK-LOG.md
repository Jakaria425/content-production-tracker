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
