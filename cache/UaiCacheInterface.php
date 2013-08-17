<?php

/**
 * Implement this interface to use a cache class with UserAgentInfoPeer.
 * 
 * @author Mikołaj Misiurewicz <quentin389+uai@gmail.com>
 * 
 * @link https://github.com/quentin389/UserAgentInfo
 *
 */
interface UaiCacheInterface
{
  /**
   * @param string $key_name
   * 
   * @return result from the cache
   */
  public static function get($key_name);
  
  /**
   * @param string $key_name
   * @param mixed $data data to save in the cache
   */
  public static function set($key_name, $data);
}

