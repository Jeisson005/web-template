/**
 * Lexical AST to Clean Semantic HTML Serializer for Astro SSG
 * Converts Payload CMS Lexical rich text AST into clean, accessible HTML
 * with zero runtime JavaScript in the user's browser.
 */

interface LexicalNode {
  type: string;
  version?: number;
  text?: string;
  tag?: string;
  format?: number | string;
  style?: string;
  children?: LexicalNode[];
  direction?: string | null;
  indent?: number;
  url?: string;
  target?: string;
  rel?: string;
  listType?: 'number' | 'bullet' | 'check';
  value?: {
    url?: string;
    alt?: string;
    caption?: string;
    [key: string]: any;
  };
  [key: string]: any;
}

export function lexicalToHtml(rootOrContent: any): string {
  if (!rootOrContent) return '';

  const root = rootOrContent.root || rootOrContent;
  if (!root || !Array.isArray(root.children)) {
    if (typeof rootOrContent === 'string') return escapeHtml(rootOrContent);
    return '';
  }

  return root.children.map(renderNode).join('');
}

function renderNode(node: LexicalNode): string {
  if (!node) return '';

  switch (node.type) {
    case 'text': {
      let text = escapeHtml(node.text || '');
      // Format bitmasks in Lexical:
      // 1 = bold, 2 = italic, 4 = strikethrough, 8 = underline, 16 = code
      const format = typeof node.format === 'number' ? node.format : 0;
      if (format & 1) text = `<strong>${text}</strong>`;
      if (format & 2) text = `<em>${text}</em>`;
      if (format & 4) text = `<s>${text}</s>`;
      if (format & 8) text = `<u>${text}</u>`;
      if (format & 16) text = `<code class="px-1.5 py-0.5 rounded bg-zinc-800 text-emerald-400 text-sm font-mono">${text}</code>`;
      return text;
    }

    case 'paragraph': {
      const childrenHtml = (node.children || []).map(renderNode).join('');
      if (!childrenHtml.trim()) return '<p class="my-2">&nbsp;</p>';
      return `<p class="my-3 text-zinc-300 leading-relaxed">${childrenHtml}</p>`;
    }

    case 'heading': {
      const tag = node.tag || 'h2';
      const childrenHtml = (node.children || []).map(renderNode).join('');
      const classMap: Record<string, string> = {
        h1: 'text-3xl sm:text-4xl font-extrabold text-white mt-8 mb-4 tracking-tight',
        h2: 'text-2xl sm:text-3xl font-bold text-white mt-6 mb-3 tracking-tight',
        h3: 'text-xl sm:text-2xl font-semibold text-white mt-5 mb-2',
        h4: 'text-lg sm:text-xl font-semibold text-zinc-100 mt-4 mb-2',
        h5: 'text-base font-semibold text-zinc-200 mt-3 mb-1',
        h6: 'text-sm font-semibold text-zinc-300 mt-2 mb-1 uppercase tracking-wider',
      };
      const cls = classMap[tag] || classMap.h2;
      return `<${tag} class="${cls}">${childrenHtml}</${tag}>`;
    }

    case 'list': {
      const tag = node.listType === 'number' ? 'ol' : 'ul';
      const cls = node.listType === 'number' ? 'list-decimal' : 'list-disc';
      const childrenHtml = (node.children || []).map(renderNode).join('');
      return `<${tag} class="${cls} pl-6 my-4 space-y-1.5 text-zinc-300">${childrenHtml}</${tag}>`;
    }

    case 'listitem': {
      const childrenHtml = (node.children || []).map(renderNode).join('');
      return `<li class="leading-relaxed">${childrenHtml}</li>`;
    }

    case 'quote': {
      const childrenHtml = (node.children || []).map(renderNode).join('');
      return `<blockquote class="border-l-4 border-emerald-500 pl-4 py-2 my-4 italic text-zinc-300 bg-zinc-900/60 rounded-r-lg">${childrenHtml}</blockquote>`;
    }

    case 'code': {
      const childrenHtml = (node.children || []).map(renderNode).join('');
      return `<pre class="p-4 rounded-xl bg-zinc-900 border border-zinc-800 overflow-x-auto my-4 text-sm font-mono text-emerald-400"><code>${childrenHtml}</code></pre>`;
    }

    case 'horizontalrule': {
      return '<hr class="my-8 border-zinc-800" />';
    }

    case 'link': {
      const url = escapeHtml(node.url || '#');
      const target = node.newTab ? ' target="_blank" rel="noopener noreferrer"' : '';
      const childrenHtml = (node.children || []).map(renderNode).join('');
      return `<a href="${url}" class="text-emerald-400 hover:text-emerald-300 underline underline-offset-4 transition-colors"${target}>${childrenHtml}</a>`;
    }

    case 'upload': {
      const media = node.value;
      if (!media || !media.url) return '';
      return `<figure class="my-6"><img src="${escapeHtml(media.url)}" alt="${escapeHtml(media.alt || '')}" class="rounded-xl border border-zinc-800 max-w-full h-auto" loading="lazy" />${media.caption ? `<figcaption class="text-xs text-zinc-400 text-center mt-2">${escapeHtml(media.caption)}</figcaption>` : ''}</figure>`;
    }

    default: {
      if (Array.isArray(node.children)) {
        return node.children.map(renderNode).join('');
      }
      return '';
    }
  }
}

function escapeHtml(str: string): string {
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
