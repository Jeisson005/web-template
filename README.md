# Web Template

A high-performance, modular website starter combining **Astro Static Site Generation** and **Payload CMS 3**. Designed for building ultra-fast websites with zero client-side JavaScript overhead and an intuitive visual management dashboard.

---

## ⚡ Quickstart for Developers

### Prerequisites
- [Docker](https://docs.docker.com/get-docker/) & [Docker Compose](https://docs.docker.com/compose/)
- [Node.js](https://nodejs.org/) v20+ (if running scripts outside Docker)

### 1. Clone & Configure
```bash
git clone https://github.com/Jeisson005/web-template.git
cd web-template
cp .env.example .env
```

### 2. Start the Local Environment
Run the entire stack with Docker Compose:
```bash
docker compose up -d --build
```

### 3. Access Services
- **Website Preview**: [http://localhost:4321](http://localhost:4321)
- **CMS Admin Dashboard**: [http://localhost:3000/admin](http://localhost:3000/admin)
- **Contact Page**: [http://localhost:4321/contact](http://localhost:4321/contact)

---

## 🧭 Architecture Highlights

- **Frontend**: Astro v5+ configured in static mode (`output: 'static'`) with Tailwind CSS v4 and Pagefind offline search.
- **CMS**: Payload CMS 3 running on Node 22 with SQLite by default (or PostgreSQL by updating `DATABASE_URI` in `.env`).
- **Media Storage**: Local Docker persistent volume (`cms-media`) with instant support for AWS S3, Cloudflare R2, or MinIO via environment variables.
- **Global Rebuild**: Trigger full site recompilations with a single button under **"Deployment / Rebuild Site"** in the CMS admin panel.

---

## 🛠️ Management Commands

```bash
# View real-time logs
docker compose logs -f

# Stop all containers
docker compose down

# Manual static build outside Docker
cd frontend && npm install && npm run build
```
