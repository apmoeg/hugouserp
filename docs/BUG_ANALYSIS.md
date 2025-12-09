# Bug Analysis & Known Issues

## Known Integration Issues

### 1. Composer Install 403 Blocking CI/CD
**Status**: Active Issue  
**Impact**: Blocks automated deployment pipelines

#### Description
CI/CD pipelines fail during composer install with 403 Forbidden errors when accessing private package repositories.

#### Root Cause
- Missing or expired authentication tokens for private repositories
- Rate limiting from package registries

#### Workaround
- Use authenticated composer tokens in CI environment
- Implement caching strategy for composer dependencies
- Configure mirror repositories

---

### 2. Depreciation Service Missing Journal Entries
**Status**: Active Issue  
**Impact**: Incomplete accounting records

#### Description
The depreciation service calculates asset depreciation but fails to create corresponding journal entries.

#### Root Cause
- Journal entry creation logic not implemented in DepreciationService
- Missing integration with AccountingService

#### Fix Required
- Implement journal entry creation in DepreciationService
- Add double-entry bookkeeping for depreciation expenses
- Create corresponding debit/credit entries

---

### 3. Store Order Deduplication Ignoring branch_id
**Status**: Active Issue  
**Impact**: Potential duplicate orders across branches

#### Description
Store order deduplication only checks external_order_id without considering branch_id, allowing duplicates across different branches.

#### Root Cause
- Unique constraint missing branch_id in validation logic
- StoreOrderRepository::findByExternalId() doesn't filter by branch

#### Fix Required
```php
// Current (Wrong)
$existing = StoreOrder::where('external_order_id', $externalId)->first();

// Should be
$existing = StoreOrder::where('external_order_id', $externalId)
    ->where('branch_id', $branchId)
    ->first();
```

---

### 4. Cross-Branch Stock Sync Vulnerability
**Status**: Critical  
**Impact**: Stock movements can bypass branch isolation

#### Description
Stock synchronization endpoints don't properly validate branch_id, allowing stock movements to be created for products in different branches.

#### Root Cause
- Insufficient branch context validation in InventoryController
- Missing authorization checks in InventoryService

#### Fix Required
- Enforce branch context in all inventory operations
- Add authorization policy to verify product belongs to user's branch
- Implement InventoryService with branch-scoped operations

---

## Recommended Actions

1. **Immediate**: Fix cross-branch stock sync vulnerability
2. **High Priority**: Implement missing journal entries for depreciation
3. **Medium Priority**: Add branch_id to store order deduplication
4. **Low Priority**: Resolve CI/CD authentication issues with better token management

## Testing Requirements

All fixes should include:
- Unit tests for the specific functionality
- Integration tests for affected workflows
- Security tests to prevent regression
- Performance tests for high-volume scenarios
