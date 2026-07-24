# Queue retry and timeout standard

Retries are bounded and job-specific. Publishing uses three attempts, 30-second timeout, and 30/120-second backoff. IndexNow uses three attempts, 15-second timeout, and 60/300-second backoff. Probe jobs use one attempt and 15 seconds. Redis `retry_after` remains 90 seconds, greater than the active worker timeout to avoid premature duplicate reservation.
