# API v1 Route Trace

This document maps API routes to their controllers, services, and key operations.

## Product Routes

### `GET /api/v1/products`
**Auth**: Store Token  
**Controller**: `ProductsController@index`  
**Service**: Direct Eloquent  
**Purpose**: List products with filtering and pagination

**Query Parameters**:
- `search`: Filter by name/SKU
- `category_id`: Filter by category
- `sort_by`: Sort field (default: created_at)
- `sort_dir`: Sort direction (default: desc)
- `per_page`: Items per page (default: 50)

**Branch Scoping**: Filtered by store's branch_id

---

### `GET /api/v1/branches/{branchId}/products/search`
**Auth**: Sanctum + throttle:api  
**Controller**: `ProductsController@search`  
**Service**: Direct Eloquent  
**Purpose**: Search products for POS terminal

**Query Parameters**:
- `q`: Search query (min 2 chars)
- `status`: Filter by status (default: active)
- `category_id`: Filter by category
- `per_page`: Items per page (default: 20, max: 100)
- `page`: Page number

**Response**: Paginated product list with price mapping

**Branch Scoping**: Required branchId in route

---

### `POST /api/v1/products`
**Auth**: Store Token  
**Controller**: `ProductsController@store`  
**Service**: Product model  
**Purpose**: Create new product

**Field Mapping**:
- `price` → `default_price`
- `cost_price` → `cost`

**Side Effects**:
- ProductStoreMapping created if external_id provided

---

### `PUT /api/v1/products/{id}`
**Auth**: Store Token  
**Controller**: `ProductsController@update`  
**Service**: Product model  
**Purpose**: Update product

**Field Mapping**: Same as POST

---

## Inventory Routes

### `GET /api/v1/inventory/stock`
**Auth**: Store Token  
**Controller**: `InventoryController@getStock`  
**FormRequest**: `GetStockRequest`  
**Purpose**: Get stock levels with current quantity calculation

**Query Parameters**:
- `sku`: Filter by SKU
- `warehouse_id`: Filter by warehouse
- `low_stock`: Boolean filter for low stock items
- `per_page`: Items per page (max: 100)

**Stock Calculation**:
```sql
SUM(CASE WHEN direction = "in" THEN qty ELSE 0 END) - 
SUM(CASE WHEN direction = "out" THEN qty ELSE 0 END)
```

---

### `POST /api/v1/inventory/update-stock`
**Auth**: Store Token  
**Controller**: `InventoryController@updateStock`  
**FormRequest**: `UpdateStockRequest`  
**Purpose**: Single product stock update

**Request Body**:
```json
{
  "product_id": 123,
  "qty": 10,
  "direction": "in|out|set",
  "reason": "Optional reason",
  "warehouse_id": 1
}
```

**Logic**:
- `direction=in`: Add qty
- `direction=out`: Subtract qty
- `direction=set`: Calculate difference and adjust

**Side Effects**:
- StockMovement record created
- Stock balance updated

---

### `POST /api/v1/inventory/bulk-update-stock`
**Auth**: Store Token  
**Controller**: `InventoryController@bulkUpdateStock`  
**FormRequest**: `BulkUpdateStockRequest`  
**Purpose**: Batch stock updates (max 100 items)

**Request Body**:
```json
{
  "updates": [
    {
      "product_id": 123,
      "qty": 10,
      "direction": "in"
    }
  ]
}
```

**Response**: Success/failed arrays

---

### `GET /api/v1/inventory/movements`
**Auth**: Store Token  
**Controller**: `InventoryController@getMovements`  
**FormRequest**: `GetMovementsRequest`  
**Purpose**: Stock movement history

**Query Parameters**:
- `product_id`: Filter by product
- `warehouse_id`: Filter by warehouse
- `direction`: in|out
- `start_date`: Date range start
- `end_date`: Date range end
- `per_page`: Items per page (max: 100)

---

## POS Routes

### `POST /api/v1/branches/{branchId}/pos/checkout`
**Auth**: Sanctum + throttle:api  
**Controller**: `POSController@checkout`  
**Service**: `POSService@checkout`  
**Purpose**: Complete POS sale

**Request Body**:
```json
{
  "items": [
    {
      "product_id": 123,
      "qty": 2,
      "price": 100,
      "discount": 5,
      "percent": true,
      "tax_id": 1
    }
  ],
  "payments": [
    {"method": "cash", "amount": 195}
  ],
  "customer_id": 1,
  "warehouse_id": 1,
  "notes": "Optional notes"
}
```

**Response Mapping**:
- `unit_price`: Item price
- `line_total`: Item total

**Side Effects**:
- Sale created
- SaleItem records created with branch_id
- SalePayment records created
- Stock movements (via event listener)
- Journal entries (via event listener)

---

### `GET /api/v1/pos/session`
**Auth**: Sanctum + throttle:api  
**Controller**: `POSController@getCurrentSession`  
**Service**: `POSService@getCurrentSession`  
**Purpose**: Get current open POS session

---

### `POST /api/v1/pos/session/open`
**Auth**: Sanctum + throttle:api  
**Controller**: `POSController@openSession`  
**Service**: `POSService@openSession`  
**Purpose**: Open new POS session

**Request Body**:
```json
{
  "branch_id": 1,
  "opening_cash": 1000
}
```

---

### `POST /api/v1/pos/session/{sessionId}/close`
**Auth**: Sanctum + throttle:api  
**Controller**: `POSController@closeSession`  
**Service**: `POSService@closeSession`  
**Purpose**: Close POS session

**Request Body**:
```json
{
  "closing_cash": 5000,
  "notes": "All good"
}
```

---

## Customer Routes

### `GET /api/v1/customers`
**Auth**: Store Token  
**Controller**: `CustomersController@index`  
**Purpose**: List customers

### `POST /api/v1/customers`
**Auth**: Store Token  
**Controller**: `CustomersController@store`  
**Purpose**: Create customer

---

## Store Order Routes

### `GET /api/v1/orders`
**Auth**: Store Token  
**Controller**: `OrdersController@index`  
**Service**: Direct Eloquent  
**Purpose**: List store orders

### `POST /api/v1/orders`
**Auth**: Store Token  
**Controller**: `OrdersController@store`  
**Service**: StoreOrder model  
**Purpose**: Create store order from external system

**Process Flow**:
1. Create StoreOrder with external_order_id
2. Check for existing order (external_id + branch_id)
3. If duplicate, return existing
4. Otherwise, create new StoreOrder
5. Background job converts to Sale via `StoreOrderToSaleService`

---

## Internal Routes

### `GET /api/v1/internal/diagnostics`
**Auth**: Sanctum + throttle:api  
**Controller**: `DiagnosticsController@index`  
**Service**: `DiagnosticsService@runAll`  
**Purpose**: System health check

**Checks**:
- Cache connectivity
- Queue connection
- Mail configuration
- Filesystem permissions
- Database connectivity

**Response**: HTTP 200 (OK) or 503 (Service Unavailable)

---

## Webhook Routes

### `POST /api/v1/webhooks/shopify/{storeId}`
**Auth**: Webhook signature verification  
**Controller**: `WebhooksController@handleShopify`  
**Purpose**: Receive Shopify webhooks

### `POST /api/v1/webhooks/woocommerce/{storeId}`
**Auth**: Webhook signature verification  
**Controller**: `WebhooksController@handleWooCommerce`  
**Purpose**: Receive WooCommerce webhooks

---

## Common Patterns

### Authentication
- **Sanctum**: User-based token auth (POS, internal)
- **Store Token**: Store integration auth (external systems)
- **Webhook**: Signature-based verification

### Branch Scoping
- Store token routes: Scoped by `store.branch_id`
- Sanctum routes: User's branch or route parameter

### Pagination
- Default: 50 items per page
- Max: 100 items per page
- Response includes: `current_page`, `last_page`, `per_page`, `total`

### Error Responses
```json
{
  "success": false,
  "message": "Error description"
}
```

### Success Responses
```json
{
  "success": true,
  "message": "Success description",
  "data": {}
}
```
