# Technical Assessment Requirements: Limit-Order Exchange Mini Engine

## Project Overview

This assessment evaluates Full Stack development competency, emphasizing financial data integrity, concurrency safety, and real-time systems through building a simplified exchange matching engine.

**Timeline:** December 17–19, 2025 (48 hours)

---

## 1. Technology Stack

- **Backend:** Laravel (latest stable)
- **Frontend:** Vue.js with Composition API (latest stable)
- **Database:** MySQL or PostgreSQL
- **Real-time Communication:** Pusher via Laravel Broadcasting

---

## 2. Database Schema Requirements

### Required Tables

| Table | Key Fields | Purpose |
|-------|-----------|---------|
| **users** | Default Laravel columns + `balance` (decimal USD) | User accounts with USD holdings |
| **assets** | `user_id`, `symbol` (BTC/ETH), `amount`, `locked_amount` | User cryptocurrency holdings |
| **orders** | `user_id`, `symbol`, `side` (buy/sell), `price`, `amount`, `status` | Limit order records |
| **trades** | (Optional) | Executed match history for bonus points |

---

## 3. API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/profile` | Returns authenticated user's USD balance and asset balances |
| GET | `/api/orders?symbol=BTC` | Returns all open orders for orderbook display |
| POST | `/api/orders` | Creates a new limit order |
| POST | `/api/orders/{id}/cancel` | Cancels an order and releases reserved funds/assets |
| POST | Internal/Job | Triggers order matching algorithm |

---

## 4. Core Business Logic

### Buy Order Processing

1. Validate user's USD balance >= (amount × price)
2. Deduct total cost from user balance immediately
3. Lock USD amount in order record
4. Mark order status as "open"

### Sell Order Processing

1. Validate user's available assets >= amount
2. Transfer amount to `locked_amount` field
3. Mark order status as "open"

### Matching Algorithm

- New BUY → match with first SELL where `sell.price <= buy.price`
- New SELL → match with first BUY where `buy.price >= sell.price`
- Matches execute only when complete order fulfillment occurs (no partial fills)

### Commission Structure

- **Commission = 1.5% of the matched USD value**
- Example: 0.01 BTC @ $95,000 USD = $950 volume; fee = $14.25 USD
- USD fee must be deducted from buyer (sender) and/or asset fee from seller — your choice, but must be consistent

### Safety Requirements

- Atomic execution and race-safety for balances/assets is required
- Use DB transactions and row-level locking (SELECT ... FOR UPDATE or Eloquent row locks) to avoid race conditions
- Ensure deterministic ordering when searching for counter-orders to make matching predictable

---

## 5. Real-Time Integration (Mandatory)

- Broadcast `OrderMatched` events via Pusher on successful matches
- Target private channels: `private-user.{id}` for both trading parties
- Frontend must update balances, assets, and order lists instantly upon event receipt

---

## 6. Frontend Requirements (Vue.js)

### Screen 1: Limit Order Form

**Inputs:**
- Symbol selector (BTC/ETH dropdown)
- Side selection (Buy/Sell)
- Price input (decimal)
- Amount input (decimal)

**Functionality:** Submit button places order via `POST /api/orders`

### Screen 2: Orders & Wallet Overview

**Sections:**
- USD and asset balance display
- All orders (open, filled, cancelled states)
- Active orderbook for selected symbol
- Real-time event listener for trade updates
- Automatic UI patches on `OrderMatched` events

### Bonus Features (Optional)

- Order filtering (by symbol / side / status)
- Toast notifications for matches and errors
- Volume preview calculations in order form

---

## 7. Evaluation Criteria

Assessment focuses on:

1. **Data Integrity:** Race condition prevention in balance/asset management
2. **Atomicity:** Transactional safety during order placement and matching
3. **Commission Accuracy:** Correct fee calculations and deductions
4. **Real-time Stability:** Pusher listener reliability and event handling
5. **Code Quality:** Clean repository structure, security validation, documentation
6. **Git History:** Meaningful, organized commits

---

## 8. Submission Requirements

1. Push completed project to GitHub or GitLab
2. Include README.md with setup instructions
3. Submit repository URL via provided form
4. Optional: Add implementation notes (max 2000 characters)
