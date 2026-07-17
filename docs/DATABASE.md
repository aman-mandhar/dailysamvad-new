# Database Design

## Existing Laravel Default Tables

The application may already contain Laravel default tables such as:

- users
- password_reset_tokens
- sessions
- cache
- cache_locks
- jobs
- job_batches
- failed_jobs

These tables should remain unless intentionally replaced.

## Core Tables

### users

Used for admins, editors, reporters and authors.

Important fields:

- id
- old_wp_id
- name
- username
- slug
- email
- password
- mobile_number
- avatar_path
- bio
- designation
- social URLs
- is_active
- last_login_at
- timestamps

No separate authors table will be created.

### categories

- id
- old_wp_id
- parent_id
- name
- slug
- description
- image_path
- meta_title
- meta_description
- sort_order
- is_active
- show_in_menu
- timestamps

### tags

- id
- old_wp_id
- name
- slug
- description
- timestamps

### posts

- id
- old_wp_id
- author_id
- title
- slug
- excerpt
- content
- featured_image
- featured_image_alt
- featured_image_caption
- status
- language
- is_breaking
- is_featured
- allow_comments
- views_count
- likes_count
- published_at
- scheduled_at
- meta_title
- meta_description
- focus_keyword
- canonical_url
- old_url
- source_data
- timestamps
- soft deletes

### category_post

- category_id
- post_id
- is_primary

A post may belong to multiple categories.

Only one category should normally be marked primary.

### post_tag

- post_id
- tag_id

### pages

- id
- old_wp_id
- author_id
- title
- slug
- content
- status
- template
- meta_title
- meta_description
- canonical_url
- published_at
- timestamps
- soft deletes

### media

- id
- old_wp_id
- uploaded_by
- disk
- directory
- file_name
- original_name
- path
- mime_type
- extension
- size
- width
- height
- title
- alt_text
- caption
- description
- metadata
- timestamps

### settings

- id
- group
- key
- value
- type
- is_public
- timestamps

Unique key:

group + key

## Post Statuses

- draft
- pending_review
- scheduled
- published
- rejected
- archived

## Page Statuses

- draft
- published
- archived

## Languages

Initial supported values:

- hi
- pa
- en

## WordPress Mapping

Imported records should retain their original WordPress ID using old_wp_id.

old_wp_id should be nullable and unique where relevant.

## Delete Strategy

- Deleting a user should not delete posts.
- author_id should become null.
- Deleting a parent category should not delete child categories.
- parent_id should become null.
- Deleting a post should remove pivot rows.
- Deleting categories or tags should remove relevant pivot rows.