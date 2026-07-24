# Database architecture

`analytics_events` is durable event storage; `post_daily_metrics` stores bounded daily aggregates. Existing counters remain intact.
