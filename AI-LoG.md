# AI Usage Log DAY-3

## Entry 1 — OpenAI service: return-based vs throw-based errors

### Task

Design the error-handling strategy for `OpenAIService`. The assignment says "throw or return a controlled application error." A throw-based approach needs a second file (an exception class). A return-based approach uses one file.

### Prompt

The AI (assistant) suggested using a dedicated `AIServiceException` class with named constructors (missingConfiguration, providerError, invalidResponse). This is clean and idiomatic Laravel.

### My Decision

Chose **return-based** — one file, no exception class. The service returns a flat array with a `status` field (`completed` or `failed`) and a safe `error_code` string. The controller inspects the status and acts accordingly.

### Why

The assignment explicitly allows "throw or return." A second file would be scope creep — the requirement says one class. Return-based keeps it to exactly `app/Services/OpenAIService.php`.

### Verification

Implemented as planned. Tests confirm the service returns the correct error_code for each failure path. No exception class created.

---

## Entry 2 — JSON Schema: why `additionalProperties: false` and `strict: true`

### Task

Decide how to ensure OpenAI returns structured JSON and not prose. The assignment requires a JSON schema.

### Prompt

The AI suggested using `text.format.type = 'json_schema'` with `strict: true` and `additionalProperties: false`. This is the official OpenAI Responses API mechanism for structured output.

### Why these settings

- `json_schema` tells OpenAI "I want structured data, not free text."
- `strict: true` instructs the model to obey the schema rules — if it ignores them, the API returns an error rather than prose.
- `additionalProperties: false` at the top level and inside outline items prevents the model from adding extra fields.

### My Verification

The service passes the schema in every API call. The `validateResponse()` method provides a second defensive layer in PHP, checking structure after extraction.

### Rejected alternative

Asking for Markdown and parsing it would be fragile. The schema approach is the correct, documented way to get reliable structured output from the Responses API.

---

## Entry 3 — `validateResponse()`: why a second check after the schema

### Task

Understand why `validateResponse()` exists when `strict: true` + `additionalProperties: false` should already guarantee the shape.

### Prompt

The AI explained that HTTP 200 OK can still contain non-compliant JSON if something goes wrong inside the model, and that defensive programming justifies a second validation layer.

### Why kept

The schema is a contract with OpenAI. `validateResponse()` is a firewall between the API response and application logic. It catches:
- Missing required keys
- Outline items that are not objects with `heading` + `purpose`

These are structural checks that the schema covers, but a second check is cheap and makes the service robust against unexpected API behaviour.

### My Changes

Implemented `validateResponse()` as a private method returning bool. On false, the service returns `failed` with `error_code = invalid_response`.

---

## Entry 4 — Model choice: `gpt-4o-mini`

### Task

Pick a model name for the development configuration.

### Prompt

I recommended `gpt-4o-mini` as the default because it is:
- Available to virtually all OpenAI keys
- Supports the Responses API and JSON schema
- Cheapest option for development, so manual tests cost minimal credit

### Verification

Set as the default in `.env.example` as an empty placeholder. User confirmed their key supports all models, so `gpt-4o-mini` is a safe, cheap default that satisfies the "model name from configuration" requirement.

### My Changes

Left `OPENAI_MODEL=` empty in `.env.example` as a placeholder. User will set their own value or use `gpt-4o-mini` during manual testing.

---

## Entry 5 — Failure flash message: one generic sentence vs per-error-code messages

### Task

Decide what the user sees when generation fails. Three distinct internal codes
exist (`missing_configuration`, `provider_error`, `invalid_response`), so the
question was whether to show three different messages.

### Suggested Solution

The AI first proposed differentiating the messages per error code with a
`match ($result['error_code'])` expression.

### My Verification

I wrote out the three branches and noticed that, to keep every message safe,
all three collapsed to the same sentence anyway. Any attempt to make them
genuinely different ("OpenAI returned an invalid response", "the provider timed
out") starts describing server internals to end users, which the assignment
explicitly forbids.

I also checked where the diagnostic detail actually needs to live: it is already
persisted in `content_generations.error_code`, which is inspectable by me and my
mentor without ever reaching the browser.

### My Changes

Rejected the per-code `match`. Chose a single class constant:

```php
private const SAFE_FAILURE_MESSAGE = 'The content plan could not be generated. Please try again later.';
```

Both failure paths (invalid project input, and service returning `failed`) go
through one private `failWithoutGeneration()` helper, so the string is defined
once and cannot drift between call sites.

### Why the final approach is safer or clearer

One fixed string has zero leak risk regardless of which failure path ran, and
the user's action is identical in every case — wait and retry. Differentiating
would add code without adding value on Day 3. It becomes worth revisiting when
retries or background jobs exist and the message can promise real behaviour
("we will retry automatically").

---

## Entry 6 — `Inertia::flash()` instead of `->with()` for the toast

### Task

Show the success/failure message after redirecting back to the Projects page.

### Suggested Solution

The AI initially wrote `return back()->with('error', '...')` and
`->with('success', '...')`, the common Laravel session-flash pattern.

### My Verification

I checked how this application actually surfaces messages:

- `resources/js/lib/flashToast.ts` listens for the Inertia `flash` event and
  reads `flash.toast` as `{ type, message }`.
- Every existing controller (`TeamInvitationController`, `TeamController`,
  `ProfileController`, `SecurityController`) uses
  `Inertia::flash('toast', ['type' => 'success', 'message' => ...])`.
- Existing feature tests assert with `assertInertiaFlash('toast', [...])`.

A plain `->with('error', ...)` would have put the message in the session where
nothing reads it — the user would see no feedback at all, and the Step 9 tests
would have no consistent assertion helper to use.

### My Changes

Replaced both `->with()` calls with `Inertia::flash('toast', [...])`, using
`type => 'error'` for failures and `type => 'success'` for the completed
generation, matching the existing controllers exactly.

### Why the final approach is safer or clearer

It follows the established project convention instead of inventing a second
messaging channel, so the toast actually renders and the tests can assert
against the same shape the rest of the suite uses.



