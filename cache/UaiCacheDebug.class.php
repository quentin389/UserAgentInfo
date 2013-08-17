<?php

/**
 * Use this class to test UserAgentInfo class without any caching system set up. The values won't be saved between requests.
 * 
 * You could use it in production environment, but it's not a good idea,
 * because retrieving all the information about given user agent from the source parsers is quite slow.
 * 
 * @author Mikołaj Misiurewicz <quentin389+uai@gmail.com>
 * 
 * @link https://github.com/quentin389/UserAgentInfo
 *
 */
class UaiCacheDebug implements UaiCacheInterface
{
  protected static $cache = array();
  
  public static function get($key_name)
  {
    if (isset(self::$cache[$key_name]))
    {
      return self::$cache[$key_name];
    }
    
    return false;
  }
  
  public static function set($key_name, $data)
  {
    self::$cache[$key_name] = $data;
  }
}

