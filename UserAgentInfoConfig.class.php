<?php

UserAgentInfoConfig::$base_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;

require_once UserAgentInfoConfig::$base_dir . 'UserAgentInfoPeer.class.php';
require_once UserAgentInfoConfig::$base_dir . UserAgentInfoConfig::DIR_IMPORTS . DIRECTORY_SEPARATOR . 'Mobile_Detect.php';

/**
 * Configuration for UserAgentInfo project.
 * 
 * Change UserAgentInfoConfig::CACHE_* values to reflect your cache choices.
 * 
 * @author Mikołaj Misiurewicz <quentin389+uai@gmail.com>
 * 
 * @link https://github.com/quentin389/UserAgentInfo
 *
 */
class UserAgentInfoConfig
{
  /**
   * All cache keys should be prefixed.
   * 
   * @var string
   */
  const CACHE_KEY_PREFIX = 'UAI:';
  
  /**
   * Cache class to use.
   * 
   * The class has to be saved in cache/<name>.class.php file and has to implement UaiCacheInterface.
   * 
   * @var string
   */
  const CACHE_CLASS_NAME = 'UaiCachePhpredis';
  
  /**
   * If serialization is used in the cache class - serialize using standard PHP serialization or igbinary serializer?
   * 
   * @var boolean
   */
  const CACHE_USE_IGBINARY = true;
  
  
  /**
   * Base directory of UserAgentInfo project.
   * 
   * Use this property when referencing any paths inside the project directory.
   * 
   * @var string
   */
  public static $base_dir;
  
  const DIR_CACHE = 'cache';
  
  const DIR_IMPORTS = 'imports';
  
  const DIR_TESTS = 'tests';
}

