# 🚀 Web Template (Astro SSG + Payload CMS 3)

A minimalist, decoupled, and production-ready static website starter powered by **Astro** (0 KB client-side JS baseline) and **Payload CMS 3** with multi-database support (SQLite / PostgreSQL).

---

## 🌐 Access Endpoints

Once launched via Docker Compose:

| Service | URL | Description |
|---|---|---|
| **Website (Astro SSG)** | [http://localhost:4321](http://localhost:4321) | Live static site preview with Tailwind CSS v4 |
| **CMS Admin Panel** | [http://localhost:3000/admin](http://localhost:3000/admin) | Content management interface, pages, and users |
| **CMS REST API** | [http://localhost:3000/api](http://localhost:3000/api) | Public and authenticated REST endpoints |
| **Contact (Native Form)** | [http://localhost:4321/contact](http://localhost:4321/contact) | Pure HTML form sending POST to `/api/messages` |
| **Global Rebuild Action** | CMS Sidebar: **"Deployment / Rebuild Site"** | Single-click global production recompilation |

---

## 🛠️ Technology Stack

- **Frontend**: Astro v5+ (`output: 'static'`), Tailwind CSS v4.
- **Backend / CMS**: Payload CMS v3, Next.js App Router.
- **Database**: SQLite native (`@payloadcms/db-sqlite`) by default, instant switch to PostgreSQL (`@payloadcms/db-postgres`).
- **Storage**: Local persistent Docker volume, or cloud buckets (AWS S3, Cloudflare R2, MinIO) via `@payloadcms/storage-s3`.
- **Search**: Pagefind (zero-server static index generated post-build).
- **SEO & Sitemaps**: `@astrojs/sitemap` automated `sitemap.xml` generation.
- **Rich Text**: Lexical to semantic HTML converter rendered via `set:html`.
- **Containerization**: Docker Compose.

---

## 📋 Architecture & Agent Guides

- [project.md](file:///home/jeisson/Documents/Artic%20proyectos/web-template/project.md): Project overview, full stack description, and guidelines.
- [config.yaml](file:///home/jeisson/Documents/Artic%20proyectos/web-template/config.yaml): Service parameters, runtimes, and ports.
- [AGENTS.md](file:///home/jeisson/Documents/Artic%20proyectos/web-template/AGENTS.md): Directives for AI coding assistants.
- [specs/](file:///home/jeisson/Documents/Artic%20proyectos/web-template/specs): Technical specifications for architecture, CMS, frontend, workflows, and Docker.

---

## 💻 Quickstart Commands

```bash
# Start all services with Docker Compose
docker compose up -d

# View live container logs
docker compose logs -f

# Stop containers
docker compose down

# Build static frontend and generate Pagefind search index
cd frontend && npm run build
```
