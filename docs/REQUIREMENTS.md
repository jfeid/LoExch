# Project Requirements

## 1. Backend — Laravel API

### Required Database Tables
You must include at least the following tables and columns:

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
  - `side` (`buy`/`sell`)
  - `price` (decimal)
  - `amount` (decimal)
  - `status` (`open = 1`, `filled = 2`, `cancelled = 3`)
  - Timestamps
- trades (optional)
  - Store executed matches here for bonus points (not required)

### Mandatory API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET    | `/api/profile` | Returns authenticated user's USD balance + asset balances |
| GET    | `/api/orders?symbol=BTC` | Returns all open orders for orderbook (both buy & sell) |
| POST   | `/api/orders` | Creates a limit order |
| POST   | `/api/orders/{id}/cancel` | Cancels an open order and releases locked USD or assets |
| POST   | (internal / job) | Matching trigger — matches new orders with the first valid counter order |

### Core Business Logic

Buy order flow:
1. Check `users.balance >= amount * price`.
2. Deduct `amount * price` from `users.balance`.
3. Mark order as `open` and lock the USD value.

Sell order flow:
1. Check `assets.amount >= amount`.
2. Move `amount` into `assets.locked_amount`.
3. Mark order as `open`.

Matching rules (Full match only — no partial fills required):
- New BUY → match with first SELL where `sell.price <= buy.price`.
- New SELL → match with first BUY where `buy.price >= sell.price`.

Commission (mandatory):
- Commission = 1.5% of matched USD value.
- Example: `0.01 BTC @ 95,000 USD => volume = 950 USD`.  
  Fee = `950 * 0.015 = 14.25 USD`.  
  USD fee must be deducted from buyer and/or asset fee from seller — choose one consistent approach and document it.

Notes:
- Atomic execution and race-safety for balances/assets is required.
- Consider using DB transactions and row-level locking to avoid race conditions.

---

## 2. Real-Time Integration (Mandatory)

- On every successful match, broadcast an `OrderMatched` event via Pusher.
- Deliver events to both parties using private channels: `private-user.{id}`.
- Front-end must update balances, assets and order lists instantly without refresh.

---

## 3. Frontend — Vue.js (Composition API) + Tailwind (latest)

You only need 2 custom screens + auth (login/logout):

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
