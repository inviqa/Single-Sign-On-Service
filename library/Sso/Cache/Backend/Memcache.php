<?php
/**
 * Sso_Cache_Backend_Memcache
 * 
 * Provides an interface to memcached using the PHP memcache extension.
 * 
 * @package sso
 *
 */
class Sso_Cache_Backend_Memcache implements Sso_Cache_Backend_Interface
{
	/**
	 * 
	 * @var Memcache
	 */
	private $_memcache;

    /**
     * @var array
     */
	private $_server_defaults = array(
		'port'             => 11211,
		'persistent'       => true,
		'weight'           => 1,
		'timeout'          => 1,
		'retry_interval'   => 15,
		'status'           => true,
		'failure_callback' => null
	);
	
	/**
	 * Create a new Sso_Cache_Backend_Memcache instance, providing an array of options.
	 * 
	 */
	public function __construct() {
		$this->_memcache = new Memcache();
	}
	
	/**
	 * Add a server to the server pool. Can optionally provide an array of options for the server.
	 * 
	 * @see http://php.net/manual/en/function.memcache-addserver.php
	 * 
	 * @param $host
	 * @param $options
     *
     * @return boolean
	 */
	public function addServer($host, $options = array()) {
		$options = array_merge($this->_server_defaults, $options);
		return $this->_memcache->addServer(
            $host,
            $options['port'],
            $options['persistent'],
            $options['weight'],
            $options['timeout'],
            $options['retry_interval'],
            $options['status'],
            $options['failure_callback']
        );
	}
	
	/**
	 * Override a default option for new servers. Give the $name and $value of the option. 
	 * 
	 * The default options are:
	 * 
	 *  'port' => 11211
	 *	'persistent' => true
	 *	'weight' => 1
	 *	'timeout' => 1
	 *	'retry_interval' => 15
	 *	'status' => true
	 *	'failure_callback' => null
	 * 
	 * See the php docs for more information on these options.
	 * 
	 * @see http://php.net/manual/en/function.memcache-addserver.php
	 * 
	 * @param string $name
	 * @param string $value
	 */
	public function setServerDefault($name, $value) {
		$this->_server_defaults[$name] = $value;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see source/public/Sso/Cache/Backend/Sso_Cache_Backend_Interface#get($name)
	 */
	public function get($name) {
		return $this->_memcache->get($name);
	}

	/**
	 * (non-PHPdoc)
	 * @see source/public/Sso/Cache/Backend/Sso_Cache_Backend_Interface#has($name)
	 */
	public function has($name) {
		return $this->_memcache->get($name) ? true : false;
	}

	/**
	 * (non-PHPdoc)
	 * @see source/public/Sso/Cache/Backend/Sso_Cache_Backend_Interface#del($name)
	 */
	public function del($name) {
		return $this->_memcache->delete($name);
	}

	/**
	 * (non-PHPdoc)
	 * @see source/public/Sso/Cache/Backend/Sso_Cache_Backend_Interface#set($name, $value, $ttl)
	 */
	public function set($name, $value, $ttl = 0) {
		return $this->_memcache->set($name, $value, false, $ttl);
	}

	/**
	 * (non-PHPdoc)
	 * @see source/public/Sso/Cache/Backend/Sso_Cache_Backend_Interface#add($name, $value, $ttl)
	 */
	public function add($name, $value, $ttl = 0) {
		return $this->_memcache->add($name, $value, false, $ttl);
	}

    /**
	 * (non-PHPdoc)
	 * @see source/public/Sso/Cache/Backend/Sso_Cache_Backend_Interface#flush()
	 */
	public function flush() {
        return $this->_memcache->flush();
    }

}