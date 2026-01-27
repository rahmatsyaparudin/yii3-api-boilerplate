# Observability

## Overview

Observability features provide insights into your API's performance, errors, and usage patterns through structured logging, request tracking, metrics, and error monitoring using a custom implementation.

## Custom Monitoring Implementation

### 1. Monitoring Service Interface

```php
// src/Infrastructure/Monitoring/MonitoringServiceInterface.php
interface MonitoringServiceInterface
{
    public function logRequest(array $data): void;
    public function logError(\Throwable $exception, array $context = []): void;
    public function incrementMetric(string $name, float $value = 1.0): void;
    public function setGauge(string $name, float $value): void;
    public function getMetrics(): array;
}
```

### 2. Custom Monitoring Service

```php
// src/Infrastructure/Monitoring/CustomMonitoringService.php
final class CustomMonitoringService implements MonitoringServiceInterface
{
    private array $counters = [];
    private array $gauges = [];
    private array $logs = [];
    private string $logFile;

    public function __construct(array $config = [])
    {
        $this->logFile = $config['log_file'] ?? 'runtime/logs/api.log';
        $this->ensureLogDirectory();
    }

    public function logRequest(array $data): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'INFO',
            'message' => 'HTTP Request',
            'context' => $data
        ];

        $this->writeLog($logEntry);
        $this->logs[] = $logEntry;
    }

    public function logError(\Throwable $exception, array $context = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'ERROR',
            'message' => $exception->getMessage(),
            'context' => array_merge([
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ], $context)
        ];

        $this->writeLog($logEntry);
        $this->logs[] = $logEntry;
    }

    public function incrementMetric(string $name, float $value = 1.0): void
    {
        $this->counters[$name] = ($this->counters[$name] ?? 0) + $value;
    }

    public function setGauge(string $name, float $value): void
    {
        $this->gauges[$name] = $value;
    }

    public function getMetrics(): array
    {
        return [
            'counters' => $this->counters,
            'gauges' => $this->gauges,
        ];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    private function writeLog(array $logEntry): void
    {
        $logLine = json_encode($logEntry) . PHP_EOL;
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    private function ensureLogDirectory(): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
}
```

## Configuration

### DI Container Configuration

```php
// config/common/di/monitoring.php
return [
    MonitoringServiceInterface::class => static function (Container $container) use ($params) {
        $monitoringConfig = $params['app/monitoring'] ?? [];
        
        return new CustomMonitoringService([
            'log_file' => $monitoringConfig['log_file'] ?? 'runtime/logs/api.log',
        ]);
    },
];
```

### Parameters Configuration

```php
// config/common/params.php
'app/monitoring' => [
    'provider' => 'custom',
    'log_file' => 'runtime/logs/api.log',
    'request_id_header' => 'X-Request-Id',
    'logging' => [
        'enabled' => true,
        'log_level' => 'info',
        'include_request_body' => false,
        'include_response_body' => false,
        'max_log_size' => 10000,
        'exclude_paths' => ['/health', '/metrics'],
        'exclude_status_codes' => [404],
    ],
    'metrics' => [
        'enabled' => true,
        'track_response_time' => true,
        'track_request_count' => true,
        'track_status_codes' => true,
        'track_memory_usage' => true,
        'track_cpu_usage' => false,
        'reset_interval' => 300,
    ],
    'error_monitoring' => [
        'enabled' => true,
        'capture_exceptions' => true,
        'capture_errors' => true,
        'max_errors_per_request' => 10,
        'include_stack_trace' => true,
        'include_request_data' => false,
        'ignore_exceptions' => [],
        'ignore_error_codes' => [404, 422],
    ],
],
```

## Usage Examples

### Dependency Injection

```php
// In your controller or service
final class BrandController
{
    public function __construct(
        private MonitoringServiceInterface $monitoringService
    ) {}

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $this->monitoringService->logRequest([
            'action' => 'brand.create',
            'user_id' => $this->getCurrentUserId($request),
        ]);

        try {
            $brand = $this->brandService->create($request->getParsedBody());
            
            $this->monitoringService->incrementMetric('brand.created');
            
            return $this->responseFactory->success($brand);
        } catch (\Throwable $e) {
            $this->monitoringService->logError($e, ['action' => 'brand.create']);
            throw $e;
        }
    }
}
```

### Runtime Access

```php
// Get monitoring service from container
$monitoringService = $container->get(MonitoringServiceInterface::class);

// Get metrics
$metrics = $monitoringService->getMetrics();

// Get logs
$logs = $monitoringService->getLogs();

// Log custom event
$monitoringService->logRequest([
    'event' => 'user.login',
    'user_id' => 123,
    'ip_address' => '192.168.1.100',
]);

// Increment custom metric
$monitoringService->incrementMetric('api.requests.success');

// Set gauge value
$monitoringService->setGauge('memory.usage', memory_get_usage(true));
```

## Benefits of Custom Implementation

1. **No External Dependencies** - Pure PHP implementation
2. **Simple & Lightweight** - Minimal overhead
3. **Full Control** - Customize as needed
4. **Easy to Debug** - No complex library internals
5. **Fast Performance** - Direct file operations
6. **Flexible** - Easy to extend and modify

## Log Format

### Request Log Example

```json
{
  "timestamp": "2024-01-01 12:00:00",
  "level": "INFO",
  "message": "HTTP Request",
  "context": {
    "action": "brand.create",
    "user_id": 123,
    "request_id": "req_640f3a2b5d4f8.1234567890",
    "method": "POST",
    "uri": "/v1/brand",
    "ip_address": "192.168.1.100"
  }
}
```

### Error Log Example

```json
{
  "timestamp": "2024-01-01 12:00:00",
  "level": "ERROR",
  "message": "Invalid brand name",
  "context": {
    "exception": "App\\Domain\\Brand\\ValidationException",
    "file": "/src/Domain/Brand/BrandValidator.php",
    "line": 42,
    "trace": "...",
    "action": "brand.create",
    "method": "POST",
    "uri": "/v1/brand"
  }
}
```

## Metrics Format

```php
[
    'counters' => [
        'api.requests.total' => 1250,
        'api.requests.success' => 1200,
        'api.requests.error' => 50,
        'brand.created' => 25,
    ],
    'gauges' => [
        'memory.usage' => 52428800,
        'memory.peak' => 67108864,
        'response_time.avg' => 150.5,
    ],
]
```

## Best Practices

1. **Log Rotation** - Implement log rotation for production
2. **Error Levels** - Use appropriate log levels (INFO, WARNING, ERROR)
3. **Context Data** - Include relevant context in logs
4. **Performance** - Monitor logging overhead
5. **Security** - Don't log sensitive data
6. **Structure** - Use consistent JSON log format
7. **Cleanup** - Regular cleanup of old logs and metrics

## Environment Configuration

### Development
```php
'app/monitoring' => [
    'log_file' => 'runtime/logs/api-dev.log',
    'logging' => [
        'include_request_body' => true,
    ],
],
```

### Production
```php
'app/monitoring' => [
    'log_file' => 'runtime/logs/api.log',
    'logging' => [
        'include_request_body' => false,
    ],
],
```

## Log Analysis

### Request Tracing

```bash
# Find all logs for a specific request
grep "req_640f3a2b5d4f8.1234567890" runtime/logs/api.log

# Find all error logs
grep '"level": "ERROR"' runtime/logs/api.log

# Find slow requests
jq -c 'select(.context.response_time > 1000)' runtime/logs/api.log
```

### Error Analysis

```bash
# Count errors by type
grep '"level": "ERROR"' runtime/logs/api.log | jq -r '.context.exception' | sort | uniq -c

# Find most frequent error paths
grep '"level": "ERROR"' runtime/logs/api.log | jq -r '.context.uri' | sort | uniq -c | sort -nr
```

```json
{
  "id": "err_640f3a2b5d4f8.1234567890",
  "timestamp": "2024-01-01 12:00:00",
  "request_id": "req_640f3a2b5d4f8.1234567890",
  "method": "POST",
  "uri": "https://api.example.com/v1/brand",
  "exception": {
    "class": "App\\Domain\\Brand\\ValidationException",
    "message": "Invalid brand name",
    "file": "/src/Domain/Brand/BrandValidator.php",
    "line": 42
  }
}
```

## Configuration

Add to `config/common/params.php`:

```php
'app/monitoring' => [
    'request_id_header' => 'X-Request-Id',
    'logging' => [
        'enabled' => true,
        'log_level' => 'info',
        'include_request_body' => false,
        'include_response_body' => false,
        'max_log_size' => 10000,
        'exclude_paths' => ['/health', '/metrics'],
        'exclude_status_codes' => [404],
    ],
    'metrics' => [
        'enabled' => true,
        'track_response_time' => true,
        'track_request_count' => true,
        'track_status_codes' => true,
        'track_memory_usage' => true,
        'track_cpu_usage' => false,
        'reset_interval' => 300,
    ],
    'error_monitoring' => [
        'enabled' => true,
        'capture_exceptions' => true,
        'capture_errors' => true,
        'max_errors_per_request' => 10,
        'include_stack_trace' => true,
        'include_request_data' => false,
        'ignore_exceptions' => [],
        'ignore_error_codes' => [404, 422],
    ],
],
```

## Middleware Pipeline

Observability middleware is applied early in the pipeline:

```php
// config/web/di/application.php
CorsMiddleware::class,
JwtMiddleware::class,
RequestIdMiddleware::class,           // Generate request ID
StructuredLoggingMiddleware::class,     // Log requests/responses
MetricsMiddleware::class,              // Collect metrics
RateLimitMiddleware::class,
SecureHeadersMiddleware::class,
ErrorMonitoringMiddleware::class,    // Capture errors
RequestBodyParser::class,
AccessMiddleware::class,
Router::class,
NotFoundMiddleware::class,
```

## Runtime Access

### Error Monitoring

```php
// Get error monitoring middleware instance
$errorMonitoring = $container->get(ErrorMonitoringMiddleware::class);

// Get recent errors
$errors = $errorMonitoring->getErrors();

// Get error by ID
$error = $errorMonitoring->getErrorById('err_640f3a2b5d4f8.1234567890');

// Get error statistics
$stats = $errorMonitoring->getErrorStats();

// Clear stored errors
$errorMonitoring->clearErrors();
```

### Structured Logging

```php
// Get logging middleware instance
$logging = $container->get(StructuredLoggingMiddleware::class);

// Get all logs
$logs = $logging->getLogs();

// Get logs by level
$errors = $logging->getLogsByLevel('ERROR');

// Get logs by request ID
$requestLogs = $logging->getLogsByRequestId('req_640f3a2b5d4f8.1234567890');

// Get log statistics
$stats = $logging->getLogStats();
```

### Metrics

```php
// Get metrics middleware instance
$metrics = $container->get(MetricsMiddleware::class);

// Get all metrics
$allMetrics = $metrics->getMetrics();

// Reset metrics
$metrics->resetMetrics();
```

## Monitoring Endpoints

### Health Check with Metrics

```php
// src/Api/V1/Monitoring/HealthAction.php
final class HealthAction
{
    public function __construct(
        private ErrorMonitoringMiddleware $errorMonitoring,
        private StructuredLoggingMiddleware $logging,
        private MetricsMiddleware $metrics
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $metrics = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'uptime' => $this->getUptime(),
            'metrics' => $this->metrics->getMetrics(),
            'errors' => [
                'total' => count($this->errorMonitoring->getErrors()),
                'recent' => array_slice($this->errorMonitoring->getErrors(), -10),
            ],
            'logs' => [
                'total' => count($this->logging->getLogs()),
                'recent' => array_slice($this->logging->getLogs(), -50),
            ],
        ];

        return $this->responseFactory->success($metrics);
    }
}
```

### Error Details Endpoint

```php
// src/Api/V1/Monitoring/ErrorsAction.php
final class ErrorsAction
{
    public function __construct(private ErrorMonitoringMiddleware $errorMonitoring) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $limit = (int) ($request->getQueryParams()['limit'] ?? 50);
        $errors = $this->errorMonitoring->getErrors();
        
        // Return most recent errors
        $recentErrors = array_slice($errors, -$limit);

        return $this->responseFactory->success([
            'total' => count($errors),
            'errors' => $recentErrors,
        ]);
    }
}
```

### Metrics Dashboard

```php
// src/Api/V1/Monitoring/MetricsAction.php
final class MetricsAction
{
    public function __construct(private MetricsMiddleware $metrics) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $allMetrics = $this->metrics->getMetrics();
        
        return $this->responseFactory->success($allMetrics);
    }
}
```

## Log Analysis

### Request Tracing

```bash
# Find all logs for a specific request
grep "req_640f3a2b5d4f8.1234567890" /var/log/api.log

# Find all error logs
grep '"level": "ERROR"' /var/log/api.log

# Find slow requests (>1000ms)
grep '"duration_ms": [0-9]{4,}' /var/log/api.log
```

### Error Analysis

```bash
# Count errors by type
grep -o '"exception_class": "[^"]*"' /var/log/api.log | sort | uniq -c

# Find most frequent error paths
grep '"path": "[^"]*"' /var/log/api.log | sort | uniq -c | sort -nr
```

## Performance Considerations

### Memory Usage

- Logs are stored in memory with a configurable limit
- Metrics are automatically reset every 5 minutes
- Error storage is limited per request

### Log Volume

- Exclude health checks and metrics endpoints
- Filter out 404 errors to reduce noise
- Limit log entry size to prevent memory issues

### Database Impact

- All monitoring is in-memory only
- No database writes for performance
- Can be extended to write to external systems

## Custom Error Handlers

### Slack Notifications

```php
// In ErrorMonitoringMiddleware
private function handleCriticalError(array $errorData): void
{
    $webhook = 'https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK';
    
    $payload = [
        'text' => '🚨 Critical API Error',
        'attachments' => [[
            'color' => 'danger',
            'fields' => [
                [
                    'title' => 'Error ID',
                    'value' => $errorData['id'],
                    'short' => true,
                ],
                [
                    'title' => 'Request',
                    'value' => $errorData['method'] . ' ' . $errorData['uri'],
                    'short' => true,
                ],
                [
                    'title' => 'Exception',
                    'value' => $errorData['exception']['class'],
                    'short' => true,
                ],
            ],
        ]],
    ];

    $this->sendWebhook($webhook, $payload);
}
```

### Email Notifications

```php
private function sendEmailAlert(array $errorData): void
{
    $to = 'alerts@example.com';
    $subject = 'API Error: ' . $errorData['exception']['class'];
    $body = $this->formatErrorEmail($errorData);
    
    mail($to, $subject, $body);
}
```

## Production Considerations

### Log Rotation

```bash
# Set up logrotate for API logs
/var/log/api/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

### External Monitoring

- **Prometheus**: Export metrics via `/metrics` endpoint
- **Grafana**: Visualize metrics and logs
- **ELK Stack**: Centralized log aggregation
- **Sentry**: Error tracking and alerting

### Alerting Rules

```yaml
# Example Prometheus alerting rules
groups:
  - name: api_alerts
    rules:
      - alert: HighErrorRate
        expr: error_rate > 0.05
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High error rate detected"
      
      - alert: SlowResponseTime
        expr: response_time_p95 > 1000
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Slow response times detected"
```

## Best Practices

1. **Structured Logging**: Use consistent JSON format for easy parsing
2. **Request Correlation**: Always include request IDs for tracing
3. **Sensitive Data**: Sanitize passwords, tokens, and PII
4. **Performance**: Monitor middleware overhead (< 1ms)
5. **Storage**: Use external systems for long-term storage
6. **Alerting**: Set up meaningful thresholds and notifications
7. **Privacy**: Comply with data protection regulations
- Attach it to logs and responses.

## Error monitoring

- Integrate Sentry or similar.
- Ensure sensitive data is not leaked.
