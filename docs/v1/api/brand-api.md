# Brand API Reference

## 🎯 Overview

Complete API reference for Brand management endpoints with detailed request/response specifications, error codes, and usage examples.

## 📋 Base Information

- **Base URL**: `http://localhost:8080/api/v1`
- **Content-Type**: `application/json`
- **Authentication**: JWT Bearer Token
- **API Version**: v1

## 🔐 Authentication

### 📝 JWT Token Format
```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### 🔄 Token Refresh
```http
POST /api/v1/auth/refresh
Content-Type: application/json

{
    "refresh_token": "your_refresh_token_here"
}
```

---

## 📄 Endpoints

### 1. 📝 Create Brand

**Endpoint**: `POST /brands`

**Creates a new brand with validation and business rules.**

#### 📋 Request
```http
POST /api/v1/brands
Content-Type: application/json
Authorization: Bearer <token>

{
    "name": "Acme Corporation",
    "status": "draft",
    "sync_mdb": 12345,
    "detail_info": {
        "description": "Technology company specializing in software",
        "website": "https://acme.com",
        "industry": "technology",
        "tags": ["software", "enterprise", "B2B"],
        "contact": {
            "email": "info@acme.com",
            "phone": "+1-555-0123"
        },
        "social_media": {
            "twitter": "@acme_corp",
            "linkedin": "acme-corporation"
        }
    }
}
```

#### 📝 Schema

| Field | Type | Required | Constraints | Description |
|-------|------|----------|-------------|-------------|
| `name` | string | ✅ | Min: 1, Max: 255, Unique | Brand name |
| `status` | string | ❌ | Enum: `draft`, `active`, `inactive`, `maintenance`, `completed`, `approved`, `rejected` | Initial status |
| `sync_mdb` | integer | ❌ | Positive integer | External system sync ID |
| `detail_info` | object | ❌ | Max: 64KB | Flexible metadata |

#### 🎯 Status Values

| Status | Description | Can Transition To |
|--------|-------------|-------------------|
| `draft` | Initial state | `active`, `inactive`, `maintenance`, `deleted` |
| `active` | Live and operational | `completed`, `approved`, `rejected` |
| `inactive` | Temporarily disabled | `active`, `draft`, `deleted` |
| `maintenance` | Under maintenance | `active`, `draft`, `deleted` |
| `completed` | Finished lifecycle | - (locked) |
| `approved` | Approved state | `completed`, `approved`, `rejected` |
| `rejected` | Rejected state | `approved`, `rejected` |

#### 📤 Response (201 Created)
```json
{
    "success": true,
    "message": "Brand successfully created",
    "data": {
        "id": 1,
        "name": "Acme Corporation",
        "status": "draft",
        "detail_info": {
            "description": "Technology company specializing in software",
            "website": "https://acme.com",
            "industry": "technology",
            "tags": ["software", "enterprise", "B2B"],
            "contact": {
                "email": "info@acme.com",
                "phone": "+1-555-0123"
            },
            "social_media": {
                "twitter": "@acme_corp",
                "linkedin": "acme-corporation"
            },
            "change_log": [
                {
                    "action": "created",
                    "user": "admin",
                    "timestamp": "2024-01-21T12:00:00Z",
                    "ip_address": "192.168.1.100"
                }
            ]
        },
        "sync_mdb": 12345,
        "created_at": "2024-01-21T12:00:00Z",
        "updated_at": "2024-01-21T12:00:00Z"
    }
}
```

#### 🚨 Error Responses

**400 Bad Request - Validation Error**
```json
{
    "success": false,
    "message": "Validation failed. Please review the provided data",
    "errors": {
        "name": ["The name field is required"],
        "status": ["The status field must be one of: draft, active, inactive, maintenance, completed, approved, rejected"],
        "detail_info": ["The detail info field must be a valid JSON object"]
    }
}
```

**409 Conflict - Brand Already Exists**
```json
{
    "success": false,
    "message": "Brand with name \"Acme Corporation\" already exists"
}
```

**422 Unprocessable Entity - Business Rule Violation**
```json
{
    "success": false,
    "message": "Invalid status \"invalid\" for Brand creation"
}
```

---

### 2. 📋 List Brands

**Endpoint**: `GET /brands`

**Retrieves paginated list of brands with filtering, sorting, and search capabilities.**

#### 📋 Request
```http
GET /api/v1/brands?page=1&page_size=20&filter[name]=acme&filter[status]=active&sort[by]=name&sort[dir]=asc
Authorization: Bearer <token>
```

#### 📝 Query Parameters

| Parameter | Type | Required | Default | Constraints | Description |
|-----------|------|----------|---------|-------------|-------------|
| `page` | integer | ❌ | 1 | Min: 1, Max: 1000 | Page number |
| `page_size` | integer | ❌ | 20 | Min: 1, Max: 100 | Items per page |
| `filter[name]` | string | ❌ | - | Max: 255 | Filter by brand name (partial match) |
| `filter[status]` | string | ❌ | - | Status enum | Filter by status |
| `filter[sync_mdb]` | integer | ❌ | - | Positive integer | Filter by sync ID |
| `sort[by]` | string | ❌ | `id` | `id`, `name`, `status`, `created_at` | Sort field |
| `sort[dir]` | string | ❌ | `desc` | `asc`, `desc` | Sort direction |

#### 🔄 Filter Examples

```http
# Filter by name (case-insensitive partial match)
GET /api/v1/brands?filter[name]=tech

# Filter by exact status
GET /api/v1/brands?filter[status]=active

# Multiple filters
GET /api/v1/brands?filter[name]=corp&filter[status]=draft

# Filter by sync_mdb
GET /api/v1/brands?filter[sync_mdb]=12345
```

#### 🔄 Sort Examples

```http
# Sort by name ascending
GET /api/v1/brands?sort[by]=name&sort[dir]=asc

# Sort by creation date descending
GET /api/v1/brands?sort[by]=created_at&sort[dir]=desc

# Sort by status then name
GET /api/v1/brands?sort[by]=status&sort[dir]=asc&sort[by]=name&sort[dir]=asc
```

#### 📤 Response (200 OK)
```json
{
    "success": true,
    "message": "Brand list successfully retrieved",
    "data": [
        {
            "id": 1,
            "name": "Acme Corporation",
            "status": "active",
            "detail_info": {
                "description": "Technology company",
                "website": "https://acme.com",
                "industry": "technology"
            },
            "sync_mdb": 12345,
            "created_at": "2024-01-21T12:00:00Z",
            "updated_at": "2024-01-21T12:30:00Z"
        },
        {
            "id": 2,
            "name": "Tech Solutions Inc",
            "status": "draft",
            "detail_info": {
                "description": "IT consulting firm"
            },
            "sync_mdb": null,
            "created_at": "2024-01-21T11:45:00Z",
            "updated_at": "2024-01-21T11:45:00Z"
        }
    ],
    "meta": {
        "filter": {
            "name": "corp",
            "status": "active"
        },
        "sort": {
            "by": "name",
            "dir": "asc"
        },
        "pagination": {
            "total": 150,
            "display": 20,
            "page": 1,
            "page_size": 20,
            "total_pages": 8
        }
    }
}
```

#### 📊 Pagination Metadata

| Field | Type | Description |
|-------|------|-------------|
| `total` | integer | Total number of records |
| `display` | integer | Number of records on current page |
| `page` | integer | Current page number |
| `page_size` | integer | Requested page size |
| `total_pages` | integer | Total number of pages |

---

### 3. 👁️ Get Brand

**Endpoint**: `GET /brands/{id}`

**Retrieves detailed information about a specific brand.**

#### 📋 Request
```http
GET /api/v1/brands/1
Authorization: Bearer <token>
```

#### 📝 Path Parameters
| Parameter | Type | Required | Constraints | Description |
|-----------|------|----------|-------------|-------------|
| `id` | integer | ✅ | Positive integer | Brand ID |

#### 📤 Response (200 OK)
```json
{
    "success": true,
    "message": "Brand details successfully retrieved",
    "data": {
        "id": 1,
        "name": "Acme Corporation",
        "status": "active",
        "detail_info": {
            "description": "Technology company specializing in software",
            "website": "https://acme.com",
            "industry": "technology",
            "tags": ["software", "enterprise", "B2B"],
            "contact": {
                "email": "info@acme.com",
                "phone": "+1-555-0123"
            },
            "social_media": {
                "twitter": "@acme_corp",
                "linkedin": "acme-corporation"
            },
            "change_log": [
                {
                    "action": "created",
                    "user": "admin",
                    "timestamp": "2024-01-21T12:00:00Z",
                    "ip_address": "192.168.1.100"
                },
                {
                    "action": "updated",
                    "user": "editor",
                    "timestamp": "2024-01-21T12:30:00Z",
                    "ip_address": "192.168.1.101",
                    "changes": ["detail_info"]
                }
            ]
        },
        "sync_mdb": 12345,
        "created_at": "2024-01-21T12:00:00Z",
        "updated_at": "2024-01-21T12:30:00Z"
    }
}
```

#### 🚨 Error Responses

**404 Not Found**
```json
{
    "success": false,
    "message": "Brand data with id: 999 was not found"
}
```

---

### 4. ✏️ Update Brand

**Endpoint**: `PUT /brands/{id}`

**Updates an existing brand with validation and business rule checks.**

#### 📋 Request
```http
PUT /api/v1/brands/1
Content-Type: application/json
Authorization: Bearer <token>

{
    "name": "Acme Corporation Updated",
    "status": "active",
    "detail_info": {
        "description": "Leading technology company specializing in enterprise software solutions",
        "website": "https://acme.com",
        "industry": "technology",
        "tags": ["software", "enterprise", "B2B", "fortune500"],
        "contact": {
            "email": "contact@acme.com",
            "phone": "+1-555-0124",
            "address": "123 Tech Street, Silicon Valley, CA 94000"
        },
        "social_media": {
            "twitter": "@acme_official",
            "linkedin": "acme-corporation",
            "facebook": "acme.corp"
        },
        "revenue": "$500M+",
        "employees": "1000+"
    }
}
```

#### 📝 Path Parameters
| Parameter | Type | Required | Constraints | Description |
|-----------|------|----------|-------------|-------------|
| `id` | integer | ✅ | Positive integer | Brand ID |

#### 📝 Request Body
All fields are optional. Only provided fields will be updated.

#### 📤 Response (200 OK)
```json
{
    "success": true,
    "message": "Brand successfully updated",
    "data": {
        "id": 1,
        "name": "Acme Corporation Updated",
        "status": "active",
        "detail_info": {
            "description": "Leading technology company specializing in enterprise software solutions",
            "website": "https://acme.com",
            "industry": "technology",
            "tags": ["software", "enterprise", "B2B", "fortune500"],
            "contact": {
                "email": "contact@acme.com",
                "phone": "+1-555-0124",
                "address": "123 Tech Street, Silicon Valley, CA 94000"
            },
            "social_media": {
                "twitter": "@acme_official",
                "linkedin": "acme-corporation",
                "facebook": "acme.corp"
            },
            "revenue": "$500M+",
            "employees": "1000+",
            "change_log": [
                {
                    "action": "created",
                    "user": "admin",
                    "timestamp": "2024-01-21T12:00:00Z",
                    "ip_address": "192.168.1.100"
                },
                {
                    "action": "updated",
                    "user": "editor",
                    "timestamp": "2024-01-21T12:30:00Z",
                    "ip_address": "192.168.1.101",
                    "changes": ["name", "detail_info"]
                }
            ]
        },
        "sync_mdb": 12345,
        "created_at": "2024-01-21T12:00:00Z",
        "updated_at": "2024-01-21T12:45:00Z"
    }
}
```

#### 🚨 Error Responses

**400 Bad Request - Invalid Status Transition**
```json
{
    "success": false,
    "message": "Cannot update Brand from status \"completed\" to \"draft\""
}
```

**404 Not Found**
```json
{
    "success": false,
    "message": "Brand data with id: 999 was not found"
}
```

**409 Conflict - Name Already Exists**
```json
{
    "success": false,
    "message": "Brand with name \"Existing Brand Name\" already exists"
}
```

---

### 5. 🗑️ Delete Brand

**Endpoint**: `DELETE /brands/{id}`

**Deletes a brand after validating business rules.**

#### 📋 Request
```http
DELETE /api/v1/brands/1
Authorization: Bearer <token>
```

#### 📝 Path Parameters
| Parameter | Type | Required | Constraints | Description |
|-----------|------|----------|-------------|-------------|
| `id` | integer | ✅ | Positive integer | Brand ID |

#### 📤 Response (200 OK)
```json
{
    "success": true,
    "message": "Brand successfully deleted",
    "data": null
}
```

#### 🚨 Error Responses

**400 Bad Request - Cannot Delete Active Brand**
```json
{
    "success": false,
    "message": "Cannot delete Brand with status \"active\""
}
```

**400 Bad Request - Cannot Delete Completed Brand**
```json
{
    "success": false,
    "message": "Cannot delete Brand with status \"completed\""
}
```

**404 Not Found**
```json
{
    "success": false,
    "message": "Brand data with id: 999 was not found"
}
```

---

## 🚨 Error Reference

### 📋 HTTP Status Codes

| Code | Category | Description |
|------|----------|-------------|
| 200 | Success | Request completed successfully |
| 201 | Success | Resource created successfully |
| 400 | Client Error | Bad request or validation error |
| 401 | Client Error | Authentication required or failed |
| 403 | Client Error | Insufficient permissions |
| 404 | Client Error | Resource not found |
| 409 | Client Error | Resource conflict |
| 422 | Client Error | Validation failed |
| 429 | Client Error | Rate limit exceeded |
| 500 | Server Error | Internal server error |

### 📝 Error Message Keys

#### Validation Errors
- `validation.failed` - General validation failure
- `validation.unknown_parameters` - Unknown request parameters
- `request.invalid_parameter` - Invalid parameter value
- `request.missing_parameter` - Required parameter missing

#### Business Logic Errors
- `resource.not_found` - Brand not found
- `resource.already_exists` - Brand name already exists
- `status.invalid_on_creation` - Invalid status for creation
- `status.invalid_transition` - Invalid status transition
- `status.cannot_delete` - Cannot delete brand with current status

#### Authentication/Authorization Errors
- `auth.header_missing` - Authorization header missing
- `auth.invalid_token` - Invalid or expired token
- `access.insufficient_permissions` - Insufficient permissions

#### System Errors
- `http.bad_request` - Generic bad request
- `http.unauthorized` - Unauthorized access
- `http.forbidden` - Access forbidden
- `http.not_found` - Resource not found
- `http.too_many_requests` - Rate limit exceeded

---

## 🧪 Usage Examples

### 📝 JavaScript/TypeScript
```typescript
interface Brand {
    id: number;
    name: string;
    status: 'draft' | 'active' | 'inactive' | 'maintenance' | 'completed' | 'approved' | 'rejected';
    detail_info: Record<string, any>;
    sync_mdb?: number;
    created_at: string;
    updated_at: string;
}

class BrandAPI {
    private baseURL = 'http://localhost:8080/api/v1';
    private token: string;

    constructor(token: string) {
        this.token = token;
    }

    private async request(endpoint: string, options?: RequestInit): Promise<any> {
        const url = `${this.baseURL}${endpoint}`;
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.token}`,
                ...options?.headers
            },
            ...options
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message);
        }

        return response.json();
    }

    async createBrand(data: Partial<Brand>): Promise<Brand> {
        const response = await this.request('/brands', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        return response.data;
    }

    async listBrands(filters?: {
        page?: number;
        page_size?: number;
        name?: string;
        status?: string;
    }): Promise<{ data: Brand[]; meta: any }> {
        const params = new URLSearchParams();
        if (filters) {
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined) {
                    params.append(key, String(value));
                }
            });
        }

        const response = await this.request(`/brands?${params}`);
        return {
            data: response.data,
            meta: response.meta
        };
    }

    async getBrand(id: number): Promise<Brand> {
        const response = await this.request(`/brands/${id}`);
        return response.data;
    }

    async updateBrand(id: number, data: Partial<Brand>): Promise<Brand> {
        const response = await this.request(`/brands/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
        return response.data;
    }

    async deleteBrand(id: number): Promise<void> {
        await this.request(`/brands/${id}`, {
            method: 'DELETE'
        });
    }
}

// Usage
const api = new BrandAPI('your-jwt-token');

// Create brand
const brand = await api.createBrand({
    name: 'New Brand',
    status: 'draft',
    detail_info: { description: 'Test brand' }
});

// List brands
const { data, meta } = await api.listBrands({
    page: 1,
    page_size: 10,
    status: 'active'
});
```

### 📝 Python
```python
import requests
from typing import Dict, List, Optional

class BrandAPI:
    def __init__(self, base_url: str, token: str):
        self.base_url = base_url.rstrip('/')
        self.token = token
        self.headers = {
            'Content-Type': 'application/json',
            'Authorization': f'Bearer {token}'
        }

    def _request(self, method: str, endpoint: str, **kwargs) -> Dict:
        url = f'{self.base_url}/api/v1{endpoint}'
        response = requests.request(method, url, headers=self.headers, **kwargs)
        response.raise_for_status()
        return response.json()

    def create_brand(self, data: Dict) -> Dict:
        return self._request('POST', '/brands', json=data)['data']

    def list_brands(self, **filters) -> Dict:
        params = {k: v for k, v in filters.items() if v is not None}
        return self._request('GET', '/brands', params=params)

    def get_brand(self, brand_id: int) -> Dict:
        return self._request('GET', f'/brands/{brand_id}')['data']

    def update_brand(self, brand_id: int, data: Dict) -> Dict:
        return self._request('PUT', f'/brands/{brand_id}', json=data)['data']

    def delete_brand(self, brand_id: int) -> None:
        self._request('DELETE', f'/brands/{brand_id}')

# Usage
api = BrandAPI('http://localhost:8080', 'your-jwt-token')

# Create brand
brand = api.create_brand({
    'name': 'Python Brand',
    'status': 'draft'
})

# List brands
brands = api.list_brands(page=1, page_size=20, status='active')
```

### 📝 cURL
```bash
# Set variables
BASE_URL="http://localhost:8080/api/v1"
TOKEN="your-jwt-token"

# Create brand
curl -X POST "$BASE_URL/brands" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "cURL Brand",
    "status": "draft",
    "detail_info": {"description": "Created via cURL"}
  }'

# List brands
curl -X GET "$BASE_URL/brands?page=1&page_size=10" \
  -H "Authorization: Bearer $TOKEN"

# Get brand
curl -X GET "$BASE_URL/brands/1" \
  -H "Authorization: Bearer $TOKEN"

# Update brand
curl -X PUT "$BASE_URL/brands/1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name": "Updated Brand"}'

# Delete brand
curl -X DELETE "$BASE_URL/brands/1" \
  -H "Authorization: Bearer $TOKEN"
```

---

## 📚 Related Documentation

- [Brand Endpoints](brand-endpoints.md)
- [Authentication](auth.md)
- [Error Handling](error-handling.md)
- [Response Formats](response-formats.md)
- [Getting Started](../development/getting-started.md)
