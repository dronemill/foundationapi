# FoundationApi Installation

1. Add the following to your composer.json's require section:
  - `"dronemill/foundationapi": "dev-master"`
  - `"dronemill/phphelpers": "dev-master"`

2. And the following to the require-dev section:
  - `"mockery/mockery": "0.9.1"`
  - `"league/factory-muffin": "3.0.*@dev"`
  - `"league/factory-muffin-faker": "~1.0-dev"`

3. Add the following repositories:

  ```json
  "repositories": [
    {
      "type": "vcs",
      "url":  "git@github.com:dronemill/foundationapi.git"
    },
    {
    "type": "vcs",
      "url":  "git@github.com:dronemill/phphelpers.git"
    }
  ],
  ```
4. Run composer update
5. Add the following class aliases to config/app.php:

  ```php
  [
  	'AuthPermission'   => 'DroneMill\FoundationApi\Auth\Permission',
  	'BaseController'   => 'App\Http\Controllers\Controller',
  	'FactoryMuffin'    => 'League\FactoryMuffin\FactoryMuffin',
  	'FMaker'           => 'League\FactoryMuffin\Faker\Facade',
  	'Model'            => 'DroneMill\FoundationApi\Database\Model',
  	'Seeder'           => 'DroneMill\FoundationApi\Database\Seeder',
    'ApiHandler'       => 'DroneMill\FoundationApi\Handlers\Api',
  ]
  ```
6. Now add the following providers in config/app.php:

  ```php
  [
    'DroneMill\FoundationApi\Providers\AuthServiceProvider',
  ]
  ```
7. In app/Http/Kernel.php, replace `App\Http\Middleware\VerifyCsrfToken` with `DroneMill\FoundationApi\Http\Middleware\ApiHeader` inside of $middleware
8. Also in app/Http/Kernel.php, inside of $routeMiddleware, replace the value of `auth` with `DroneMill\FoundationApi\Http\Middleware\Authenticate`
9. Open config/auth.php
  1. replace the driver with `FoundationApi`
  2. replace the model with `\App\Models\User`
  3. add token_model with `\App\Models\UserToken`
10. Modify app/Http/Controllers/Controller.php, and replace `use Illuminate\Routing\Controller as BaseController` with `DroneMill\FoundationApi\Http\Controllers\BaseController`

Todo:
- DatabaseServiceProvider
  - Documentation for it
  - set connection and table properties in the DbResolverConnection and DbResolverConnectionHost models
  - add db seed DbConnectionSeeder
  - db migrations
  - config in config/database.php
