[MongoDB](http://www.mongodb.org/) Cache driver for [Laravel 4](http://laravel.com/).

[![Build Status](https://travis-ci.org/navruzm/laravel-mongo-cache.png?branch=master)](https://travis-ci.org/navruzm/laravel-mongo-cache)
Installation
============

Add `navruzm/laravel-mongo-cache` as a requirement to composer.json:

```json
{
    "require": {
        "navruzm/laravel-mongo-cache": "*"
    }
}
```
And then run `composer update`

Once Composer has updated your packages open up `app/config/app.php` and change `Illuminate\Cache\CacheServiceProvider` to `MongoCache\CacheServiceProvider`

Then open `app/config/cache.php` and find the `driver` key and change to `mongo`.