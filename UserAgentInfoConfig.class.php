<?php

UserAgentInfoConfig::$base_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;

require_once UserAgentInfoConfig::$base_dir . 'UserAgentInfoPeer.class.php';
require_once UserAgentInfoConfig::$base_dir . UserAgentInfoConfig::DIR_IMPORTS . DIRECTORY_SEPARATOR . 'Mobile_Detect.php';

class UserAgentInfoConfig
{
  const CACHE_KEY_PREFIX = 'UAI:';
  
  const CACHE_CLASS_NAME = 'UaiCachePhpredis';
  
  const CACHE_USE_IGBINARY = true;
  
  const DIR_CACHE = 'cache';
  
  const DIR_IMPORTS = 'imports';
  
  const DIR_TESTS = 'tests';
  
  public static $base_dir;
}

