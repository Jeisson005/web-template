# Specification 02: Content and Data Layer (Payload CMS 3)

## 1. Role and Responsibility
Payload CMS serves as the centralized headless CMS for the project:
- Web administration panel accessible at `http://localhost:3000/admin`.
- REST API at `http://localhost:3000/api`.
- Strict TypeScript schema generation (`payload-types.ts`).

---

## 2. Supported Databases
The template decouples database logic and automatically loads the driver according to `DATABASE_URI`:

### 2.1. SQLite (Default)
- Driver: `@payloadcms/db-sqlite`
- Environment variable: `DATABASE_URI=file:/app/data/backend.db`
- Advantages: Zero external infrastructure, ideal for fast development and standard sites.

### 2.2. PostgreSQL
- Driver: `@payloadcms/db-postgres`
- Environment variable: `DATABASE_URI=postgresql://user:password@host:5432/dbname`
- Advantages: High concurrency and cloud compatibility.

---

## 3. Media & Asset Storage

### 3.1. Local Storage (Default)
- Directory: `MEDIA_DIR=/app/media` persisted inside the `cms-media` Docker volume.
- Direct image delivery from `/media/...`.

### 3.2. Cloud Buckets (AWS S3 / Cloudflare R2 / MinIO)
- Driver: `@payloadcms/storage-s3`
- Activated when `S3_BUCKET` is configured in `.env`.
- Uploads go directly to the bucket, storing public URLs or keys.

### 3.3. FTP / SFTP Servers
- Mount remote FTP/SFTP directories directly to `/app/media` via volume plugins (e.g. Rclone or SSHFS).

---

## 4. Global Deployment Hook (`Deploy` Global)
Instead of dispatching webhooks on every document draft save:
1. The CMS provides the **`Deployment / Rebuild Site`** Global (`backend/src/globals/Deploy.ts`).
2. Editors make all batch updates and click **Save / Rebuild** when finished.
3. The hook dispatches a POST request to `BUILD_WEBHOOK_URL` (GitHub Actions, Vercel, Netlify, or local runner).
4. An endpoint `POST /api/trigger-build` is also exposed for external automations.

---

## 5. Registered Collections
- `Users`: Panel administrators.
- `Media`: File uploads, images, and metadata.
- `Pages`: Dynamic and static landing pages.
- `Messages`: Native HTML form submissions.
