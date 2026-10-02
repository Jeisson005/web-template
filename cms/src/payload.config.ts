import { sqliteAdapter } from '@payloadcms/db-sqlite'
import { postgresAdapter } from '@payloadcms/db-postgres'
import { lexicalEditor } from '@payloadcms/richtext-lexical'
import { s3Storage } from '@payloadcms/storage-s3'
import path from 'path'
import { buildConfig } from 'payload'
import { fileURLToPath } from 'url'
import sharp from 'sharp'

import { Users } from './collections/Users'
import { Media } from './collections/Media'
import { Pages } from './collections/Pages'
import { Messages } from './collections/Messages'
import { Deploy } from './globals/Deploy'
import { ftpStorage } from './plugins/storage-ftp'

const filename = fileURLToPath(import.meta.url)
const dirname = path.dirname(filename)

// 1. Flexible database resolution (SQLite by default, or Postgres if specified in DATABASE_URI)
const dbUri = process.env.DATABASE_URI || process.env.DATABASE_URL || 'file:./cms.db'
const isPostgres = dbUri.startsWith('postgres://') || dbUri.startsWith('postgresql://') || process.env.DB_ADAPTER === 'postgres'

const databaseAdapter = isPostgres
  ? postgresAdapter({
      pool: {
        connectionString: dbUri,
      },
    })
  : sqliteAdapter({
      client: {
        url: dbUri,
      },
    })

// 2. Storage resolution:
//    - FTP (GoDaddy or custom FTP server) if FTP_HOST or GODADDY_USER is provided
//    - S3 / Cloudflare R2 / MinIO if S3_BUCKET is provided
//    - Local persistent directory otherwise (MEDIA_DIR)
const plugins = []

const ftpHost = process.env.FTP_HOST || process.env.GODADDY_HOST || process.env.HOST
const ftpUser = process.env.FTP_USER || process.env.GODADDY_USER

if (ftpHost && ftpUser) {
  const publicHost = process.env.SITE_URL || `https://${ftpHost}`
  const remotePath = process.env.FTP_REMOTE_DIR || `${process.env.GODADDY_FRONTEND_PATH || ''}/media`

  plugins.push(
    ftpStorage({
      collections: {
        media: true,
      },
      config: {
        host: ftpHost,
        user: ftpUser,
        password: process.env.FTP_PASSWORD || process.env.GODADDY_PASSWORD || '',
        port: process.env.FTP_PORT ? parseInt(process.env.FTP_PORT, 10) : 21,
        secure: process.env.FTP_SECURE === 'true',
        remoteDir: remotePath.startsWith('/') ? remotePath : `/${remotePath}`,
        publicUrl: process.env.FTP_PUBLIC_URL || `${publicHost.replace(/\/+$/, '')}/media`,
      },
    })
  )
} else if (process.env.S3_BUCKET) {
  plugins.push(
    s3Storage({
      collections: {
        media: true,
      },
      bucket: process.env.S3_BUCKET,
      config: {
        credentials: {
          accessKeyId: process.env.S3_ACCESS_KEY_ID || '',
          secretAccessKey: process.env.S3_SECRET_ACCESS_KEY || '',
        },
        region: process.env.S3_REGION || 'auto',
        endpoint: process.env.S3_ENDPOINT,
        forcePathStyle: process.env.S3_FORCE_PATH_STYLE === 'true',
      },
    })
  )
}

export default buildConfig({
  admin: {
    user: Users.slug,
    importMap: {
      baseDir: path.resolve(dirname),
    },
  },
  cors: [
    process.env.PUBLIC_SITE_URL || 'http://localhost:4321',
    'http://localhost:4321',
  ].filter(Boolean),
  csrf: [
    process.env.PUBLIC_SITE_URL || 'http://localhost:4321',
    'http://localhost:4321',
  ].filter(Boolean),
  // Optional i18n localization (English default, ready for Spanish)
  localization: {
    locales: [
      { label: 'English', code: 'en' },
      { label: 'Spanish', code: 'es' },
    ],
    defaultLocale: 'en',
    fallback: true,
  },
  collections: [Users, Media, Pages, Messages],
  globals: [Deploy],
  editor: lexicalEditor(),
  secret: process.env.PAYLOAD_SECRET || 'super-secret-payload-key-change-in-prod-12345',
  typescript: {
    outputFile: path.resolve(dirname, 'payload-types.ts'),
  },
  db: databaseAdapter,
  sharp,
  plugins,
  endpoints: [
    // REST endpoint to trigger programmatic site rebuilds
    {
      path: '/trigger-build',
      method: 'post',
      handler: async (req) => {
        if (!req.user) {
          return Response.json({ error: 'Unauthorized' }, { status: 401 })
        }

        const buildWebhook = process.env.BUILD_WEBHOOK_URL
        if (buildWebhook) {
          try {
            const res = await fetch(buildWebhook, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                event: 'site.rebuild',
                triggeredBy: req.user.email,
                timestamp: new Date().toISOString(),
              }),
            })
            return Response.json({
              success: true,
              status: res.status,
              message: 'Build triggered successfully on remote deployment webhook.',
            })
          } catch (e) {
            return Response.json({ error: (e as Error).message }, { status: 500 })
          }
        }

        return Response.json({
          success: true,
          message: 'Build request received (configure BUILD_WEBHOOK_URL to dispatch to an external CI/CD pipeline).',
          timestamp: new Date().toISOString(),
        })
      },
    },
  ],
})
