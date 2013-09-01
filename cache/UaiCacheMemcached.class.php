<?php

/**
 * A simple wrapper around Memcached (http://memcached.org/)
 * 
 * @author Ignas Bernotas
 * 
 * @link https://github.com/ignasbernotas/UserAgentInfo
 * 
 */
class UaiCacheMemcached implements UaiCacheInterface
{
  /**
   * @var $instance
   */
  public static $instance = null;
  
  /**
   * If you want to use your own instance of Memcached then pass it to UaiCacheMemcached::$instance before calling UserAgentInfoPeer for the first time.
   */
  public static function autoInit()
  {
    if (self::$instance !== null)
    {
      return;
    }
    // since some servers may have either Memcached or Memcache extension installed, we need to check it
    if(class_exists('Memcache', false)) {
        $extension = 'Memcache';
    } elseif(class_exists('Memcached', false)) {
        $extension = 'Memcached';
    } else {
        throw new Exception('Neither Memcache nor Memcached extensions aren\'t installed.');
    }
    
    self::$instance = new $extension();
    self::$instance->addServer('127.0.0.1', 11211);

  }
  
  public static function get($key_name)
  {
    self::autoInit();
    
    return self::$instance->get(UserAgentInfoConfig::CACHE_KEY_PREFIX . $key_name);
  }
  
  public static function set($key_name, $data)
  {
    self::autoInit();
    
    // no timeout - UserAgentInfo objects should live forever, cache keys are reused if the data change
    self::$instance->set(UserAgentInfoConfig::CACHE_KEY_PREFIX . $key_name, $data);
  }
}

