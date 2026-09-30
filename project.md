# Project Map: Web Template

## 1. High-Level Vision
This repository provides a minimalist, decoupled, and production-ready starter template for building modern websites (corporate portals, landing pages, blogs, and documentation). It pairs pure static site generation (SSG) with a flexible headless content management system, delivering optimal performance with zero runtime client-side JavaScript by default.

---

## 2. System Status & Module Map

| Module | Location | Status | Description |
|---|---|---|---|
| **Core CMS** | `backend/` | **Stable** | Payload CMS 3 running on Next.js App Router with REST and GraphQL APIs. |
| **Collections & Schemas** | `backend/src/collections/` | **Stable** | `Pages` (with draft support), `Media` (uploads), `Messages` (contact submissions), and `Users`. |
| **Global Deployment Hook** | `backend/src/globals/Deploy.ts` | **Stable** | Centralized trigger to dispatch production rebuild webhooks. |
| **Frontend SSG** | `frontend/` | **Stable** | Astro v5+ static site generator with native `.astro` components. |
| **Design System** | `frontend/src/styles/` | **Stable** | Tailwind CSS v4 atomic utility styling with dark theme baseline. |
| **Static Search** | `frontend/src/components/SearchModal.astro` | **Stable** | Zero-server search indexed post-build via Pagefind. |
| **Asset Pipeline** | `backend/media/` & `frontend/src/lib/` | **Stable** | Local persistent storage with pre-configured S3 bucket and FTP compatibility. |
| **i18n Localization** | CMS & Frontend Config | **Ready (Optional)** | English default with Spanish routing prepared without mandatory subpath prefixes. |
| **Orchestration** | `docker-compose.yml` | **Stable** | Multi-container setup for CMS (port 3000) and Frontend (port 4321). |

---

## 3. Global Technical Strategy

- **Static Generation First**: The frontend compiles to pure HTML and CSS (`output: 'static'`). Client JavaScript is forbidden unless required for interactive widgets (e.g. search modal).
- **Database Engine Agnostic**: Embedded SQLite by default for zero-setup execution, dynamically switching to PostgreSQL when a PostgreSQL connection string is detected in `DATABASE_URI`.
- **Decoupled Asset Delivery**: Uploads are stored locally or in S3-compatible cloud storage, consumed directly via CDN or compiled into responsive formats at build time with `astro:assets`.
- **Centralized Deployment Lifecycle**: Content edits do not trigger automatic per-document builds. Site compilation is initiated in batches through the CMS deployment action or CI/CD pipelines.
