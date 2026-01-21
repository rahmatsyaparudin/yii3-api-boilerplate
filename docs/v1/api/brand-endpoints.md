# Brand API Endpoints

## 🎯 Overview

Brand API provides CRUD operations for brand management with comprehensive validation, error handling, and pagination support.

## 📋 Base URL
```
http://localhost:8080/api/v1/brands
```

## 🔐 Authentication

All endpoints require JWT authentication:
```http
Authorization: Bearer <jwt-token>
```

## 📄 Endpoints

### 1. 📝 Create Brand

**Endpoint:** `POST /api/v1/brands`

**Description:** Create a new brand with validation and business rules.

**Request:**
```http
POST /api/v1/brands
Content-Type: application/json
Authorization: Bearer <token>

{
    "name": "My Brand Name",
    "status": "draft",
    "sync_mdb": 12345,
    "detail_info": {
        "description": "Brand description",
        "tags": ["tag1", "tag2"],
        "category": "technology"
    }
}
```

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | ✅ | Brand name (unique) |
| `status` | string | ❌ | Status: `draft`, `active`, `inactive`, `maintenance` |
| `sync_mdb` | integer | ❌ | External system sync ID |
| `detail_info` | object | ❌ | Flexible metadata storage |

**Response (201 Created):**
```json
{
    "success": true,
    "message": "Brand successfully created",
    "data": {
        "id": 1,
        "name": "My Brand Name",
        "status": "draft",
        "detail_info": {
            "description": "Brand description",
            "tags": ["tag1", "tag2"],
            "category": "technology",
            "change_log": [
                {
                    "action": "created",
                    "user": "admin",
                    "timestamp": "2024-01-21T12:00:00Z"
                }
            ]
        },
        "sync_mdb": 12345,
        "created_at": "2024-01-21T12:00:00Z",
        "updated_at": "2024-01-21T12:00:00Z"
    }
}
```

**Error Responses:**
```json
// 400 Bad Request - Validation Error
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": ["The name field is required"],
        "status": ["Invalid status value"]
    }
}

// 409 Conflict - Brand Already Exists
{
    "success": false,
    "message": "Brand with name \"My Brand Name\" already exists"
}
```

---

### 2. 📋 List Brands

**Endpoint:** `GET /api/v1/brands`

**Description:** Retrieve paginated list of brands with filtering and sorting.

**Request:**
```http
GET /api/v1/brands?page=1&page_size=20&filter[name]=test&sort[by]=name&sort[dir]=asc
Authorization: Bearer <token>
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | ❌ | Page number (default: 1) |
| `page_size` | integer | ❌ | Items per page (default: 20, max: 100) |
| `filter[name]` | string | ❌ | Filter by brand name |
| `filter[status]` | string | ❌ | Filter by status |
| `filter[sync_mdb]` | integer | ❌ | Filter by sync ID |
| `sort[by]` | string | ❌ | Sort field: `id`, `name`, `status` |
| `sort[dir]` | string | ❌ | Sort direction: `asc`, `desc` |

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Brand list successfully retrieved",
    "data": [
        {
            "id": 1,
            "name": "Brand A",
            "status": "active",
            "detail_info": {...},
            "sync_mdb": 12345,
            "created_at": "2024-01-21T12:00:00Z",
            "updated_at": "2024-01-21T12:00:00Z"
        },
        {
            "id": 2,
            "name": "Brand B",
            "status": "draft",
            "detail_info": {...},
            "sync_mdb": null,
            "created_at": "2024-01-21T12:00:00Z",
            "updated_at": "2024-01-21T12:00:00Z"
        }
    ],
    "meta": {
        "filter": {
            "name": "test"
        },
        "sort": {
            "by": "name",
            "dir": "asc"
        },
        "pagination": {
            "total": 50,
            "display": 20,
            "page": 1,
            "page_size": 20,
            "total_pages": 3
        }
    }
}
```

---

### 3. 👁️ Get Brand

**Endpoint:** `GET /api/v1/brands/{id}`

**Description:** Retrieve a specific brand by ID.

**Request:**
```http
GET /api/v1/brands/1
Authorization: Bearer <token>
```

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | ✅ | Brand ID |

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Brand details successfully retrieved",
    "data": {
        "id": 1,
        "name": "Brand A",
        "status": "active",
        "detail_info": {...},
        "sync_mdb": 12345,
        "created_at": "2024-01-21T12:00:00Z",
        "updated_at": "2024-01-21T12:00:00Z"
    }
}
```

**Error Responses:**
```json
// 404 Not Found
{
    "success": false,
    "message": "Brand data with id: 999 was not found"
}
```

---

### 4. ✏️ Update Brand

**Endpoint:** `PUT /api/v1/brands/{id}`

**Description:** Update an existing brand with validation and business rules.

**Request:**
```http
PUT /api/v1/brands/1
Content-Type: application/json
Authorization: Bearer <token>

{
    "name": "Updated Brand Name",
    "status": "active",
    "detail_info": {
        "description": "Updated description",
        "category": "enterprise"
    }
}
```

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | ✅ | Brand ID |

**Request Body:**
Same as create endpoint, but all fields are optional.

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Brand successfully updated",
    "data": {
        "id": 1,
        "name": "Updated Brand Name",
        "status": "active",
        "detail_info": {
            "description": "Updated description",
            "category": "enterprise",
            "change_log": [
                {
                    "action": "updated",
                    "user": "admin",
                    "timestamp": "2024-01-21T12:30:00Z",
                    "changes": ["name", "detail_info"]
                }
            ]
        },
        "sync_mdb": 12345,
        "created_at": "2024-01-21T12:00:00Z",
        "updated_at": "2024-01-21T12:30:00Z"
    }
}
```

**Error Responses:**
```json
// 400 Bad Request - Invalid Status Transition
{
    "success": false,
    "message": "Cannot update Brand from status \"active\" to \"draft\""
}

// 404 Not Found
{
    "success": false,
    "message": "Brand data with id: 999 was not found"
}
```

---

### 5. 🗑️ Delete Brand

**Endpoint:** `DELETE /api/v1/brands/{id}`

**Description:** Delete a brand with business rule validation.

**Request:**
```http
DELETE /api/v1/brands/1
Authorization: Bearer <token>
```

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | ✅ | Brand ID |

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Brand successfully deleted",
    "data": null
}
```

**Error Responses:**
```json
// 400 Bad Request - Cannot Delete Active Brand
{
    "success": false,
    "message": "Cannot delete Brand with status \"active\""
}

// 404 Not Found
{
    "success": false,
    "message": "Brand data with id: 999 was not found"
}
```

---

## 🎯 Business Rules

### 📋 Status Rules
- **Draft → Active**: Allowed
- **Draft → Inactive**: Allowed  
- **Draft → Maintenance**: Allowed
- **Active → Completed**: Allowed
- **Active → Approved**: Allowed
- **Active → Rejected**: Allowed
- **Inactive → Active**: Allowed
- **Inactive → Draft**: Allowed
- **Inactive → Deleted**: Allowed
- **Maintenance → Active**: Allowed
- **Maintenance → Draft**: Allowed
- **Maintenance → Deleted**: Allowed

### 🚫 Deletion Rules
- Cannot delete brands with `active` status
- Cannot delete brands with `completed` status
- Can delete brands with `draft`, `inactive`, `maintenance` status

### 🏷️ Name Rules
- Brand names must be unique
- Brand names cannot be empty
- Brand names are case-sensitive

---

## 📊 Response Format

### ✅ Success Response
```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {...},
    "meta": {...} // Only for list endpoints
}
```

### ❌ Error Response
```json
{
    "success": false,
    "message": "Error description",
    "errors": {...} // Only for validation errors
}
```

---

## 🔍 Search and Filtering

### 📋 Filter Examples
```http
# Filter by name (partial match)
GET /api/v1/brands?filter[name]=test

# Filter by status
GET /api/v1/brands?filter[status]=active

# Multiple filters
GET /api/v1/brands?filter[name]=test&filter[status]=active

# Filter by sync_mdb
GET /api/v1/brands?filter[sync_mdb]=12345
```

### 🔄 Sorting Examples
```http
# Sort by name ascending
GET /api/v1/brands?sort[by]=name&sort[dir]=asc

# Sort by status descending
GET /api/v1/brands?sort[by]=status&sort[dir]=desc

# Sort by ID
GET /api/v1/brands?sort[by]=id&sort[dir]=desc
```

### 📄 Pagination Examples
```http
# First page, default size
GET /api/v1/brands

# Page 2, 10 items per page
GET /api/v1/brands?page=2&page_size=10

# Last page
GET /api/v1/brands?page=3&page_size=20
```

---

## 🚨 Error Codes

| HTTP Code | Error Type | Description |
|-----------|------------|-------------|
| 400 | Bad Request | Validation errors, business rule violations |
| 401 | Unauthorized | Missing or invalid authentication |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Resource already exists |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Unexpected server error |

---

## 📚 Usage Examples

### 📝 JavaScript/Fetch
```javascript
// Create brand
const response = await fetch('/api/v1/brands', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
        name: 'My Brand',
        status: 'draft'
    })
});

const result = await response.json();
console.log(result);
```

### 📝 cURL
```bash
# List brands
curl -X GET "http://localhost:8080/api/v1/brands" \
  -H "Authorization: Bearer ${token}"

# Create brand
curl -X POST "http://localhost:8080/api/v1/brands" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${token}" \
  -d '{"name":"My Brand","status":"draft"}'
```

### 📝 PHP/Guzzle
```php
$client = new GuzzleHttp\Client();

$response = $client->post('/api/v1/brands', [
    'headers' => [
        'Authorization' => "Bearer {$token}",
        'Content-Type' => 'application/json'
    ],
    'json' => [
        'name' => 'My Brand',
        'status' => 'draft'
    ]
]);

$result = json_decode($response->getBody(), true);
```

---

## 📚 Related Documentation

- [Request/Response](request-response.md)
- [Validation](validation.md)
- [Error Handling](error-handling.md)
- [Brand Application Service](../application/brand-service.md)
- [Brand Domain](../domain/brand.md)
