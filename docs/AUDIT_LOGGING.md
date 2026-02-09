# Admin Audit Logging System

## Overview

The audit logging system provides secure, immutable server-side logging for admin actions, system events, and critical operations. It is designed for compliance, forensics, incident investigation, and security monitoring.

**Key Features:**
- ✅ Write-once, immutable logs (cannot be modified or deleted via API)
- ✅ Admin-only access with policy enforcement
- ✅ Advanced filtering and search capabilities
- ✅ CSV export for archival and SIEM integration
- ✅ Automatic request correlation for distributed tracing
- ✅ IP address and user agent tracking
- ✅ Severity levels for alerting and monitoring
- ✅ JSON details field for flexible structured data

---

## API Endpoints

### 1. List Audit Logs (with filtering)
```
GET /api/v1/admin/audit-logs
```

**Authentication:** Required (admin or super_admin only)

**Query Parameters:**
- `start_date` - Filter from date (ISO 8601)
- `end_date` - Filter until date (ISO 8601)
- `actor_id` - Filter by user ID
- `actor_role` - Filter by role (admin, super_admin, etc.)
- `action_type` - Filter by action (e.g., `user.created`, `package.updated`)
- `resource_type` - Filter by resource (e.g., `User`, `Package`, `Ad`)
- `resource_id` - Filter by specific resource ID
- `severity` - Minimum severity (`debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`)
- `correlation_id` - Filter by correlation ID (trace related events)
- `page` - Page number (default: 1)
- `per_page` - Results per page (default: 50, max: 500)
- `sort` - Sort field (default: `timestamp`)
- `sort_direction` - Sort order (`asc`/`desc`, default: `desc`)
- `format` - Response format (`json`/`csv`, default: `json`)

**Example Request:**
```bash
GET /api/v1/admin/audit-logs?start_date=2026-02-01&severity=warning&per_page=100
```

**Example Response:**
```json
{
  "success": true,
  "message": "Audit logs retrieved successfully",
  "data": [
    {
      "id": 1,
      "timestamp": "2026-02-09T10:30:00Z",
      "actor_id": 5,
      "actor_name": "John Admin",
      "actor_role": "admin",
      "action_type": "user.deleted",
      "resource_type": "User",
      "resource_id": "123",
      "ip_address": "192.168.1.10",
      "user_agent": "Mozilla/5.0...",
      "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
      "severity": "warning",
      "details": {
        "deleted_user": {
          "email": "user@example.com",
          "name": "John Doe"
        }
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 150,
    "last_page": 3
  }
}
```

### 2. Get Audit Statistics
```
GET /api/v1/admin/audit-logs/stats
```

**Authentication:** Required (admin or super_admin only)

**Example Response:**
```json
{
  "success": true,
  "data": {
    "total_logs": 1523,
    "logs_today": 45,
    "logs_this_week": 312,
    "logs_this_month": 1200,
    "critical_logs": 15,
    "by_action_type": {
      "user.updated": 450,
      "package.assigned": 320,
      "user.created": 280
    },
    "by_severity": {
      "info": 1200,
      "warning": 200,
      "error": 100
    },
    "top_actors": [
      {
        "actor_id": 5,
        "actor_name": "John Admin",
        "count": 523
      }
    ]
  }
}
```

### 3. Get Single Audit Log
```
GET /api/v1/admin/audit-logs/{id}
```

**Authentication:** Required (admin or super_admin only)

### 4. Export as CSV
```
GET /api/v1/admin/audit-logs?format=csv&start_date=2026-02-01
```

**Authentication:** Required (admin with export permission)

Downloads a CSV file with all filtered logs. Large exports are streamed to avoid memory issues.

---

## Usage in Code

### Option 1: Using the LogsAudit Trait (Recommended)

Add the trait to your controller:

```php
use App\Http\Traits\LogsAudit;

class MyController extends Controller
{
    use LogsAudit;

    public function update(Request $request, User $user)
    {
        // ... update logic ...

        // Log the action
        $this->auditLog('user.updated', 'User', $user->id, [
            'changes' => $request->only(['name', 'email'])
        ]);

        // Or use specialized methods
        $this->auditLogUser('updated', $user->id, [
            'changes' => $request->only(['name', 'email'])
        ]);
    }

    public function destroy(User $user)
    {
        // Log destructive actions with warning severity
        $this->auditLogDestructive('user.deleted', 'User', $user->id, [
            'email' => $user->email
        ]);

        $user->delete();
    }
}
```

**Trait Methods:**
- `auditLog($actionType, $resourceType, $resourceId, $details, $severity)`
- `auditLogUser($action, $userId, $details, $severity)`
- `auditLogPackage($action, $packageId, $details, $severity)`
- `auditLogAd($action, $adId, $details, $severity)`
- `auditLogDestructive($actionType, $resourceType, $resourceId, $details)` - Uses warning severity
- `auditLogSecurity($actionType, $resourceType, $resourceId, $details)` - Uses alert severity

### Option 2: Using the AuditLogger Service Directly

```php
use App\Services\AuditLogger;

// Basic usage
AuditLogger::log(
    'user.created',           // Action type
    'User',                   // Resource type
    $user->id,                // Resource ID
    $request->user(),         // Actor (current user)
    ['email' => $user->email], // Details
    'info',                   // Severity
    $request                  // Request (for IP/user agent)
);

// Specialized methods
AuditLogger::logUserAction('created', $user->id, $request->user(), [
    'email' => $user->email
]);

AuditLogger::logPackageAction('assigned', $package->id, $request->user(), [
    'user_id' => $targetUser->id
]);

AuditLogger::logAdAction('published', $ad->id, $request->user());

AuditLogger::logConfigChange('site.maintenance', false, true, $request->user());

AuditLogger::logError('database.connection', 'Failed to connect', [
    'host' => 'db.example.com'
]);
```

---

## What to Log

### ✅ Always Log
- **User Management:** Create, update, delete, role changes, verification status
- **Billing/Packages:** Package creation, updates, assignments, revocations
- **Ad Moderation:** Publish, unpublish, reject, feature, delete
- **Permissions:** Role assignments, permission changes
- **System Config:** Settings changes, feature toggles
- **Destructive Actions:** Any delete or irreversible operation
- **Security Events:** Failed auth attempts, suspicious activity
- **Critical Errors:** System failures, data corruption

### ❌ Don't Log (or Be Careful)
- **Passwords:** Never log passwords (even hashed)
- **API Keys/Tokens:** Never log sensitive credentials
- **PII:** Minimize logging of personally identifiable information
- **High-Volume Events:** Avoid logging read-only operations (views, searches) unless required

---

## Action Type Naming Convention

Use dot notation for consistency:

- `user.created`, `user.updated`, `user.deleted`, `user.verification_approved`
- `package.created`, `package.updated`, `package.assigned`, `package.revoked`
- `ad.published`, `ad.unpublished`, `ad.rejected`, `ad.deleted`
- `system.config_changed`, `system.maintenance_enabled`, `system.error.database`

---

## Severity Levels

| Level | Use For |
|-------|---------|
| `debug` | Detailed diagnostic information |
| `info` | Normal operations (default) |
| `notice` | Important but normal events (e.g., package assignments) |
| `warning` | Destructive actions (deletes, status changes) |
| `error` | Errors that don't require immediate action |
| `critical` | Critical errors requiring investigation |
| `alert` | Security events requiring immediate attention |
| `emergency` | System-wide failures |

---

## Correlation IDs

Correlation IDs allow tracing related events across the system:

```php
// Automatically generated from request headers or UUID
$correlationId = request()->header('X-Correlation-ID') ?? Str::uuid();

// Pass to related operations
AuditLogger::log('user.created', 'User', $user->id, ..., correlationId: $correlationId);
AuditLogger::log('package.assigned', 'Package', $pkg->id, ..., correlationId: $correlationId);

// Query all logs for a specific request
GET /api/v1/admin/audit-logs?correlation_id=550e8400-e29b-41d4-a716-446655440000
```

---

## Security Considerations

### Immutability Enforcement
- Audit logs **cannot be updated or deleted** via API
- The `AuditLog` model throws exceptions on `update()` or `delete()` calls
- Only archival processes (outside API) should remove logs

### Access Control
- All audit log endpoints require admin authentication
- `AuditLogPolicy` enforces role-based access
- Non-admin users receive 403 Forbidden

### Tamper Detection
- Consider implementing HMAC signing for log entries
- Periodic exports to immutable storage (S3, SIEM)
- Monitor for suspicious gaps in log sequences

### Data Retention
- Configure retention policy (90-365 days typical)
- Archive old logs to cold storage
- Use `archived_at` field to mark exported logs

---

## Database Schema

```sql
CREATE TABLE audit_logs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  actor_id BIGINT NULL,
  actor_name VARCHAR(100) NULL,
  actor_role VARCHAR(50) NULL,
  action_type VARCHAR(100) NOT NULL,
  resource_type VARCHAR(100) NOT NULL,
  resource_id VARCHAR(100) NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  correlation_id VARCHAR(36) NULL,
  details JSON NULL,
  severity ENUM('debug','info','notice','warning','error','critical','alert','emergency') DEFAULT 'info',
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  archived_at TIMESTAMP NULL,
  INDEX idx_timestamp (timestamp),
  INDEX idx_actor_id (actor_id),
  INDEX idx_action_type (action_type),
  INDEX idx_resource_type (resource_type),
  INDEX idx_severity (severity),
  INDEX idx_correlation_id (correlation_id),
  INDEX idx_timestamp_severity (timestamp, severity),
  INDEX idx_resource (resource_type, resource_id)
);
```

---

## Migration & Setup

1. **Run Migration:**
```bash
php artisan migrate
```

2. **Register Policy (if not auto-discovered):**
In `AuthServiceProvider.php`:
```php
protected $policies = [
    AuditLog::class => AuditLogPolicy::class,
];
```

3. **Test Endpoints:**
```bash
# Create a test log
curl -X GET http://localhost/api/v1/admin/audit-logs \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

---

## Retention & Archival

### Manual Archival Command (Example)

Create a command to archive old logs:

```php
// app/Console/Commands/ArchiveAuditLogs.php
public function handle()
{
    $cutoffDate = now()->subDays(90);
    
    $logs = AuditLog::where('timestamp', '<', $cutoffDate)
        ->whereNull('archived_at')
        ->get();
    
    // Export to CSV/S3
    foreach ($logs->chunk(1000) as $chunk) {
        Storage::disk('s3')->put(
            "audit-logs/archive-{$cutoffDate}.csv",
            $this->toCsv($chunk)
        );
        
        // Mark as archived
        foreach ($chunk as $log) {
            $log->markAsArchived();
        }
    }
}
```

Schedule in `app/Console/Kernel.php`:
```php
$schedule->command('audit:archive')->monthly();
```

---

## Monitoring & Alerting

### Critical Log Monitoring

Set up alerts for critical severity logs:

```php
// In a monitoring service
$criticalLogs = AuditLog::where('severity', 'critical')
    ->where('timestamp', '>=', now()->subHours(1))
    ->get();

if ($criticalLogs->count() > 10) {
    // Send alert to Slack/PagerDuty
    Notification::route('slack', env('SLACK_WEBHOOK'))
        ->notify(new CriticalLogsAlert($criticalLogs));
}
```

---

## Integration Examples

See the following files for complete implementation examples:
- `app/Http/Controllers/Api/V1/UserController.php` - User management logging
- `app/Http/Controllers/Api/V1/PackageController.php` - Package/billing logging

---

## Troubleshooting

**Issue:** Logs not appearing
- Verify admin role assignment: `$user->hasAnyRole(['admin', 'super_admin'])`
- Check policy authorization
- Verify migration ran successfully

**Issue:** Policy authorization fails
- Ensure `AuditLogPolicy` is registered
- Check user has correct roles
- Verify middleware applied to routes

**Issue:** CSV export fails
- Check disk space
- Verify file permissions
- Check PHP memory limit for large exports

---

## Future Enhancements

- [ ] HMAC signing for tamper detection
- [ ] Real-time streaming to SIEM (e.g., Elasticsearch, Splunk)
- [ ] Automated compliance reports
- [ ] Role-based log filtering (limit what each admin can see)
- [ ] Anomaly detection for suspicious patterns
- [ ] Integration with monitoring tools (Datadog, New Relic)

---

## Support

For questions or issues with the audit logging system:
1. Check this documentation
2. Review example implementations in UserController and PackageController
3. Check Laravel logs for errors
4. Contact the backend team
