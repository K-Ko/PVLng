<?php
/**
 *
 * @author      Knut Kohl <github@knutkohl.de>
 * @copyright   2012-2013 Knut Kohl
 * @license     GNU General Public License http://www.gnu.org/licenses/gpl.txt
 * @version     $Id$
 */
namespace Cache;

/** @defgroup Cache Caching classes

*/

/**
 * Abstract class \Cache\Base
 *
 * The following settings are supported:
 * - @c token  : Used to build unique cache ids (general)
 * - @c packer : Instance of Cache_PackerI (general)
 *
 * @ingroup     Cache
 * @author      Knut Kohl <github@knutkohl.de>
 * @copyright   2012-2013 Knut Kohl
 * @license     GNU General Public License http://www.gnu.org/licenses/gpl.txt
 * @version     $Id$
 *
 * @changelog
 * - v1.1.0
 *	 - Add test to find supported caches
 * - v1.2.0
 *	 - Move validation check against timestamps into here
 *
 */
abstract class Base implements CacheI {

	/**
	 * Mark all cached data with this prefix to check consistency
	 */
	const MARKER = 'CACHE // ';

	/**
	 * Take first ID_LENGTH characters from generated MD5 hash
	 */
	const ID_LENGTH = 8;

	// -------------------------------------------------------------------------
	// PUBLIC
	// -------------------------------------------------------------------------

	/**
	 * Some infos about the cache
	 *
	 * @return array
	 */
	public function info() {
		return array('class' => get_class($this));
	}

	/**
	 * Create/find a cache instance
	 *
	 * The following settings are supported:
	 * - @c token : Used to build unique cache ids (general)
	 * - @c packer : Instance of Cache_PackerI (general)
	 *
	 * @param array $settings
	 * @param mixed $class Force cache class to create
	 * @return Cache
	 */
	public static final function Create( $class=NULL, $settings=array() ) {
		$caches = empty($class) ? self::$Caches : (is_array($class)?$class:array($class));
		foreach ($caches as $class) {
			$class = '\Cache\\'.$class;
			if (!class_exists($class)) continue;
			$cache = new $class();
			if ($cache instanceof ICache AND $cache->isAvailable()) {
				$cache->bootstrap($settings);
				return $cache;
			}
		}
		// No possible cache class found
		return FALSE;
	} // function create()

	/**
	 * Get data from cache, if not yet exists, save to cache
	 *
	 * Nested calls of save() will be handled correctly.
	 *
	 * @par Scenarios:
	 * - Data not cached yet @b or not more valid
	 *	 - On 1st call: Return TRUE and go 1 times through the loop to build
	 *		 the data
	 *	 - On 2nd call: Store the data to the cache and return FALSE
	 * - Data cached @b and valid
	 *	 - On 1st call: Retrieve the data from cache and return FALSE
	 *
	 * @usage
	 * @code
	 * $cache = Cache::create('...');
	 * while ($cache->save($id, $data[, $ttl])) {
	 *	 ...
	 *	 $data = ...;
	 * }
	 * echo $data;
	 * @endcode
	 *
	 * @throws CacheException
	 * @param string $id Unique cache Id
	 * @param mixed &$data Data to store / retrieve
	 * @param int $ttl Time to live, if set to 0, expire never
	 * @return bool
	 */
	public final function save( $id, &$data, $ttl=0 ) {
		if ($id == end($this->stack)) {
			$this->set($id, $data, $ttl);
			// done, remove id from stack
			array_pop($this->stack);
			return FALSE;
		} elseif (in_array($id, $this->stack)) {
			// $id is in stack, but NOT on top
			throw new CacheException(__CLASS__.': Stack problem - '.end($this->stack).' not properly finished!', 99);
		} else {
			$data = $this->get($id, $ttl);
			if ($data !== NULL) {
				// Content found in cache, done
				return FALSE;
			} else {
				// not found yet, let's go
				$this->stack[] = $id;
				return TRUE;
			}
		}
	} // function save()

	/**
	 * Increments value of an item by the specified value.
	 *
	 * If item specified by key was not numeric and cannot be converted to a
	 * number, it will change its value to value.
	 *
	 * inc() does not create an item if it doesn't already exist.
	 *
	 * @param string $id Unique cache Id
	 * @param numeric $step
	 * @return numeric|bool New items value on success or FALSE on failure.
	 */
	public function inc( $id, $step=1 ) {
		return $this->modify($id, $step);
	} // function inc()

	/**
	 * Decrements value of the item by value.
	 *
	 * If item specified by key was not numeric and cannot be converted to a
	 * number, it will change its value to value.
	 *
	 * dec() does not create an item if it doesn't already exist.
	 *
	 * Similarly to inc(), current value of the item is being converted to
	 * numerical and after that value is substracted.
	 *
	 * @param string $id Unique cache Id
	 * @param numeric $step
	 * @return numeric|bool New items value on success or FALSE on failure.
	 */
	public function dec( $id, $step=1 ) {
		return $this->modify($id, -$step);
	} // function dec()

	/**
	 * Magic method to set cache data
	 *
	 * Use implicit $ttl == NULL
	 *
	 * @usage
	 * @code
	 * $cache = Cache::create('...');
	 * // Set data
	 * $cache->Key = '...';
	 * // Retrieve data
	 * $data = $cache->Key;
	 * @endcode
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	public final function __set( $name, $value ) {
		$this->set($name, $value);
	}

	/**
	 * Magic method to get cached data
	 *
	 * Use implicit $expire == NULL
	 *
	 * @usage
	 * @code
	 * $cache = Cache::create('...');
	 * // Set data
	 * $cache->Key = '...';
	 * // Retrieve data
	 * $data = $cache->Key;
	 * @endcode
	 *
	 * @param string $name
	 * @return mixed
	 */
	public final function __get( $name ) {
		return $this->get($name);
	}

	/**
	 * Magic method to check existence and validity of cached data
	 *
	 * @usage
	 * @code
	 * $cache = Cache::create('...');
	 * if (!isset($cache->Key)) {
	 *	 $cache->Key = '...';
	 * }
	 * $data = $cache->Key;
	 * @endcode
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __isset( $name ) {
		return ($this->get($name) !== NULL);
	}

	/**
	 * Magic method to unset cached data
	 *
	 * @param string $name
	 * @return mixed
	 */
	public final function __unset( $name ) {
		return $this->delete($name);
	}

	// -------------------------------------------------------------------------
	// PROTECTED
	// -------------------------------------------------------------------------

	/**
	 * Available caching methods
	 *
	 * @todo Test 'EAccelerator', 'XCache', 'MemCache'
	 * @var array $Caches
	 */
	protected static $Caches = array(
		// Tested methods
		'MySQL',
		// Only avail. with a writeable directory
		'File', 'Files',
		// Only avail. if compiled in
		'Session',
		// Always avail.
		'Mock',
		# not fully tested yet...
		'APC',
		'EAccelerator',
		'XCache',
		'MemCache',
	);

	/**
	 * Unique cache token
	 *
	 * @var string $token
	 */
	protected $token;

	/**
	 * Master timestamp
	 *
	 * @var int $ts
	 */
	protected $ts;

	/**
	 * Garbage collection probability in percent
	 *
	 * @var int $gc
	 */
	protected $gc = 5;

	/**
	 * Class constructor
	 *
	 * The following settings are supported:
	 * - @c token	: Used to build unique cache ids (general)
	 * - @c packer : Instance of Cache_PackerI (general)
	 *
	 * @throws CacheException
	 * @param array $settings
	 * @return void
	 */
	protected function __construct() {
		$this->ts = time();
		$this->stack = array();
	} // function __construct()

	/**
	 * Bootstrap will be called AFTER IsAvailable()
	 *
	 * The following settings are supported:
	 * - @c token : Used to build unique cache ids (general)
	 * - @c packer : Instance of Cache_PackerI (general)
	 *
	 * @throws CacheException
	 * @param array $settings
	 * @return void
	 */
	protected function bootstrap( $settings=array() ) {
		$this->token = !empty($settings['token']) ? $settings['token'] : md5(__FILE__);
		if (isset($settings['packer'])) {
			$this->packer = $settings['packer'];
			if (!is_object($this->packer) OR !($this->packer instanceof \Cache\IPacker))
				throw new Exception(__CLASS__.': $settings[\'packer\'] is no valid packler instance.', 3);
		}
	} // function bootstrap()

	/**
	 * Check data validity according to the timestamps
	 *
	 * @see set()
	 * @see get()
	 * @param int $ts Timestamp when data was last saved
	 * @param int $ttl Time to live of data to check against
	 *										- = 0 - expire never
	 *										- > 0 - Time to live
	 *										- < 0 - Timestamp of expiration
	 * @return bool
	 */
	protected function valid( $ts, $ttl ) {
		// expiration timestamp NOT set
		return ($ttl === 0 OR
						$ttl > 0 AND $ts+$ttl >= $this->ts OR
						$ttl < 0 AND -$ttl >= $this->ts);
	} // function valid()

	/**
	 * Build internal Id from external Id and the cache token
	 *
	 * @param string $id Unique cache Id
	 * @return string
	 */
	protected function id( $id ) {
		return substr(md5($this->token.strtolower($id)), 0, self::ID_LENGTH);
	} // function id()

	/**
	 * Serialize data, using potentially defined packer
	 *
	 * @param mixed $data
	 * @return string
	 */
	protected function serialize( $data ) {
		if (isset($this->packer))
			$this->packer->pack($data);
		else
			$data = serialize($data);
		// Mark cached data
		return self::MARKER . $data;
	} // function serialize()

	/**
	 * Unserialize data, using potentially defined packer
	 *
	 * @param string $data
	 * @return mixed
	 */
	protected function unserialize( $data ) {
		// Cached data correctly marked?
		if (strpos($data, self::MARKER) !== 0) return;
		$data = substr($data, strlen(self::MARKER));
		if (isset($this->packer))
			$this->packer->unpack($data);
		else
			$data = unserialize($data);
		return $data;
	} // function unserialize()

	/**
	 * Instance of Cache_PackerI to pack data before storing into cache
	 *
	 * Set it during {@link create() creation} of class by setting parameter
	 * 'packer'.
	 *
	 * @var Cache_PackerI $packer
	 */
	protected $packer;

	// -------------------------------------------------------------------------
	// PRIVATE
	// -------------------------------------------------------------------------

	/**
	 * Stack of save() calls
	 *
	 * @var array $stack
	 */
	private $stack;

	/**
	 * Increments / decrements value of the item by value.
	 *
	 * @param string $id Unique cache Id
	 * @param int $step
	 * @return num New items value on success or FALSE on failure.
	 */
	private function modify( $id, $step ) {
		$id = $this->id($id);
		$data = $this->get($id);
		if ($data !== NULL) {
			$data += $step;
			if ($this->set($id, $data) === TRUE) return $data;
		} else {
			return FALSE;
		}
	}

}

/**
 * Class CacheException
 *
 * @ingroup Cache
 */
class Exception extends \Exception {}
