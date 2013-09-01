<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'UserAgentInfoConfig.class.php';

/**
 * This is the main class of UserAgentInfo which retrieves as much information about give user agent as possible
 * and stitches it together to create UserAgentInfo object.
 * 
 * Information is retieved from four sources:
 * 
 * 1) browscap (bc) - http://tempdownloads.browserscap.com/ - browscap contains a huge database of incredibly detailed specific user agents information
 * 
 * 2) Mobile_Detect (md) - https://github.com/serbanghita/Mobile-Detect - it detects mobile device types with very high precision
 * 
 * 3) ua-parser (uap) with data from BrowserScope - https://github.com/tobie/ua-parser - provides good generic information about all types of browsers
 * 
 * 4) Some information is generated directly in UserAgentInfo. Currently those are two things:
 *    a) Additional user agents identified in browscap format (see BrowscapWrapper class).
 *    b) Browser and operating system architecture information (see self::parseArchitecture()).
 * 
 * Execution time optimization:
 * 
 * This class is created for enterprise level websites ( == websites with lot of users). The idea is to always retrieve 100% information about given
 * user agent in one go and then cache it. Thus, the execution time of a single call is not that important. What's more important is that:
 * 
 * a) The browser md5 is cached locally so when you request UserAgentInfoPeer info once in a given script all the subsequent calls will just
 *    return an object already present in a local array. This means that you can (and should) just use UserAgentInfoPeer::getMy() all over your code
 *    to get whatever info you need at the moment. It also means that if you are batch processing non unique user agents (eg. groped by IP) you will
 *    just make one cache / source call per unique md5.
 *    
 * b) The browser md5 is cached in a high speed cache of your choice. With each new user on your website the probability of this user having a new,
 *    unique user agent is getting lower. That means that after some time, if you do not forcefully remove items from your cache, almost 100% of
 *    UserAgentInfo objects will be served from the cache without any need of parsing. So the average request time of an user agent information
 *    will be as fast as your cache retieval is.
 * 
 * For very high speed batch jobs you may want to pre-populate UserAgentInfoPeer::$local_cache with UserAgentInfo object requested in bulk from your cache.
 * 
 * How to set up your cache?
 * Change myUAICacheAdapter to use the cache of your choice.
 * The objects saved in the cache are small so you don't need gigabytes of space.
 * You can set the item lifetime as high as you want. If the source files are updated the objects will be requested from cache and updated under the same keys,
 * so there should be no need to ever clear the cache keys forcefully.
 * 
 * @author Mikołaj Misiurewicz <quentin389+uai@gmail.com>
 * 
 * @link https://github.com/quentin389/UserAgentInfo
 * 
 * @version 1.3
 */
class UserAgentInfoPeer
{
  /**
   * Increase this number if you want to forcefully regnerate the cache.
   * 
   * @var integer
   */
  const CLASS_PARSER_VERSION = 2;
  
  /**
   * String to show when something is 64 bit
   */
  const NAME_64_BIT = '64 bit';
  
  /**
   * Some specific browser type names.
   */
  const UA_IE_DESKTOP = 'IE';
  const UA_IE_MOBILE = 'IE Mobile';
  
  /**
   * Version number parts are separated by dot.
   */
  const SEPARATOR_VERSION = '.';
  
  /**
   * Used for separating human readable data
   */
  const SEPARATOR_GENERIC = ', ';
  
  /**
   * This user agent was not identified at all.
   */
  const ID_LEVEL_NONE = 0;
  
  /**
   * This user agent was partially identified.
   */
  const ID_LEVEL_PARTIAL = 1;
  
  /**
   * This user agent was fully identified.
   */
  const ID_LEVEL_FULL = 2;
  
  /**
   * Mobile grades as defined in Mobile_Detect
   */
  const MOBILE_GRADE_A = Mobile_Detect::MOBILE_GRADE_A;
  const MOBILE_GRADE_B = Mobile_Detect::MOBILE_GRADE_B;
  const MOBILE_GRADE_C = Mobile_Detect::MOBILE_GRADE_C;
  const MOBILE_GRADE_UNKNOWN = '';
  
  /**
   * Some names used by Mobile_Detect
   */
  const MOBILE_DETECT_BOT_NAME = 'Bot';
  const MOBILE_DETECT_MOBILE_BOT_NAME = 'MobileBot';
  const MOBILE_DETECT_TABLET_SUFFIX = 'Tablet'; 
  
  /**
   * Location of the source JSON file for uaparser, relative to this file.
   */
  const UAPARSER_JSON_LOCATION = 'imports/regexes.json';
  
  /**
   * Location of the source PHP file for phpbrowscap, relative to this file.
   */
  const BROWSCAP_CACHE_LOCATION = 'imports/browscap_cache.php';
  
  /**
   * Some names used by uaparser
   */
  const UAPARSER_DEFAULT_NAME = 'Other';
  const UAPARSER_BOT_NAME = 'Spider';
  const UAPARSER_GENERIC_WINDOWS = 'Windows';
  
  /**
   * Those names are ignored if they are found in device name.
   */
  protected static $generic_devices = array(
    self::UAPARSER_DEFAULT_NAME,
    self::UAPARSER_BOT_NAME,
    'Generic Feature Phone',
    'Generic Smartphone',
    'GenericTablet',
    'GenericPhone'
  );
  
  /**
   * OS names conversion table for Mobile_Detect. For the sake of standarization.
   */
  protected static $md_os_proper_names = array(
    'AndroidOS' => 'Android',
    'BlackBerryOS' => 'BlackBerry OS',
    'PalmOS' => 'Palm OS',
    'SymbianOS' => 'Symbian OS',
    'WindowsMobileOS' => 'Windows Mobile',
    'WindowsPhoneOS' => 'Windows Phone',
    'iOS' => 'iOS',
    'JavaOS' => 'JAVA',
    'MeeGoOS' => 'MeeGo',
    'MaemoOS' => 'Maemo',
    'webOS' => 'webOS',
    'badaOS'=> 'Bada',
    'BREWOS' => 'BREW'
  );
  
  /**
   * OS names conversion table for browscap.
   */
  protected static $bc_os_proper_names = array(
    'MacOSX' => 'Mac OS X',
    'SymbianOS' => 'Symbian OS',
    'iPhone OSX' => 'iOS',
    'Win31' => '',
    'ChromeOS' => 'Chrome OS'
  );
  
  /**
   * List of device family names that weren't identified automatically.
   */
  protected static $device_families = array(
    'DoCoMo',
    'Nintendo'
  );
  
  /**
   * Cache for names lists from Mobile_Detect
   */
  protected static $md_browsers;
  protected static $md_os_list;
  protected static $md_devices;
  protected static $md_devices_generic;
  
  /**
   * Cached current version of the data, composed from versions of all data from different source scripts.
   */
  protected static $data_version;
  
  /**
   * This cache contains 'user agent md5' => 'UserAgentInfo object' pairs
   */
  protected static $local_cache = array();
  
  /**
   * Instance of BrowscapWrapper
   * 
   * @var BrowscapWrapper
   */
  protected static $browscap_wrapper;
  
  /**
   * Cached Mobile_Detect object
   * 
   * @var Mobile_Detect
   */
  protected static $mobile_detect;
  
  /**
   * An array to pass to Mobile_Detect so it won't take http headers into account when parsing the user agent.
   */
  protected static $fake_md_headers = array('' => 'x');
  
  /**
   * Cached uaparser object 
   * 
   * @var UAParser
   */
  protected static $uaparser;
  
  /**
   * URI of the source json file for ua-parser
   * 
   * @var string
   */
  protected static $uaparser_source_file;
  
  /**
   * URI of the source php file for phpbrowscap
   * 
   * @var string
   */
  protected static $browscap_source_file;
  
  /**
   * User agent of the current user, taken from http headers.
   */
  protected static $my_user_agent;
  
  /**
   * Return UserAgentInfo object for an user agent of the current script user.
   * 
   * Can be used as many times as you want - no need to apply additional cache.
   * 
   * @return UserAgentInfo
   */
  public static function getMy()
  {
    return self::getInfo(null, true);
  }
  
  /**
   * Return UserAgentInfo object for any given user agent.
   * 
   * To retrieve info about the current user use ::getMy()
   * 
   * @param string $user_agent the user agent string
   * @param boolean $use_cache if you set it to false the response will always be parsed from the source data; don't use this option if you're not debugging
   * 
   * @return UserAgentInfo
   */
  
  public static function getOther($user_agent, $use_cache = true)
  {
    return self::getInfo($user_agent, $use_cache);
  }
  
  /**
   * Checks local cache for user agent md5, checks the main cache, and if that fails - retrieves the information from source.
   * 
   * @return UserAgentInfo
   */
  protected static function getInfo($user_agent, $use_cache)
  {
    self::initBeforeCache();
    
    if (null === $user_agent)
    {
      $user_agent = self::$my_user_agent;
    }
    
    $result = false;
    $user_agent = trim($user_agent);
    $ua_md5 = md5($user_agent);
    
    if ($use_cache)
    {
      $result = self::getFromCache($ua_md5);
    }
    
    if (!$result)
    {
      self::initAfterCache();
      
      $result = self::parse($user_agent);
      
      if ($use_cache)
      {
        self::setCache($ua_md5, $result);
      }
    }
    
    return $result;
  }
  
  /**
   * Executed only once. Sets information required for each new UserAgentInfo object.
   */
  protected static function initBeforeCache()
  {
    if (null !== self::$data_version)
    {
      return;
    }
    
    $base_dir = UserAgentInfoConfig::$base_dir;
    
    require_once $base_dir . 'UserAgentInfo.class.php';
    require_once $base_dir . UserAgentInfoConfig::DIR_IMPORTS . DIRECTORY_SEPARATOR . 'BrowscapWrapper.class.php';
    require_once $base_dir . UserAgentInfoConfig::DIR_CACHE . DIRECTORY_SEPARATOR . 'UaiCacheInterface.php';
    require_once $base_dir . UserAgentInfoConfig::DIR_CACHE . DIRECTORY_SEPARATOR . UserAgentInfoConfig::CACHE_CLASS_NAME . '.class.php';
    
    if (!in_array('UaiCacheInterface', class_implements(UserAgentInfoConfig::CACHE_CLASS_NAME, false)))
    {
      throw new Exception('Class ' . UserAgentInfoConfig::CACHE_CLASS_NAME . ' doesn\'t implement UaiCacheInterface.');
    }
    
    // can be empty
    self::$my_user_agent = (string) @$_SERVER['HTTP_USER_AGENT'];
    
    self::$mobile_detect = new Mobile_Detect(self::$fake_md_headers);
    
    self::$uaparser_source_file = $base_dir . self::UAPARSER_JSON_LOCATION;
    
    self::$browscap_source_file = $base_dir . self::BROWSCAP_CACHE_LOCATION;
    
    // this value should change if anything is changed in the source data; the value is human readable (I don't see a need for md5) 
    self::$data_version = implode(self::SEPARATOR_GENERIC, array(
      self::CLASS_PARSER_VERSION, // modifications in UserAgentInfoPeer parser
      filesize(self::$browscap_source_file), // changes in browscap original file (not a real version, filesize() is used)
      BrowscapWrapper::getBrowscapReplacementVersion(), // changes in additional, custom detection rules defined in BrowscapWrapper
      self::$mobile_detect->getScriptVersion(), // nice, Mobile_Detect actually returns a proper version string
      filesize(self::$uaparser_source_file) // changes in uaparser source file; same as browscap, the version is not passed so we use filesize() to approximate
    ));
  }
  
  /**
   * Executed once or never. Sets all the information that is required for parsing the user agent string.
   * 
   * If all the user agents are taken from cache, this method is not called
   */
  protected static function initAfterCache()
  {
    if (null !== self::$uaparser)
    {
      return;
    }
    
    require_once UserAgentInfoConfig::$base_dir . UserAgentInfoConfig::DIR_IMPORTS . DIRECTORY_SEPARATOR . 'uaparser.php';
    
    self::$browscap_wrapper = new BrowscapWrapper(self::$browscap_source_file);
    
    self::$uaparser = new UAParser(self::$uaparser_source_file);
    
    self::$md_browsers = array_keys(self::$mobile_detect->getBrowsers());
    
    $md_os_list = self::$mobile_detect->getOperatingSystems();
    self::$md_os_list = array_keys($md_os_list);
    
    self::$md_devices = array_keys(self::$mobile_detect->getPhoneDevices() + self::$mobile_detect->getTabletDevices());
    self::$md_devices_generic = array();
    
    foreach (self::$md_devices as $device)
    {
      if (!in_array($device, self::$generic_devices))
      {
        self::$md_devices_generic[] = str_replace(self::MOBILE_DETECT_TABLET_SUFFIX, '', $device);
      }
    }
    self::$md_devices_generic[] = 'Nokia';
    self::$md_devices_generic = array_unique(self::$md_devices_generic);
  }
  
  /**
   * Tries to retrieve the user agent info from either local class cache or the main (cross script) cache.
   * 
   * @param string $ua_md5 user agent md5 string
   * 
   * @return UserAgentInfo or false if either the value was not cached or it's cached in older version
   */
  protected static function getFromCache($ua_md5)
  {
    if (isset(self::$local_cache[$ua_md5]))
    {
      return self::$local_cache[$ua_md5];
    }
    
    $result = call_user_func(array(UserAgentInfoConfig::CACHE_CLASS_NAME, 'get'), $ua_md5);
    
    if (!is_object($result) || !($result instanceof UserAgentInfo))
    {
      return false;
    }
    
    if ($result->getDataVersion() != self::$data_version)
    {
      return false;
    }
    
    self::$local_cache[$ua_md5] = $result;
    
    return $result;
  }
  
  /**
   * Saves the UserAgentInfo object to caches.
   * 
   * @param string $ua_md5 user agent md5 string
   * @param UserAgentInfo $data
   */
  protected static function setCache($ua_md5, UserAgentInfo $data)
  {
    self::$local_cache[$ua_md5] = $data;
    
    call_user_func(array(UserAgentInfoConfig::CACHE_CLASS_NAME, 'set'), $ua_md5, $data);
  }
  
  /**
   * Main method - get all the source data about given user agent and parse it to create an object
   * with as much good and acurrate information as possible.
   * 
   * @param string $user_agent user agent string
   * 
   * @return UserAgentInfo
   */
  protected static function parse($user_agent)
  {
    // 1. >>> retrieve the data from sources
    
    $bc = self::$browscap_wrapper->getInfo($user_agent);
    
    self::$mobile_detect->setUserAgent($user_agent);
    $md = self::$mobile_detect;
    
    $uap = self::$uaparser->parse($user_agent);
    
    
    // 2. >>> parse the data
    
    // Mobile_Detect class is ONLY used for mobile devices, the data it returns for other devices is of much worse quality than the two other classes
    $md_is_mobile = $md->isMobile();
    $md_browser = $md_is_mobile ? self::parseBrowserMd($md) : false;
    
    $is_mobile = $md_is_mobile || $bc['isMobileDevice'];
    
    if (false === strpos($user_agent, 'WSCommand'))
    {
      // uaparser returns good quality results for browser detection because it's based on generic substrings
      $browser = self::parseBrowserUap($uap);
      // for mobile browsers we alternatively try Mobile_Detect
      if (!$browser) $browser = $md_browser;
      // if nothing worked we try browscap, this is very useful for legacy and exotic browsers 
      if (!$browser) $browser = self::parseBrowserBc($bc);
    }
    else
    {
      // this is one very specific case - incorrect identification of WSCommand bot
      $browser = self::parseBrowserBc($bc);
    }
    
    // returning 'Android' for both browser and os is a bad idea
    if ('Android' == @$browser['name'])
    {
      $browser['name'] = 'Android Browser';
    }
    
    list($device['family'], $device['version']) = self::parseDevice($md, $uap);
    
    // uaparser is usually good here too, for the same reasons as with browsers
    $os = self::parseOsUap($uap);
    // sometimes uaparser tries to guess just the OS name and fails badly ...
    if (!isset($os['major']) && $md_is_mobile) $os = self::parseOsMd($md);
    
    if (!$os)
    {
      // if nothing was found revert to browscap, good with really old user agents
      $os = self::parseOsBc($bc);
    }
    elseif (self::UAPARSER_GENERIC_WINDOWS == $os['name'])
    {
      // if the OS name is 'Windows' (without version) then we try to override that data using browscap,
      // because if it returns anything it's always very specific
      $tmp_os = self::parseOsBc($bc);
      
      if ($tmp_os)
      {
        $os = $tmp_os;
      }
    }
    
    $architecture = self::parseArchitecture($user_agent);
    
    // trying to mark as many bots as possible
    $is_bot = $bc['Crawler'] || self::UAPARSER_BOT_NAME == $uap->device->family || $md->is(self::MOBILE_DETECT_BOT_NAME) || $md->is(self::MOBILE_DETECT_MOBILE_BOT_NAME);
    
    // that's an interesting feature, so why not include it
    $mobile_grade = $md_is_mobile && !empty($md_browser['name']) ? $md->mobileGrade() : '';
    
    
    // 3. >>> set the user agent identification level
    
    if ($is_mobile && !$is_bot && !empty($browser['name']) && !empty($os['name']) && (!empty($device['family']) || !empty($device['version'])))
    {
      // this is a mobile user (not a mobile bot) and was nicely and specifically identified by Mobile_Detect or uaparser
      $id_level = self::ID_LEVEL_FULL;
    }
    elseif (BrowscapWrapper::DEFAULT_NAME != $bc['Browser'])
    {
      // it's not a mobile user but it was identified by browscap, that's enough to provide full information
      $id_level = self::ID_LEVEL_FULL;
    }
    elseif (!empty($browser['name']) || $is_mobile || $is_bot)
    {
      // browser name is the basic data that we need and that all the parsers should provide;
      // if browser name is not given then mobile and bot checks are also very important
      $id_level = self::ID_LEVEL_PARTIAL;
      
      if (empty($browser['name']))
      {
        // we don't want to leave the browser name empty
        $browser['name'] = trim('generic ' . ($is_mobile ? 'mobile ': '') . ($is_bot ? 'bot ' : ''));
      }
    }
    else
    {
      // not identfied at all (some data may be present, but nothing useful)
      $id_level = self::ID_LEVEL_NONE;
    }
    
    // $browser['name'] is always filled if $id_level != self::ID_LEVEL_NONE
    
    $ua_info = new UserAgentInfo(
      $user_agent,
      $id_level,
      self::$data_version,
      array(
        'browser' => (string) @$browser['name'],
        'browser_major' => (string) @$browser['major'],
        'browser_minor' => (string) @$browser['minor'],
        'browser_patch' => (string) @$browser['patch'],
        'device_family' => $device['family'],
        'device_version' => $device['version'],
        'os' => (string) @$os['name'],
        'os_major' => (string) @$os['major'],
        'os_minor' => (string) @$os['minor'],
        'os_patch' => (string) @$os['patch'],
        'is_banned' => (boolean) $bc['isBanned'],
        'is_mobile' => $is_mobile,
        'is_mobile_tablet' => $is_mobile && $md->isTablet(),
        'is_bot' => $is_bot,
        'is_bot_reader' => $is_bot && $bc['isSyndicationReader'],
        'is_64_bit_os' => (64 == @$architecture['os']),
        'is_64_bit_browser' => (64 == @$architecture['browser']),
        'mobile_grade' => $mobile_grade
      )
    );
    
    return $ua_info;
  }
  
  /**
   * Identify the browser based on uaparser data
   * 
   * @param stdClass $uap data from uaparser
   * 
   * @return array or false
   */
  protected static function parseBrowserUap(stdClass $uap)
  {
    $family = $uap->ua->family;
    $version = $uap->ua->toVersionString;
    
    if (self::UAPARSER_DEFAULT_NAME == $family)
    {
      return false;
    }
    
    return self::parseBrowserOrOs($family, $version);
  }
  
  /**
   * Identify the os based on uaparser data
   * 
   * @param stdClass $uap data from uaparser
   * 
   * @return array or false
   */
  protected static function parseOsUap(stdClass $uap)
  {
    $family = $uap->os->family;
    $version = $uap->os->toVersionString;
    
    if (self::UAPARSER_DEFAULT_NAME == $family)
    {
      return false;
    }
    
    if ('linux' == $family)
    {
      $family = 'Linux';
    }
    
    return self::parseBrowserOrOs($family, $version);
  }
  
  /**
   * Identify the browser based on Mobile_Detect data
   * 
   * @param Mobile_Detect $md
   * 
   * @return array or false
   */
  protected static function parseBrowserMd(Mobile_Detect $md)
  {
    foreach (self::$md_browsers as $family)
    {
      if ($md->is($family))
      {
        return self::parseBrowserOrOs($family, $md->version($family));
      }
    }
    
    return false;
  }
  
  /**
   * Identify the os based on Mobile_Detect data
   * 
   * @param Mobile_Detect $md
   * 
   * @return array or false
   */
  protected static function parseOsMd(Mobile_Detect $md)
  {
    foreach (self::$md_os_list as $family)
    {
      if ($md->is($family))
      {
        return array('name' => self::$md_os_proper_names[$family]);
      }
    }
    
    return false;
  }
  
  /**
   * Identify the browser based on browscap data
   * 
   * @param array $bc data from browscap
   * 
   * @return array or false
   */
  protected static function parseBrowserBc(array $bc)
  {
    $family = $bc['Browser'];
    $version = $bc['Version'];
    
    if (BrowscapWrapper::DEFAULT_NAME == $family)
    {
      return false;
    }
    
    return self::parseBrowserOrOs($family, $version);
  }
  
  /**
   * Identify the os based on browscap data
   * 
   * @param array $bc data from browscap
   * 
   * @return array or false
   */
  protected static function parseOsBc(array $bc)
  {
    $family = $bc['Platform'];
    $version = $bc['Platform_Version'];
    
    if (BrowscapWrapper::DEFAULT_OS == $family)
    {
      $family = '';
    }
    
    if (BrowscapWrapper::DEFAULT_OS == $version)
    {
      $version = '';
    }
    
    if (isset(self::$bc_os_proper_names[$family]))
    {
      $family = self::$bc_os_proper_names[$family];
    }
    elseif ('WinPhone' == substr($family, 0, 8))
    {
      $family = 'Windows Phone';
      
      if (!$version)
      {
        $version = substr($family, 8);
      }
    }
    elseif ('Win' == substr($family, 0, 3))
    {
      // standarize os names
      $family = 'Windows ' . substr($family, 3);
    }
    
    if ('Windows' == substr($family, 0, 7))
    {
      // if we've identified windows as, for example, 'Windows 8' we don't need the Microsoft internal version (eg.: 6.1)
      $version = '';
    }
    
    return self::parseBrowserOrOs($family, $version);
  }
  
  /**
   * Browser data and os data is returned in the same way.
   * Parse the family name and explode the version number to identify major, minor and patch versions.
   * 
   * @param string $family
   * @param string $version
   * 
   * @return array or false
   */
  protected static function parseBrowserOrOs($family, $version)
  {
    // no version information without family name
    if ('' == $family)
    {
      return false;
    }
    
    $version = explode(self::SEPARATOR_VERSION, $version, 3);
    
    // sometimes a version like 0.0 is returned, we need to remove that
    if (('' == $version[0] || '0' == $version[0]) && ('' == @$version[1] || '0' == @$version[1]) && ('' == @$version[2] || '0' == @$version[2]))
    {
      return array('name' => $family);
    }
    
    return array(
      'name' => $family,
      'major' => $version[0],
      'minor' => @$version[1],
      'patch' => @$version[2]
    );
  }
  
  /**
   * Identify the device 'family' and version. The term 'family' is really arbitrary as we have things like 'iPhone' and 'Samsung' there together
   * 
   * Currently browscap lite and standard versions do not identify devices.
   * .
   * @param Mobile_Detect $md
   * 
   * @param stdClass $uap uaparser class
   * 
   * @return array of (family, version)
   */
  protected static function parseDevice(Mobile_Detect $md, stdClass $uap)
  {
    $md_device = self::parseDeviceMd($md);
    
    $uap_device = self::parseDeviceUap($uap);
    
    // nothing was found
    if (empty($md_device) && empty($uap_device))
    {
      return array('', '');
    }
    
    // in the context of other manufacturer names, variants of 'Nexus' name are more fit to be a version for 'Google' family than a family itself
    if (false !== stripos($uap_device, 'Nexus'))
    {
      return array('Google', $uap_device);
    }
    
    // a very specific case of weird user agent misidentification
    if ('iPhone' == $md_device && 'iPod' == $uap_device)
    {
      return array($md_device, '');
    }
    
    // Mobile_Detect found nothing - base the results solely on uaparser data
    if (empty($md_device))
    {
      // Mobile_Detect names are family names, so they are good source of separation of the device family and version
      foreach (self::$md_devices_generic as $device)
      {
        if (0 === stripos($uap_device, $device))
        {
          return array($device, trim(substr($uap_device, strlen($device))));
        }
      }
      
      // no need to separate 'Lumia' as a family
      if (0 === stripos($uap_device, 'Lumia'))
      {
        return array('Nokia', $uap_device);
      }
      
      // some cases not identified automatically, if a family name is misidentified as a version an entry to self::$device_families can be added
      foreach (self::$device_families as $this_family)
      {
        if (0 === stripos($uap_device, $this_family))
        {
          $name_length = strlen($this_family);
          return array(substr($uap_device, 0, $name_length), trim(substr($uap_device, $name_length)));
        }
      }
      
      // by default what uaparser returns is really specific so it cannot be treated as a family name
      return array('', $uap_device);
    }
    
    // parseDeviceMd returns only the device family from Mobile_Detect
    if (empty($uap_device) || $md_device == $uap_device)
    {
      return array($md_device, '');
    }
    
    // uaparser returns device family and version as one string, in many cases it's easy to separate them by looking at what Mobile_Detect returned
    if (0 === stripos($uap_device, $md_device))
    {
      return array($md_device, trim(substr($uap_device, strlen($md_device))));
    }
    
    // if no other method succeded we assume that Mobile_Detect returned device family and uaparser the device version; it's probably not true :)
    return array($md_device, $uap_device);
  }
  
  /**
   * Identify the device based on Mobile_Detect data.
   * 
   * We ignore version information that may be returned by the class for given device
   * as it currently either returns nothing or data worse than uaparser
   * 
   * @param Mobile_Detect $md
   * 
   * @return string
   */
  protected static function parseDeviceMd(Mobile_Detect $md)
  {
    $device = '';
    
    foreach (self::$md_devices as $family)
    {
      if ($md->is($family))
      {
        $device = $family;
        break;
      }
    }
    
    if (empty($device) || in_array($device, self::$generic_devices))
    {
      return '';
    }
    
    // there is no need to separate device families by tablet/phone because this distinction is already present in ->isMobileTablet()
    $device = str_replace(self::MOBILE_DETECT_TABLET_SUFFIX, '', $device);
    
    return $device;
  }
  
  /**
   * Identify the device based on uaparser data.
   * 
   * Uaparser returns device family and version as one string.
   * 
   * @param stdClass $uap data from uaparser
   * 
   * @return string
   */
  protected static function parseDeviceUap(stdClass $uap)
  {
    $device = $uap->device->family;
    
    if (in_array($device, self::$generic_devices))
    {
      return '';
    }
    
    if ('Palm OS' == $device)
    {
      $device = 'Palm';
    }
    
    return $device;
  }
  
  /**
   * Return the browser and os architecture bits information.
   * 
   * While the formal identification is made in number of bits, the UserAgentInfo class only uses ->is64bit checks,
   * because having 32 bits is a default state. 
   * 
   * @param string $user_agent
   * 
   * @return array or false
   */
  protected static function parseArchitecture($user_agent)
  {
    if (empty($user_agent))
    {
      return false;
    }
    
    // WOW64 is a 32 bit browser on 64 bit Windows
    if (false !== stripos($user_agent, 'WOW64'))
    {
      return array('browser' => 32, 'os' => 64);
    }
    
    // Win64 is a 64 browser on 64 bit Windows
    if (false !== stripos($user_agent, 'Win64; x64'))
    {
      return array('browser' => 64, 'os' => 64);
    }
    
    // 64 bit non-Windows generic (Intel, AMD, some other) processor or a specific identification of 64 bit AMD processor
    // information about browser architecture is not provided
    if (false !== stripos($user_agent, 'x86_64') || false !== stripos($user_agent, 'amd64'))
    {
      return array('os' => 64);
    }
    
    // 32 bit Intel processor
    if (false !== stripos($user_agent, 'i686'))
    {
      return array('browser' => 32, 'os' => 32);
    }
    
    // :(
    return false;
  }
}

