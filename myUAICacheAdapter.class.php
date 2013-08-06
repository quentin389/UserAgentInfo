<?php

/**
 * A simple adapter to plug in to the cache you want to use for UserAgentInfo objects.
 * 
 * You could always use a dummy ->set() and ->get() but that's not something you'd want to do in production environment.
 * UserAgentInfo project relies on a good caching system, because getting all information about user agent is a costly operation - 
 * it's something you don't want to repeat over and over for the same user agent.
 * 
 * @author Mikołaj Misiurewicz <quentin389+uai@gmail.com>
 * 
 * @link https://github.com/quentin389/UserAgentInfo
 * 
 * @version 1.1
 *
 */
class myUAICacheAdapter
{
  const CACHE_LIFETIME = 604800;
  
  const CACHE_PREFIX = 'user_agent_';
  
  public static function set($ua_md5, UserAgentInfo $data)
  {
    RedisCacheManager::getDefault()->set(self::CACHE_PREFIX . $ua_md5, $data, self::CACHE_LIFETIME);
  }
  
  public static function get($ua_md5)
  {
    return RedisCacheManager::getDefault()->get(self::CACHE_PREFIX . $ua_md5);
  }
}

