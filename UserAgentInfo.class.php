<?php

/**
 * Class containing information about a single user agent string.
 * 
 * This class should provide means of identifying any data present in user agent string that can be used for practical purposes.
 * 
 * The most important values you can get are:
 * 
 * ->getUserAgentString() - return the source user agent string, using this is NOT the same as using HTTP_USER_AGENT
 *   because Mobile_Detect can take this value from other http header fields
 * 
 * ->isMobile(), ->isMobileTablet(), ->isMobileAndroid(), ->isMobileAppleIos() - allows a full user device identification for mobile redirects:
 * if ($ua->isMobileAndroid() && !$ua->isMobileTablet()) echo 'Android Phone';
 * if ($ua->isMobileAndroid() && $ua->isMobileTablet()) echo 'Android Tablet';
 * if ($ua->isMobileAppleIos() && !$ua->isMobileTablet()) echo 'iPhone';
 * if ($ua->isMobileAppleIos() && $ua->isMobileTablet()) echo 'iPad';
 * if ($ua->isMobile() && !$ua->isMobileAndroid() && !$ua->isMobileAppleIos()) echo 'Meh, some other mobile device';
 * 
 * ->isBanned() - it's a bot you probably want to ban right away; it may be an e-mail scrapper, fake user agent (someone is trying to conceal his identity), etc.
 * 
 * ->isBot() - a very useful check to both save in your logs and serve slightly different content, for example disable dynamic images loading for spiders;
 *   be careful, never hide or show any user readable content only to bots or you'll get banned from Google!
 * 
 * ->isIEVersion(...) - separate old Internet Explorer versions from other browsers, for example to show 'you are using an outdated browser' notice
 * 
 * ->renderInfoBrowser(), ->renderInfoDevice(), ->renderInfoOs() - to get a human readable information about user browser, device or operating system
 * ->renderInfoAll() - get all the above values in one string, very usefull to include if you show information about given user for your internal purposes;
 *   for example when users report bugs to via forms on your website
 * 
 * @author Mikołaj Misiurewicz <quentin389+uai@gmail.com>
 * 
 * @link https://github.com/quentin389/UserAgentInfo
 *
 */
class UserAgentInfo
{
  /**
   * The names could be more descriptive but we're saving this object in cache, so the shorter the better.
   */
  
  protected $user_agent;
  
  protected $browser; // browser family
  protected $browser_major;
  protected $browser_minor;
  protected $browser_patch;
  
  protected $device_family;
  protected $device_version;
  
  protected $os; // os family
  protected $os_major;
  protected $os_minor;
  protected $os_patch;
  
  protected $is_banned;
  
  protected $is_mobile;
  protected $is_mobile_tablet;
  
  protected $is_bot;
  protected $is_bot_reader;
  
  protected $is_64_bit_os;
  protected $is_64_bit_browser;
  
  protected $mobile_grade;
  
  protected $_id_level;
  protected $_data_version;
  
  /**
   * @param string $user_agent
   * @param UserAgentInfoPeer::ID_LEVEL_* $identification_level
   * @param string $data_version
   * @param array $params
   */
  public function __construct($user_agent, $identification_level, $data_version, array $params)
  {
    $this->user_agent = $user_agent;
    
    $this->browser = $params['browser'];
    $this->browser_major = $params['browser_major'];
    $this->browser_minor = $params['browser_minor'];
    $this->browser_patch = $params['browser_patch'];
    
    $this->device_family = $params['device_family'];
    $this->device_version = $params['device_version'];
    
    $this->os = $params['os'];
    $this->os_major = $params['os_major'];
    $this->os_minor = $params['os_minor'];
    $this->os_patch = $params['os_patch'];
    
    $this->is_banned = $params['is_banned'];
    
    $this->is_mobile = $params['is_mobile'];
    $this->is_mobile_tablet = $params['is_mobile_tablet'];
    
    $this->is_bot = $params['is_bot'];
    $this->is_bot_reader = $params['is_bot_reader'];
    
    $this->is_64_bit_os = $params['is_64_bit_os'];
    $this->is_64_bit_browser = $params['is_64_bit_browser'];
    
    $this->mobile_grade = $params['mobile_grade'];
    
    $this->_id_level = $identification_level;
    $this->_data_version = $data_version;
  }
  
  /**
   * Was the user agent identified at least a little bit or not at all?
   * 
   * @return boolean
   */
  public function isIdentified()
  {
    return UserAgentInfoPeer::ID_LEVEL_NONE != $this->_id_level;
  }
  
  /**
   * Was the user agent idenified fully?
   * 
   * @return boolean
   */
  public function isIdentifiedFully()
  {
    return UserAgentInfoPeer::ID_LEVEL_FULL == $this->_id_level;
  }
  
  /**
   * Is this a mobile device?
   * 
   * @return boolean
   */
  public function isMobile()
  {
    return $this->is_mobile;
  }
  
  /**
   * Is this a mobile device AND a tablet?
   * 
   * Not all tablets will identify as such by default. Some will be indistinguishable from desktop computers.
   * 
   * @return boolean
   */
  public function isMobileTablet()
  {
    return $this->is_mobile_tablet;
  }
  
  /**
   * Is the operating system Android (which also means it's a mobile device)?
   * 
   * @return boolean
   */
  public function isMobileAndroid()
  {
    return $this->is_mobile && 'Android' == $this->os;
  }
  
  /**
   * Is the operating system Apple iOS (which also means it's a mobile device)?
   * 
   * iOS means you've probably encountered iPad or iPhone (->isMobileTablet() will tell you which one)
   * 
   * @return boolean
   */
  public function isMobileAppleIos()
  {
    return $this->is_mobile && 'iOS' == $this->os;
  }
  
  /**
   * Was the mobile grade detected at all?
   * 
   * @return boolean
   */
  public function isMobileGradeRated()
  {
    return UserAgentInfoPeer::MOBILE_GRADE_UNKNOWN != $this->mobile_grade;
  }
  
  /**
   * Is this a grade A mobile device?
   * 
   * Grade A mobile device should support all the latest technologies like HTML5, CSS3, full JavaScript with AJAX.
   * 
   * Do not assume that only devices with mobile grade A have full latest technologies support because some devices
   * may stay unidentified.
   * If you want to find out which devices most likely do NOT support all technologies check:
   * if ($ua->isMobileGradeRated() && !$ua->isMobileGradeA())
   * 
   * This value is experimental.
   * 
   * @return boolean
   */
  public function isMobileGradeA()
  {
    return UserAgentInfoPeer::MOBILE_GRADE_A == $this->mobile_grade;
  }
  
  /**
   * Is this a really, really bad bot which you should ban right away?
   * (and then verify what you've done, because you are a responsible person)
   * 
   * @return boolean
   */
  public function isBanned()
  {
    return $this->is_banned;
  }
  
  /**
   * Is this a bot? (crawler, spider, scrapper or other type of bot)
   * 
   * If this check returns false then the user agent is most likely a user browser.
   * Unless it wasn't identified at all, then we don't know anything (check using ->isIdentified()).
   * 
   * @return boolean
   */
  public function isBot()
  {
    return $this->is_bot;
  }
  
  /**
   * Is this a bot AND it's designed to read RSS, ATOM and other kinds of syndication feeds?
   * 
   * @return boolean
   */
  public function isBotReader()
  {
    return $this->is_bot_reader;
  }
  
  /**
   * Is this a 64 bit browser?
   * 
   * @return boolean
   */
  public function isBrowser64bit()
  {
    return $this->is_64_bit_browser;
  }
  
  /**
   * Is this a 64 bit system?
   * 
   * @return boolean
   */
  public function isOs64bit()
  {
    return $this->is_64_bit_os;
  }
  
  /**
   * Detect Internet Explorer version.
   * 
   * $this->isIEVersion(7, false) - is this desktop IE 7?
   * $this->isIEVersion(8, true) - is this desktop IE 8 or lower?
   * 
   * @param integer $include_version the IE major version number you want to check for
   * @param boolean $also_match_lower do you want to check just for $include_version or also for all older IE versions?
   * @param boolean $include_mobile include only desktop IE in the check or also a mobile versions?
   * 
   * I'm not sure if setting $include_mobile to true makes any sense. I never had to create any specific code for mobile IE... 
   * 
   * @return boolean - false if this is not an Internet Explorer or its version doesn't match the filter
   */
  public function isIEVersion($include_version, $also_match_lower, $include_mobile = false)
  {
    if (empty($this->browser_major) || $this->browser_major > $include_version)
    {
      return false;
    }
    
    if (!$also_match_lower && $this->browser_major < $include_version)
    {
      return false;
    }
    
    if (UserAgentInfoPeer::UA_IE_DESKTOP != $this->browser && (!$include_mobile || UserAgentInfoPeer::UA_IE_MOBILE != $this->browser))
    {
      return false;
    }
    
    return true;
  }
  
  /**
   * Return user agent string that this class returns information about.
   * 
   * @return string
   */
  public function getUserAgentString()
  {
    return $this->user_agent;
  }
  
  /**
   * Get the browser name / type ('Firefox', 'Chrome', etc.)
   * 
   * @return string
   */
  public function getBrowserName()
  {
    return $this->browser;
  }
  
  /**
   * Get the browser major version number.
   * 
   * @return string (usually an integer string)
   */
  public function getBrowserVersionMajor()
  {
    return $this->browser_major;
  }
  
  /**
   * Get the browser minor version number.
   * 
   * @return string
   */
  public function getBrowserVersionMinor()
  {
    return $this->browser_minor;
  }
  
  /**
   * Get the browser patch version number (the part written after the major and minor version).
   * 
   * @return string
   */
  public function getBrowserVersionPatch()
  {
    return $this->browser_patch;
  }
  
  /**
   * Get the browser version.
   * 
   * @param boolean $detailed_version whether to also return the patch version part or just major and minor part
   * 
   * @return string
   */
  public function getBrowserVersion($detailed_version = false)
  {
    if (!$this->browser_major)
    {
      return '';
    }
    
    $version = $this->browser_major;
    
    if ('' != $this->browser_minor)
    {
      $version .= UserAgentInfoPeer::SEPARATOR_VERSION . $this->browser_minor;
      
      if ($detailed_version && '' != $this->browser_patch)
      {
        $version .= UserAgentInfoPeer::SEPARATOR_VERSION . $this->browser_patch;
      }
    }
    
    return $version;
  }
  
  /**
   * Get the device family name (eg. Samsung, Nokia, iPhone, iPad, Kindle).
   * 
   * That will usually work only for mobile devices. You can't really detect a device type using desktop browsers user agent strings.
   * 
   * @return string
   */
  public function getDeviceFamily()
  {
    return $this->device_family;
  }
  
  /**
   * Get the device version or something we think may be a device version.
   * This may or may not return specific phone models and stuff like that.
   * 
   * Same as with ->getDeviceFamily() will only work for mobile devices, not desktop computers.
   * 
   * @return string
   */
  public function getDeviceVersion()
  {
    return $this->device_version;
  }
  
  /**
   * Get the operating system name, which is either a generic name (Linux, Ubuntu, Android, Mac OS X, ...)
   * or a specific Windows version (Windows XP, Windows 8, etc.)
   * 
   * @return string
   */
  public function getOsName()
  {
    return $this->os;
  }
  
  /**
   * Get the operating system major version number.
   * Value is empty for Windows.
   * 
   * @return string (usually an integer string)
   */
  public function getOsVersionMajor()
  {
    return $this->os_major;
  }
  
  /**
   * Get the operating system minor version number.
   * 
   * @return string
   */
  public function getOsVersionMinor()
  {
    return $this->os_minor;
  }
  
  /**
   * Get the operating system patch version number (the part written after the major and minor version).
   * 
   * @return string
   */
  public function getOsVersionPatch()
  {
    return $this->os_patch;
  }
  
  /**
   * Get the operating system version.
   * 
   * @param boolean $detailed_version whether to also return the patch version part or just major and minor part
   * 
   * @return string
   */
  public function getOsVersion($detailed_version = false)
  {
    if (!$this->os_major)
    {
      return '';
    }
    
    $version = $this->os_major;
    
    if ('' != $this->os_minor)
    {
      $version .= UserAgentInfoPeer::SEPARATOR_VERSION . $this->os_minor;
      
      if ($detailed_version && '' != $this->os_patch)
      {
        $version .= UserAgentInfoPeer::SEPARATOR_VERSION . $this->os_patch;
      }
    }
    
    return $version;
  }
  
  /**
   * Get mobile grade identified by UserAgentInfoPeer::MOBILE_GRADE_*
   * 
   * @return UserAgentInfoPeer::MOBILE_GRADE_*
   */
  public function getMobileGrade()
  {
    return $this->mobile_grade;
  }
  
  /**
   * Get a combined version of all source data used to identify this user agent.
   * 
   * If this version changes then it's time to regenerate the data from source.
   * 
   * This value is human readable so you can check which source system has what version.
   * 
   * @return string
   */
  public function getDataVersion()
  {
    return $this->_data_version;
  }
  
  /**
   * Render human readable information about the browser, operating system and device, for example:
   * 'Mobile Safari 6.3, iOS 6.1, iPhone'
   * 
   * It's a string combined from the data available for browser, os and device.
   * It does not include boolean checks info like 'isMobile' or 'isBot'. 
   * 
   * @param string $detailed_versions whether to print very detailed browser and os version numbers or not
   * 
   * @return string
   */
  public function renderInfoAll($detailed_versions = false)
  {
    $info = array();
    
    if ($info_browser = self::renderInfoBrowser($detailed_versions))
    {
      $info[] = $info_browser;
    }
    
    if ($info_os = self::renderInfoOs($detailed_versions))
    {
      $info[] = $info_os;
    }
    
    if ($info_device = self::renderInfoDevice())
    {
      $info[] = $info_device;
    }
    
    return implode(', ', $info);
  }
  
  /**
   * Render human readable information about the browser, for example 'Opera 12.16 (64 bit)'.
   *
   * @param string $detailed_versions whether to print very detailed version number or not
   *
   * @return string
   */
  public function renderInfoBrowser($detailed_version = false)
  {
    $info = $this->browser;
    
    if ('' != $info)
    {
      $version = $this->getBrowserVersion($detailed_version);
      
      if ($version)
      {
        $info .= ' ' . $version;
      }
      
      if ($this->is_64_bit_browser)
      {
        $info .= ' (' . UserAgentInfoPeer::NAME_64_BIT . ')';
      }
    }
    
    return $info;  
  }
  
  /**
   * Render human readable information about user device name and version, for example 'Samsung GT-I9000'.
   * 
   * This information should be available for most mobile devices, but not for desktop computers.
   * 
   * @return string
   */
  public function renderInfoDevice()
  {
    $name = array();
    
    if ($this->device_family)
    {
      if ($this->device_version)
      {
        if ('-' == substr($this->device_version, 0, 1))
        {
          return $this->device_family . $this->device_version;
        }
        
        return $this->device_family . ' ' . $this->device_version;
      }
      
      return $this->device_family;
    }
    
    if ($this->device_version)
    {
      return $this->device_version;
    }
    
    return '';
  }
  
  /**
   * Render human readable information about the operating system, for example 'Windows 7 (64 bit)' or 'Windows XP'.
   *
   * @param string $detailed_versions whether to print very detailed version number or not
   *
   * @return string
   */
  public function renderInfoOs($detailed_version = false)
  {
    $info = $this->os;
    
    if ('' != $info)
    {
      $version = $this->getOsVersion($detailed_version);
      
      if ($version)
      {
        $info .= ' ' . $version;
      }
      
      if ($this->is_64_bit_os)
      {
        $info .= ' (' . UserAgentInfoPeer::NAME_64_BIT . ')';
      }
    }
    
    return $info;
  }
}

