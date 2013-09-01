<?php

/**
 * A simple wrapper around APC
 * 
 * @author Ignas Bernotas
 * 
 * @link https://github.com/ignasbernotas/UserAgentInfo
 * 
 */
class UaiCacheAPC implements UaiCacheInterface
{
  
  public static function get($key_name)
  {
    return apc_fetch(UserAgentInfoConfig::CACHE_KEY_PREFIX . $key_name);
  }
  
  public static function set($key_name, $data)
  {
    apc_store(UserAgentInfoConfig::CACHE_KEY_PREFIX . $key_name, $data);
  }
}

