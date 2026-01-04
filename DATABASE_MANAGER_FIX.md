# Database Manager TypeError Fix

## Issue
Browser console error: `TypeError: a.toFixed is not a function` when rendering Database Manager page.

This error occurred because the `size_mb` property was being returned as `null` for system databases (information_schema, mysql, performance_schema, sys), and the React component was trying to call `.toFixed()` on a null value.

## Root Cause
In the `PhpMyAdminController.php` index() method, the query:
```sql
SELECT SUM(ROUND(((data_length + index_length) / 1024 / 1024), 2)) as size_mb
FROM information_schema.TABLES
WHERE table_schema = ?
```

Returns `null` for system databases that don't have table data. The code was:
```php
'size_mb' => $size->size_mb ?? 0,
```

While this should work, system databases were returning `null` instead of a proper numeric value.

## Solution
Updated the controller to:
1. Explicitly cast `size_mb` to float: `(float) $size->size_mb`
2. Check both that `$size` exists AND that `size_mb` is not null
3. Ensure all numeric fields are properly cast to integers/floats

### Changed Code
**File**: `app/Http/Controllers/PhpMyAdminController.php`

```php
$sizeMb = $size && $size->size_mb !== null ? (float) $size->size_mb : 0;
$rowCount = array_sum(array_column($tables, 'TABLE_ROWS') ?? []);

return [
    'name' => $name,
    'tables' => (int) count($tables),
    'size_mb' => round($sizeMb, 2),
    'rows' => (int) $rowCount,
];
```

## Result
✅ All numeric values are now guaranteed to be numbers (not null)
✅ React component can safely call `.toFixed()` on size_mb
✅ All 131 tests pass
✅ Database Manager page displays all databases correctly

## Frontend Build
- Rebuilt: `npm run build` (8.60s)
- Size_mb values are now always numeric
- No console errors

## Verification
- PhpMyAdmin Tests: ✅ 4/4 passing
- Full Test Suite: ✅ 131/131 passing
- Data Type: Ensured all values are numbers before sending to React
