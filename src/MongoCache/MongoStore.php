<?php namespace MongoCache;

use LMongo\Database;
use Illuminate\Cache\Store;
use Illuminate\Encryption\Encrypter;

class MongoStore extends Store {

	/**
	 * The database connection instance.
	 *
	 * @var LMongo\Database
	 */
	protected $connection;

	/**
	 * The encrypter instance.
	 *
	 * @param  Illuminate\Encrypter
	 */
	protected $encrypter;

	/**
	 * The name of the cache collection.
	 *
	 * @var string
	 */
	protected $collection;

	/**
	 * A string that should be prepended to keys.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Create a new database store.
	 *
	 * @param  LMongo\Database  $connection
	 * @param  Illuminate\Encrypter  $encrypter
	 * @param  string  $collection
	 * @param  string  $prefix
	 * @return void
	 */
	public function __construct(Database $connection, Encrypter $encrypter, $collection, $prefix = '')
	{
		$this->collection = $collection;
		$this->prefix = $prefix;
		$this->encrypter = $encrypter;
		$this->connection = $connection;
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	protected function retrieveItem($key)
	{
		$prefixed = $this->prefix.$key;

		$cache = $this->collection()->findOne(array('key' => $prefixed));

		// If we have a cache record we will check the expiration time against current
		// time on the system and see if the record has expired. If it has, we will
		// remove the records from the database collection so it isn't returned again.
		if ( ! is_null($cache))
		{
			if (time() >= $cache['expiration']->sec)
			{
				return $this->removeItem($key);
			}

			return $this->encrypter->decrypt($cache['value']);
		}
	}

	/**
	 * Store an item in the cache for a given number of minutes.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  int     $minutes
	 * @return void
	 */
	protected function storeItem($key, $value, $minutes)
	{
		$key = $this->prefix.$key;

		// All of the cached values in the database are encrypted in case this is used
		// as a session data store by the consumer. We'll also calculate the expire
		// time and place that on the collection so we will check it on our retrieval.
		$value = $this->encrypter->encrypt($value);

		$expiration = new \MongoDate($this->getTime() + ($minutes * 60));

		$item = $this->collection()->findOne(array('key' => $key));
		
		if(is_null($item))
		{
			$this->collection()->insert(compact('key', 'value', 'expiration'));
		}
		else
		{
			$update_data = array('value' => $value, 'expiration' => $expiration);
		
			$this->collection()->update(array('key' => $key), array('$set' => $update_data));
		}
	}

	/**
	 * Get the current system time.
	 *
	 * @return int
	 */
	protected function getTime()
	{
		return time();
	}

	/**
	 * Store an item in the cache indefinitely.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	protected function storeItemForever($key, $value)
	{
		return $this->storeItem($key, $value, 5256000);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	protected function removeItem($key)
	{
		$this->collection()->remove(array('key' => $this->prefix.$key));
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	protected function flushItems()
	{
		$this->collection()->drop();
	}

	/**
	 * Get a MongoCollection.
	 *
	 * @return MongoCollection
	 */
	protected function collection()
	{
		return $this->connection->{$this->collection};
	}

	/**
	 * Get the underlying database connection.
	 *
	 * @return LMongo\Database
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * Get the encrypter instance.
	 *
	 * @return Illuminate\Encrypter
	 */
	public function getEncrypter()
	{
		return $this->encrypter;
	}

}