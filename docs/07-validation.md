# Validation

## Validator base

`App\Shared\Validation\AbstractValidator` wraps `yiisoft/validator`.

## BrandValidator

`App\Domain\Brand\BrandValidator` defines rules for:

- `ValidationContext::SEARCH`
- `ValidationContext::CREATE`
- `ValidationContext::UPDATE`

## Error formatting

`App\Shared\Helper\ValidationHelper` formats validator results for API responses.
