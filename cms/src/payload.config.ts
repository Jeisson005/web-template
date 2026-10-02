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

// 2. Storage resolution (Local persistent directory by default, or S3/R2/MinIO if S3_BUCKET is provided)
const plugins = []

if (process.env.S3_BUCKET) {
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
