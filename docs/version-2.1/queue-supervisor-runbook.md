# Queue supervisor runbook

Production should run workers under the application service user, never root. A process manager should supervise bounded commands such as `php artisan queue:work redis --queue=publishing,external,maintenance,default --tries=3 --timeout=30 --memory=256 --max-jobs=500 --max-time=3600` with environment-specific paths and logs. Deployments should run `php artisan queue:restart`, wait for graceful exits, then confirm replacement workers and `queue:health`.

No Supervisor/systemd configuration was added locally because no deployment authority or Linux process manager exists in this Windows environment.
