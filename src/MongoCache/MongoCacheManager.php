<?php namespace MongoCache;

use Illuminate\Cache\CacheManager;

class MongoCacheManager extends CacheManager {

	/**
	 * Create an instance of the database cache driver.
	 *
	 * @return MongoCache\MongoStore
	 */
	protected function createMongoDriver()
	{
		$connection = $this->getMongoConnection();

		$encrypter = $this->app['encrypter'];

		// We allow the developer to specify which connection and table should be used
		// to store the cached items. We also need to grab a prefix in case a table
		// is being used by multiple applications although this is very unlikely.
		$table = $this->app['config']['cache.table'];

		$prefix = $this->app['config']['cache.prefix'];

		return $this->repository(new MongoStore($connection, $encrypter, $table, $prefix));
	}

	/**
	 * Get the database connection for the mongo driver.
	 *
	 * @return LMongo\Connection
	 */
	protected function getMongoConnection()
	{
		$connection = $this->app['config']['cache.connection'];

		return $this->app['lmongo']->connection($connection);
	}

}