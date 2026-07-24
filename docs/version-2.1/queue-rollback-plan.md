# Queue rollback plan

Stop supervised Redis workers gracefully with `queue:restart`, restore `QUEUE_CONNECTION=database` through deployment configuration, and run database workers using the same named queues. Do not delete queued jobs or Redis keys. Scheduled publishing remains protected by database state locks and can resume through the database queue.
