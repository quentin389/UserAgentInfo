<?php

interface UaiCacheInterface
{
  public static function get($key_name);
  
  public static function set($key_name, $data);
}

