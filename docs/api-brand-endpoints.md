# Brand API Endpoints Documentation

## 📋 Overview
Complete documentation for Brand API endpoints with current implementation status, request/response formats, and business rules.

## 🔗 Base URL
```
/api/v1/brands
```

## 📊 Endpoints Summary

| Method | Endpoint | Description | Status |
|--------|----------|-------------|--------|
| `POST` | `/brands` | Create new brand | ✅ Implemented |
| `GET` | `/brands` | List brands with pagination | ✅ Implemented |
| `GET` | `/brands/{id}` | Get brand by ID | ✅ Implemented |
| `PUT` | `/brands/{id}` | Update brand | ✅ Implemented |
| `DELETE` | `/brands/{id}` | Delete brand (soft delete) | ✅ Implemented |

---

## 🔧 Create Brand

### **POST** `/brands`

Create a new brand with validation and business rules.

#### **Request Headers**
```http
Content-Type: application/json
Accept: application/json
```

#### **Request Body**
```json
{
    "name": "Test Brand"
}
```

#### **Request Parameters**
| Parameter | Type | Required | Validation | Default |
|-----------|------|----------|------------|---------|
| `name` | string | ✅ Yes | Max 100 characters, unique | - |
| `status` | integer | ❌ No | Must be valid status enum | `INACTIVE (3)` |

#### **Validation Rules**
```php
// Input Validation:
- name: required, string, max 100 characters
- status: optional, integer (if provided)

// Business Rules:
- Status must be ACTIVE (2) or DRAFT (1) for creation
- Name must be unique across all brands
```

#### **Response (201 Created)**
```json
{
    "code": 201,
    "success": true,
    "message": "Brand created successfully",
    "data": {
        "id": 71,
        "name": "Test Brand",
        "status": 3,
        "status_label": "Inactive",
        "detail_info": {
            "change_log": {
                "created_at": "2026-01-15T13:47:39Z",
                "created_by": "testid",
                "deleted_at": null,
                "deleted_by": null,
                "updated_at": null,
                "updated_by": null
            }
        },
        "sync_mdb": null
    }
}
```

#### **Error Responses**
```json
// Validation Error (422):
{
    "code": 422,
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": "Name is required",
        "status": "Brand must be in 'active' or 'draft' status to proceed with creation."
    }
}

// Duplicate Error (422):
{
    "code": 422,
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": "Brand with value 'Test Brand' already exists"
    }
}
```

---

## 📋 List Brands

### **GET** `/brands`

Retrieve paginated list of brands with optional filtering and sorting.

#### **Query Parameters**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | ❌ No | `1` | Page number |
| `limit` | integer | ❌ No | `20` | Items per page (max 100) |
| `search` | string | ❌ No | - | Search by name |
| `status` | integer | ❌ No | - | Filter by status |
| `sort` | string | ❌ No | `id` | Sort field |
| `order` | string | ❌ No | `asc` | Sort direction (asc/desc) |

#### **Request Examples**
```http
GET /api/v1/brands?page=1&limit=10&search=test&status=2&sort=name&order=asc
```

#### **Response (200 OK)**
```json
{
    "code": 200,
    "success": true,
    "message": "Success",
    "data": [
        {
            "id": 1,
            "name": "Test Brand",
            "status": 2,
            "status_label": "Active",
            "detail_info": {
                "change_log": {
                    "created_at": "2026-01-15T13:47:39Z",
                    "created_by": "testid"
                }
            },
            "sync_mdb": null
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 10,
        "total": 25,
        "total_pages": 3,
        "has_next": true,
        "has_prev": false
    }
}
```

---

## 🔍 Get Brand by ID

### **GET** `/brands/{id}`

Retrieve a specific brand by ID.

#### **Path Parameters**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | ✅ Yes | Brand ID |

#### **Response (200 OK)**
```json
{
    "code": 200,
    "success": true,
    "message": "Success",
    "data": {
        "id": 1,
        "name": "Test Brand",
        "status": 2,
        "status_label": "Active",
        "detail_info": {
            "change_log": {
                "created_at": "2026-01-15T13:47:39Z",
                "created_by": "testid",
                "updated_at": "2026-01-15T14:30:00Z",
                "updated_by": "testid"
            }
        },
        "sync_mdb": 12345
    }
}
```

#### **Error Responses**
```json
// Not Found (404):
{
    "code": 404,
    "success": false,
    "message": "Brand not found",
    "errors": {
        "brand": "Brand with ID 999 not found"
    }
}
```

---

## ✏️ Update Brand

### **PUT** `/brands/{id}`

Update an existing brand with validation and business rules.

#### **Path Parameters**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | ✅ Yes | Brand ID |

#### **Request Body**
```json
{
    "name": "Updated Brand Name",
    "status": 3
}
```

#### **Request Parameters**
| Parameter | Type | Required | Validation |
|-----------|------|----------|------------|
| `name` | string | ❌ No | Max 100 characters, unique if changed |
| `status` | integer | ❌ No | Must be valid status enum |

#### **Business Rules**
```php
// Update Validation:
- Brand must exist
- Name must be unique if changed
- Status transition must be allowed (canTransitionTo check)
- Brand cannot be updated if status is COMPLETED or DELETED

// Status Transition Rules:
DRAFT (1) → INACTIVE (3), ACTIVE (2), DELETED (7), MAINTENANCE (4)
ACTIVE (2) → COMPLETED (6), APPROVED (5), REJECTED (8)
INACTIVE (3) → ACTIVE (2), DRAFT (1), DELETED (7)
MAINTENANCE (4) → INACTIVE (3), ACTIVE (2), DRAFT (1), DELETED (7)
APPROVED (5) → COMPLETED (6), APPROVED (5), REJECTED (8)
```

#### **Response (200 OK)**
```json
{
    "code": 200,
    "success": true,
    "message": "Brand updated successfully",
    "data": {
        "id": 1,
        "name": "Updated Brand Name",
        "status": 3,
        "status_label": "Inactive",
        "detail_info": {
            "change_log": {
                "created_at": "2026-01-15T13:47:39Z",
                "created_by": "testid",
                "updated_at": "2026-01-15T14:30:00Z",
                "updated_by": "testid",
                "previous_status": "Active",
                "new_status": "Inactive"
            }
        },
        "sync_mdb": null
    }
}
```

#### **Error Responses**
```json
// Status Update Error (422):
{
    "code": 422,
    "success": false,
    "message": "Validation failed",
    "errors": {
        "brand": "Cannot update Brand from status 'Active' to status 'Completed'."
    }
}

// Status Forbidden Error (422):
{
    "code": 422,
    "success": false,
    "message": "Validation failed",
    "errors": {
        "brand": "Brand data with status 'Completed' cannot be updated."
    }
}
```

---

## 🗑️ Delete Brand

### **DELETE** `/brands/{id}`

Soft delete a brand by changing status to DELETED.

#### **Path Parameters**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | ✅ Yes | Brand ID |

#### **Request Body**
```json
{
    "id": 1
}
```

#### **Business Rules**
```php
// Delete Validation:
- Brand must exist
- Brand cannot be deleted if status is ACTIVE
- Uses entity business rules validation
- Performs soft delete (status = DELETED)

// Entity Validation:
$brand = $this->service->getEntity($id);
$this->brandValidator->validateForDelete($brand);
```

#### **Response (200 OK)**
```json
{
    "code": 200,
    "success": true,
    "message": "Brand deleted successfully",
    "data": {
        "id": 1,
        "name": "Test Brand",
        "status": 7,
        "status_label": "Deleted",
        "detail_info": {
            "change_log": {
                "created_at": "2026-01-15T13:47:39Z",
                "created_by": "testid",
                "updated_at": "2026-01-15T14:45:00Z",
                "updated_by": "testid",
                "previous_status": "Active",
                "new_status": "Deleted"
            }
        },
        "sync_mdb": null
    }
}
```

#### **Error Responses**
```json
// Cannot Delete Active (422):
{
    "code": 422,
    "success": false,
    "message": "Validation failed",
    "errors": {
        "brand": "Cannot delete active Brand"
    }
}

// Not Found (404):
{
    "code": 404,
    "success": false,
    "message": "Brand not found"
}
```

---

## 📊 Status Values

| Value | Name | Label | Can Create | Can Update | Can Delete | Description |
|-------|------|-------|------------|------------|------------|-------------|
| 1 | DRAFT | Draft | ✅ Yes | ❌ No | ❌ No | Initial draft status |
| 2 | ACTIVE | Active | ✅ Yes | ❌ No | ❌ No | Active and usable |
| 3 | INACTIVE | Inactive | ❌ No | ❌ No | ❌ No | Not active (default for creation) |
| 4 | MAINTENANCE | Maintenance | ❌ No | ❌ No | ❌ No | Under maintenance |
| 5 | APPROVED | Approved | ❌ No | ❌ No | ❌ No | Approved status |
| 6 | COMPLETED | Completed | ❌ No | ❌ No | ❌ No | Final completed status |
| 7 | DELETED | Deleted | ❌ No | ❌ No | ❌ No | Soft deleted status |
| 8 | REJECTED | Rejected | ❌ No | ❌ No | ❌ No | Rejected status |

### Status Business Rules

#### **Creation Rules**
- ✅ Only DRAFT (1) and ACTIVE (2) can be used for creation
- ❌ INACTIVE (3) is default but will fail validation
- ❌ Other statuses cannot be used for creation

#### **Update Rules**
- ✅ Status not in ALLOWED_UPDATE_STATUS_LIST can be updated
- ❌ Status in ALLOWED_UPDATE_STATUS_LIST cannot be updated
- ❌ COMPLETED (6) and DELETED (7) cannot be updated

#### **Delete Rules**
- ✅ Only non-ACTIVE brands can be deleted
- ❌ ACTIVE brands cannot be deleted
- ✅ Uses entity business rules validation

---

## 🔧 Implementation Details

### **Architecture Pattern**
```
API Layer (Actions) 
    ↓
Application Layer (Validators)
    ↓
Service Layer (BrandService)
    ↓
Repository Layer (DbBrandRepository)
    ↓
Database Layer
```

### **Entity Integration**
- **Brand Entity**: Used for business rules validation
- **StatusDelegationTrait**: Provides business methods (canBeDeleted, canBeUpdated)
- **Status Value Object**: Encapsulates status business logic
- **ValidationException**: Translated error messages

### **Validation Flow**
1. **Input Validation**: Format and type checking
2. **Business Validation**: Entity business rules
3. **Cross-Entity Validation**: Unique field validation
4. **Database Validation**: Constraints and referential integrity

### **Audit Trail**
All brand operations create audit trail in `detail_info.change_log`:
- `created_at` / `created_by`: Creation timestamp and user
- `updated_at` / `updated_by`: Update timestamp and user
- `previous_status` / `new_status`: Status change tracking

---

## 🚀 Usage Examples

### **Create Brand**
```bash
curl -X POST http://localhost:8080/api/v1/brands \
  -H "Content-Type: application/json" \
  -d '{"name": "New Brand"}'
```

### **List Brands**
```bash
curl -X GET "http://localhost:8080/api/v1/brands?page=1&limit=10&search=test"
```

### **Update Brand**
```bash
curl -X PUT http://localhost:8080/api/v1/brands/1 \
  -H "Content-Type: application/json" \
  -d '{"name": "Updated Brand", "status": 3}'
```

### **Delete Brand**
```bash
curl -X DELETE http://localhost:8080/api/v1/brands/1 \
  -H "Content-Type: application/json" \
  -d '{"id": 1}'
```

---

## 📝 Notes

### **Recent Changes**
- ✅ Implemented entity-based validation for delete operations
- ✅ Added StatusDelegationTrait for business rules
- ✅ Enhanced status transition validation
- ✅ Improved error messages with translations
- ✅ Added comprehensive audit trail

### **DDD Pattern Implementation**
- **Entity**: Brand with business logic
- **Value Object**: Status with encapsulated rules
- **Repository**: Data access abstraction
- **Service**: Domain operations orchestration
- **Application**: Input validation and coordination

### **Security Considerations**
- All inputs are validated and sanitized
- Business rules prevent invalid operations
- Audit trail tracks all changes
- Soft delete prevents data loss

---

*Last Updated: January 15, 2026*
*Version: 2.0 - With Entity Integration*
