# NetGuard Monitoring Dashboard

A real-time network monitoring and ML-based anomaly detection dashboard. Built with Laravel + Livewire on this side, paired with a Python daemon ([`netguard-daemon`](../netguard-daemon)) that handles ICMP/SNMP polling and ML-based traffic analysis.

Tracks device uptime, latency, bandwidth, and CPU/memory over time, raises severity-based alerts on threshold breaches and detected anomalies, and notifies via Telegram/Email.



## Installation

Requires PHP 8.3+, Composer, Node.js + npm, and a running PostgreSQL database matching `.env` (or edit `.env` to point at your own).

### Linux / macOS

```bash
git clone https://github.com/sxif-4/NetGuard-Monitoring-Dashboard.git
cd NetGuard-Monitoring-Dashboard
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
composer run dev
```

### Windows

```bat
git clone https://github.com/sxif-4/NetGuard-Monitoring-Dashboard.git
cd NetGuard-Monitoring-Dashboard
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
composer run dev
```

The app will be running at `http://localhost:8000`.

To also run live device polling and ML anomaly detection, set up [`netguard-daemon`](../netguard-daemon/README.md) separately.
