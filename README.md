[![](https://img.shields.io/codecov/c/github/reload/material-list.svg?style=for-the-badge)](https://codecov.io/gh/reload/material-list)

## Installation ##


1. Run `composer install` to install dependencies.
2. Copy `.env.example` to `.env` and adjust the configuration.
3. Serve using `php -S 0.0.0.0:8000 -t public/` (for testing), FPM, or
   Apache.

### Configuration ###

The configuration may be passed via environment variables, but the
`.env` file allows for easy configuration of all variables. See
`.env.example` for configuration options.


## Development ##

### Overview ###

The application code is in the `App` namespace and located in the
`app` directory.

Application bootstrapping is in `bootstrap/app.php`, it sets up the
container, middleware and service providers, and points at the route
file.

Routes are defined in `routes/web.php`. They all point to a method in
a Controller class. See the [Lumen documentation on
routing](https://lumen.laravel.com/docs/5.3/routing) for more
information.

### Controllers ###

The controller classes is defined in `App\Http\Controllers`. The
controller methods handling requests gets the URL path placeholders as
arguments, and typehinted arguments are auto-wired from the container.
They can return array data (which is automatically transformed into a
JSON response), a `Illuminate\Http\Response` (which subclasses
`Symfony\Component\HttpFoundation\Response`), or throw an exception
(which is converted to an appropriate response by the error handler).

See the [Lumen documentation on
controllers](https://lumen.laravel.com/docs/5.3/controllers) for more
information.

### Middleware ###

The application defines three middleware classes in
`App\Http\Middleware`, two "token checkers" and `TokenAccess`.

`TokenChecker` and `TestTokenChecker` both extract the token from the
`Authorization` header, and sets the user GUID to be returned by the
`Request::user()` method of the current request. The difference is
that `TestTokenChecker` just uses the token in the `Authorization`
header as is (for easier testing), while the `TokenChecker` validates
it using OAuth.

The `TokenAccess` middleware simply checks that the GUID has been set
to a non-empty value, or aborts the request with a 401 response.

See the [Lumen documentation on
middleware](https://lumen.laravel.com/docs/5.3/middleware) for more
information.

### Error handling ###

The `App\Exceptions\Handler` handles exceptions thrown by the
controllers. It converts
`Symfony\Component\HttpKernel\Exception\HttpException` and its
subclasses into the corresponding responses (`NotFoundHttpException`
into a 404, for instance). For
`Illuminate\Http\Exceptions\HttpResponseException` (which is an
exception that encapsulates a `Response`) it simply uses the
exceptions response. Everything else causes a "500 Internal error"
response, unless the `APP_DEBUG` environment variable is true, in
which case it serves the exception message as `text/plain` to ease
debugging.

### Database ###

The database schema is defined in `databese/migrations`. 

See the [Laravel documentation on
migrations](https://laravel.com/docs/5.8/migrations) for more
information.

Queries are done with the Laravel query builder. The application does
not use an ORM.

See the [Lumen documentation on
databases](https://lumen.laravel.com/docs/5.3/database) for more
information.

### Testing ###

### Behavior ###

Most tests are done as behavior test using Behat. The features are in
`tests/features` while the context classes reside in `tests/contexts`.

The context doesn't interact with the application over HTTP, rather
the application is booted inside the test for each scenario. This is
the same way that unit tests of controllers is done, in fact the
context is using the same
`Laravel\Lumen\Testing\Concerns\MakesHttpRequests` trait that
`Laravel\Lumen\Testing\TestCase` uses to construct the right request
objects.

This also makes code coverage collection simpler. Behat writes
coverage to `coverage`, which can be rendered to HTML with
`./vendor/bin/phpcov merge --html=./coverage/html ./coverage`.

### API specification test ###

API specification tests are done by generating requests as documented
by the specification and testing if the application reacts as
documented. [Dredd](https://dredd.org/en/latest/) is used for this.

To install Dredd, run: `npm install --global dredd`.

Running Dredd is as simple as `dredd`. Dredd is configured to run
`scripts/dredd-server.sh` to start the server, which simply runs the
application using the PHP built-in webserver.

In order to ensure the right conditions for each test, Dredd uses a
hooks file (`tests/dredd/hooks.php`), which allows for setting
fixtures or modifying the requests/response.

To get the names of requests (for use in hook file), use `dredd
--names`. Getting dredd to display any output from the hook file (for
debugging), you need to run it in verbose mode: `dredd
--loglevel=debug`.
