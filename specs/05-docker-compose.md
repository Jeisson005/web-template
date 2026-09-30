# Specification 05: Docker Compose Orchestration

## 1. Container Topology

The architecture runs reproducibly with two services connected on the `web-template-net` bridge network:

| Container | Base Image | Port Mapping | Role |
|---|---|---|---|
| `web-template-cms` | `node:22-alpine` | `3000:3000` | Payload CMS v3 (Next.js) with multi-database support |
| `web-template-web` | `node:22-alpine` | `4321:4321` | Astro dev and static preview server |

---

## 2. Data Persistence
Managed via two Docker volumes:
1. `cms-data`: Stores SQLite `backend.db` and database files.
2. `cms-media`: Stores file uploads located at `/app/media`.

---

## 3. Connecting to PostgreSQL
To use external PostgreSQL instead of SQLite, define `DATABASE_URI` in `.env` or `docker-compose.yml`:
```yaml
environment:
  - DATABASE_URI=postgresql://user:password@postgres_host:5432/dbname
```
Payload CMS automatically switches to `@payloadcms/db-postgres`.

---

## 4. Useful Commands
```bash
# Start all containers in the background
docker compose up -d

# Rebuild images after dependency changes
docker compose up -d --build

# View real-time logs
docker compose logs -f

# Stop containers
docker compose down
```
