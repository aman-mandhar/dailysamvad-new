# Redis client decision

PhpRedis is preferred because it is native and supported by the configured Laravel client. The local Windows CLI does not load `ext-redis`; no PHP-FPM binary exists locally. Predis is the safe fallback only after an approved targeted `composer require predis/predis` succeeds. The attempted install was blocked by unavailable Packagist networking and was reverted, so neither client is active locally.
