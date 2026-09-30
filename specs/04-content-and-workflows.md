# Specification 04: Content Lifecycle & Workflows

## 1. Decoupled Content Lifecycle
The standard workflow for editing, publishing, and rebuilding follows this sequence:

```mermaid
sequenceDiagram
    autonumber
    actor Editor as Content Editor
    participant CMS as Payload CMS
    participant Webhook as Webhook / CI Pipeline
    participant Astro as Astro SSG
    participant Prod as Static Hosting (CDN)

    Editor->>CMS: Edits pages, text, or uploads media
    CMS->>CMS: Persists to DB (SQLite or Postgres) & Storage (Local or S3)
    Note over Editor,CMS: Multiple edits can be made without triggering builds
    Editor->>CMS: In "Deploy", clicks "Save / Rebuild Site"
    CMS->>Webhook: Dispatches POST request to BUILD_WEBHOOK_URL
    Webhook->>Astro: Runs SSG build (npm run build)
    Astro->>CMS: Fetches published data via REST API
    Astro->>Astro: Compiles HTML & builds Pagefind index
    Astro->>Prod: Deploys /dist static bundle to CDN
```

---

## 2. Creating a New Website Using this Template
1. **Configure Database**:
   - Keep SQLite for default development.
   - Set `DATABASE_URI=postgresql://...` in `.env` for PostgreSQL.
2. **Configure Storage**:
   - Retain local storage or set `S3_BUCKET` for Cloudflare R2 / AWS S3.
3. **Set Domain & Metadata**:
   - Adjust `site` in `frontend/astro.config.mjs` (e.g. `site: 'https://mydomain.com'`).
4. **Create Custom Collections**:
   - Add collections in `backend/src/collections/` and run `npm run generate:types`.
5. **Set Production Webhook**:
   - Set `BUILD_WEBHOOK_URL` in `.env` to hook into your hosting provider or CI runner.
