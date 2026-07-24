# Queue idempotency map

Publishing uses `ShouldBeUnique` by post ID, a five-minute uniqueness window, database row locking, due-status revalidation, and idempotent published-state handling. IndexNow payloads are normalized and bounded by the service; failures are logged safely. Probe keys are random, namespaced, short-lived, and diagnostic only.
