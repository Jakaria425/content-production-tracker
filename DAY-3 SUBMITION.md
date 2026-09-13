# JAK-D3-001

# JAK-D3-002

# JAK-D3-003

# JAK-D3-004

## A screenshot of the structured content plan

### Detailed test output

### The TypeScript-check result

npm run type-check && echo TYPE CHECK PASSED

> type-check
> vue-tsc --noEmit

TYPE CHECK PASSED

### Final commit hash ->

```text
21a045804193ecf3654f956fdb352a24e2d78fe1
```

# Questions and Answers

### 1. Why must the OpenAI API key remain on the Laravel server?

The OpenAI API key is a secret credential, so it must never be exposed to the browser or Vue frontend. Laravel keeps the key in server-side environment/configuration and uses it when communicating with OpenAI. This prevents users from inspecting the key through browser developer tools or network requests.

### 2. Why is a structured JSON schema better here than asking for a Markdown answer?

A structured JSON schema makes the AI response predictable and machine-readable. Laravel can validate that all required fields exist and have the correct types before saving the result. Markdown is flexible and difficult to validate reliably, so an AI response could look correct to a person but still be unusable by the application.

### 3. Which project fields are included in the prompt, and which data is deliberately excluded?

The prompt includes only `title`, `content_type`, `brief`, and `notes` when notes are present. I deliberately exclude the user's email, password, API key, session data, authentication information, unrelated project fields, and private company or customer data.

### 4. Why is OpenAI integration placed in a service instead of directly in the controller?

I placed the OpenAI integration in a service to keep the controller focused on handling the HTTP request, authorization, and response. The service handles communication with OpenAI, which makes the code easier to maintain, test, and reuse. It also makes it easier to fake the OpenAI API during automated tests.

### 5. How does the application prevent one user from generating content for another user's project?

Before generating the content plan, Laravel checks that the selected project's `user_id` belongs to the currently authenticated user. If the project belongs to another user, the request is denied and OpenAI is not called.

### 6. What is the difference between an HTTP failure and an invalid structured response?

An HTTP failure means the request to the OpenAI provider failed, for example because of a network problem, authentication problem, rate limit, or server error. An invalid structured response means OpenAI responded, but the returned data was malformed or did not match the required JSON schema. Both cases must be handled as failed generations, but they represent different failure points.

### 7. How does the test suite prove that it does not call the real API?

The tests fake or mock the OpenAI HTTP/API request and provide predefined responses. The tests then verify that Laravel receives those fake responses and behaves correctly. Because the OpenAI client/request is intercepted, the test suite does not make a real request or require a real API key.

### 8. Why should a failed generation store a safe error code instead of the raw provider body?

Provider error bodies can contain sensitive information, implementation details, or data that should not be exposed to users. A safe error code such as `provider_error` or `invalid_response` gives the application enough information for logging and debugging without storing or displaying potentially sensitive provider details.

### 9. Why is the model name stored with each generation?

The model name records which OpenAI model produced that particular generation. Models can change their behavior, capabilities, or output quality over time. Storing the model makes each generation traceable and helps with debugging, auditing, and comparing results later.

### 10. What do input and output token counts tell you?

Input tokens represent how much text/data was sent to OpenAI, while output tokens represent how much content OpenAI generated. These values help monitor API usage, estimate costs, and understand how large prompts and responses are.

### 11. Which AI-generated coding suggestion did you reject or change, and why?

One suggestion I changed was to send the entire project or user object to OpenAI. I rejected that approach because it could expose unnecessary or private information. I changed the implementation to explicitly build the prompt using only `title`, `content_type`, `brief`, and optional `notes`, which follows the security requirements and minimizes the data sent to the provider.

### 12. What error took the most time today, what caused it, and how did you verify the fix?

The most time-consuming issue was that the OpenAI API was not working because my account had **0 credits**, so the API request could not be completed successfully. I also spent time understanding the **OpenAI service response verification and error cases**, especially how to distinguish a provider/API failure from a malformed or invalid structured response.
