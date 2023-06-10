# Phlo

> This documentation is a work in progress. Please check back later for more information. This project is still in its early stages and is probably not ready for heavy production use.

Pronounced as 'flow', Phlo is a framework for minimalists built to require almost no configuration.

## Features

- [x] File-based Routing
- [x] File-scoped and folder-scoped middleware
- [x] Nested wildcard routes
- [x] Zero third-party dependencies
- [x] Built-in support for environment variables (from `.env` files and the system)
- [x] Built-in support for default NOT FOUND pages
- [ ] Built-in support for custom error handlers (coming soon)
- [x] Supports JavaScript frameworks like Astro, Vue, Svelte, etc.

## Getting Started

> There is no installation required for now. A starter script will be added soon.

Just clone this repository (or [create your project](https://docs.github.com/en/repositories/creating-and-managing-repositories/creating-a-repository-from-a-template) with this template on GitHub) and start building your app.

Phlo's file and the core lives in the `/phlo` folder so that you can use the `src` folder for your app without any conflicts. You can add your own namespace to the `composer.json` file like this:

```json
{
	"autoload": {
		"psr-4": {
			"App\\": "app/lib/"
		}
	}
}
```

# Usage

The `index.php` file in the root directory is the entry point for your app. It is the only file that is required to be in the root directory. However, you can add your own files and folders in the root directory as well if you want to. All other files and folders are optional and can be moved around as you want, as long as you update the route rules in the `index.php` file accordingly.

# Routing

## API Routes

By default, API routes live in the `/api` folder. You can change this by editing this line in the `index.php` file:

```php
...
->addRule(Rule::new("api")->setRuleType(RuleType::API)->setTarget("app/api"))
...
```

Phlo uses what I call `rules` for routing, rules are created with the preferred prefix (in this case `api`, it can be anything else) and target folder.
The `RuleType` enum is used to specify the type of rule. The `RuleType::API` type is used for API routes and the `RuleType::STATIC` type is used for web that need to be served as they are and `RuleType::REDIRECT` simply redirects the user to the specified URL. If you are using an SPA framework like Vue, Svelte, Astro, etc. you can use the `RuleType::STICKY` type and point it to the `index.html` to serve that file for all routes.

#### Example

To create a handler that handles a GET request to `/api/user/1`, you need to create a file named `[id].php` in the `/api/user` folder. The file name can be anything you want, but it must be unique in the folder, the `[id]` part is a wildcard that can be anything. You can access the wildcard value using the `$ctx->getParam()` method.
Then you can create a handler in the file like this:

```php
<?php

use Phlo\Core\Context;

function get(Context $ctx) {
// in examples, reference to the context is taken instead to show that it is possible but this would also work as long as you are not modifying it here to pass it on later
  $id = $ctx->getParam("id");
  $ctx->send([
    "id" => $id,
    "name" => "John Doe",
  ]);
};
```

To add a handler for a POST request to the same route, you can add a function named `post` in the same file. And to create a handler that handles all, you can add a function named `any` in the same file (this will always be a fallback).

> Phlo supports standard HTTP methods like `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, and `ANY` (for all methods) - PHP only supports `GET` and `POST` by default and will not provide the request body for other methods.

#### Adding a middleware

To add a middleware to every file in the `/api/user` folder, you can create a file named `_middleware.php` in the folder and add a function named `_global_init` in it. This function will be called before any other function in the file.

In the `_global_init` function, you can access the context and modify it as you want. To stop execution in a middleware, you can use the `$ctx->send(...)` method to send a response and stop execution or simply call `exit()` or `die()`. You can also use the `$ctx->redirect(...)` method to redirect the user to another URL.

Your folder structure should look like this:

```
  |-- api
        |-- user
            |-- [id].php
            |-- _middleware.php
```

## Static Routes

Files in static routes are served as they are, which mean you can build your app using any framework you want and simply serve the files using Phlo. By default, static routes live in the `/pages` folder (and `public` folder for assets). You can change this by adding your own rules or editing these lines in the `index.php` file:

```php
...
	->addRule(Rule::new("public")->setRuleType(RuleType::STATIC)->setTarget("public"))
	->addRule(Rule::new("")->setRuleType(RuleType::STATIC)->setTarget("app/pages"))
...
```

You could even update the `RuleType::STATIC` to `RuleType::STICKY` to serve the `index.html` file for all routes and point that to your SPA framework's build folder.

> Note: this documentation is a work in progress. Please check back later for more information. This project is still in its early stages and is probably not ready for heavy production use.
