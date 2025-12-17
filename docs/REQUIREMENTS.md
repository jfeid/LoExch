# Project Requirements

## 1. Backend — Laravel API

### Required Database Tables
Include at least the following tables and columns:

- users
  - Default Laravel columns
  - `balance` (decimal) — USD funds
- assets
  - `user_id`
  - `symbol` (e.g., `BTC`, `ETH`)
  - `amount` (decimal) — available asset balance
  - `locked_amount` (decimal) — reserved for open sell orders
- orders
  - `user_id`
  - `symbol`
  - `side` (`buy` / `sell`)
  - `price` (decimal)
  - `amount` (decimal)
  - `status` (`open = 1`, `filled = 2`, `cancelled = 3`)
  - Timestamps
- trades (optional)
  - Store executed matches here for bonus points (not required)

### Mandatory API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET    | `/api/profile` | Return authenticated user's USD balance + asset balances |
| GET    | `/api/orders?symbol=BTC` | Return all open orders for orderbook (buy & sell) |
| POST   | `/api/orders` | Create a limit order |
| POST   | `/api/orders/{id}/cancel` | Cancel an open order and release locked USD or assets |
| POST   | internal/job | Matching trigger — match new orders with first valid counter order |

### Core Business Logic

Buy order flow:
1. Verify `users.balance >= amount * price`.
2. Deduct `amount * price` from `users.balance`.
3. Create order with status `open` and lock the USD value.

Sell order flow:
1. Verify `assets.amount >= amount`.
2. Move `amount` into `assets.locked_amount`.
3. Create order with status `open`.

Matching rules (Full match only — no partial fills required):
- New BUY → match with first SELL where `sell.price <= buy.price`.
- New SELL → match with first BUY where `buy.price >= sell.price`.

Commission (mandatory):
- Commission = 1.5% of matched USD value.
- Example:
  ```text
  0.01 BTC @ 95,000 USD = 950 USD volume
  Fee = 950 * 0.015 = 14.25 USD
  ```
- Decide consistently whether the USD fee is deducted from the buyer or an asset fee from the seller, and document that choice in implementation.

Notes:
- Atomic execution and race-safety for balances/assets is required.
- Use DB transactions and row-level locking (SELECT ... FOR UPDATE / Eloquent row locks) to avoid race conditions.
- Ensure consistent ordering when searching for counter-orders to make matching deterministic.

---

## 2. Real-Time Integration (Mandatory)

- Broadcast an `OrderMatched` event on every successful match.
- Use Pusher (or configured broadcaster) and private channels: `private-user.{id}` for each party.
- Frontend must listen and update balances, assets, and order lists instantly.

---

## 3. Frontend — Vue.js (Composition API) + Tailwind (latest)

You need two custom screens plus auth (login/logout):

A) Limit Order Form
- Inputs:
  - Symbol (dropdown: `BTC` / `ETH`)
  - Side (Buy / Sell)
  - Price
  - Amount
- Submit button: Place Order

B) Orders & Wallet Overview
- Sections:
  - USD and asset balances (from `/api/profile`)
  - All past orders (open, filled, cancelled)
  - Orderbook for selected symbol
- Listen for `OrderMatched` events and:
  - Patch new trade into UI
  - Update balance and asset views
  - Update order status in list

Bonus (optional):
- Order filtering (by symbol / side / status)
- Toasts / alerts for matches and errors
- Volume calculation preview in the order form

---

## 4. Technology Stack
* Backend: Laravel (latest stable preferred)
* Frontend: Vue.js (latest stable preferred, Composition API encouraged)
* Database: MySQL or PostgreSQL
* Real-time: Pusher via Laravel Broadcasting

## 5. Evaluation Focus
- Balance & asset race safety
- Atomic execution (transactions)
- Commission correctness
- Real-time listener stability
- Clean repository, security validation, fast setup, meaningful git commits
