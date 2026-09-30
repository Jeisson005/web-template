# AGENTS.md: Operational Interface for AI Agents

Operational commands, environmental constraints, and codebase conventions for automated agents working on this repository.

---

## 1. Explicit Commands

| Action | Working Directory | Command | Notes |
|---|---|---|---|
| **Build Frontend** | `frontend/` | `npm run build` | Compiles Astro SSG and generates Pagefind search index in `dist/`. |
| **Dev Frontend** | `frontend/` | `npm run dev` | Starts Astro dev server on `http://0.0.0.0:4321`. |
| **Generate Types** | `backend/` | `npm run generate:types` | Updates `backend/src/payload-types.ts`. Must copy to `frontend/src/lib/`. |
| **Dev Backend** | `backend/` | `npm run dev` | Starts Next.js/Payload server on `http://localhost:3000`. |
| **Lint Backend** | `backend/` | `npm run lint` | ESLint check for CMS backend. |
| **Test Backend** | `backend/` | `npm run test:int` | Runs Vitest integration suite. |
| **Start Stack** | Root | `docker compose up -d` | Launches CMS and Web containers. |
| **Rebuild Stack** | Root | `docker compose up -d --build` | Rebuilds and relaunches modified containers. |
| **Stop Stack** | Root | `docker compose down` | Halts all containers without deleting data volumes. |

---

## 2. Environment & Tooling Constraints

- **Package Manager**: Use `npm` across all subdirectories. Do not introduce yarn, pnpm, or bun lockfiles unless instructed.
- **Node Runtime**: Node.js 22 LTS (Alpine base in Docker).
- **Paths & Imports**: Always use the `@/*` alias for imports inside `frontend/src/` (configured in `frontend/tsconfig.json`). Never use deep relative paths (`../../`).

---

## 3. Mandatory Gotchas & Conventions

1. **Schema Modifications**:
   Whenever adding or changing fields in `backend/src/collections/` or `backend/src/globals/`:
   ```bash
   cd backend && npm run generate:types && cp src/payload-types.ts ../frontend/src/lib/payload-types.ts
   ```
2. **Database Engine Independence**:
   Never hardcode SQLite-specific queries or drivers. The database adapter is chosen dynamically via `DATABASE_URI` in `backend/src/payload.config.ts`.
3. **Zero Unnecessary Client JavaScript**:
   All new UI components must be native `.astro` files. Do not apply hydration directives (`client:load`, `client:only`) unless user interaction strictly requires client-side execution.
4. **Draft Resiliency**:
   The `Pages` collection uses `versions: { drafts: true }`. In frontend fetchers, always filter by `where[_status][equals]=published` or inspect document status safely.
5. **No Per-Document Rebuild Hooks**:
   Never trigger deployment webhooks on document save. Rebuilds are dispatched exclusively through the `Deploy` global or `POST /api/trigger-build`.
6. **Centralized Configuration**:
   Never hardcode `http://localhost:3000` or API endpoints in components. Always import from `@/lib/config`.

---

## 4. Operational Loop & Task Status

Use this section to record completed iterations and metrics during automated agent tasks:
- **Last Verified Build**: Frontend SSG build passed (3 pages, Pagefind indexed in 0.02s).
- **Service Availability**: CMS (port 3000) and Frontend (port 4321) responsive.
- **Active Spec**: `specs/template-core/spec.md`.
