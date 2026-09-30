# Project Overview: Modular Web Template (Astro SSG + Headless CMS)

## 1. Vision and Purpose
This template provides a minimalist, decoupled, and production-ready web development architecture designed for maximum performance and maintainability. It enables building any type of website (corporate portals, landing pages, blogs, documentation, portfolios) with:
- **0 KB client-side JavaScript by default** via **Astro** Static Site Generation (SSG).
- **Professional, typed content management** powered by **Payload CMS 3**.
- **Database Agnostic**: SQLite by default requiring zero setup, with instant support for PostgreSQL or other Payload-compatible database engines simply by switching the `DATABASE_URI` environment variable.
- **Flexible Asset Storage**: Local persistent storage by default, with out-of-the-box support for S3-compatible cloud buckets (AWS S3, Cloudflare R2, MinIO) and FTP servers.
- **i18n Ready**: Internationalization pre-configured in both the CMS and frontend, with no mandatory language prefix for single-language sites.
- **Global Deployment Hook**: Centralized rebuild action and endpoint in the CMS to trigger website recompilation once content editing is finished, avoiding unwanted automated builds on every individual draft save.
- **Reproducible containerized development** with **Docker Compose**.

---

## 2. Technology Stack

| Layer | Technology | Role in Project |
|---|---|---|
| **Headless CMS** | Payload CMS v3 | Content administration panel, REST API, and schema builder. |
| **Database** | SQLite (default) / PostgreSQL | Source of truth for content. Embedded local SQLite or PostgreSQL via `@payloadcms/db-postgres`. |
| **Asset Storage** | Local / S3 Buckets / FTP | Upload handling and media delivery. |
| **Strict Typing** | Payload Types Generator (`generate:types`) | TypeScript interface synchronization between Payload and frontend. |
| **Web Generator (SSG)** | Astro (`output: 'static'`) | Pure HTML/CSS static compilation at build time (`npm run build`). |
| **Styling** | Tailwind CSS v4 | Atomic utility design system, purged and minified on build. |
| **Rich Text** | Lexical (`@payloadcms/richtext-lexical`) | Visual rich text editor compiled to semantic HTML in Astro. |
| **Search Engine** | Pagefind | Zero-server static search indexed post-build. |
| **SEO & Sitemaps** | `@astrojs/sitemap` | Automated `sitemap.xml` and OpenGraph metadata generation. |
| **Orchestration** | Docker Compose | Reproducible containers for Backend (CMS) and Frontend (Astro). |

---

## 3. Media & Asset Management

### How are assets handled in this template?
1. **CMS Uploads**: When an editor uploads an image or file to the `Media` collection, Payload saves it using the configured storage driver:
   - **Local / Volume**: Mounted directory at `media/` inside the Docker container.
   - **Cloud Buckets**: AWS S3, Cloudflare R2, MinIO, or DigitalOcean Spaces via `@payloadcms/storage-s3`.
   - **FTP Server**: Via remote volume mounts (SSHFS/Rclone) or synchronization hooks.
2. **Direct Delivery vs. Build-Time Compilation**:
   - **Option A (Recommended for SSG - Build Optimization)**: During `npm run build`, Astro pulls the image reference, optimizes dimensions (`srcset`), and converts it to modern WebP/AVIF formats in `dist/_astro/`. The static site serves images directly from your static hosting or CDN without querying the CMS or bucket at runtime.
   - **Option B (Direct Delivery from CDN / Bucket)**: The frontend can render standard `<img>` tags pointing directly to the public CDN/bucket URL (e.g. `https://cdn.yourdomain.com/media/...`), minimizing Astro build times.

---

## 4. Global Deployment Hook

The CMS includes the **`Deployment / Rebuild Site`** Global (`backend/src/globals/Deploy.ts`) and the `POST /api/trigger-build` endpoint:
- **Purpose**: Allows editors to make batch edits across multiple collections and trigger a single compilation when ready.
- **Action**: When `BUILD_WEBHOOK_URL` is set, it dispatches an HTTP POST request to your CI/CD runner (GitHub Actions, Cloudflare Pages, Vercel, Netlify, or local Docker runner).
- **Safety**: Prevents redundant or premature build triggers on intermediate drafts.

---

## 5. Directory Structure

```text
.
├── backend/                  # Payload CMS 3 (Next.js App Router)
│   ├── src/
│   │   ├── collections/      # Collections (Pages, Media, Messages, Users)
│   │   ├── globals/          # Deploy.ts (Global rebuild trigger)
│   │   ├── payload.config.ts # Multi-database and storage configuration
│   │   └── app/              # Admin and API routes
│   ├── Dockerfile            # Payload Docker image
│   └── package.json
│
├── frontend/                 # Astro Frontend (SSG + Tailwind + Pagefind)
│   ├── src/
│   │   ├── components/       # Reusable UI components
│   │   ├── layouts/          # Base layout with SEO and Pagefind
│   │   ├── pages/            # Static routes (index, contact, [slug])
│   │   └── lib/              # Payload client, Lexical serializer, types
│   ├── Dockerfile            # Astro Docker image
│   ├── astro.config.mjs      # Astro, Tailwind, and Sitemap configuration
│   └── package.json
│
├── specs/                    # Internal technical specifications
│   ├── 01-architecture.md
│   ├── 02-payload-cms.md
│   ├── 03-astro-frontend.md
│   ├── 04-content-and-workflows.md
│   └── 05-docker-compose.md
│
├── docker-compose.yml        # Service orchestration
├── config.yaml               # Technical service parameters
├── AGENTS.md                 # AI agent operating guidelines
└── README.md                 # Quickstart guide
```
