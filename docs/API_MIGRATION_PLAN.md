# API Migration Plan - Envelope Standardization

**Version:** 1.0.0  
**Target Date:** TBD  
**Breaking Changes:** Yes  

---

## Executive Summary

This migration plan addresses inconsistent response envelopes across the API. Currently, some endpoints return Laravel ResourceCollection format (`{data, links, meta}`), while others use the application's standard envelope (`{status, message, data}`). This inconsistency makes frontend integration error-prone and unpredictable.

**Goals:**
1. Standardize ALL API responses to use the application's standard envelope
2. Move extra error fields (like `remaining`) into proper nested structures
3. Ensure all success responses include `status` and `message` fields
4. Maintain backward compatibility options where critical

---

## Design Flaws Identified

### 1. Inconsistent Response Envelopes

**Problem:** Listing endpoints return Laravel ResourceCollection directly

**Current Behavior (INCONSISTENT):**
```json
// GET /api/v1/normal-ads
{
  "data": [ /* items */ ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "path": "...",
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

**Expected Behavior (STANDARDIZED):**
```json
// GET /api/v1/normal-ads
{
  "status": "success",
  "message": "Ads retrieved successfully",
  "data": {
    "items": [ /* items */ ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 150,
      "last_page": 10,
      "from": 1,
      "to": 15
    }
  }
}
```

**Affected Endpoints:**
- `GET /api/v1/normal-ads`
- `GET /api/v1/normal-ads/my-ads`
- `GET /api/v1/normal-ads/admin`
- `GET /api/v1/users/{user}/normal-ads`
- `GET /api/v1/unique-ads`
- `GET /api/v1/unique-ads/my-ads`
- `GET /api/v1/unique-ads/admin`
- `GET /api/v1/users/{user}/unique-ads`
- `GET /api/v1/auction-ads`
- `GET /api/v1/auction-ads/my-ads`
- `GET /api/v1/auction-ads/admin`
- `GET /api/v1/users/{user}/auction-ads`
- `GET /api/v1/caishha-ads`
- `GET /api/v1/caishha-ads/my-ads`
- `GET /api/v1/caishha-ads/admin`
- `GET /api/v1/reviews`
- `GET /api/v1/ads/{ad}/reviews`
- `GET /api/v1/users/{user}/reviews`
- `GET /api/v1/reports/admin/index`
- `GET /api/v1/packages`
- `GET /api/v1/notifications`
- `GET /api/v1/favorites`
- And many other list endpoints...

---

### 2. Nonstandard Error Fields

**Problem:** Package limit errors include `remaining` at top level, breaking error schema

**Current Behavior (INCONSISTENT):**
```json
// POST /api/v1/normal-ads (package limit exceeded)
{
  "status": "error",
  "code": 403,
  "message": "You have reached your ad creation limit",
  "errors": {
    "package": ["You have reached your ad creation limit"]
  },
  "remaining": 0  // ❌ Extra top-level field
}
```

**Expected Behavior (STANDARDIZED):**
```json
// POST /api/v1/normal-ads (package limit exceeded)
{
  "status": "error",
  "code": 403,
  "message": "You have reached your ad creation limit",
  "errors": {
    "package": ["You have reached your ad creation limit"],
    "limit_info": {
      "allowed": 10,
      "used": 10,
      "remaining": 0
    }
  }
}
```

**Affected Locations:**
- `NormalAdsController@store` - package limit check
- `UniqueAdsController@store` - package limit check
- Any controller using `PackageFeatureService` that returns limit info

---

### 3. Missing Message Field

**Problem:** Some show endpoints return `{status, data}` without `message`

**Current Behavior (INCONSISTENT):**
```json
// GET /api/v1/normal-ads/5
{
  "status": "success",
  "data": { /* ad object */ }
  // ❌ Missing "message" field
}
```

**Expected Behavior (STANDARDIZED):**
```json
// GET /api/v1/normal-ads/5
{
  "status": "success",
  "message": "Ad retrieved successfully",
  "data": { /* ad object */ }
}
```

**Affected Endpoints:**
- `GET /api/v1/normal-ads/{ad}`
- `GET /api/v1/unique-ads/{ad}`
- `GET /api/v1/auction-ads/{ad}`
- `GET /api/v1/caishha-ads/{ad}`
- Various other show endpoints

---

## Migration Strategy

### Phase 1: Backend Changes (Breaking)

#### Step 1.1: Create Helper Method in BaseApiController

Add a new method to wrap ResourceCollection responses:

```php
// app/Http/Controllers/Api/BaseApiController.php

/**
 * Return a success JSON response with ResourceCollection wrapped in standard envelope.
 */
protected function successCollection($collection, string $message = 'Data retrieved successfully'): JsonResponse
{
    if ($collection instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
        return $this->success([
            'items' => $collection->items(),
            'pagination' => [
                'current_page' => $collection->currentPage(),
                'per_page' => $collection->perPage(),
                'total' => $collection->total(),
                'last_page' => $collection->lastPage(),
                'from' => $collection->firstItem(),
                'to' => $collection->lastItem(),
            ],
        ], $message);
    }

    // Fallback for simple collections
    return $this->success(['items' => $collection], $message);
}
```

#### Step 1.2: Update Controllers to Use successCollection

**Example for NormalAdsController:**

```php
// app/Http/Controllers/Api/V1/NormalAdsController.php

public function index(Request $request): JsonResponse
{
    $query = Ad::where('type', 'normal')
        ->with([/* ... */])
        ->where('status', 'published');
    
    // ... apply filters ...
    
    $ads = $query->paginate($limit);
    
    // OLD: return NormalAdResource::collection($ads);
    // NEW:
    return $this->successCollection(
        NormalAdResource::collection($ads),
        'Ads retrieved successfully'
    );
}
```

**Apply to ALL listing methods:**
- `NormalAdsController@index`, `@myAds`, `@adminIndex`
- `UniqueAdsController@index`, `@myAds`, `@adminIndex`
- `AuctionAdsController@index`, `@myAds`, `@adminIndex`
- `CaishhaAdsController@index`, `@myAds`, `@adminIndex`
- `ReviewController@index`, `@myReviews`, `@adReviews`, `@userReviews`
- `ReportController@myReports`, `@adminIndex`
- `PackageController@index`, `@myPackages`
- `NotificationController@index`
- `FavoriteController@index`
- And all other controllers returning ResourceCollections

#### Step 1.3: Fix Show Endpoints Missing Message

```php
// Example: NormalAdsController@show

public function show($id): JsonResponse
{
    $ad = Ad::where('type', 'normal')
        ->with([/* ... */])
        ->find($id);

    if (!$ad) {
        return $this->error(404, 'Ad not found', ['ad' => ['The requested ad does not exist']]);
    }

    if (!auth()->check() || auth()->id() !== $ad->user_id) {
        $ad->increment('views_count');
    }

    // OLD: return response()->json(['status' => 'success', 'data' => new NormalAdResource($ad)]);
    // NEW:
    return $this->success(
        new NormalAdResource($ad),
        'Ad retrieved successfully'
    );
}
```

#### Step 1.4: Standardize Package Limit Errors

Update all package limit error responses:

```php
// app/Http/Controllers/Api/V1/NormalAdsController.php

public function store(StoreNormalAdRequest $request, PackageFeatureService $packageService): JsonResponse
{
    // ...
    
    $adValidation = $packageService->validateAdCreation($user, 'normal');
    if (!$adValidation['allowed']) {
        DB::rollBack();
        
        // OLD:
        // return response()->json([
        //     'status' => 'error',
        //     'code' => 403,
        //     'message' => $adValidation['reason'],
        //     'errors' => ['package' => [$adValidation['reason']]],
        //     'remaining' => $adValidation['remaining']  // ❌ Top-level field
        // ], 403);
        
        // NEW:
        return $this->error(
            403,
            $adValidation['reason'],
            [
                'package' => [$adValidation['reason']],
                'limit_info' => [
                    'allowed' => $adValidation['allowed'] ?? 0,
                    'used' => $adValidation['used'] ?? 0,
                    'remaining' => $adValidation['remaining'] ?? 0,
                ]
            ]
        );
    }
    
    // ...
}
```

Apply to:
- `NormalAdsController@store`
- `UniqueAdsController@store`
- `CaishhaAdsController@store`
- `AuctionAdsController@store`
- Any other controllers checking package limits

---

### Phase 2: Frontend Migration

#### Step 2.1: Update API Client Response Handlers

**Before:**
```javascript
// Old inconsistent handling
async function fetchAds() {
  const response = await fetch('/api/v1/normal-ads');
  const json = await response.json();
  
  // Inconsistent: sometimes data.items, sometimes json.data
  const ads = json.data || json;  // ❌ Guessing
  const pagination = json.meta || json.pagination;  // ❌ Guessing
}
```

**After:**
```javascript
// New consistent handling
async function fetchAds() {
  const response = await fetch('/api/v1/normal-ads');
  const json = await response.json();
  
  if (json.status !== 'success') {
    throw new Error(json.message);
  }
  
  // ✅ Always predictable
  const ads = json.data.items;
  const pagination = json.data.pagination;
  
  return { ads, pagination };
}
```

#### Step 2.2: Update Error Handling

**Before:**
```javascript
catch (error) {
  const json = await error.response.json();
  
  // Inconsistent: sometimes remaining is top-level
  const remaining = json.remaining || json.errors?.limit_info?.remaining;  // ❌
}
```

**After:**
```javascript
catch (error) {
  const json = await error.response.json();
  
  // ✅ Always in errors.limit_info
  const limitInfo = json.errors?.limit_info;
  if (limitInfo) {
    console.log(`Limit: ${limitInfo.allowed}, Used: ${limitInfo.used}, Remaining: ${limitInfo.remaining}`);
  }
}
```

#### Step 2.3: Update Redux/State Management

```javascript
// Redux reducer example
const adsSlice = createSlice({
  name: 'ads',
  initialState: {
    items: [],
    pagination: null,
  },
  reducers: {
    setAds: (state, action) => {
      // OLD: action.payload could be { data: [...], meta: {...} }
      // NEW: action.payload is always { items: [...], pagination: {...} }
      state.items = action.payload.items;
      state.pagination = action.payload.pagination;
    },
  },
});
```

---

### Phase 3: Testing & Validation

#### Step 3.1: Automated Tests

Create/update tests to verify envelope consistency:

```php
// tests/Feature/Api/NormalAdsTest.php

public function test_index_returns_standardized_envelope()
{
    $response = $this->getJson('/api/v1/normal-ads');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'title',
                        // ... all expected fields
                    ]
                ],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                ]
            ]
        ])
        ->assertJson([
            'status' => 'success',
        ]);
}

public function test_show_returns_standardized_envelope()
{
    $ad = Ad::factory()->create(['type' => 'normal']);
    
    $response = $this->getJson("/api/v1/normal-ads/{$ad->id}");
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',  // ✅ Must include message
            'data' => [
                'id',
                'title',
                // ...
            ]
        ]);
}

public function test_package_limit_error_has_nested_limit_info()
{
    // Setup user with maxed out package
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // Create max ads
    // ...
    
    $response = $this->postJson('/api/v1/normal-ads', [/* ... */]);
    
    $response->assertStatus(403)
        ->assertJsonStructure([
            'status',
            'code',
            'message',
            'errors' => [
                'package',
                'limit_info' => [
                    'allowed',
                    'used',
                    'remaining',
                ]
            ]
        ])
        ->assertJsonMissing(['remaining']);  // ✅ Not at top level
}
```

#### Step 3.2: API Contract Testing

Use tools like Postman/Insomnia with test scripts:

```javascript
// Postman test script
pm.test("Response has standard envelope", function () {
    const json = pm.response.json();
    pm.expect(json).to.have.property('status');
    pm.expect(json).to.have.property('message');
    pm.expect(json).to.have.property('data');
});

pm.test("Paginated response has correct structure", function () {
    const json = pm.response.json();
    pm.expect(json.data).to.have.property('items');
    pm.expect(json.data).to.have.property('pagination');
    pm.expect(json.data.pagination).to.have.all.keys(
        'current_page', 'per_page', 'total', 'last_page', 'from', 'to'
    );
});
```

---

### Phase 4: Deployment & Rollback Strategy

#### Step 4.1: Feature Flag (Optional - for gradual rollout)

```php
// config/api.php
return [
    'use_standardized_envelopes' => env('API_STANDARDIZED_ENVELOPES', true),
];

// In BaseApiController
protected function successCollection($collection, string $message): JsonResponse
{
    if (!config('api.use_standardized_envelopes')) {
        // Return old format for backward compatibility
        return response()->json($collection);
    }
    
    // Return new standardized format
    return $this->success([
        'items' => $collection->items(),
        'pagination' => [/* ... */],
    ], $message);
}
```

#### Step 4.2: Versioned API (Alternative approach)

Create `/api/v2` with standardized envelopes while maintaining `/api/v1` compatibility:

```php
// routes/api_v2.php
Route::prefix('v2')->group(function () {
    // All routes use standardized envelopes
    Route::get('normal-ads', [V2\NormalAdsController::class, 'index']);
    // ...
});
```

#### Step 4.3: Rollback Plan

If issues arise:

1. Set `API_STANDARDIZED_ENVELOPES=false` in `.env`
2. Redeploy previous version
3. Frontend reverts to old response handling

---

## Implementation Checklist

### Backend

- [ ] Add `successCollection()` method to `BaseApiController`
- [ ] Update all listing endpoints to use `successCollection()`
  - [ ] NormalAdsController (index, myAds, adminIndex)
  - [ ] UniqueAdsController (index, myAds, adminIndex)
  - [ ] AuctionAdsController (index, myAds, adminIndex)
  - [ ] CaishhaAdsController (index, myAds, adminIndex)
  - [ ] ReviewController (index, myReviews, adReviews, userReviews)
  - [ ] ReportController (myReports, adminIndex)
  - [ ] PackageController (index, myPackages)
  - [ ] NotificationController (index)
  - [ ] FavoriteController (index)
  - [ ] SavedSearchController (index)
  - [ ] BlogController (index)
  - [ ] All other controllers with list endpoints
- [ ] Update all show endpoints to include `message` field
  - [ ] Normal/Unique/Auction/Caishha Ads show methods
  - [ ] User show
  - [ ] Media show
  - [ ] All other show methods
- [ ] Standardize package limit errors
  - [ ] NormalAdsController@store
  - [ ] UniqueAdsController@store
  - [ ] CaishhaAdsController@store
  - [ ] AuctionAdsController@store
- [ ] Standardize media limit errors (same pattern)
- [ ] Write/update feature tests for all changes
- [ ] Update API documentation

### Frontend

- [ ] Create new API client with standardized response handling
- [ ] Update all list fetching functions
- [ ] Update all show fetching functions
- [ ] Update error handling for package limits
- [ ] Update Redux/state management reducers
- [ ] Test all API integrations
- [ ] Update frontend documentation

### Testing

- [ ] Run full test suite
- [ ] Manual testing of all list endpoints
- [ ] Manual testing of all show endpoints
- [ ] Manual testing of error responses
- [ ] Load testing to ensure no performance regression
- [ ] Security testing (no data leakage in new structure)

### Documentation

- [ ] Update API reference documentation
- [ ] Update integration guides
- [ ] Create migration guide for API consumers
- [ ] Announce breaking changes with deprecation timeline
- [ ] Update OpenAPI/Swagger spec (if used)

---

## Timeline Estimate

- **Backend Changes:** 2-3 days
- **Frontend Changes:** 3-5 days
- **Testing & QA:** 2-3 days
- **Documentation:** 1-2 days
- **Buffer:** 2 days
- **Total:** ~2 weeks

---

## Risk Assessment

### High Risk
- **Breaking Change:** All API consumers must update simultaneously
  - **Mitigation:** Use versioned API (`/api/v2`) or feature flag

### Medium Risk
- **Frontend Bugs:** Missed response structure assumptions
  - **Mitigation:** Comprehensive testing, staged rollout

### Low Risk
- **Performance Impact:** Minimal (just response restructuring)
  - **Mitigation:** Load testing before deployment

---

## Success Criteria

✅ All API responses follow consistent envelope structure  
✅ All tests pass  
✅ Frontend successfully integrates new response format  
✅ No increase in error rates post-deployment  
✅ API documentation updated and accurate  
✅ Zero data loss or corruption  

---

## Appendix: Code Snippets

### BaseApiController Complete

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

abstract class BaseApiController extends Controller
{
    /**
     * Return a success JSON response with consistent structure.
     */
    protected function success($data = null, string $message = 'Operation successful', int $statusCode = 200): JsonResponse
    {
        $response = [
            'status' => 'success',
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a success JSON response with ResourceCollection wrapped in standard envelope.
     */
    protected function successCollection($collection, string $message = 'Data retrieved successfully'): JsonResponse
    {
        if ($collection instanceof LengthAwarePaginator) {
            return $this->success([
                'items' => $collection->items(),
                'pagination' => [
                    'current_page' => $collection->currentPage(),
                    'per_page' => $collection->perPage(),
                    'total' => $collection->total(),
                    'last_page' => $collection->lastPage(),
                    'from' => $collection->firstItem(),
                    'to' => $collection->lastItem(),
                ],
            ], $message);
        }

        // Fallback for simple collections
        return $this->success(['items' => $collection], $message);
    }

    /**
     * Return an error JSON response with consistent structure.
     */
    protected function error(int $statusCode = 400, string $message = 'An error occurred', $errors = []): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'code' => $statusCode,
            'message' => $message,
            'errors' => $errors ?: (object) [],
        ], $statusCode);
    }
}
```

---

## Questions & Support

For questions about this migration plan, contact:
- **Backend Team Lead:** [Name]
- **Frontend Team Lead:** [Name]
- **DevOps:** [Name]

---

## Approval Sign-off

- [ ] Backend Team Lead
- [ ] Frontend Team Lead
- [ ] QA Lead
- [ ] Product Owner
- [ ] CTO

**Approved Date:** _____________
