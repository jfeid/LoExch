# LoExch - Limit Order Exchange

A simplified cryptocurrency exchange matching engine built with Laravel and Vue.js.

## Features

- **Limit Orders**: Place buy/sell orders for BTC and ETH against USD
- **Order Matching**: Full-match algorithm with deterministic ordering
- **Real-time Updates**: Pusher-based broadcasting for instant UI updates
- **Commission System**: Maker-taker fee model (0.5% maker, 1.0% taker)
- **Atomic Transactions**: Race-safe balance and asset management

## Quick Start (DDEV - Recommended)

[DDEV](https://ddev.com/) provides a containerized development environment with all dependencies pre-configured.

**Install DDEV**: https://ddev.readthedocs.io/en/stable/users/install/

```bash
git clone <repository-url>
cd LoExch
ddev start
ddev composer setup
```

Configure Pusher credentials in `.env`, then access the app at https://loexch.ddev.site

**SSL Certificate Warning?** Run `mkcert -install` to trust DDEV's local certificates. See [DDEV SSL documentation](https://ddev.readthedocs.io/en/stable/users/usage/commands/#mkcert).

## Setup on Host (Alternative)

### System Requirements

- PHP 8.3+ with extensions: bcmath, pdo_mysql
- Composer 2.x
- Node.js 18+ with npm
- MySQL 8+

### Installation

```bash
git clone <repository-url>
cd LoExch
composer setup
```

Configure database and Pusher credentials in `.env`, then:

```bash
php artisan serve
```

## Configuration

After setup, configure the following in `.env`:

### Pusher (Required for real-time features)
```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=your-cluster
```

### Database (Host only - DDEV auto-configures)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=loexch
DB_USERNAME=root
DB_PASSWORD=
```

## Test Accounts

After seeding, the following accounts are available:

| Email | Password | USD Balance | Assets | 2FA |
|-------|----------|-------------|--------|-----|
| alice@example.com | password | $50,000 | 1 BTC, 10 ETH | Enabled |
| bob@example.com | password | $100,000 | 2 BTC, 20 ETH | No |

**2FA Demo**: In local environment, use code `111111` for two-factor authentication.

## Order Matching

Order matching is triggered externally, not automatically. Use the artisan command:

```bash
# DDEV
ddev exec php artisan orders:match

# Host
php artisan orders:match
```

Or via API:
```bash
curl -X POST https://loexch.ddev.site/api/internal/job \
  -H "Authorization: Bearer YOUR_INTERNAL_JOB_SECRET" \
  -H "Accept: application/json"
```

## Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan internal:secret` | Generate INTERNAL_JOB_SECRET |
| `php artisan orders:match` | Trigger order matching |

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/profile` | Sanctum | Get user balance and assets |
| GET | `/api/orders?symbol=BTC` | Sanctum | Get open orders for orderbook |
| GET | `/api/user/orders` | Sanctum | Get user's order history |
| POST | `/api/orders` | Sanctum | Create a limit order |
| POST | `/api/orders/{id}/cancel` | Sanctum | Cancel an open order |
| POST | `/api/internal/job` | Bearer Token | Trigger order matching |

## Commission Model

| Party | Fee Rate | Description |
|-------|----------|-------------|
| **Maker** | 0.5% | The resting order in the orderbook |
| **Taker** | 1.0% | The incoming order that triggers the match |

Both fees are deducted in USD. Buyers lock `volume × 1.01` upfront to cover worst-case fees; excess is refunded at settlement.

## Testing

```bash
# DDEV
ddev exec php artisan test

# Host
php artisan test
```

## Development

```bash
# DDEV
ddev npm run dev

# Host
composer run dev
```

## License

MIT License - Free to use, modify, and distribute.
