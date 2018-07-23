# Laravel Handlers

> Goodbye controllers, hello request handlers!

This package adds a handy way of creating the request handlers.

Basically, the request handler is a single action controller which leads to a more clear request to the response flow.

You should try to use request handlers instead of with controllers and you probably won't feel the need to go back to controllers anytime soon.

## Some advantages in comparison with controllers

- single responsibility (seems like controllers with many actions break this principle);
- testability (you won't need to resolve dependencies that are not related to the action you are testing, since there is only single action in each request handler);
- registering of routes (`Route::get('/posts/{post}', ShowPost::class)` is much more convenient and pretty than `Route::get('/posts/{post}', 'PostController@show')`).

## Installation

You can install this package via composer using this command:

```bash
composer require hivokas/laravel-handlers
```

The package will automatically register itself.

## Creation of request handlers

![Showcase](showcase.svg)

- Create a handler

```bash
php artisan make:handler ShowPost
```

> `ShowPost` handler will be created

- Create handlers for all resource actions (`index`, `show`, `create`, `store`, `edit`, `update`, `destroy`)

```bash
php artisan make:handler Post --resource
```

> `IndexPost`, `ShowPost`, `CreatePost`, `StorePost`, `EditPost`, `UpdatePost`, `DestroyPost` handlers will be created

- Exclude unnecessary for an API actions (`create`, `edit`)

```bash
php artisan make:handler Post --resource --api
```

> `IndexPost`, `ShowPost`, `StorePost`, `UpdatePost`, `DestroyPost` handlers will be created

- Create handlers by the specified actions

```bash
php artisan make:handler Post --actions=show,destroy,approve
```

> `ShowPost`, `DestroyPost`, `ApprovePost` handlers will be created

- Exclude specified actions

```bash
php artisan make:handler Post --resource --except=index,show,edit
```

> `CreatePost`, `StorePost`, `UpdatePost`, `DestroyPost` handlers will be created

- Specify namespace for handlers creating (relative path)

```bash
php artisan make:handler --resource --namespace=Post
```

> `IndexPost`, `ShowPost`, `CreatePost`, `StorePost`, `EditPost`, `UpdatePost`, `DestroyPost` handlers will be created under `App\Http\Handlers\Post` namespace in `app/Http/Handlers/Post` directory

- Specify namespace for handlers creating (absolute path)

```bash
php artisan make:handler ActivateUser --namespace=\\App\\Foo\\Bar
```

> `ActivateUser` handler will be created under `App\Foo\Bar` namespace in `app/Foo/Bar` directory

- Force create

```bash
php artisan make:handler EditPost --force
```

> If `EditPost` handler already exists, it will be overwritten by the new one

## Writing logic in request handler

Request handlers are invokable classes that use PHP's `__invoke` magic function, turning them into a Callable, which allows them to be called as a function. So you need to return response in `__invoke` method.

Eventually your handlers will look something like this:

```php
<?php

namespace App\Http\Handlers\Foo;

use App\Models\Foo;
use App\Services\FooService;
use Hivokas\LaravelHandlers\Handler;
use App\Http\Requests\Foo\UpdateFooRequest;

class UpdateFoo extends Handler
{
    protected $service;
    
    public function __construct(FooService $service)
    {
        $this->service = $service;
    }
    
    public function __invoke(UpdateFooRequest $request, Foo $foo)
    {
        $this->bar($foo, $request->validated());
        
        return redirect('foo.show', $foo);
    }

    protected function bar(Foo $foo, array $validated)
    {
        return $this->service->baz($foo, $validated);
    }
}
```

## Registering of routes 

Here are several ways to register routes where request handlers are used as an actions.

#### In separate `handlers.php` route file

- Create `routes/handlers.php` file (you can choose any name, it's just an example)
- Define the "handlers" route group in `app/Providers/RouteServiceProvider.php`

> ##### With namespace auto prefixing

```php
// app/Providers/RouteServiceProvider.php

protected function mapHandlersRoutes()
{
    Route::middleware('web')
         ->namespace('App\Http\Handlers')
         ->group(base_path('routes/handlers.php'));
}
```

```php
// app/Providers/RouteServiceProvider.php

public function map()
{
    $this->mapApiRoutes();

    $this->mapWebRoutes();
    
    $this->mapHandlersRoutes();

    //
}
```

```php
// routes/handlers.php

Route::get('/post/{post}', 'ShowPost');
```

> ##### Without namespace auto prefixing

```php
// app/Providers/RouteServiceProvider.php

protected function mapHandlersRoutes()
{
    Route::middleware('web')
         ->group(base_path('routes/handlers.php'));
}
```

```php
// app/Providers/RouteServiceProvider.php

public function map()
{
    $this->mapApiRoutes();

    $this->mapWebRoutes();
    
    $this->mapHandlersRoutes();

    //
}
```

```php
// routes/handlers.php

use App\Handlers\ShowPost;

Route::get('/post/{post}', ShowPost::class); // pretty sweet, isn't it? 😍
```

#### In `web.php` route file

- Change the namespace for "web" group in `RouteServiceProvider.php`

```php
// app/Providers/RouteServiceProvider.php

protected function mapWebRoutes()
{
    Route::middleware('web')
         ->namespace('App\Http') // pay attention here
         ->group(base_path('routes/web.php'));
}
```

- Put request handlers and controllers in different route groups in `routes/web.php` file and prepend an appropriate namespace for each of them

```php
// routes/web.php

Route::group(['namespace' => 'Handlers'], function () {
    Route::get('/posts/{post}', 'ShowPost');
    Route::delete('/posts/{post}', 'DestroyPost');
});

Route::group(['namespace' => 'Controllers'], function () {
    Route::get('/users', 'UserController@index');
    Route::get('/users/{user}', 'UserController@show');
});
```

## Testing

You can run the tests with:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.