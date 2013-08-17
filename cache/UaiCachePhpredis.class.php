<?php

/**
 * A simple wrapper around Redis cache (http://redis.io/).
 * 
 * @author Mikołaj Misiurewicz <quentin389+uai@gmail.com>
 * 
 * @link https://github.com/quentin389/UserAgentInfo
 * 
 */
class UaiCachePhpredis implements UaiCacheInterface
{
  /**
   * @var Redis
   */
  public static $redis;
  
  /**
   * If you want to use your own instance of Redis then pass it to UaiCachePhpredis::$redis before calling UserAgentInfoPeer for the first time.
   */
  public static function autoInit()
  {
    if (self::$redis instanceof Redis)
    {
      return;
    }
    
    self::$redis = new Redis();
    self::$redis->pconnect('127.0.0.1');
    self::$redis->setOption(Redis::OPT_SERIALIZER, UserAgentInfoConfig::CACHE_USE_IGBINARY ? Redis::SERIALIZER_IGBINARY : Redis::SERIALIZER_PHP);
  }
  
  public static function get($key_name)
  {
    self::autoInit();
    
    return self::$redis->get(UserAgentInfoConfig::CACHE_KEY_PREFIX . $key_name);
  }
  
  public static function set($key_name, $data)
  {
    self::autoInit();
    
    // no timeout - UserAgentInfo objects should live forever, cache keys are reused if the data change
    self::$redis->set(UserAgentInfoConfig::CACHE_KEY_PREFIX . $key_name, $data);
  }
}

