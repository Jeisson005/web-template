# Tasks: Core Web Template Architecture

Sequential task breakdown for automated implementation and verification.

- [x] **Task 1: Backend Scaffolding & Multi-DB Configuration**
  - [x] Install `@payloadcms/db-postgres` and `@payloadcms/storage-s3` in `backend/`.
  - [x] Implement dynamic database resolution in `backend/src/payload.config.ts`.
  - [x] Add CORS and CSRF origin whitelisting in Payload config.

- [x] **Task 2: Content Collections & Rebuild Global**
  - [x] Create `Pages` collection with `versions: { drafts: true }`.
  - [x] Create `Messages` collection with public create permissions.
  - [x] Create `Deploy` global with `beforeChange` webhook dispatcher.
  - [x] Run `npm run generate:types` and sync types to `frontend/src/lib/payload-types.ts`.

- [x] **Task 3: Frontend SSG & Styling Foundation**
  - [x] Configure Astro in static mode with Tailwind CSS v4 and sitemap plugin.
  - [x] Set path alias `@/* -> src/*` in `frontend/tsconfig.json`.
  - [x] Centralize URLs in `frontend/src/lib/config.ts`.
  - [x] Implement `frontend/src/lib/lexicalToHtml.ts` with code blocks and horizontal rule support.
  - [x] Implement `frontend/src/styles/global.css` with focus-visible accessibility.

- [x] **Task 4: Interactive Components & Offline Search**
  - [x] Create `SearchModal.astro` loading Pagefind dynamically.
  - [x] Create `ContactForm.astro` with progressive enhancement and status banners.
  - [x] Configure post-build script: `astro build && pagefind --site dist`.

- [x] **Task 5: Container Orchestration & OpenSpec Compliance**
  - [x] Create root `docker-compose.yml` with health checks and volume persistence.
  - [x] Create `project.md` (high-level map), `README.md` (human onboarding), and `AGENTS.md` (agent manual).
  - [x] Verify static build passes cleanly under 1 second.
  - [x] Verify Docker Compose boots both containers with HTTP 200 responses.
