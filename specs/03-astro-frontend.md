# Specification 03: Frontend & Static Site Generation (Astro)

## 1. Astro Configuration
- Engine: Astro v5+
- Mode: `output: 'static'` (Pure Static Site Generation)
- Dev Port: `4321`
- Host: `0.0.0.0` (accessible inside and outside Docker)

---

## 2. Core Implementation Highlights

### 2.1. Zero Runtime Client-Side JavaScript
By default, `.astro` pages compile to pure HTML and CSS without sending heavy JavaScript bundles to the browser.

### 2.2. Optional Multi-language Support (i18n)
- Configured with `routing: { prefixDefaultLocale: false }`.
- Single-language sites retain standard routes (`/`, `/contact`) without forced language subpaths.
- When multilingual pages are published, Astro routes localized paths (e.g. `/es/`) seamlessly.

### 2.3. Asset Optimization Strategies

1. **Build-Time Optimization (`astro:assets`) [Recommended]**:
   - During `npm run build`, Astro pulls image references, creates responsive `srcset` breakpoints, and converts them to modern formats (`.webp` and `.avif`) inside `dist/_astro/`.
   - Result: Production sites deliver optimized images directly from the CDN without needing live CMS access.
2. **Direct Delivery from URL / CDN**:
   - Uses public CDN URLs directly through native `<img>` tags.
   - Result: Faster build execution since images are not re-encoded.

### 2.4. Rich Text Transformation (Lexical to HTML)
Lexical saves rich text as structured AST JSON. The utility `frontend/src/lib/lexicalToHtml.ts` transforms this tree into clean semantic HTML (`<p>`, `<h2>`, `<ul>`, `<blockquote>`, `<code>`) and `<RichText />` injects it at build time using `set:html`.

### 2.5. Pagefind Search Engine
- Scans compiled HTML files in `frontend/dist/` post-build.
- Generates a lightweight static search index in `frontend/dist/pagefind/`.
- Operates entirely client-side with zero server infrastructure.
