import type { CollectionConfig } from 'payload'

export const Pages: CollectionConfig = {
  slug: 'pages',
  admin: {
    useAsTitle: 'title',
    defaultColumns: ['title', 'slug', 'updatedAt'],
  },
  versions: {
    drafts: true,
  },
  access: {
    read: () => true, // Public read access for Astro SSG build
  },
  fields: [
    {
      name: 'title',
      type: 'text',
      required: true,
      label: 'Page Title',
    },
    {
      name: 'slug',
      type: 'text',
      required: true,
      unique: true,
      index: true,
      label: 'Slug (URL Identifier)',
      admin: {
        description: 'Unique URL path (e.g. "home", "about-us", "contact")',
      },
    },
    {
      name: 'hero',
      type: 'group',
      label: 'Hero Section',
      fields: [
        {
          name: 'heading',
          type: 'text',
          label: 'Main Heading',
        },
        {
          name: 'subheading',
          type: 'textarea',
          label: 'Subheading or summary',
        },
        {
          name: 'badge',
          type: 'text',
          label: 'Top Badge / Tag',
        },
      ],
    },
    {
      name: 'content',
      type: 'richText',
      label: 'Page Body (Lexical Rich Text)',
    },
    {
      name: 'seo',
      type: 'group',
      label: 'SEO Metadata',
      fields: [
        {
          name: 'metaTitle',
          type: 'text',
          label: 'Meta Title',
        },
        {
          name: 'metaDescription',
          type: 'textarea',
          label: 'Meta Description',
        },
      ],
    },
  ],
}
