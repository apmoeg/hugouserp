# Timezone Configuration Documentation

## Current Configuration

The application is already configured to use **Africa/Cairo** timezone, which is the correct timezone for Egyptian operations.

### Configuration File

Location: `config/app.php`

```php
'timezone' => env('APP_TIMEZONE', 'Africa/Cairo'),
```

## Why This Matters

### The Problem (Issue #5 from Bug Report)

If the timezone is set to UTC (the default for many Laravel applications), there will be timing mismatches:

- **Example**: A sale made at 1:00 AM Cairo time (CAT) is actually 11:00 PM the previous day in UTC
- **Impact**: Sales reports for "today" would be incorrect in the first few hours of each day
- **Business Impact**: Daily reports, shift reconciliation, and POS day-close operations would show incorrect data

### Our Solution

The application is configured to use **Africa/Cairo** (GMT+2), which matches the business timezone.

## Database Storage

Best practices for timezone handling:

1. **Storage**: All timestamps are stored in the configured timezone (Africa/Cairo)
2. **Queries**: Date queries use the application timezone automatically
3. **Display**: Timestamps are displayed in the user's timezone (Africa/Cairo)

## Verification

To verify the timezone configuration:

```bash
php artisan tinker
> config('app.timezone')
=> "Africa/Cairo"
```

## Related Code

### Date Filtering in Reports

When filtering by date, the system correctly uses Cairo timezone:

```php
// This correctly filters for "today" in Cairo timezone
whereDate('created_at', now())

// This correctly filters for a specific date in Cairo timezone  
whereDate('created_at', '2024-01-15')
```

### Carbon/DateTime Usage

All Carbon instances use the configured timezone:

```php
now() // Returns current Cairo time
today() // Returns current Cairo date
Carbon::parse('2024-01-15') // Parses in Cairo timezone
```

## Best Practices

1. **Always use `now()` and `today()`** instead of `new Carbon()` or `new DateTime()`
2. **Use `whereDate()` for date comparisons** instead of raw SQL date functions
3. **For UTC conversions**, explicitly convert: `now()->utc()`
4. **For reporting across timezones**, store and display timezone info with each record

## Status

✅ **CONFIGURED CORRECTLY** - No action needed for timezone configuration.

The system is already using Africa/Cairo timezone, preventing the timing mismatch issues described in the bug report.
