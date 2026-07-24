# Queue production rollout

1. Verify Redis client/server, DB 3, prefix isolation, and `queue:health`.
2. Provision supervised workers under the application user.
3. Start one publishing worker and run a real probe.
4. Verify scheduled publication and failed-job storage.
5. Add external and maintenance workers.
6. Monitor depth, retries, memory, and failures before expansion.

Keep database queue as rollback authority until Redis worker evidence is complete.
