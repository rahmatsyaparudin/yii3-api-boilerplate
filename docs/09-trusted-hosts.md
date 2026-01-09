# Trusted Hosts

## Goal

Block requests by the `Host` header.

## Configuration

- `config/common/params.php` key `app/trusted_hosts.allowedHosts`

## Middleware

- `App\Shared\Middleware\TrustedHostMiddleware`

If host is missing or not in whitelist, middleware throws an `HttpException` which is converted into an API `fail` response by `ExceptionResponderFactory`.
