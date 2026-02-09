# Quick Reference: Admin Audit Log API

## For Frontend Developers

This is a quick reference guide for integrating the admin audit log feature into the frontend.

---

## 🔐 Authentication Required

All audit log endpoints require:
- **Bearer token authentication**
- **Admin or super_admin role**

Non-admin users will receive `403 Forbidden`.

---

## 📍 API Endpoints

### 1. List Audit Logs
```
GET /api/v1/admin/audit-logs
```

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Query Parameters (all optional):**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `start_date` | string | Start date (ISO 8601) | `2026-02-01` |
| `end_date` | string | End date (ISO 8601) | `2026-02-09` |
| `actor_id` | integer | User ID who performed action | `5` |
| `actor_role` | string | User role | `admin` |
| `action_type` | string | Action performed | `user.created` |
| `resource_type` | string | Resource affected | `User`, `Package` |
| `resource_id` | string | ID of resource | `123` |
| `severity` | string | Minimum severity | `info`, `warning`, `error` |
| `correlation_id` | string | Trace related events | UUID |
| `page` | integer | Page number | `1` |
| `per_page` | integer | Results per page (max 500) | `50` |
| `sort` | string | Sort field | `timestamp`, `severity` |
| `sort_direction` | string | Sort order | `asc`, `desc` |
| `format` | string | Response format | `json`, `csv` |

**Example Request:**
```javascript
fetch('/api/v1/admin/audit-logs?start_date=2026-02-01&severity=warning&per_page=50', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
```

**Response (JSON):**
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
      },
      "actor": {
        "id": 5,
        "name": "John Admin",
        "email": "admin@example.com"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 150,
    "last_page": 3,
    "from": 1,
    "to": 50
  }
}
```

---

### 2. Get Audit Statistics
```
GET /api/v1/admin/audit-logs/stats
```

**Example Request:**
```javascript
fetch('/api/v1/admin/audit-logs/stats', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
```

**Response:**
```json
{
  "success": true,
  "message": "Audit statistics retrieved successfully",
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
      "notice": 150,
      "warning": 200,
      "error": 100,
      "critical": 15
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

---

### 3. Get Single Audit Log
```
GET /api/v1/admin/audit-logs/{id}
```

**Response:**
```json
{
  "success": true,
  "message": "Audit log retrieved successfully",
  "data": {
    "id": 1,
    "timestamp": "2026-02-09T10:30:00Z",
    "actor_id": 5,
    "actor_name": "John Admin",
    "actor_role": "admin",
    "action_type": "user.deleted",
    "resource_type": "User",
    "resource_id": "123",
    "ip_address": "192.168.1.10",
    "severity": "warning",
    "details": { ... },
    "actor": { ... }
  }
}
```

---

### 4. Export as CSV
```
GET /api/v1/admin/audit-logs?format=csv&start_date=2026-02-01
```

**Example Implementation:**
```javascript
// Download CSV
const downloadCsv = async (filters) => {
  const params = new URLSearchParams({ 
    format: 'csv',
    ...filters 
  });
  
  const response = await fetch(`/api/v1/admin/audit-logs?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `audit_logs_${new Date().toISOString()}.csv`;
  a.click();
};
```

---

## 🎨 UI Components Needed

### 1. Audit Log List Page
- **Table/List view** with columns:
  - Timestamp
  - Actor (name/ID)
  - Action Type
  - Resource Type
  - Resource ID
  - Severity (with color coding)
  - Details (expandable)

- **Filters:**
  - Date range picker
  - Severity dropdown
  - Action type dropdown (auto-populated from stats)
  - Resource type dropdown
  - Search by actor name
  
- **Pagination controls**
- **CSV Export button**

### 2. Audit Log Detail Modal
- Full log entry with formatted JSON details
- Link to related resource (user, package, ad)
- Link to actor profile
- Related logs by correlation ID

### 3. Audit Statistics Dashboard
- Count widgets:
  - Total logs
  - Logs today/this week/this month
  - Critical logs (alert count)
  
- Charts:
  - Logs by action type (bar chart)
  - Logs by severity (pie chart)
  - Top actors (leaderboard)
  
- Recent critical logs list

---

## 🎯 Severity Levels & Colors

Suggest using these colors:

| Severity | Color | Icon | Usage |
|----------|-------|------|-------|
| `debug` | Gray | 🐛 | Diagnostic info |
| `info` | Blue | ℹ️ | Normal operations |
| `notice` | Cyan | 📢 | Important events |
| `warning` | Orange | ⚠️ | Destructive actions |
| `error` | Red | ❌ | Errors |
| `critical` | Dark Red | 🔥 | Critical errors |
| `alert` | Purple | 🚨 | Security events |
| `emergency` | Black/Red | 💀 | System failures |

---

## 📝 Common Action Types

Display-friendly labels:

| Action Type | Label | Icon |
|-------------|-------|------|
| `user.created` | User Created | ➕👤 |
| `user.updated` | User Updated | ✏️👤 |
| `user.deleted` | User Deleted | 🗑️👤 |
| `user.verification_approved` | User Verified ✓ | ✅👤 |
| `user.verification_rejected` | User Verification Rejected | ❌👤 |
| `package.created` | Package Created | ➕📦 |
| `package.updated` | Package Updated | ✏️📦 |
| `package.deleted` | Package Deleted | 🗑️📦 |
| `package.assigned` | Package Assigned | 🎁 |
| `package.revoked` | Package Revoked | ⛔ |
| `ad.published` | Ad Published | 📰 |
| `ad.unpublished` | Ad Unpublished | 🚫📰 |
| `ad.deleted` | Ad Deleted | 🗑️📰 |
| `system.config_changed` | Config Changed | ⚙️ |
| `system.error.*` | System Error | ⚠️ |

---

## 🔍 Search & Filter Examples

### Example 1: Show all user deletions in the last month
```javascript
{
  action_type: 'user.deleted',
  start_date: '2026-01-09',
  end_date: '2026-02-09'
}
```

### Example 2: Show critical errors today
```javascript
{
  severity: 'critical',
  start_date: new Date().toISOString().split('T')[0]
}
```

### Example 3: Show all actions by specific admin
```javascript
{
  actor_id: 5,
  sort: 'timestamp',
  sort_direction: 'desc'
}
```

### Example 4: Trace a specific request
```javascript
{
  correlation_id: '550e8400-e29b-41d4-a716-446655440000'
}
```

---

## ⚡ Performance Tips

1. **Pagination:** Always use pagination for large result sets (default: 50 per page)
2. **Date Ranges:** Limit date ranges to avoid slow queries
3. **Export:** CSV exports may take time for large datasets - show loading indicator
4. **Caching:** Consider caching statistics for dashboard (refresh every 5 minutes)
5. **Real-time:** For real-time monitoring, poll every 10-30 seconds (don't overload)

---

## 🚨 Error Handling

### Common Errors:

**403 Forbidden:**
```json
{
  "success": false,
  "message": "Unauthorized"
}
```
→ User is not admin. Redirect to login or show access denied.

**422 Validation Error:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "start_date": ["The start date must be a valid date."]
  }
}
```
→ Show validation errors in filter form.

**500 Internal Error:**
```json
{
  "success": false,
  "message": "Internal server error"
}
```
→ Show generic error message, log to frontend monitoring.

---

## 📱 Responsive Design

- **Desktop:** Full table with all columns
- **Tablet:** Hide less important columns (IP, user agent)
- **Mobile:** Card view with essential info, expandable details

---

## 🔗 Integration Points

### Link to Resources:
```javascript
// Link to user profile
if (log.resource_type === 'User') {
  return `/admin/users/${log.resource_id}`;
}

// Link to package details
if (log.resource_type === 'Package') {
  return `/admin/packages/${log.resource_id}`;
}

// Link to ad
if (log.resource_type === 'Ad') {
  return `/admin/ads/${log.resource_id}`;
}
```

### Link to Actor:
```javascript
// Link to admin profile
return `/admin/users/${log.actor_id}`;
```

---

## ✅ Testing Checklist

- [ ] List view loads with default pagination
- [ ] Filters work correctly (date, severity, action type)
- [ ] Pagination works (next/prev/jump to page)
- [ ] Sort works (timestamp, severity)
- [ ] CSV export downloads correctly
- [ ] Detail modal shows full log entry
- [ ] Statistics dashboard loads
- [ ] Non-admin users see 403
- [ ] Loading states during fetch
- [ ] Error messages display properly
- [ ] Mobile responsive view works

---

## 🆘 Support

For backend API issues or questions:
- Check [docs/AUDIT_LOGGING.md](../docs/AUDIT_LOGGING.md) for detailed documentation
- Review [AUDIT_LOGGING_IMPLEMENTATION.md](../AUDIT_LOGGING_IMPLEMENTATION.md) for implementation details
- Contact backend team

---

**Last Updated:** February 9, 2026
