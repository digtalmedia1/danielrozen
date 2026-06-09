<?php
return [
    "host" => getenv("DB_HOST") ?: "localhost",
    "port" => getenv("DB_PORT") ?: "3306",
    "name" => getenv("DB_NAME") ?: "danielrozen_db",
    "user" => getenv("DB_USER") ?: "root",
    "pass" => getenv("DB_PASSWORD") ?: "",
];