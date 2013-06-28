<?php namespace MongoCache;

use LMongo\Connection;
use Illuminate\Cache\StoreInterface;
use Illuminate\Encryption\Encrypter;

class MongoStore implements StoreInterface {

	/**
	 * The database connection instance.
	 *
	 * @var LMongo\Connection
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
	 * @param  LMongo\Connection  $connection
	 * @param  Illuminate\Encrypter  $encrypter
	 * @param  string  $collection
	 * @param  string  $prefix
	 * @return void
	 */
	public function __construct(Connection $connection, Encrypter $encrypter, $collection, $prefix = '')
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
	public function get($key)
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
				return $this->forget($key);
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
	public function put($key, $value, $minutes)
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
	 * Increment the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function increment($key, $value = 1)
	{
		throw new \LogicException("Increment operations not supported by this driver.");
	}

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function decrement($key, $value = 1)
	{
		throw new \LogicException("Increment operations not supported by this driver.");
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
	public function forever($key, $value)
	{
		return $this->put($key, $value, 5256000);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		$this->collection()->remove(array('key' => $this->prefix.$key));
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	public function flush()
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
	 * @return LMongo\Connection
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

	/**
	 * Get the cache key prefix.
	 *
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

}