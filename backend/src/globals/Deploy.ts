import type { GlobalConfig } from 'payload'

export const Deploy: GlobalConfig = {
  slug: 'deploy',
  label: 'Deployment / Rebuild Site',
  access: {
    read: ({ req: { user } }) => Boolean(user),
    update: ({ req: { user } }) => Boolean(user),
  },
  admin: {
    description: 'Triggers a global static rebuild of the entire website when content editing is finished.',
  },
  fields: [
    {
      name: 'lastTriggeredAt',
      type: 'date',
      label: 'Last Rebuild Requested',
      admin: {
        readOnly: true,
      },
    },
    {
      name: 'triggeredBy',
      type: 'text',
      label: 'Triggered By',
      admin: {
        readOnly: true,
      },
    },
    {
      name: 'status',
      type: 'select',
      label: 'Last Rebuild Status',
      defaultValue: 'idle',
      options: [
        { label: 'Idle / Ready', value: 'idle' },
        { label: 'Rebuild Triggered', value: 'triggered' },
        { label: 'Success', value: 'success' },
        { label: 'Error', value: 'error' },
      ],
      admin: {
        readOnly: true,
      },
    },
    {
      name: 'notes',
      type: 'textarea',
      label: 'Deployment Notes',
      admin: {
        placeholder: 'Optional release notes or changelog before rebuilding...',
      },
    },
  ],
  hooks: {
    beforeChange: [
      async ({ data, req }) => {
        data.lastTriggeredAt = new Date().toISOString()
        data.triggeredBy = req.user?.email || 'admin'
        data.status = 'triggered'

        const buildWebhook = process.env.BUILD_WEBHOOK_URL
        if (buildWebhook) {
          try {
            const res = await fetch(buildWebhook, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                event: 'site.rebuild',
                triggeredBy: data.triggeredBy,
                timestamp: data.lastTriggeredAt,
              }),
            })
            data.status = res.ok ? 'success' : 'error'
          } catch (e) {
            console.error('[Build Hook] Error calling build webhook:', (e as Error).message)
            data.status = 'error'
          }
        } else {
          console.info(`[Build Hook] Global rebuild requested by ${data.triggeredBy}. Set BUILD_WEBHOOK_URL in .env to trigger external pipeline.`)
          data.status = 'success'
        }

        return data
      },
    ],
  },
}
