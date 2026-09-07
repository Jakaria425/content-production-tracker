# AI Feature Specification — Content Plan Generation

## 1. Feature Overview

The AI content-plan feature allows an authenticated user to generate a structured content plan for a project they own.

The workflow is:

```text
Authenticated user
    ↓
Selects their project
    ↓
Requests a content plan
    ↓
Laravel verifies project ownership
    ↓
Laravel builds the prompt
    ↓
Laravel calls OpenAI
    ↓
Laravel validates the structured result
    ↓
Laravel saves the generation
    ↓
Vue displays the saved result
```

The OpenAI API must be called from the Laravel backend. The browser communicates only with the Laravel application and must never communicate directly with OpenAI using the secret API key.

---

## 2. Project Fields Sent to OpenAI

Laravel may send only these project fields to OpenAI:

| Field | Required | Description |
|---|---|---|
| `title` | Yes | Project title |
| `content_type` | Yes | Type of content being planned |
| `brief` | Yes | Project/content brief |
| `notes` | No | Additional project notes, only included when present |

The prompt must not contain:

- User email
- User password
- OpenAI API key
- Session data
- Authentication tokens
- Unrelated project fields
- Other users' project data
- Private company/customer data

Laravel should construct the prompt explicitly from the approved fields rather than sending the complete project or user object.

---

## 3. Required AI Response Structure

OpenAI must return a structured JSON response matching the following schema:

```json
{
  "suggested_title": "string",
  "content_brief": "string",
  "outline": [
    {
      "heading": "string",
      "purpose": "string"
    }
  ],
  "key_points": [
    "string"
  ],
  "production_tasks": [
    "string"
  ],
  "risks_or_missing_information": [
    "string"
  ]
}
```

### Required fields

- `suggested_title` — string
- `content_brief` — string
- `outline` — array
- `outline[].heading` — string
- `outline[].purpose` — string
- `key_points` — array of strings
- `production_tasks` — array of strings
- `risks_or_missing_information` — array of strings

Unstructured Markdown, plain text, or a response that does not match the required structure must be treated as an unsuccessful generation.

Laravel must validate the response before saving it as `completed`.

---

## 4. Data Saved in the Database

Each generation is stored in the `content_generations` table.

The following information is saved:

| Field | Purpose |
|---|---|
| `project_id` | Identifies the project for which the plan was generated |
| `status` | Indicates whether generation was `completed` or `failed` |
| `prompt` | Stores the sanitized prompt sent to OpenAI |
| `response` | Stores the validated structured AI response |
| `model` | Stores the OpenAI model used |
| `input_tokens` | Stores input token usage when provided |
| `output_tokens` | Stores output token usage when provided |
| `error_code` | Stores a safe error code when generation fails |
| `created_at` | Records creation time |
| `updated_at` | Records update time |

The `project_id` must reference an existing project.

When a project is deleted, its associated content generations must also be deleted.

---

## 5. Successful Generation

When generation succeeds:

1. Laravel verifies that the authenticated user owns the project.
2. Laravel sends the approved project fields to OpenAI.
3. Laravel receives the structured response.
4. Laravel validates the response against the required schema.
5. Laravel saves the generation with `status = completed`.
6. Laravel returns the saved result to the frontend.
7. Vue displays the generated content plan.

The user should see:

- Suggested title
- Content brief
- Outline with headings and purposes
- Key points
- Production tasks
- Risks or missing information

The displayed result must come from the validated and saved generation.

---

## 6. Failed Generation

Generation can fail because of:

- Invalid project input
- Unauthorized project access
- OpenAI/provider failure
- Network/API failure
- Invalid or malformed AI response
- Structured response validation failure

When generation fails:

- Laravel must save the generation with `status = failed`.
- A safe `error_code` should be stored.
- Sensitive provider details must not be exposed.
- Stack traces must not be returned to the browser.
- API keys and other secrets must never appear in the response.

The user should see a simple, safe message such as:

```text
Unable to generate the content plan. Please try again later.
```

The user does not need to see the internal provider error, stack trace, API response body, or other technical details.

---

## 7. Why the Browser Must Never Receive the API Key

The OpenAI API key is a server-side secret and must remain inside the Laravel application's secure environment configuration.

The browser must never receive the key because anything sent to the browser can potentially be inspected by the user or exposed through browser developer tools, network requests, frontend code, screenshots, or logs.

The correct architecture is:

```text
Vue
  ↓
Laravel API
  ↓
OpenAI API
```

Not:

```text
Vue
  ↓
OpenAI API
```

Laravel should read the API key from server-side environment/configuration and use it only for backend-to-OpenAI communication.

The API key must also never be committed to Git.

---

## 8. Why Tests Must Not Call the Real OpenAI API

Automated tests must use fake or mocked OpenAI API responses.

Tests must never make real OpenAI API requests because real API calls:

- Can expose or depend on secrets.
- Can cost money.
- Depend on network availability.
- Can make tests slow or unreliable.
- May produce different responses.
- Make it difficult to test specific failure scenarios.

Fake responses allow the test suite to reliably verify successful responses, malformed responses, provider failures, token usage, and security requirements without contacting OpenAI.

For example, tests should simulate:

```text
Valid structured response
Malformed JSON response
Invalid schema response
Provider failure
Token usage response
```

---

## 9. Authorization and Security

Only authenticated users can request content plans.

Before calling OpenAI, Laravel must verify that the selected project belongs to the authenticated user.

The system must prevent a user from generating a plan for another user's project.

The prompt must contain only the approved project fields:

```text
title
content_type
brief
notes (when present)
```

The following must never be included:

```text
email
password
API key
session data
authentication tokens
unrelated project data
private company/customer data
```

---

## 10. Deliberately Postponed Features

The following features are intentionally out of scope for the current implementation:

- Background jobs / queues
- Automatic retries
- Streaming responses
- Multiple AI providers
- Chat functionality
- RAG
- Tool calling
- Editing generated plans
- Accepting generated plans
- Generation history management
- Regenerating content plans
- Deleting individual generations

These features may be considered in future iterations but should not be implemented as part of this feature.

---

## 11. Acceptance Criteria

The feature is complete when:

- An authenticated user can request a content plan for their own project.
- Laravel verifies project ownership before calling OpenAI.
- Only `title`, `content_type`, `brief`, and optional `notes` are included in the prompt.
- No secrets or unrelated user/project data are included.
- The OpenAI API key remains server-side.
- The configured OpenAI model is used.
- The AI response is validated against the required JSON structure.
- Markdown/plain-text responses are rejected.
- Successful generations are saved with `completed` status.
- Failed generations are saved with `failed` status and a safe error code.
- Token usage is saved when provided by the provider.
- The saved result can be displayed by Vue.
- Automated tests use fake OpenAI responses only.
- No real OpenAI API requests are made by tests.
- No API keys or private data are committed to Git.