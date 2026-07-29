# YouTube Playlist Player Integration — Homepage and Post Pages

## Project Context

This is an existing Laravel news application using Blade, Livewire/Filament, Vite, Redis/full-page caching, and responsive frontend components.

Implement a reusable YouTube playlist player using this playlist ID:

```text
PLHZXkx2rzrVUxs_--bZGvq-KkStA_OTGq
```

The player must be displayed on:

1. Homepage
2. Individual post/article pages

Do not duplicate player logic between pages. Build one reusable component.

---

# Main Requirements

## 1. Playlist Playback

The player must:

* Load videos from playlist:

```text
PLHZXkx2rzrVUxs_--bZGvq-KkStA_OTGq
```

* Start playback from the latest video in the playlist.
* Automatically play the next video when the current video ends.
* Continue through the complete playlist.
* After the last video finishes, restart playback from the first/latest video.
* Keep the full playlist playing in an infinite loop.
* Preserve the playlist sequence.
* Avoid reloading the full page when changing videos.

---

## 2. Latest Video Handling

Do not hardcode a YouTube video ID.

Determine the latest playlist video dynamically.

Preferred implementation:

* Use the YouTube Data API to fetch playlist items.
* Order or process the returned items by publication date.
* Select the most recently published valid video as the starting video.
* Cache the playlist response so the YouTube API is not called on every page request.
* Exclude deleted, private, unavailable, or invalid playlist videos.

Use a configurable cache duration, with a sensible default such as 30 minutes.

If the API is unavailable or no API key is configured:

* Fall back gracefully to the normal YouTube playlist embed.
* Do not break the homepage or post page.
* Log the failure without exposing an exception to visitors.

---

## 3. YouTube Autoplay Restrictions

Modern browsers may block autoplay with sound.

Implement autoplay using the following behaviour:

* Initially start the video muted.
* Set autoplay enabled.
* Display an accessible unmute button or allow the YouTube player controls to unmute.
* Do not attempt to bypass browser autoplay policies.
* On browsers where autoplay is blocked, show the player normally with a clear play button.

Expected player parameters:

```text
autoplay=1
mute=1
playsinline=1
enablejsapi=1
rel=0
```

Use the YouTube IFrame Player API rather than depending only on a basic iframe.

---

# Technical Architecture

## 4. Configuration

Add environment/config support.

Example `.env.example` entries:

```env
YOUTUBE_PLAYLIST_ID=PLHZXkx2rzrVUxs_--bZGvq-KkStA_OTGq
YOUTUBE_API_KEY=
YOUTUBE_PLAYLIST_CACHE_TTL=1800
YOUTUBE_PLAYER_AUTOPLAY=true
YOUTUBE_PLAYER_MUTED=true
YOUTUBE_PLAYER_LOOP=true
```

Create or update a suitable configuration file, for example:

```php
config/youtube.php
```

The playlist ID and API key must not be hardcoded throughout controllers, Blade templates, or JavaScript.

Use:

```php
config('youtube.playlist_id')
```

and related configuration keys.

---

## 5. YouTube Playlist Service

Create a dedicated service, for example:

```text
app/Services/YouTubePlaylistService.php
```

Responsibilities:

* Fetch playlist items using the YouTube Data API.
* Handle pagination when the playlist contains more than 50 videos.
* Normalize playlist video data.
* Remove unavailable or invalid videos.
* Sort videos using their actual publication date.
* Return the latest video ID.
* Return an ordered array of playable video IDs.
* Cache the normalized playlist result.
* Handle API errors safely.
* Log failures with useful context.
* Return a fallback result when the API is unavailable.

Suggested output structure:

```php
[
    'playlist_id' => '...',
    'latest_video_id' => '...',
    'video_ids' => [
        'video-id-1',
        'video-id-2',
        'video-id-3',
    ],
    'fetched_at' => now(),
]
```

Use Laravel's HTTP client.

Apply:

* Connection timeout
* Request timeout
* Limited retry behaviour
* Exception handling
* Cache locking where appropriate to avoid multiple simultaneous API refreshes

Do not call the YouTube API directly from Blade templates.

---

## 6. Reusable View Component

Create one reusable Blade or Livewire component.

Preferred example:

```text
app/View/Components/YouTubePlaylistPlayer.php
resources/views/components/youtube-playlist-player.blade.php
```

Usage should be simple:

```blade
<x-youtube-playlist-player placement="homepage" />
```

and:

```blade
<x-youtube-playlist-player placement="post" />
```

The component must:

* Receive the normalized playlist data.
* Generate a unique DOM/player ID for every component instance.
* Support more than one player on a page without ID conflicts.
* Avoid loading the YouTube IFrame API script multiple times.
* Render nothing or render a safe fallback when the playlist ID is missing.
* Escape all values safely.
* Avoid inline PHP business logic.

---

# JavaScript Player Behaviour

## 7. YouTube IFrame API

Implement player control through the YouTube IFrame Player API.

The player must:

1. Start with the latest video.
2. Use the normalized ordered video ID list.
3. Detect the `ENDED` state.
4. Start the next available video.
5. Restart from array index `0` after the final video.
6. Continue looping indefinitely.
7. Skip any video that fails to play.
8. Prevent an infinite JavaScript error loop when multiple videos are unavailable.
9. Keep playback inline on supported mobile devices.
10. Work when the component is rendered more than once.

Suggested lifecycle:

```text
Player ready
    ↓
Load latest video
    ↓
Video ends
    ↓
Increment current index
    ↓
Play next video
    ↓
Last video ends
    ↓
Reset index to 0
    ↓
Continue playlist loop
```

Handle these YouTube events:

* `onReady`
* `onStateChange`
* `onError`

Handle common player errors, including unavailable or restricted videos, by moving to the next playable item.

Do not refresh the page between videos.

---

## 8. Playlist Order

The first item in the generated player array must be the latest published video.

The remaining videos should follow a clearly defined sequence.

Use this preferred order:

```text
Newest video → older videos → oldest video → restart from newest video
```

Therefore:

* Sort videos by publication date descending.
* Start from index `0`.
* After the oldest video ends, restart from index `0`.

Do not depend blindly on the order returned by YouTube unless it has been verified.

---

# Frontend Placement

## 9. Homepage Integration

Display the playlist player on the homepage in an appropriate existing section.

Requirements:

* Do not disturb the existing hero, breaking news, latest news, advertisements, or sidebar layout.
* Prefer placement in the homepage sidebar or a dedicated video-news section.
* Reuse existing design tokens and card styles.
* Do not create a visually unrelated design.
* Add an appropriate heading such as:

```text
Video News
```

or use an existing project translation/label system.

The exact placement should be chosen after auditing the current homepage Blade structure.

---

## 10. Post Page Integration

Display the same reusable playlist player on individual post pages.

Preferred placement:

* In the article sidebar, if a sidebar exists.
* Otherwise, after the article content or before related posts.

Requirements:

* Do not insert the player in the middle of article paragraphs.
* Do not disrupt schema markup, advertisements, related posts, social sharing, or article navigation.
* Avoid rendering duplicate players if the post layout already includes the shared sidebar component.

Audit the current post page composition before selecting the insertion point.

---

# Responsive Design

## 11. Player Layout

The player must be fully responsive.

Use a 16:9 aspect ratio:

```css
aspect-ratio: 16 / 9;
```

Requirements:

* Width: 100%
* No fixed iframe width or height
* No horizontal overflow
* Correct display on mobile, tablet, desktop, and sidebar widths
* Rounded corners and spacing consistent with the current project design
* Respect existing dark/light design behaviour, if present

Prevent layout shift by reserving the player aspect ratio before the iframe loads.

---

# Performance

## 12. Loading Strategy

Because this player will be present on high-traffic pages:

* Do not call the YouTube Data API during every request.
* Cache playlist data.
* Load the YouTube IFrame API only once.
* Avoid duplicate scripts.
* Defer non-critical JavaScript.
* Do not block initial page rendering unnecessarily.
* Use a thumbnail/poster placeholder before the player initializes where practical.
* Respect the project's full-page caching architecture.
* Ensure cached HTML does not contain expired signed URLs or request-specific data.

If lazy loading conflicts with autoplay:

* Initialize the player when it approaches the viewport.
* Autoplay only after initialization.
* Do not initialize hidden duplicate players unnecessarily.

Choose a practical balance between autoplay and page performance.

---

# Privacy and Accessibility

## 13. Privacy

Prefer YouTube's privacy-enhanced embed domain where compatible:

```text
https://www.youtube-nocookie.com
```

Do not include tracking parameters beyond what is required for player operation.

---

## 14. Accessibility

Add:

* Meaningful player title
* Accessible section heading
* Keyboard-operable controls
* Visible focus states
* Descriptive fallback link
* `aria-label` where needed

Fallback example:

```text
Watch the video playlist on YouTube
```

Do not hide essential controls from keyboard or screen-reader users.

---

# Failure and Fallback Behaviour

## 15. Graceful Fallback

The page must remain functional when:

* YouTube API key is absent.
* API quota is exhausted.
* YouTube API returns an error.
* The playlist is empty.
* The latest video is private or deleted.
* The IFrame API fails to load.
* JavaScript is disabled.
* Autoplay is blocked.
* A specific video is unavailable.

Fallback options:

1. Render a standard playlist embed using the configured playlist ID.
2. Render a direct link to the YouTube playlist.
3. Hide the section only when no safe fallback is possible.

Never show raw exceptions, API responses, API keys, or stack traces.

---

# Security

## 16. Security Requirements

* Store API keys only in environment configuration.
* Never expose the YouTube Data API key unnecessarily in frontend JavaScript.
* Fetch playlist metadata server-side.
* Validate playlist and video IDs using strict allowed patterns.
* Escape values rendered into JavaScript.
* Avoid unsafe raw JSON concatenation.
* Use Laravel helpers such as `Js::from()` for server data passed to JavaScript.
* Do not allow arbitrary user-provided iframe URLs.

---

# Testing

## 17. Automated Tests

Add focused tests for:

### Service tests

* Returns normalized playlist data.
* Sorts videos newest-first.
* Selects the latest valid video.
* Removes unavailable videos.
* Handles pagination.
* Uses cache.
* Handles API failures.
* Handles missing API key.
* Falls back safely.

Use Laravel HTTP fakes. Tests must not make real YouTube API calls.

### Component/rendering tests

* Homepage contains the player component.
* Post page contains the player component.
* Correct playlist ID is rendered.
* Latest video ID is passed to the frontend.
* Multiple component instances use unique IDs.
* Missing configuration does not crash the page.
* API key is never rendered in HTML.

### JavaScript behaviour

Where practical, add a focused frontend test or clearly testable JavaScript module for:

* Moving to the next video after `ENDED`.
* Restarting from index `0` after the last video.
* Skipping unavailable videos.
* Preventing duplicate IFrame API loading.

Do not introduce a heavy frontend testing framework solely for this feature unless the project already uses one.

---

# Validation Commands

Run the relevant commands available in the project, including:

```bash
php artisan test
```

```bash
php artisan config:clear
php artisan cache:clear
```

```bash
npm run build
```

Also run targeted tests for the new YouTube playlist functionality.

Check formatting using the formatter already configured in the project, such as:

```bash
./vendor/bin/pint
```

Do not run destructive commands against production data.

---

# Manual Verification

Verify the following manually:

## Homepage

* Player appears in the expected section.
* Latest video starts first.
* Autoplay begins muted where the browser permits.
* Next video starts automatically.
* Full playlist loops.
* Layout remains responsive.
* Existing homepage sections still work.

## Post page

* Player appears once.
* It does not duplicate through nested sidebars/layouts.
* Article content and advertisements remain unaffected.
* Related posts and sharing continue working.

## Browser checks

Check behaviour on:

* Chrome desktop
* Mobile viewport
* Safari-compatible autoplay restrictions where practical
* Browser with JavaScript disabled
* Browser where autoplay is blocked

---

# Protected Boundaries

Do not:

* Change the existing post publishing workflow.
* Change authentication, roles, or permissions.
* Change WordPress import logic.
* Change post URLs or legacy redirects.
* Break SEO schema, OpenGraph, sitemap, Google News, or robots handling.
* Remove existing homepage sections.
* Replace existing frontend architecture.
* Add a second frontend framework.
* Hardcode a particular latest video ID.
* Expose the YouTube API key.
* Make real YouTube API calls during tests.
* commit generated cache, log, or build artifacts unless already required by the repository.

---

# Completion Criteria

The work is complete only when:

* One reusable playlist player component exists.
* It is displayed on both homepage and post pages.
* The latest published valid playlist video starts first.
* Playback starts automatically in muted mode where browsers allow.
* The next video starts when the current video ends.
* The entire playlist loops continuously.
* Deleted/private/unavailable videos are skipped.
* Playlist data is cached.
* The player works without a YouTube API key through graceful fallback.
* YouTube API failures do not break page rendering.
* The player is responsive and accessible.
* Existing homepage and post layouts remain stable.
* Tests pass.
* Production build succeeds.

---

# Required Completion Report

After implementation, return a detailed report with:

1. Summary of implementation
2. Files created
3. Files modified
4. Homepage integration location
5. Post-page integration location
6. Playlist ordering logic
7. Autoplay and mute behaviour
8. Full-playlist loop implementation
9. Cache strategy and TTL
10. API failure/fallback behaviour
11. Security measures
12. Tests added
13. Commands executed
14. Test/build results
15. Any browser autoplay limitations
16. Any environment variables that must be added on local and production
17. Any deployment or cache-clearing commands required

Do not stop after auditing. Implement the complete feature, run the tests/build, and report the actual results.
