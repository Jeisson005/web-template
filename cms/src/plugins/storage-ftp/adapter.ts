import * as ftp from 'basic-ftp';
import { Readable } from 'stream';
import path from 'path';
import type { Adapter } from '@payloadcms/plugin-cloud-storage/types';
import type { FtpConfig } from './types';

export function createFtpAdapter(ftpConfig: FtpConfig): Adapter {
  const publicBaseUrl = (ftpConfig.publicUrl || `https://${ftpConfig.host}/media`).replace(/\/+$/, '');
  const remoteDir = ftpConfig.remoteDir || '/media';

  const getFtpClient = async () => {
    const client = new ftp.Client();
    client.ftp.verbose = process.env.NODE_ENV === 'development';
    await client.access({
      host: ftpConfig.host,
      user: ftpConfig.user,
      password: ftpConfig.password,
      port: ftpConfig.port || 21,
      secure: ftpConfig.secure ?? false,
    });
    return client;
  };

  return ({ collection, prefix = '' }) => ({
    name: 'ftp',
    generateURL: ({ filename, prefix: urlPrefix = '' }) => {
      const parts = [publicBaseUrl];
      const effectivePrefix = urlPrefix || prefix;
      if (effectivePrefix) {
        parts.push(effectivePrefix.replace(/^\/+|\/+$/g, ''));
      }
      parts.push(filename);
      return parts.join('/');
    },
    handleUpload: async ({ file, storageFilePath }) => {
      const client = await getFtpClient();
      try {
        const fullRemotePath = path.posix.join(remoteDir, storageFilePath);
        const remoteTargetDir = path.posix.dirname(fullRemotePath);
        await client.ensureDir(remoteTargetDir);

        if (file.buffer) {
          const readable = Readable.from(file.buffer);
          await client.uploadFrom(readable, fullRemotePath);
        } else if (file.tempFilePath) {
          await client.uploadFrom(file.tempFilePath, fullRemotePath);
        }
      } finally {
        client.close();
      }
    },
    handleDelete: async ({ storageFilePath }) => {
      const client = await getFtpClient();
      try {
        const fullRemotePath = path.posix.join(remoteDir, storageFilePath);
        await client.remove(fullRemotePath).catch(() => {});
      } finally {
        client.close();
      }
    },
    staticHandler: async (req, { params: { filename, prefix: urlPrefix = '' } }) => {
      const parts = [publicBaseUrl];
      const effectivePrefix = urlPrefix || prefix;
      if (effectivePrefix) {
        parts.push(effectivePrefix.replace(/^\/+|\/+$/g, ''));
      }
      parts.push(filename);
      return Response.redirect(parts.join('/'), 302);
    },
  });
}
