<?php
return [
    "url" => getenv("APP_URL") ?: "https://danielrozen.com",
    "timezone" => getenv("APP_TIMEZONE") ?: "Asia/Jerusalem",
    "session_secret" => getenv("SESSION_SECRET") ?: "default_secret_key_change_me",
];