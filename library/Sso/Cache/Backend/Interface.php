<?php
/**
 * Sso_Cache_Backend_Interface
 * 
 * Interface used by all Sso_Cache_Backends
 * 
 * @package sso
 *  
 */
interface Sso_Cache_Backend_Interface
{
	/**
     *
     * @param string  $name
     *
     * @return mixed
     */
    public function get($name);
	
	/**
     *
     * @param string  $name
     * @param mixed   $value
     * @param integer $ttl
     *
     * @return boolean
     */
    public function set($name, $value, $ttl = 0);
	
	/**
     *
     * @param string  $name
     * @param mixed   $value
     * @param integer $ttl
     *
     * @return boolean
     */
    public function add($name, $value, $ttl = 0);

    /**
     *
     * @param string  $name
     *
     * @return boolean
     */
    public function del($name);
	
	/**
     *
     * @param string  $name
     *
     * @return boolean
     */
    public function has($name);

    /**
     * Flush all the cache entries
     *
     * @return boolean
     */
    public function flush();
}