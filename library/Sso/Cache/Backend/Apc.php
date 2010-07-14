<?php

/**
 * Sso_Cache_Backend_Apc
 * 
 * Provides an interface to APC.
 * 
 * @package sso
 *
 */
class Sso_Cache_Backend_Apc implements Sso_Cache_Backend_Interface
{
	/**
	 * (non-PHPdoc)
	 * @see source/public/Sso/Cache/Backend/Sso_Cache_Backend_Interface#get($name)
	 */
	public function get($name) {
		return apc_fetch($name);
	}

	/**
	 * (non-PHPdoc)
	 * @see source/public/Sso/Cache/Backend/Sso_Cache_Backend_Interface#has($name)
	 */
	public function has($name) {
		apc_fetch($name, $success);
		return $success;
	}

	/**
	 * (non-PHPdoc)
	 * @see source/public/Sso/Cache/Backend/Sso_Cache_Backend_Interface#del($name)
	 */
	public function del($name) {
		return apc_delete($name);
	}

	/**
	 * (non-PHPdoc)
	 * @see source/public/Sso/Cache/Backend/Sso_Cache_Backend_Interface#set($name, $value, $ttl)
	 */
	public function set($name, $value, $ttl = 0) {
		return apc_store($name, $value, $ttl);
	}

    /**
	 * (non-PHPdoc)
	 * @see source/public/Sso/Cache/Backend/Sso_Cache_Backend_Interface#add($name, $value, $ttl)
	 */
	public function add($name, $value, $ttl = 0) {
		return apc_add($name, $value, $ttl);
	}

    /**
	 * (non-PHPdoc)
	 * @see source/public/Sso/Cache/Backend/Sso_Cache_Backend_Interface#flush()
	 */
	public function flush() {
        return apc_clear_cache('user');
    }
}
