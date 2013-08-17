<?php

class UaiCachePhpredis implements UaiCacheInterface
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
  
  public static function getSize($as_igbinary)
  {
    if ($as_igbinary)
    {
      $data = igbinary_serialize(self::$cache);
    }
    else
    {
      $data = serialize(self::$cache);
    }
    
    return strlen($data);
  }
}

