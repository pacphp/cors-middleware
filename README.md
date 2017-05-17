PAC CORS Middleware
===================

## Description

This is middleware for [Cross-Origin Resource Sharing](http://www.w3.org/TR/cors/) (CORS) compliant with the proposed [PSR-15](https://github.com/php-fig/fig-standards/blob/master/proposed/http-middleware/middleware.md) middleware interface. 
It is also compliant with [PSR-7](http://www.php-fig.org/psr/psr-7/) HTTP message interfaces.

## Usage in PAC

Add the `CorsMiddlewareExtension` class to your app kernel.

```php
class AppKernel extends PacKernel
{
    protected function appendedExtensions(): array
    {
        return [
            new CorsMiddlewareExtension(),
        ];
    }
}

```

In one of your `config.yml` files add

```yaml
cors_middleware:
    default:
        allow_credentials: false
        allow_origin: []
        allow_headers: []
        allow_methods: []
        expose_headers: []
        max_age: 0
```

## Todo
* configurable settings on a route basis
