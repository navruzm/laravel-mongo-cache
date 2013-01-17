[MongoDB](http://www.mongodb.org/) Cache driver for [Laravel 4](http://laravel.com/).

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