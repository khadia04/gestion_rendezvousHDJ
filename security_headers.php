<?php

// Protection Clickjacking
header("X-Frame-Options: SAMEORIGIN");

// Protection MIME sniffing
header("X-Content-Type-Options: nosniff");

// Protection XSS (legacy mais ok)
header("X-XSS-Protection: 1; mode=block");

// Politique referrer
header("Referrer-Policy: strict-origin-when-cross-origin");

// 🔐 CONTENT SECURITY POLICY

header(
    "Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; font-src 'self' https://cdn.jsdelivr.net data:; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'"
);

