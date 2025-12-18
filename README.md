# LoExch - Limit Order Exchange

A simplified cryptocurrency exchange matching engine built with Laravel and Vue.js.

## Features

- **Limit Orders**: Place buy/sell orders for BTC and ETH against USD
- **Order Matching**: Full-match algorithm with deterministic ordering
- **Real-time Updates**: Pusher-based broadcasting for instant UI updates
- **Commission System**: Maker-taker fee model (1.5% total)
- **Atomic Transactions**: Race-safe balance and asset management

## Technology Stack

- **Backend**: Laravel 12
- **Frontend**: Vue.js 3 (Composition API) + Tailwind CSS 4
- **Database**: MySQL
- **Real-time**: Pusher via Laravel Broadcasting
- **Authentication**: Laravel Fortify with 2FA support

## Setup Instructions

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 18+
- MySQL 8+
- Pusher account (for real-time features)

### Installation

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd LoExch
   ```

2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Copy environment file and configure:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure database in `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=loexch
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Configure Pusher in `.env`:
   ```
   BROADCAST_CONNECTION=pusher
   PUSHER_APP_ID=your-app-id
   PUSHER_APP_KEY=your-app-key
   PUSHER_APP_SECRET=your-app-secret
   PUSHER_APP_CLUSTER=your-cluster
   ```

6. Configure internal job secret in `.env`:
   ```
   INTERNAL_JOB_SECRET=your-secure-random-secret
   ```
   Generate a secure secret: `php artisan tinker --execute="echo bin2hex(random_bytes(32));"`

7. Run migrations and seed:
   ```bash
   php artisan migrate --seed
   ```

8. Build frontend assets:
   ```bash
   npm run build
   ```

9. Start the server:
   ```bash
   php artisan serve
   ```

### DDEV Setup (Alternative)

If using DDEV:
```bash
ddev start
ddev composer install
ddev npm install
ddev exec php artisan migrate --seed
ddev npm run build
```

## Usage

### Test Accounts

After seeding, the following test accounts are available:

| Email | Password | USD Balance | Assets |
|-------|----------|-------------|--------|
| test@example.com | password | $100,000 | 10 BTC, 100 ETH |
| trader@example.com | password | $50,000 | 5 BTC, 50 ETH |

**2FA Demo Mode**: In local environment, use code `111111` for two-factor authentication.

### Screens

1. **Place Order** (`/order`): Create new limit orders
2. **Overview** (`/overview`): View wallet, orders, and orderbook

### API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/profile` | Sanctum | Get user balance and assets |
| GET | `/api/orders?symbol=BTC` | Sanctum | Get open orders for orderbook |
| POST | `/api/orders` | Sanctum | Create a limit order |
| POST | `/api/orders/{id}/cancel` | Sanctum | Cancel an open order |
| POST | `/api/internal/job` | Bearer Token | Trigger order matching |

### Triggering Order Matching

Order matching is not automatic. It must be triggered by an external process (e.g., cron job, admin panel) calling the internal job endpoint with a secret token.

**Using curl:**
```bash
curl -X POST http://localhost/api/internal/job \
  -H "Authorization: Bearer YOUR_INTERNAL_JOB_SECRET" \
  -H "Accept: application/json"
```

**Using DDEV:**
```bash
curl -X POST https://loexch.ddev.site/api/internal/job \
  -H "Authorization: Bearer YOUR_INTERNAL_JOB_SECRET" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "message": "Successfully matched 1 order(s)",
  "matches": 1
}
```

## Commission Model

This implementation uses a **Maker-Taker fee model**:

| Party | Fee Rate | Description |
|-------|----------|-------------|
| **Maker** | 0.5% | The order already in the orderbook (resting order) |
| **Taker** | 1.0% | The order that triggers the match (incoming order) |
| **Total** | 1.5% | Combined fee on matched volume |

Both fees are deducted in USD from the respective party's balance.

### Example

Trade: 0.01 BTC @ $95,000 = $950 volume

- Maker fee: $950 × 0.5% = $4.75 USD
- Taker fee: $950 × 1.0% = $9.50 USD
- Total fees: $14.25 USD

### Fee Handling

| Party | How Fee is Applied |
|-------|-------------------|
| **Buyer** | Fee is added to the cost. Buyer locks `volume × 1.01` upfront to cover worst-case (taker) fee. Excess is refunded at settlement. |
| **Seller** | Fee is deducted from USD proceeds. Seller receives `volume - fee`. No upfront buffer needed. |

**Why buyers lock extra:**
Since the trade price and maker/taker role are unknown at order time, buyers lock a 1% fee buffer to guarantee sufficient funds for any scenario. This prevents negative balances when fees exceed price improvements.

## Testing

Run all tests:
```bash
php artisan test
```

Run specific test suites:
```bash
php artisan test tests/Unit/Services/
php artisan test tests/Feature/Api/
```

## Architecture

### Order Flow

1. User places order via `POST /api/orders`
2. Order is validated and created with status `open`
3. USD (buy) or asset (sell) is locked
4. `OrderCreated` event is broadcast to orderbook and user's private channel
5. External process triggers matching via `POST /api/internal/job` (with Bearer token)
6. If matched, `OrderMatched` event is broadcast to orderbook and both parties' private channels

### Real-time Events

| Event | Channel | Trigger |
|-------|---------|---------|
| `OrderCreated` | `orderbook.{symbol}`, `private-user.{id}` | New order placed |
| `OrderCancelled` | `orderbook.{symbol}`, `private-user.{id}` | Order cancelled |
| `OrderMatched` | `orderbook.{symbol}`, `private-user.{buyer}`, `private-user.{seller}` | Orders matched |

## License

This project is for assessment purposes.
