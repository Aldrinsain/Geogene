<?php
// GeoGene - Google Sign-In (OAuth) configuration
//
// To make "Sign in with Google" actually work, you need a free Google
// Cloud OAuth 2.0 Client ID:
//
// 1. Go to https://console.cloud.google.com/apis/credentials
// 2. Click "Create Credentials" > "OAuth client ID"
// 3. Application type: "Web application"
// 4. Under "Authorized JavaScript origins", add the URL you access GeoGene
//    from, e.g. http://localhost or http://localhost/geogene (no trailing
//    slash, no path) - for a live server, add that domain too
//    (e.g. https://geogene.gov.ph)
// 5. Click Create, then copy the "Client ID" value (ends in
//    ".apps.googleusercontent.com") and paste it below.
//
// Only Google accounts whose email already exists in the `users` table
// with role 'office_head' or 'admin' will be allowed to sign in this way -
// signing in with Google does NOT create new staff accounts by itself.

define('GOOGLE_CLIENT_ID', '106940233442-cfbbmubvg7jcakhc028rh0gdrtj5lkoa.apps.googleusercontent.com');

// Optional: restrict Google Sign-In to accounts from your own email
// domain (recommended). Leave as '' to allow any Google account whose
// email matches an existing staff record regardless of domain.
define('GOOGLE_ALLOWED_DOMAIN', 'staff@geogene.gov.ph');
