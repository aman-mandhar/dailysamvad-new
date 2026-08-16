# Firebase browser push setup

Phase 2.3A provides browser permission, service-worker registration, and FCM token retrieval only. It does not persist tokens or send notifications.

## Firebase Console

1. Create or select the Firebase project for this installation.
2. In **Project settings > General**, register a Web app and copy its browser configuration values.
3. Open **Project settings > Cloud Messaging** and ensure Firebase Cloud Messaging is available for the project.
4. Under **Web Push certificates**, generate or import a key pair and copy the public VAPID key. Never copy a private key into this application.
5. Add the browser-safe values to the deployment environment:

```dotenv
FIREBASE_WEB_API_KEY=your-web-api-key
FIREBASE_WEB_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_STORAGE_BUCKET=your-storage-bucket
FIREBASE_MESSAGING_SENDER_ID=your-sender-id
FIREBASE_WEB_APP_ID=your-app-id
FIREBASE_MEASUREMENT_ID=
FIREBASE_VAPID_KEY=your-public-vapid-key
```

These are placeholders. Do not add a service-account JSON, private key, OAuth secret, or FCM server credential.

## Deploy and verify

1. Run `php artisan optimize:clear` after changing the environment.
2. Run `npm install` and `npm run build` for the current release.
3. Confirm `https://your-host/firebase-messaging-sw.js` returns JavaScript from the site root.
4. Configure the CDN/reverse proxy to revalidate that path (recommended: `Cache-Control: no-cache`) so worker updates do not remain stale. The tracked static file is not part of Laravel's full-page cache.
5. Visit the public site over HTTPS. Localhost is also treated as a secure development context by supported browsers.
6. Confirm no permission prompt appears before clicking **Enable Notifications**.
7. Click the button, grant permission, and confirm the UI reports success. Development tools should show a `/firebase-messaging-sw.js` registration and an FCM registration request. The token itself is deliberately not logged.

## Manual browser matrix

Check current Chrome desktop, Edge desktop, Chrome Android, and Safari where Firebase Messaging supports the installed browser version. Also test an unsupported/private environment.

For each supported browser, verify default, granted, and denied permission states; worker registration; token generation; reload behavior; no duplicate permission request; and no unrelated JavaScript errors. Missing Firebase configuration must leave the card hidden and the rest of the site operational.

Reset permission only through the browser's site settings (lock/site-controls icon, **Notifications**, then reset or clear the permission). Browser permission cannot and should not be reset by application code.

Safari/WebKit support and private browsing restrictions vary by OS/browser release. HTTPS, a valid Web Push certificate, and current browser testing are required before production rollout.
