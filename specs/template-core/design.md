# Design: Core Web Template Architecture

## 1. Architectural Changes (Deltas)

### ADDED
- `cms/src/globals/Deploy.ts`: Centralized Global with `beforeChange` hook to dispatch `BUILD_WEBHOOK_URL`.
- `cms/src/collections/Pages.ts`: Collection with `versions: { drafts: true }`, Lexical RichText, and SEO fields.
- `cms/src/collections/Messages.ts`: Public-writable collection for contact submissions.
- `frontend/src/lib/config.ts`: Centralized URL configuration module preventing hardcoded hosts.
- `frontend/src/lib/lexicalToHtml.ts`: AST serializer handling paragraphs, headings, lists, quotes, code blocks, and media uploads.
- `docker-compose.yml`: Bridge network `web-template-net` with persistent volumes `cms-data` and `cms-media`.

### MODIFIED
- `cms/src/payload.config.ts`:
  - Added dynamic database resolution selecting between `@payloadcms/db-sqlite` and `@payloadcms/db-postgres`.
  - Added `s3Storage` conditional plugin mapping to `media` collection.
  - Added `cors` and `csrf` origin arrays matching `PUBLIC_SITE_URL`.
- `frontend/astro.config.mjs`:
  - Added `output: 'static'`, `@astrojs/sitemap`, `@tailwindcss/vite`, and non-prefixed i18n routing.
- `frontend/tsconfig.json`:
  - Added `compilerOptions.paths` alias `@/* -> src/*`.

### REMOVED
- Removed monolithic specification pages from frontend runtime navigation.

---

## 2. Key Data Contracts & Schemas

### Database Resolution Logic
```typescript
// cms/src/payload.config.ts
const dbUri = process.env.DATABASE_URI || 'file:./cms.db';
const isPostgres = dbUri.startsWith('postgres://') || dbUri.startsWith('postgresql://');

const databaseAdapter = isPostgres
  ? postgresAdapter({ pool: { connectionString: dbUri } })
  : sqliteAdapter({ client: { url: dbUri } });
```

### Global Rebuild Webhook Payload
```typescript
// Dispatched on Deploy Global save
interface RebuildEventPayload {
  event: 'site.rebuild';
  triggeredBy: string; // User email
  timestamp: string;   // ISO-8601 string
}
```

### Resilient SSG Page Filter
```typescript
// frontend/src/lib/payload.ts
export async function getPages(): Promise<Page[]> {
  const res = await fetchCollection<Page>('pages', { depth: '2' });
  return res.docs.filter((page: any) => !page._status || page._status === 'published');
}
```
