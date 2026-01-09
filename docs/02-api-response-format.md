# API Response Format

All responses are returned as JSON.

## Success

Produced by `App\Api\Shared\ResponseFactory::success()`.

## Fail

Produced by `App\Api\Shared\ResponseFactory::fail()`.

The fail payload is presented by `App\Api\Shared\Presenter\FailPresenter`.

## Validation fail

Produced by `App\Api\Shared\ResponseFactory::failValidation()` when `Yiisoft\Input\Http\InputValidationException` is thrown.
