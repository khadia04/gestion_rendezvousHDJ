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

// header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' data: https://cdn.jsdelivr.net; connect-src 'self';");
