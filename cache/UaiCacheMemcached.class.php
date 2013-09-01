<?php

/**
 * A simple wrapper around Redis cache (http://redis.io/).
 * 
 * @author Mikołaj Misiurewicz <quentin389+uai@gmail.com>
 * 
 * @link https://github.com/quentin389/UserAgentInfo
 * 
 */
class UaiCacheMemcached implements UaiCacheInterface
{
  /**
   * @var Redis
   */
  public static $instance = null;
  
  /**
   * If you want to use your own instance of Redis then pass it to UaiCachePhpredis::$redis before calling UserAgentInfoPeer for the first time.
   */
  public static function autoInit()
  {
    if (self::$instance !== null)
    {
      return;
    }

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

