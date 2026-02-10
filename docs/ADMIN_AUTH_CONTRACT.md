# Admin Authentication Contract

**Version:** 1.0  
**Last Updated:** February 10, 2026  
**Status:** Authoritative source of truth for admin authentication

---

## Overview

**Admin authentication is token-based only. Cookies are not used.**

All admin authentication relies on **Bearer Token authentication** via the `Authorization` header. Tokens are issued upon successful login and must be sent with every protected request.

---

## Authentication Flow

### 1. Login

**Endpoint:** `POST /api/v1/auth/login`

**Request Headers:**
```http
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "admin@example.com",
  "password": "your-password"
}
```

**Successful Response (200):**
```json
{
  "success": true,
  "message": "Authenticated",
  "data": {
    "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz1234567890",
    "token_type": "Bearer",
    "expires_in": 604800,
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "account_type": "admin",
      "roles": ["admin"],
      "is_verified": true,
      "email_verified_at": "2026-01-15T10:30:00.000000Z",
      "created_at": "2025-12-01T08:00:00.000000Z",
      "updated_at": "2026-02-10T12:00:00.000000Z"
    }
  }
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Invalid credentials",
  "errors": [],
  "status_code": 401
}
```

---

### 2. Making Authenticated Requests

**All protected admin endpoints require the `Authorization` header with the Bearer token.**

**Request Headers:**
```http
Authorization: Bearer 1|AbCdEfGhIjKlMnOpQrStUvWxYz1234567890
Content-Type: application/json
Accept: application/json
```

**Example: Get Admin Profile**

**Endpoint:** `GET /api/v1/admin/profile`

**Request:**
```http
GET /api/v1/admin/profile HTTP/1.1
Host: api.example.com
Authorization: Bearer 1|AbCdEfGhIjKlMnOpQrStUvWxYz1234567890
Accept: application/json
```

**Successful Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "account_type": "admin",
    "roles": ["admin"]
  }
}
```

**Unauthenticated Response (401):**
```json
{
  "message": "Unauthenticated."
}
```

---

### 3. Logout

**Endpoint:** `POST /api/v1/auth/logout`

**Request Headers:**
```http
Authorization: Bearer 1|AbCdEfGhIjKlMnOpQrStUvWxYz1234567890
Content-Type: application/json
```

**Successful Response (200):**
```json
{
  "success": true,
  "message": "Logged out",
  "data": null
}
```

**Notes:**
- The token is revoked server-side and becomes invalid immediately.
- The frontend should delete the stored token from memory/localStorage after logout.

---

## Frontend Integration Guide

### Token Storage

**Recommended approaches:**

1. **Memory (Most Secure):** Store token in JavaScript memory (React state, Vue store, etc.)
   - Pros: No XSS risk if tab closes, tokens are cleared.
   - Cons: Token lost on page refresh—requires re-login.

2. **localStorage (Convenience):** Store token in `localStorage`.
   - Pros: Persists across page refreshes.
   - Cons: Vulnerable to XSS attacks—sanitize all user inputs.

3. **sessionStorage (Balance):** Store token in `sessionStorage`.
   - Pros: Cleared when tab closes, persists across page navigation.
   - Cons: Still vulnerable to XSS—sanitize inputs.

### Example: Axios Setup

```javascript
import axios from 'axios';

// Create axios instance
const apiClient = axios.create({
  baseURL: 'https://api.example.com/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to add token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('admin_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor to handle 401
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Clear token and redirect to login
      localStorage.removeItem('admin_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default apiClient;
```

### Example: Login Flow

```javascript
import apiClient from './apiClient';

async function login(email, password) {
  try {
    const response = await apiClient.post('/auth/login', {
      email,
      password,
    });

    const { token, user } = response.data.data;

    // Store token
    localStorage.setItem('admin_token', token);

    // Store user info (optional)
    localStorage.setItem('admin_user', JSON.stringify(user));

    return { token, user };
  } catch (error) {
    console.error('Login failed:', error.response?.data?.message);
    throw error;
  }
}
```

### Example: Logout Flow

```javascript
import apiClient from './apiClient';

async function logout() {
  try {
    await apiClient.post('/auth/logout');
  } catch (error) {
    console.error('Logout failed:', error);
  } finally {
    // Always clear local token even if API call fails
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin_user');
    window.location.href = '/login';
  }
}
```

---

## Protected Admin Endpoints

All endpoints under `/api/v1/admin/*` require authentication via Bearer token:

- `GET /api/v1/admin/profile` - Get admin profile
- `PUT /api/v1/admin/profile` - Update admin profile
- `POST /api/v1/admin/profile/image` - Upload profile image
- `PUT /api/v1/admin/profile/change-password` - Change password
- `GET /api/v1/admin/profile/activity` - Get profile activity
- `GET /api/v1/admin/stats/dashboard` - Get dashboard statistics
- `GET /api/v1/admin/dashboard/activity` - Get activity feed
- `GET /api/v1/admin/settings` - Get admin settings
- `PUT /api/v1/admin/settings` - Update admin settings
- `GET /api/v1/admin/audit-logs` - Get audit logs
- ... (all other admin endpoints)

---

## Authentication Guards

**Guard Used:** `auth:sanctum`

**Token Source:** `Authorization` header only (format: `Bearer <token>`)

**No cookie fallback logic is implemented.**

---

## Error Responses

### 401 Unauthenticated

Returned when:
- No `Authorization` header is present
- Token is invalid or expired
- Token has been revoked

**Response:**
```json
{
  "message": "Unauthenticated."
}
```

**Frontend Action:** Redirect to login page and clear stored token.

---

### 403 Forbidden

Returned when:
- Token is valid but user lacks required permissions/role

**Response:**
```json
{
  "success": false,
  "message": "Unauthorized.",
  "errors": [],
  "status_code": 403
}
```

**Frontend Action:** Show "Access Denied" message or redirect to dashboard.

---

## Security Best Practices

1. **HTTPS Only:** Always use HTTPS in production to protect tokens in transit.

2. **Token Expiration:** Tokens expire after 7 days (configurable in `config/sanctum.php`). Implement token refresh or prompt re-login.

3. **XSS Protection:** 
   - Sanitize all user inputs
   - Use Content Security Policy (CSP) headers
   - Validate and escape rendered content

4. **CORS Configuration:**
   - Configure allowed origins in `config/cors.php`
   - Do NOT use wildcard `*` for origins in production

5. **Rate Limiting:**
   - Login endpoint is rate-limited to prevent brute-force attacks
   - Implement exponential backoff on frontend for failed attempts

6. **Token Storage:**
   - Never log tokens in console or analytics
   - Clear tokens on logout
   - Consider token rotation for long sessions

---

## Testing Authentication

### cURL Example: Login

```bash
curl -X POST https://api.example.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "your-password"
  }'
```

### cURL Example: Authenticated Request

```bash
curl -X GET https://api.example.com/api/v1/admin/profile \
  -H "Authorization: Bearer 1|AbCdEfGhIjKlMnOpQrStUvWxYz1234567890" \
  -H "Accept: application/json"
```

### cURL Example: Logout

```bash
curl -X POST https://api.example.com/api/v1/auth/logout \
  -H "Authorization: Bearer 1|AbCdEfGhIjKlMnOpQrStUvWxYz1234567890" \
  -H "Content-Type: application/json"
```

---

## Troubleshooting

### "Unauthenticated" error on every request

**Cause:** Token not being sent or formatted incorrectly.

**Solution:**
1. Verify token is stored: `console.log(localStorage.getItem('admin_token'))`
2. Check Authorization header format: `Bearer <token>` (note the space)
3. Ensure axios interceptor is configured correctly
4. Check for typos in header key: `Authorization` (capital A)

### Token expires too quickly

**Cause:** Default expiration is 7 days (configurable).

**Solution:**
- Update `SANCTUM_EXPIRATION` in `.env` (value in minutes)
- Implement token refresh logic or prompt re-login

### CORS errors when making requests

**Cause:** Admin origin not in CORS allowed origins.

**Solution:**
- Update `config/cors.php` with your admin origin
- Ensure `ADMIN_ORIGIN` is set in `.env`
- Check browser console for specific CORS error details

---

## Summary

✅ **Authentication Type:** Token-based (Bearer)  
✅ **Token Location:** `Authorization` header  
✅ **No Cookies:** Authentication does not use cookies  
✅ **Guard:** `auth:sanctum` on all admin routes  
✅ **Error Handling:** `401` for unauthenticated, `403` for unauthorized  

**This document is the authoritative source of truth for admin authentication implementation.**
