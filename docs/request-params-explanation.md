# RequestParams Tidak Dapat ID dari Route

## 🔍 **Root Cause Analysis**

### 📋 **RequestParamsMiddleware Flow:**

#### **✅ 1. RequestDataParser:**
```php
// RequestDataParser hanya menangani:
- Query parameters: ?key=value&key2=value2
- Body parameters: {"key": "value"}
- TIDAK menangani route parameters: /brand/1
```

#### **✅ 2. RequestParamsMiddleware:**
```php
// RequestParamsMiddleware hanya membuat:
$params = new RequestParams($parser, $defaultPageSize, $maxPageSize);

// RequestParams hanya berisi:
- $rawParams: array()  ← dari RequestDataParser
- $pagination: PaginationParams ← dari $rawData['pagination']
- $sort: SortParams ← dari $rawData['sort']
```

#### **✅ 3. Request Attribute:**
```php
// RequestParamsMiddleware menyimpan:
$request = $request->withAttribute('payload', $params);
// $request->getAttribute('payload') → RequestParams object
```

### 📋 **Route Parameters Flow:**

#### **✅ 1. Router Middleware:**
```php
// Router middleware (di application pipeline):
$result = $this->matcher->match($request);
$this->currentRoute->setRouteWithArguments($result->route(), $result->arguments());

// Route arguments disimpan di CurrentRoute:
// $currentRoute->getArgument('id') → "1"
```

#### **✅ 2. Request Attributes:**
```php
// Request attributes yang tersedia:
- 'payload' → RequestParams (body + query)
- 'currentRoute' → CurrentRoute (route + arguments)
- 'applicationStartTime' → float
- 'actor' → Actor object
- 'paginationConfig' → array
```

### 🎯 **Kenapa RequestParams Tidak Dapat ID?**

#### **✅ 1. Different Middleware:**
```php
// RequestParamsMiddleware (sebelum Router):
// - Hanya menangani body + query
// - Tidak ada akses ke route parameters

// RouterMiddleware (setelah RequestParams):
// - Menangani routing
// - Menyimpan route arguments di CurrentRoute
```

#### **✅ 2. Execution Order:**
```php
// Middleware pipeline order:
1. RequestParamsMiddleware → $request->withAttribute('payload', $params)
2. RouterMiddleware → $request->withAttribute('currentRoute', $currentRoute)
3. Action → $request->getAttribute('payload') → RequestParams
```

#### **✅ 3. Scope Isolation:**
```php
// RequestParams hanya melihat:
- $_GET (query parameters)
- $_POST (body parameters)
- $_SERVER (server variables)

// RequestParams TIDAK melihat:
- URL path parameters (/brand/1)
- Route arguments
- CurrentRoute data
```

### 🔧 **Solusi yang Tersedia:**

#### **✅ 1. CurrentRoute (Ideal):**
```php
// Di action:
$currentRoute = $request->getAttribute(CurrentRoute::class);
$id = $currentRoute->getArgument('id');
```

#### **✅ 2. URI Parsing (Fallback):**
```php
// Di action:
$uri = (string) $request->getUri();
$path = parse_url($uri, PHP_URL_PATH);
if (preg_match('/\/(v1|test)\/brand[s]?\/(\d+)$/', $path, $matches)) {
    $id = $matches[2];
}
```

#### **✅ 3. RouteHelper (Clean):**
```php
// Di action:
$id = RouteHelper::getIdWithFallback($request);
// Internally:
// 1. Try CurrentRoute
// 2. Try request attribute
// 3. Fallback to URI parsing
```

### 💡 **RequestParams Purpose:**

#### **✅ 1. Body + Query Parameters:**
```php
// RequestParams dirancang untuk:
- POST data: {"name": "test"}
- Query string: ?search=test&status=2
- Form data: name=test&status=2
```

#### **✅ 2. Validation & Pagination:**
```php
// RequestParams menyediakan:
- Input validation
- Pagination handling
- Sort configuration
- Parameter normalization
```

#### **✅ 3. API Response Format:**
```php
// RequestParams digunakan untuk:
- Extract search parameters
- Build pagination meta
- Create consistent API responses
```

### 🎯 **Architecture Pattern:**

#### **✅ 1. Separation of Concerns:**
```php
// RequestParamsMiddleware → Body + Query
// RouterMiddleware → Route Parameters
// Action → Business Logic
```

#### **✅ 2. Single Responsibility:**
```php
// RequestParams: Handle HTTP input
// Router: Handle URL routing
// Action: Handle business logic
```

#### **✅ 3. Testability:**
```php
// RequestParams bisa di-test dengan mock data
// Router bisa di-test dengan mock routes
// Action bisa di-test dengan mock requests
```

### 🚀 **Best Practice:**

#### **✅ 1. Use CurrentRoute:**
```php
// Preferred method (cleanest):
$id = $request->getAttribute(CurrentRoute::class)?->getArgument('id');
```

#### **✅ 2. Add Fallback:**
```php
// Fallback method (robust):
$id = RouteHelper::getIdWithFallback($request);
```

#### **✅ 3. Don't Mix Concerns:**
```php
// ❌ Jangan campur aduk:
$params = $request->getAttribute('payload')->get('id');

// ✅ Pisahkan dengan jelas:
$id = RouteHelper::getIdWithFallback($request);
$params = $request->getAttribute('payload')->get('search');
```

---

**RequestParams tidak bisa dapat ID karena dirancang untuk body + query parameters, bukan route parameters!** 🎯

**Route parameters ditangani oleh Router middleware dan disimpan di CurrentRoute!** 🔧

**Gunakan CurrentRoute atau RouteHelper untuk mendapatkan ID dari URL!** 🚀
