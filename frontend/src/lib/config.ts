/**
 * Centralized Application Configuration
 * Encapsulates environment variables and fallback URLs for DRY consistency.
 */

export const PUBLIC_CMS_URL = 
  import.meta.env.PUBLIC_CMS_URL || 'http://localhost:3000';

export const CMS_INTERNAL_URL = 
  import.meta.env.CMS_INTERNAL_URL || PUBLIC_CMS_URL;

export const CMS_ADMIN_URL = `${PUBLIC_CMS_URL}/admin`;
export const CMS_MESSAGES_ENDPOINT = `${PUBLIC_CMS_URL}/api/messages`;
export const CMS_PAGES_ADMIN_URL = `${PUBLIC_CMS_URL}/admin/collections/pages`;
