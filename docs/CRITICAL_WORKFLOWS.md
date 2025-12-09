# Critical Workflows - User Journey Mapping

This document maps critical user workflows for comprehensive E2E test coverage.

## 1. Authentication & Session Security

### 1.1 User Login Flow
**Journey**: Guest → Authenticated User
- Navigate to login page
- Enter credentials
- Verify 2FA (if enabled)
- Establish session
- Redirect to dashboard

**Critical Points**:
- Session token generation
- CSRF protection
- Failed login attempts tracking
- Account lockout mechanism

### 1.2 Session Management
**Journey**: Active Session Lifecycle
- Session creation on login
- Session refresh on activity
- Concurrent session handling
- Session termination on logout
- Auto-logout on inactivity

**Critical Points**:
- Session hijacking prevention
- Cross-device session tracking
- Force logout capability

---

## 2. Admin & Configuration

### 2.1 Branch Setup
**Journey**: System Setup → Multi-Branch Operation
- Create branch entity
- Configure branch settings
- Assign users to branch
- Set default warehouse
- Enable modules for branch

**Side Effects**:
- User permissions recalculation
- Module availability changes
- Data visibility scope updates

### 2.2 User Management
**Journey**: Create User → Active Staff Member
- Create user account
- Assign role and permissions
- Set branch access
- Configure POS permissions
- Set discount limits

**Critical Points**:
- Permission inheritance
- Branch access validation
- Role hierarchy enforcement

---

## 3. Front-Office Operations

### 3.1 POS Sale Workflow
**Journey**: Open Session → Complete Sale → Close Session

#### Step 1: Open POS Session
- User opens POS session
- Records opening cash balance
- Session becomes active

#### Step 2: Process Sale
- Search and select products
- Add items to cart
- Apply discounts (within limits)
- Calculate tax
- Process payment
- Generate receipt

#### Step 3: Stock Adjustment
- Deduct sold quantities
- Create stock movements
- Update product availability

#### Step 4: Close Session
- Calculate expected cash
- Record closing cash
- Calculate variance
- Generate session report

**Critical Points**:
- Stock deduction atomicity
- Payment method validation
- Discount limit enforcement
- Session concurrency (prevent multiple open sessions)
- Cash variance reconciliation

**Side Effects**:
- Stock movements created
- Journal entries for revenue
- Customer loyalty points updated (if applicable)
- POS session statistics updated

### 3.2 Customer Order Flow
**Journey**: Browse → Add to Cart → Checkout → Payment → Fulfillment

#### Web Store Integration
- Customer browses products (external store)
- Adds items to cart (external store)
- Completes checkout (external store)
- **Integration Point**: Webhook receives order
- System creates StoreOrder
- StoreOrderToSaleService converts to Sale
- Stock reserved/deducted
- Fulfillment workflow triggered

**Critical Points**:
- Webhook authentication
- Duplicate order prevention (external_id + branch_id)
- Stock availability check
- store_order_id foreign key capture

### 3.3 Inventory Adjustment
**Journey**: Stock Count → Adjustment → Reconciliation
- Count physical inventory
- Compare with system quantity
- Create adjustment entry
- Record reason and approver
- Update stock levels

**Critical Points**:
- Adjustment authorization
- Audit trail creation
- Stock movement direction validation

---

## 4. Workflow Approvals & Side Effects

### 4.1 Purchase Order Approval
**Journey**: Create PO → Approval → Receiving

#### Workflow Steps
1. User creates purchase order
2. System triggers approval workflow
3. Approver reviews and approves/rejects
4. On approval: PO status changes to "approved"
5. Goods receipt: Stock quantities increase
6. Invoice matching: AP journal entry created

**Side Effects**:
- WorkflowInstance created
- WorkflowApproval records generated
- Email notifications sent
- Stock movements on receipt
- Journal entries on invoice posting

### 4.2 Expense Approval
**Journey**: Submit Expense → Approval → Payment

#### Workflow Steps
1. User submits expense claim
2. Manager approval required
3. Finance approval for amounts > threshold
4. Payment processing

**Side Effects**:
- WorkflowInstance transitions
- Approval notifications
- Payment journal entries
- Budget consumption tracking

---

## 5. Scheduled & Background Jobs

### 5.1 Daily POS Close
**Schedule**: Daily at 23:59
- Auto-close open POS sessions (optional)
- Generate daily reports
- Calculate cash variance
- Email reports to managers

### 5.2 Scheduled Reports
**Schedule**: Configurable (daily/weekly/monthly)
- Generate report data
- Create PDF/Excel export
- Email to recipients
- Archive report copies

**Data Sources**:
- Sales from `sales` table
- Inventory from `products` + `stock_movements`
- Customer analytics from `customers` + `sales`

---

## 6. Data Integrity Checkpoints

### 6.1 Foreign Key Constraints
- `store_orders.branch_id` → `branches.id` (nullOnDelete)
- `sales.store_order_id` → `store_orders.id` (nullOnDelete)

**Test Scenarios**:
- Delete branch: store_orders.branch_id becomes null
- Delete store_order: sales.store_order_id becomes null
- No orphaned records created

### 6.2 Stock Movement Balance
**Integrity Rule**: Sum of movements = current stock
```sql
SELECT 
    product_id,
    SUM(CASE WHEN direction = 'in' THEN qty ELSE -qty END) as calculated_stock
FROM stock_movements
GROUP BY product_id
```

---

## Testing Strategy

### E2E Test Coverage
1. **Happy Path**: Complete successful workflow
2. **Authorization**: Verify permission checks at each step
3. **Concurrency**: Test race conditions (e.g., double stock deduction)
4. **Failure Recovery**: Test rollback on errors
5. **Side Effects**: Verify all downstream impacts occur correctly

### Critical Assertions
- Database state consistency
- Foreign key integrity maintained
- Audit logs created
- Notifications sent
- Background jobs queued
- Journal entries balanced
