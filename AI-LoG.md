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



