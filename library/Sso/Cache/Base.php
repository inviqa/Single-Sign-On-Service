<?php
/**
 * Sso Cache
 *
 * The Sso Cache provides a caching abstraction layer.
 * 
 * @package sso
 */
class Sso_Cache_Base
{
    /**
     * @var string
     */
	private $_ns;

	/**
	 * Sso_Cache_Backend_Interface
	 *
	 * @var Sso_Cache_Backend_Interface
	 */
	private $_backend;

	/**
	 * Create a new instance and encapsulate the passed Sso_Cache_Backend.
	 * 
	 * @param Sso_Cache_Backend_Interface $backend
	 */
	public function __construct(Sso_Cache_Backend_Interface $backend) {
		$this->_ns = md5(__FILE__);	
		$this->_backend = $backend;
	}

	/**
	 * Retrieve the key with $name from the cache.
	 * 
	 * @param string $name
     *
	 * @return mixed
	 */
	public function get($name) {
		$name = "{$this->_ns}.{$name}";
		return $this->_backend->get($name);
	}

	/**
	 * Set the key with the given $name to $value. Can also give a $timeout seconds.
	 * 
	 * @param string $name
	 * @param mixed  $value
	 * @param int    $ttl
     *
     * @return boolean
	 */
	public function set($name, $value, $ttl = 0) {
		$name = "{$this->_ns}.{$name}";
		return $this->_backend->set($name, $value, $ttl);
	}

	/**
	 * Add the key with the given $name to the cache with the given $value only if it does not 
	 * currently exist. Can also give a $timeout in seconds. 
	 * 
	 * @param string $name
	 * @param mixed  $value
	 * @param int    $ttl
     *
     * @return boolean
	 */
	public function add($name, $value, $ttl = 0) {
		$name = "{$this->_ns}.{$name}";
		return $this->_backend->add($name, $value, $ttl);
	}

	/**
	 * Delete the key with the given $name.
	 * 
	 * @param string $name
	 */
	public function del($name) {
		$name = "{$this->_ns}.{$name}";
		return $this->_backend->del($name);
	}

	/**
	 * Return boolean true of false if the key with the given $name exists or not.
	 * @param string $name
	 * @return boolean
	 */
	public function has($name) {
		$name = "{$this->_ns}.{$name}";
		return $this->_backend->has($name);
	}

    /**
     * Flush all the cache
     *
     * @return boolean
     */
    public function flush() {
        return $this->_backend->flush();
    }
	
	/**
	 * Retrieve the currently configured backend.
	 * 
	 * @return Sso_Cache_Backend_Interface
	 */
	public function getBackend() {
		return $this->_backend;
	}
}