# AGENTS.md: AI Agent Operating Guidelines

This document governs behavior, architectural patterns, and development rules for any Artificial Intelligence agent working within this repository (`web-template`).

---

## 1. Core Mission
The primary objective of this repository is to serve as a **minimalist, modular, and production-ready starter template** for building any type of website. It enforces clean decoupling:
1. **CMS (`backend/`)**: Single source of truth for dynamic content, collections, configuration, and media uploads.
2. **Frontend (`frontend/`)**: Ultra-optimized static site generator using Astro, Tailwind CSS, Pagefind, and native SEO.
3. **Docker Compose**: Standard for reproducible local execution and deployment.

---

## 2. Golden Rules for Agents

### 2.1. Architecture & Code Quality
- **Simplicity & Minimalism**: Keep the user interface clean with only essentials. Do not add internal documentation or spec pages to the public user navigation.
- **Zero Unnecessary Client-Side JavaScript**: Use native `.astro` components. Avoid client directives (`client:load`, `client:only`) unless strictly required for user interactions (e.g. search modal).
- **Database Flexibility**: Never hardcode SQLite assumptions. The backend dynamically loads database drivers based on `DATABASE_URI` (SQLite by default, or PostgreSQL). Any database logic must remain engine-agnostic.
- **Asset Storage**: Media uploads support local persistent volumes, S3/Cloudflare R2 buckets, and FTP mounts.
- **Optional Multi-language Support**: i18n is pre-configured in Payload and Astro with `prefixDefaultLocale: false`. Single-language sites must function without mandatory language URL prefixes.
- **Global Deployment Hook**: Never trigger rebuild hooks on individual document saves. Rebuilds are managed exclusively through the `Deploy` global ("Deployment / Rebuild Site") or the `/api/trigger-build` endpoint.
- **Strict Payload Typing**: Whenever modifying collections in `backend/src/collections/` or globals in `backend/src/globals/`, run `npm run generate:types` in `backend/` and sync the types to `frontend/src/lib/payload-types.ts`.
- **Tailwind Styling**: Use clean utility classes consistent with the design system. Never write messy inline CSS.

---

## 3. Workflow Protocols

### Step 1: Create a New Collection or Global
1. Create the collection in `backend/src/collections/<CollectionName>.ts` or global in `backend/src/globals/<GlobalName>.ts`.
2. Register it in `backend/src/payload.config.ts`.
3. Run `npm run generate:types` inside `backend/`.
4. Copy the generated types to `frontend/src/lib/payload-types.ts`.

### Step 2: Create a New Page in the Frontend
1. Static route: Create `frontend/src/pages/<route>.astro`.
2. Dynamic CMS route: Create `frontend/src/pages/[slug].astro` implementing `getStaticPaths()` with fallback handling when the CMS has no entries yet.

### Step 3: Docker Orchestration
- Start the entire environment:
  ```bash
  docker compose up -d --build
  ```
- View real-time logs:
  ```bash
  docker compose logs -f
  ```
- Stop containers:
  ```bash
  docker compose down
  ```
