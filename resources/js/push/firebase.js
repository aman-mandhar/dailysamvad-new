import { getApp, getApps, initializeApp } from 'firebase/app';

let firebaseApp;

export function getFirebaseApp(config) {
    if (firebaseApp) {
        return firebaseApp;
    }

    firebaseApp = getApps().length > 0 ? getApp() : initializeApp(config);

    return firebaseApp;
}
