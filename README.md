# Notification System

An event-driven notification system built with Laravel 11 that processes and delivers messages through multiple channels (SMS, Email, Push) with priority queues, rate limiting, retry logic, and idempotency support.

---

## Architecture Overview

```
HTTP Request
    │
    ▼
NotificationController
    │  (validates & creates)
    ▼
CreateNotificationAction ──► IdempotencyService (dedup check)
    │
    ▼
Notification (DB) ──► NotificationCreated Event
                              │
                              ▼
                  DispatchNotificationJobListener
                              │
                              ▼
                  Redis Queue (high / normal / low)
                              │
                              ▼
                  ProcessNotificationJob
                              │  (rate limit check via Redis Lua)
                              ▼
                  WebhookSiteProvider ──► External Provider
                              │
                    ┌─────────┴─────────┐
                    ▼                   ▼
            NotificationSent     NotificationFailed
                    │                   │
             RecordMetrics        Retry / markFailed
             SyncBatchCounters
```

**Key components:**
- **Actions** — single-responsibility business logic (`CreateNotificationAction`, `CancelNotificationAction`)
- **Events/Listeners** — decoupled side effects (metrics, batch counters, job dispatch)
- **RateLimiterService** — sliding window algorithm via Redis Lua script (100 req/s per channel)
- **MetricsService** — real-time counters stored in Redis
- **IdempotencyService** — prevents duplicate sends via keyed hash comparison

---

## Requirements

- Docker & Docker Compose
- Composer (to install vendor before first `sail up`)

---

## Quick Start (Docker)

```bash
# 1. Clone the repository
git clone <repo-url>
cd notification-system

# 2. Install PHP dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Set your webhook.site URL in .env
#    NOTIFICATION_WEBHOOK_URL=https://webhook.site/your-uuid

# 5. Start all services (app + queue worker + scheduler + mysql + redis)
./vendor/bin/sail up -d

# 6. Generate app key
./vendor/bin/sail artisan key:generate

# 7. Run migrations
./vendor/bin/sail artisan migrate
```

The application will be available at **http://localhost**

| Service    | URL                                  |
|------------|--------------------------------------|
| API        | http://localhost/api                 |
| Swagger UI | http://localhost/api/documentation   |
| phpMyAdmin | http://localhost:8080                |

---

## Services Started by Docker Compose

| Container      | Role                                               |
|----------------|----------------------------------------------------|
| `laravel.test` | Laravel application (HTTP)                         |
| `queue-worker` | Processes notifications from Redis queues          |
| `scheduler`    | Runs `DispatchScheduledNotificationsJob` per minute |
| `mysql`        | Database                                           |
| `redis`        | Queue, cache, rate limiter, metrics                |
| `phpmyadmin`   | Database GUI                                       |

---

## Running Tests

```bash
./vendor/bin/sail test
```

All 110 tests should pass.

---

## API Reference

Base URL: `http://localhost/api`

Full interactive documentation available at **http://localhost/api/documentation**

### Create Notification

```bash
POST /api/notifications
Content-Type: application/json

{
  "channel": "sms",
  "recipient": "+905551234567",
  "content": "Your OTP is 1234",
  "priority": "high",
  "idempotency_key": "order-789-sms"
}
```

**Response 201:**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "channel": "sms",
    "status": "queued",
    "priority": "high",
    "recipient": "+905551234567",
    "content": "Your OTP is 1234",
    "created_at": "2026-03-07T10:00:00+00:00"
  }
}
```

### Schedule a Notification

```bash
POST /api/notifications

{
  "channel": "email",
  "recipient": "user@example.com",
  "content": "Your flash sale starts soon!",
  "scheduled_at": "2026-03-08T09:00:00Z"
}
```

### Create Batch (up to 1000)

```bash
POST /api/batches

{
  "name": "Flash sale campaign",
  "notifications": [
    { "channel": "sms",  "recipient": "+905551234567",  "content": "Sale starts now!" },
    { "channel": "push", "recipient": "device-token-xyz", "content": "50% off today!" }
  ]
}
```

### Get Notification Status

```bash
GET /api/notifications/{id}
```

### List Notifications (with filters)

```bash
GET /api/notifications?status=sent&channel=sms&priority=high&from=2026-03-01&to=2026-03-07&per_page=20
```

### Cancel a Notification

```bash
POST /api/notifications/{id}/cancel
```

### Get Batch Status

```bash
GET /api/batches/{id}
```

### Health Check

```bash
GET /api/health
```

```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "services": {
      "database": { "status": "ok" },
      "redis":    { "status": "ok" },
      "cache":    { "status": "ok" }
    }
  }
}
```

### Real-time Metrics

```bash
GET /api/metrics
```

```json
{
  "success": true,
  "data": {
    "notifications": { "sent": 1540, "failed": 12 },
    "channels": {
      "sms":   { "sent": 800, "failed": 5, "avg_latency_ms": 243.5, "sample_count": 805 },
      "email": { "sent": 600, "failed": 7, "avg_latency_ms": 189.2, "sample_count": 607 },
      "push":  { "sent": 140, "failed": 0, "avg_latency_ms": 98.1,  "sample_count": 140 }
    },
    "queues": {
      "notifications-high":   { "depth": 0 },
      "notifications-normal": { "depth": 3 },
      "notifications-low":    { "depth": 12 }
    },
    "generated_at": "2026-03-07T10:05:00+00:00"
  }
}
```

---

## Key Features

| Feature | Implementation |
|---|---|
| Multi-channel | SMS, Email, Push via `NotificationChannel` enum |
| Priority queues | 3 Redis queues: `notifications-high/normal/low` |
| Rate limiting | Sliding window (100 req/s per channel) via Redis Lua |
| Retry with backoff | Delays: 60s → 300s → 900s |
| Idempotency | SHA-256 hash of request payload stored in DB |
| Scheduled delivery | `DispatchScheduledNotificationsJob` runs every minute |
| Correlation IDs | `X-Correlation-ID` header propagated through all logs |
| Structured logging | All provider calls logged with context |
| Batch processing | Up to 1000 notifications per batch request |
| Observability | `/api/health` + `/api/metrics` endpoints |
| API Docs | Swagger UI at `/api/documentation` |

---

## Environment Variables

| Variable | Default | Description |
|---|---|---|
| `NOTIFICATION_WEBHOOK_URL` | — | webhook.site URL for delivery |
| `NOTIFICATION_WEBHOOK_TIMEOUT` | `15` | HTTP timeout in seconds |
| `NOTIFICATION_MAX_ATTEMPTS` | `3` | Max retry attempts per notification |
| `NOTIFICATION_RATE_LIMIT_PER_SECOND` | `100` | Rate limit per channel per second |
| `QUEUE_CONNECTION` | `redis` | Queue driver |
| `DB_CONNECTION` | `mysql` | Database driver |

---

## External Provider

The system sends notifications to [webhook.site](https://webhook.site) as a simulated provider.

**Request sent:**
```json
{
  "notification_id": "uuid",
  "channel": "sms",
  "recipient": "+905551234567",
  "content": "Your message",
  "priority": "high"
}
```

**Expected response (202):**
```json
{
  "message_id": "uuid",
  "status": "accepted",
  "timestamp": "2026-03-07T10:00:00Z"
}
```

- **4xx responses** → permanent failure (no retry)
- **5xx + connection errors** → transient failure (retried with backoff)
