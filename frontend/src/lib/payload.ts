import type { Page, Message, Media } from './payload-types';
import { CMS_INTERNAL_URL, PUBLIC_CMS_URL } from './config';

export interface PayloadResponse<T> {
  docs: T[];
  totalDocs: number;
  limit: number;
  totalPages: number;
  page: number;
  pagingCounter: number;
  hasPrevPage: boolean;
  hasNextPage: boolean;
  prevPage: number | null;
  nextPage: number | null;
}

/**
 * Generic typed fetcher for Payload CMS collections
 */
export async function fetchCollection<T>(
  collection: string,
  queryParams: Record<string, string> = {}
): Promise<PayloadResponse<T>> {
  const query = new URLSearchParams(queryParams).toString();
  const url = `${CMS_INTERNAL_URL}/api/${collection}${query ? `?${query}` : ''}`;
  
  try {
    const res = await fetch(url, {
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!res.ok) {
      console.warn(`[Payload Client] Response ${res.status} ${res.statusText} from ${url}`);
      return emptyResponse<T>();
    }

    return await res.json();
  } catch (error) {
    console.warn(`[Payload Client] Could not reach Payload at ${url}:`, (error as Error).message);
    return emptyResponse<T>();
  }
}

/**
 * Retrieve published pages for SSG compilation.
 * Resilient to collections with or without drafts enabled.
 */
export async function getPages(): Promise<Page[]> {
  const res = await fetchCollection<Page>('pages', { depth: '2' });
  return res.docs.filter((page: any) => !page._status || page._status === 'published');
}

/**
 * Retrieve a single published page by its unique slug.
 */
export async function getPageBySlug(slug: string): Promise<Page | null> {
  const res = await fetchCollection<Page>('pages', {
    'where[slug][equals]': slug,
    depth: '2',
  });
  const page = res.docs[0];
  if (!page) return null;
  if ((page as any)._status && (page as any)._status !== 'published') return null;
  return page;
}

function emptyResponse<T>(): PayloadResponse<T> {
  return {
    docs: [],
    totalDocs: 0,
    limit: 10,
    totalPages: 0,
    page: 1,
    pagingCounter: 1,
    hasPrevPage: false,
    hasNextPage: false,
    prevPage: null,
    nextPage: null,
  };
}

export { CMS_INTERNAL_URL, PUBLIC_CMS_URL };
