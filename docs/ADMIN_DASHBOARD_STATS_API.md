# Admin Dashboard Statistics API

This document describes the enhanced admin dashboard statistics endpoints.

## Endpoints

### 1. Dashboard Statistics
**GET** `/api/v1/admin/stats/dashboard` or `/api/v1/admin/dashboard/stats`

Comprehensive dashboard statistics including all platform metrics.

#### Authentication
- Requires `admin` or `super-admin` role
- Use Bearer token in Authorization header

#### Response Structure

```json
{
  "success": true,
  "message": "Admin dashboard stats retrieved successfully",
  "data": {
    "total_ads": 1250,
    "active_ads": 980,
    "ads_by_type": {
      "normal": 650,
      "caishha": 200,
      "findit": 150,
      "auction": 100,
      "unique": 150
    },
    "ads_by_category": [
      {
        "category_id": 1,
        "category_name_en": "Cars",
        "category_name_ar": "سيارات",
        "total_ads": 800
      },
      {
        "category_id": 2,
        "category_name_en": "Motorcycles",
        "category_name_ar": "دراجات نارية",
        "total_ads": 250
      }
    ],
    "total_categories": 15,
    "active_categories": 12,
    "total_blogs": 45,
    "published_blogs": 38,
    "total_users": 5420,
    "non_admin_users": 5400,
    "admin_users": 20,
    "users_by_account_type": {
      "individual": 3200,
      "seller": 1800,
      "showroom": 320,
      "dealer": 80,
      "marketeer": 0
    },
    "total_views": 125000,
    "total_contacts": 8500
  }
}
```

#### Statistics Included

1. **Total Ads**
   - `total_ads`: Total number of all ads across all types
   - `active_ads`: Number of published ads

2. **Ads by Type**
   - Breakdown of ads by type (normal, caishha, findit, auction, unique)

3. **Ads by Category**
   - Array showing each category with:
     - Category ID
     - Category name (English and Arabic)
     - Total ads count

4. **Categories**
   - `total_categories`: Total number of categories
   - `active_categories`: Number of active categories

5. **Blogs**
   - `total_blogs`: Total number of blog posts
   - `published_blogs`: Number of published blogs

6. **Users**
   - `total_users`: Total registered users
   - `non_admin_users`: Users excluding admin roles
   - `admin_users`: Number of admin users
   - `users_by_account_type`: Breakdown by account type
     - individual
     - seller
     - showroom
     - dealer
     - marketeer

7. **Engagement Metrics**
   - `total_views`: Sum of all ad views
   - `total_contacts`: Sum of all ad contacts

#### Time Series Data (Optional)

Add query parameters to get time-series data:
- `?time_series=true` - Enable time series
- `?start=2026-01-01` - Start date (optional)
- `?end=2026-02-12` - End date (optional)
- `?interval=day` - Interval (day or week, default: day)

**Example Request:**
```
GET /api/v1/admin/stats/dashboard?time_series=true&start=2026-01-01&end=2026-02-12
```

**Additional Response Fields:**
```json
{
  "data": {
    // ... all previous fields ...
    "time_series": {
      "userGrowth": [
        {"timestamp": "2026-01-01", "value": 50},
        {"timestamp": "2026-01-02", "value": 65}
      ],
      "adsPublished": [
        {"timestamp": "2026-01-01", "value": 20},
        {"timestamp": "2026-01-02", "value": 35}
      ]
    }
  }
}
```

---

### 2. Ads Distribution by Category (Chart Data)
**GET** `/api/v1/admin/stats/ads-by-category-chart`

Returns ads distribution by category with percentages, optimized for chart visualization.

#### Authentication
- Requires `admin` or `super-admin` role
- Use Bearer token in Authorization header

#### Response Structure

```json
{
  "success": true,
  "message": "Ads distribution by category retrieved successfully",
  "data": {
    "total_ads": 1250,
    "categories": [
      {
        "category_id": 1,
        "category_name_en": "Cars",
        "category_name_ar": "سيارات",
        "ads_count": 800,
        "percentage": 64.00
      },
      {
        "category_id": 2,
        "category_name_en": "Motorcycles",
        "category_name_ar": "دراجات نارية",
        "ads_count": 250,
        "percentage": 20.00
      },
      {
        "category_id": 3,
        "category_name_en": "Boats",
        "category_name_ar": "قوارب",
        "ads_count": 120,
        "percentage": 9.60
      },
      {
        "category_id": 4,
        "category_name_en": "Heavy Equipment",
        "category_name_ar": "معدات ثقيلة",
        "ads_count": 80,
        "percentage": 6.40
      }
    ]
  }
}
```

#### Response Fields

- `total_ads`: Total number of ads across all categories
- `categories`: Array of category statistics (sorted by ads_count descending)
  - `category_id`: Category unique identifier
  - `category_name_en`: Category name in English
  - `category_name_ar`: Category name in Arabic
  - `ads_count`: Number of ads in this category
  - `percentage`: Percentage of total ads (rounded to 2 decimal places)

#### Use Cases

This endpoint is specifically designed for:
- Pie charts
- Donut charts
- Bar charts
- Percentage comparisons
- Category distribution visualizations

#### Example Chart Implementation (JavaScript)

```javascript
// Fetch the data
const response = await fetch('/api/v1/admin/stats/ads-by-category-chart', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Accept': 'application/json'
  }
});
const { data } = await response.json();

// Use with Chart.js (Pie Chart)
const chartData = {
  labels: data.categories.map(cat => cat.category_name_en),
  datasets: [{
    data: data.categories.map(cat => cat.percentage),
    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
  }]
};

// Or for counts instead of percentages
const countData = {
  labels: data.categories.map(cat => cat.category_name_en),
  datasets: [{
    data: data.categories.map(cat => cat.ads_count),
    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
  }]
};
```

---

## Error Responses

### Unauthorized (403)
```json
{
  "success": false,
  "message": "Unauthorized",
  "status": 403
}
```

### Unauthenticated (401)
```json
{
  "success": false,
  "message": "Unauthenticated",
  "status": 401
}
```

---

## Testing with cURL

### Dashboard Statistics
```bash
curl -X GET "http://localhost:8000/api/v1/admin/stats/dashboard" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Dashboard with Time Series
```bash
curl -X GET "http://localhost:8000/api/v1/admin/stats/dashboard?time_series=true&start=2026-01-01&end=2026-02-12" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Category Chart Data
```bash
curl -X GET "http://localhost:8000/api/v1/admin/stats/ads-by-category-chart" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## Frontend Integration Examples

### React Example

```javascript
import { useEffect, useState } from 'react';
import axios from 'axios';

function AdminDashboard() {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const response = await axios.get('/api/v1/admin/stats/dashboard', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        });
        setStats(response.data.data);
      } catch (error) {
        console.error('Error fetching stats:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchStats();
  }, []);

  if (loading) return <div>Loading...</div>;

  return (
    <div className="dashboard">
      <h1>Admin Dashboard</h1>
      
      <div className="stats-grid">
        <StatCard title="Total Ads" value={stats.total_ads} />
        <StatCard title="Active Ads" value={stats.active_ads} />
        <StatCard title="Total Users" value={stats.total_users} />
        <StatCard title="Total Views" value={stats.total_views} />
        <StatCard title="Total Contacts" value={stats.total_contacts} />
        <StatCard title="Total Categories" value={stats.total_categories} />
        <StatCard title="Total Blogs" value={stats.total_blogs} />
      </div>

      <div className="charts">
        <CategoryDistributionChart />
        <UsersByAccountTypeChart data={stats.users_by_account_type} />
      </div>
    </div>
  );
}
```

---

## Notes

1. All endpoints require admin authentication
2. Percentages in chart endpoint are rounded to 2 decimal places
3. Categories without ads will not appear in the `ads_by_category` or chart data
4. Time series data defaults to the last 30 days if no date range is specified
5. All dates in responses are in ISO 8601 format
6. Admin users are automatically excluded from `users_by_account_type` breakdown
7. Views and contacts are cumulative sums across all ads
