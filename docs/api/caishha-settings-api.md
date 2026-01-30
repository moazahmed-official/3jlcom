# Caishha Settings Management API

## Overview
The Caishha Settings Management API allows administrators to configure system-wide settings for the Caishha Ads platform. This replaces hard-coded values with dynamic, admin-configurable settings.

## Base URL
```
/api/v1/caishha/admin/settings
```

## Authentication
All endpoints require admin authentication via Sanctum bearer token.

## Settings Configuration

### Available Settings

| Setting Key | Type | Description | Default Value | Min | Max |
|-------------|------|-------------|---------------|-----|-----|
| `default_dealer_window_seconds` | integer | Default time for dealers to submit offers | 129600 (36 hours) | 3600 | 604800 |
| `default_visibility_period_seconds` | integer | Default time ads remain visible to sellers | 129600 (36 hours) | 0 | 604800 |
| `min_dealer_window_seconds` | integer | Minimum allowed dealer window | 3600 (1 hour) | 3600 | 86400 |
| `max_dealer_window_seconds` | integer | Maximum allowed dealer window | 604800 (7 days) | 86400 | 2592000 |
| `min_visibility_period_seconds` | integer | Minimum visibility period | 0 | 0 | 86400 |
| `max_visibility_period_seconds` | integer | Maximum visibility period | 604800 (7 days) | 86400 | 2592000 |

## Endpoints

### 1. Get All Settings

**GET** `/api/v1/caishha/admin/settings`

Retrieves all Caishha settings.

#### Response
```json
{
  "status": "success",
  "data": {
    "settings": [
      {
        "key": "default_dealer_window_seconds",
        "value": "129600",
        "type": "integer",
        "description": "Default time in seconds for dealers to submit offers"
      },
      {
        "key": "default_visibility_period_seconds",
        "value": "129600",
        "type": "integer", 
        "description": "Default time in seconds ads remain visible to sellers"
      }
    ]
  }
}
```

### 2. Update Multiple Settings (Bulk)

**PUT** `/api/v1/caishha/admin/settings`

Updates multiple settings in a single request.

#### Request Body
```json
{
  "settings": [
    {
      "key": "default_dealer_window_seconds",
      "value": "86400"
    },
    {
      "key": "default_visibility_period_seconds",
      "value": "172800"
    }
  ]
}
```

#### Response
```json
{
  "status": "success",
  "message": "Settings updated successfully",
  "data": {
    "updated": [
      "default_dealer_window_seconds",
      "default_visibility_period_seconds"
    ]
  }
}
```

#### Validation Errors
```json
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "settings.0.value": [
      "The value must be between 3600 and 604800 seconds."
    ]
  }
}
```

### 3. Update Single Setting

**PUT** `/api/v1/caishha/admin/settings/{key}`

Updates a single setting by key.

#### Parameters
- `key` (string, path): The setting key to update

#### Request Body
```json
{
  "value": "86400"
}
```

#### Response
```json
{
  "status": "success",
  "message": "Setting updated successfully",
  "data": {
    "key": "default_dealer_window_seconds",
    "value": "86400",
    "old_value": "129600"
  }
}
```

#### Error Responses
```json
{
  "status": "error",
  "code": 404,
  "message": "Setting not found"
}
```

```json
{
  "status": "error",
  "code": 422,
  "message": "Validation failed",
  "errors": {
    "value": [
      "The value must be between 3600 and 604800 seconds."
    ]
  }
}
```

### 4. Get Setting Presets

**GET** `/api/v1/caishha/admin/settings/presets`

Retrieves predefined setting presets for quick configuration.

#### Response
```json
{
  "status": "success",
  "data": {
    "presets": {
      "quick_turnaround": {
        "name": "Quick Turnaround",
        "description": "Fast-paced trading with shorter windows",
        "settings": {
          "default_dealer_window_seconds": "3600",
          "default_visibility_period_seconds": "7200"
        }
      },
      "standard": {
        "name": "Standard",
        "description": "Balanced timing for most use cases",
        "settings": {
          "default_dealer_window_seconds": "86400",
          "default_visibility_period_seconds": "86400"
        }
      },
      "extended": {
        "name": "Extended",
        "description": "Longer periods for complex negotiations",
        "settings": {
          "default_dealer_window_seconds": "259200",
          "default_visibility_period_seconds": "432000"
        }
      }
    }
  }
}
```

## Time Conversion Reference

For convenience when working with time values:

| Time Unit | Seconds |
|-----------|---------|
| 1 hour | 3600 |
| 6 hours | 21600 |
| 12 hours | 43200 |
| 24 hours (1 day) | 86400 |
| 36 hours | 129600 |
| 48 hours (2 days) | 172800 |
| 72 hours (3 days) | 259200 |
| 5 days | 432000 |
| 7 days (1 week) | 604800 |
| 30 days | 2592000 |

## Usage Examples

### Setting a 24-hour default window
```bash
curl -X PUT \
  http://your-domain/api/v1/caishha/admin/settings/default_dealer_window_seconds \
  -H 'Authorization: Bearer {admin_token}' \
  -H 'Content-Type: application/json' \
  -d '{"value": "86400"}'
```

### Applying a preset configuration
```bash
# First get the preset values
curl -X GET \
  http://your-domain/api/v1/caishha/admin/settings/presets \
  -H 'Authorization: Bearer {admin_token}'

# Then apply the standard preset
curl -X PUT \
  http://your-domain/api/v1/caishha/admin/settings \
  -H 'Authorization: Bearer {admin_token}' \
  -H 'Content-Type: application/json' \
  -d '{
    "settings": [
      {
        "key": "default_dealer_window_seconds",
        "value": "86400"
      },
      {
        "key": "default_visibility_period_seconds", 
        "value": "86400"
      }
    ]
  }'
```

## Impact of Settings Changes

### Default Values
- Changes to default settings affect new ads created after the change
- Existing ads retain their original settings unless manually updated

### Validation Limits
- Changes to min/max settings affect validation of new ad creation and updates
- Existing ads that fall outside new limits are not automatically modified

### Cache Behavior
- Settings are cached for 60 minutes to improve performance
- Cache is automatically cleared when settings are updated
- Settings changes take effect immediately for new requests

## Error Handling

All endpoints follow the standard API error response format:

```json
{
  "status": "error",
  "code": 400|401|403|404|422|500,
  "message": "Error description",
  "errors": {
    "field": ["Specific error message"]
  }
}
```

### Common Error Codes
- `401` - Unauthorized (invalid or missing token)
- `403` - Forbidden (user is not admin)
- `404` - Setting not found
- `422` - Validation failed
- `500` - Server error

## Security Notes

1. Only users with admin role can access these endpoints
2. All changes are logged (if audit logging is enabled)
3. Settings values are validated against configured min/max ranges
4. Invalid setting keys are rejected
5. Type validation ensures only appropriate data types are stored