import type { Plugin, UploadCollectionSlug } from 'payload';
import { cloudStoragePlugin } from '@payloadcms/plugin-cloud-storage';
import type { CollectionOptions } from '@payloadcms/plugin-cloud-storage/types';
import { createFtpAdapter } from './adapter';
import type { FtpStorageOptions } from './types';

export const ftpStorage = (options: FtpStorageOptions): Plugin => {
  return (incomingConfig) => {
    if (options.enabled === false) {
      return incomingConfig;
    }

    const adapter = createFtpAdapter(options.config);

    const collections = Object.entries(options.collections).reduce<
      Partial<Record<UploadCollectionSlug, CollectionOptions>>
    >((acc, [slug, collOptions]) => {
      const prefix = typeof collOptions === 'object' && collOptions !== null ? collOptions.prefix : undefined;
      acc[slug as UploadCollectionSlug] = {
        adapter,
        prefix,
        disableLocalStorage: true,
      };
      return acc;
    }, {});

    return cloudStoragePlugin({
      collections,
    })(incomingConfig);
  };
};

export type { FtpStorageOptions, FtpConfig } from './types';
