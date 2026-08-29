# NetGuard Monitoring Dashboard

Real-time network infrastructure monitoring and ML-based anomaly detection dashboard, built with Laravel and Livewire.

NetGuard tracks device health across a network — routers, switches, servers, firewalls, and access points — polling them for uptime, latency, bandwidth, and resource usage via a companion Python daemon, and surfaces everything through a live NOC-style dashboard with severity-based alerting and Telegram/Email notifications.

## Installation & Run Instructions

**Prerequisites:** PHP 8.3+, Composer, Node.js + npm, and a running PostgreSQL database matching `.env` (or edit `.env` to point at your own).

1. Clone the repository:

```bash
git clone https://github.com/sxif-4/NetGuard-Monitoring-Dashboard.git
cd NetGuard-Monitoring-Dashboard
```

2. Install PHP dependencies:

```bash
composer install
```

3. Set up your environment:

```bash
cp .env.example .env      # Windows: copy .env.example .env
php artisan key:generate
```

4. Run migrations and seed demo data:

```bash
php artisan migrate --seed
```

5. Install JS dependencies and start the dev server:

```bash
npm install
composer run dev
```

6. Visit `http://localhost:8000` and log in with the seeded demo account (`test@example.com` / `password`).

To also run live device polling and ML anomaly detection, set up the companion [`netguard-daemon`](../netguard-daemon) service separately.

## Feature List

- **Real-time Device Monitoring** — ICMP + SNMP polling of routers, switches, servers, firewalls, and access points, reporting online/offline/warning status, latency, packet loss, CPU/memory, and bandwidth.
- **NOC Overview Dashboard** — Live summary cards for network health, active alerts, and aggregate bandwidth/latency, a 30-day alert trend chart, and a device health table with sparkline history.
- **Device Management (CRUD)** — Add, edit, and remove monitored devices, toggle monitoring on/off per device, and search/filter the device list by status or type.
- **Historical Charts** — Per-device CPU, memory, latency, packet loss, and bandwidth trends over 1h/24h/7d/30d ranges.
- **Threshold-Based Alerting** — Configurable warning/critical thresholds per metric, with automatic alert creation, occurrence tracking, and an acknowledge → investigate → resolve lifecycle.
- **ML-Based Security Alerts** — The companion Python daemon captures live traffic and flags port scans, DoS activity, and other anomalies using a trained Random Forest / Isolation Forest pipeline; results surface as security alerts alongside operational ones.
- **Telegram & Email Notifications** — Alerts route to Telegram and/or Email based on severity, with per-alert and per-type cooldowns so repeated breaches don't spam either channel.
- **Role-Based Access Control** — Admin and User roles; only admins can manage devices, alerts, and settings, while regular users get a restricted read-only overview.
- **Settings** — Manage notification channels, alert thresholds, admin users, and personal profile/security settings from a dedicated settings area.

## Technologies Used

- **Framework:** Laravel 13
- **Frontend:** Livewire, Flux UI, Tailwind CSS, Vite
- **Language:** PHP 8.3, JavaScript
- **Charts:** Chart.js
- **Auth:** Laravel Fortify
- **Database:** PostgreSQL (SQLite for tests)
- **Companion Service:** Python (FastAPI) daemon for ICMP/SNMP polling and ML anomaly detection — see [`netguard-daemon`](../netguard-daemon)
- **Notifications:** Telegram Bot API, Email

## Known Issues & Future Improvements

- **No automatic network/topology discovery** — devices are added manually; there's no network scanning or auto-discovery.
- **No SIEM-style features** — event correlation, root cause analysis, and threat intelligence feeds are intentionally out of scope.
- **Alerts are never auto-resolved** by design — a human must manually resolve an alert once the underlying issue is handled (a device coming back online is the one exception, which auto-resolves and sends a recovery notification).
- **Future improvement — Topology view** — a visual network map showing device relationships is planned but not yet built.
- **Future improvement — Exportable reports** — generating PDF/CSV reports from historical data is planned but not yet implemented.
- **Future improvement — Device health scoring** — a composite per-device health score (beyond the current status/threshold model) is planned but not yet built.
