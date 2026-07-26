# Creating a Post

This guide explains how an authorized staff member creates and submits a news post in the Daily Samvad admin panel.

## Access the form

1. Sign in to the admin panel.
2. Open **Editorial > Posts** (the menu may be labelled **My Work > My Posts** for limited-access roles).
3. Select **New post**.

Direct URL:

```text
/admin/posts/create
```

The account must be active and have the `create posts` permission.

## Complete the Post section

| Field | Required | Description |
| --- | --- | --- |
| Title | Yes | The public headline. |
| Slug | Yes | The URL-friendly identifier. It is generated from the title until manually edited and must be unique. |
| Excerpt | No | A short summary, limited to 500 characters. |
| Content | Yes | The full article body entered through the rich-text editor. |
| Language | Yes | Select Hindi, Punjabi, or English. |
| Author | Yes | Defaults to the signed-in user. Staff with author-assignment permission can select another active editorial user. |

## Publishing settings

The available status depends on the signed-in user's permissions:

- A super-admin, admin, or editor with the `publish posts` permission can choose **Draft** or **Published**.
- Other roles are restricted to **Draft** and cannot publish by changing the submitted form data.

Selecting **Published** publishes the new post immediately and records its publication time and the user who published it.

The following values may also be set:

- **Is Featured**: marks the article for featured-content placement.
- **Is Breaking**: marks the article as breaking news.

Publication time is assigned automatically when **Published** is selected. Scheduled publishing remains part of the editorial workflow after creation.

## Assign taxonomy

1. Select at least one **Category**.
2. Select one of those categories as the **Primary Category**.
3. Optionally select one or more **Tags**.

The primary category must also be present in the selected Categories field. Duplicate or nonexistent category and tag assignments are rejected by the server.

## Add a featured image

Choose one of these methods:

- **Select from Media Library**: search for and attach an existing image.
- **Featured Image upload**: upload a new JPEG, PNG, or WebP image up to 5 MB.

Selecting an existing media item sets it as the featured image. Uploading a new file clears the media-library selection so that only one featured-image source is used.

## Add SEO and source information

Expand the optional **SEO** section to enter:

- Meta title
- Focus keyword
- Meta description (recommended maximum: 160 characters)
- Canonical URL
- Robots directive
- Source name
- Source URL
- Historical URL for imported WordPress redirect mapping

Use a canonical URL only when another URL should be treated as the preferred version. Source attribution should be completed whenever the story is based on an external source.

## Create the draft

Select **Create** after completing all required fields. The system will:

1. Validate the form and taxonomy assignments.
2. Insert the post as `Draft`, or as `Published` when an authorized user selected direct publication.
3. Save its author, language, content, media, SEO data, categories, primary category, and tags.

Validation errors appear beneath the relevant fields. Correct them and select **Create** again.

## Editorial workflow

Draft posts follow the standard editorial workflow:

```text
Draft
  -> Submit for review
Pending Review
  -> Approve, Reject, or Request corrections
Approved
  -> Publish now or Schedule
Published
  -> Archive
```

Super-admins, admins, and editors with publishing permission may instead select **Published** on the creation form when a story does not require the review workflow. Invalid actions are hidden for the current status.

## Common problems

### The post cannot be created

Confirm that Title, Slug, Content, Language, Author, Categories, and Primary Category are complete.

### The slug is already in use

Edit the slug so that it uniquely identifies the article. Imported WordPress slugs must be preserved when migrating existing content.

### The primary category is rejected

Add the same category to **Categories**, then select it as **Primary Category**.

### Publish is not available

Only accounts with the `publish posts` permission can select **Published** during creation. Otherwise, submit the draft for review, approve it, and then publish or schedule it according to the account's permissions.
