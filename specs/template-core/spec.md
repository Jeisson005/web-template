# Spec: Core Web Template Architecture

## 1. Goal
Provide a decoupled, production-ready website template integrating Astro Static Site Generation (SSG) with Payload CMS 3 and multi-database support (SQLite default, PostgreSQL optional), running under Docker Compose.

---

## 2. Requirements & Expected Behavior

### 2.1. Frontend Delivery
- **R1.1**: The website MUST compile to static HTML and CSS (`output: 'static'`) with 0 KB client-side JavaScript on initial load.
- **R1.2**: Content from the CMS must be retrieved at build time via `getStaticPaths()`. If no CMS entries exist, the build MUST NOT crash and MUST provide safe fallback routes.
- **R1.3**: The contact form MUST submit data to the Payload REST API `/api/messages` with progressive enhancement (functional with or without client JavaScript).
- **R1.4**: Static search MUST index compiled pages automatically post-build via Pagefind without requiring server-side runtime queries.

### 2.2. Content Management (CMS)
- **R2.1**: The CMS MUST expose a visual administration panel at `/admin` and REST endpoints at `/api`.
- **R2.2**: The CMS MUST support embedded SQLite by default and dynamically connect to PostgreSQL when `DATABASE_URI` starts with `postgres://` or `postgresql://`.
- **R2.3**: Uploaded media MUST be persisted in a local Docker volume by default, with opt-in support for S3-compatible cloud buckets when `S3_BUCKET` is defined.
- **R2.4**: Content editors MUST have a single centralized action in the CMS (`Deploy` global) to dispatch full site rebuild webhooks. Automated rebuild triggers on individual document saves are strictly prohibited.

---

## 3. Acceptance Criteria

1. **Static Build**: Running `npm run build` inside `frontend/` succeeds in under 2 seconds and produces `dist/` containing `index.html`, `contact/index.html`, and `dist/pagefind/`.
2. **Containerization**: Running `docker compose up -d` boots both `web-template-cms` (port 3000) and `web-template-web` (port 4321) in healthy/running state.
3. **Draft Safety**: Draft pages saved in Payload CMS do not appear on public SSG routes until published.
4. **Resilience**: The frontend builds cleanly even if the CMS is completely empty or temporarily unreachable during build time.
