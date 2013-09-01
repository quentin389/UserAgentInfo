<?php

/**
 * >>> NOTE: THIS DOESN'T USE get_browser() OR THE PHP.INI SETTING <<<
 * 
 * Retrieves information from browscap project file using phpbrowscap library and cache files
 * and adds new user agents, not present in original files.
 * 
 * The list of new user agents is in this class, saved as an array, so it's very easy to add your own entries there.
 * 
 * This project and the list that goes with it was created because of the lack of updates to the original browscap files.
 * 
 * Big thanks to Gary Keith - the creator of browscap - who has been maintaining and updating his project for many many years.
 * 
 * 
 * This class is built on https://github.com/GaretJax/phpbrowscap class, which uses the browscap.ini files to create its own cache file.
 * Then it parses the user agents 100% in PHP which results in much better performance and some bug fixes for get_browser() errors.
 * 
 * So, there is no need for php.ini 'browscap' setting any more.
 * 
 * 
 * This class uses 'full' version of browscap file. The 'standard' version offers less details but is only tiny bit slower than the full one
 * (see https://github.com/quentin389/ua-speed-tests)
 * 
 * 
 * @author Mikołaj Misiurewicz <quentin389+uai@gmail.com>
 * 
 * @link https://github.com/quentin389/UserAgentInfo
 *
 */
class BrowscapWrapper
{
  /**
   * That's what browscap returns if it didn't find any information about given user agent
   */
  const DEFAULT_NAME = 'Default Browser';
  
  /**
   * The default value of ->platform when it's not recognised
   */
  const DEFAULT_OS = 'unknown';
  
  /**
   * If the values in source arrays change the cache will automatically update. However,
   * if you want to regenerate the cache anyway - just increase this number. 
   * 
   * @var integer
   */
  const CLASS_PARSER_VERSION = 8;
  
  /**
   * Path to the cache file.
   * 
   * @var string
   */
  protected $cache_file_location;
  
  protected $source_version;
  protected $source_browsers;
  protected $source_userAgents;
  protected $source_patterns;
  protected $source_properties;
  
  public function __construct($cache_location)
  {
    $this->cache_file_location = $cache_location;
    
    $this->load();
  }
  
  /**
   * Get the version of the original browscap project files.
   */
  public function getBrowscapVerison()
  {
    return $this->source_version;
  }
  
  /**
   * Get a version of the user agent arrays present in this class.
   * 
   * The md5 is cut to 5 characters because we 32 chars are way to many for a version number.
   * This value is saved in cache for each user agent so we should save space.
   * 
   * @return string
   */
  public static function getBrowscapReplacementVersion()
  {
    $md5 = md5(serialize(array_merge(self::$custom_agents_exact, self::$custom_agents_exact_override, self::$custom_agents_preg, self::$custom_agents_preg_override)));
    
    return self::CLASS_PARSER_VERSION . '.' . substr($md5, 0, 5);
  }
  
  /**
   * Load the data from the cache file
   */
  protected function load()
  {
    require_once $this->cache_file_location;
    
    $this->source_version = $source_version;
    $this->source_browsers = $browsers;
    $this->source_userAgents = $userAgents;
    $this->source_patterns = $patterns;
    $this->source_properties = $properties;
  }
  
  /**
   * Return browscap/phpbrowscap info array for given user agent.
   * 
   * @param string $user_agent
   * 
   * @return the phpbrowscap info array
   */
  public function getInfo($user_agent)
  {
    $user_agent_info = $this->getFromBrowscap($user_agent);
    
    $user_agent_info['isBanned'] = null;
    
    // nothing here
    // $user_agent_info = $this->customDetect($user_agent, self::$custom_agents_exact_override, self::$custom_agents_preg_override, $user_agent_info);
    
    if (self::DEFAULT_NAME == $user_agent_info['Browser'])
    {
      $user_agent_info = $this->customDetect($user_agent, self::$custom_agents_exact, self::$custom_agents_preg, $user_agent_info);
    }
    
    return $user_agent_info;
  }
  
  /**
   * Copy of https://github.com/GaretJax/phpbrowscap/blob/master/src/phpbrowscap/Browscap.php getBrowser() method, version 2.0b.
   * 
   * @param string $user_agent
   * 
   * @return array
   */
  protected function getFromBrowscap($user_agent)
  {
    $browser = array();
    
    foreach ($this->source_patterns as $pattern => $pattern_data)
    {
      if (preg_match($pattern . 'i', $user_agent, $matches))
      {
        if (1 == count($matches))
        {
          $key = $pattern_data;
          $simple_match = true;
        }
        else
        {
          $pattern_data = unserialize($pattern_data);
          array_shift($matches);
          $match_string = '@' . implode('|', $matches);
          
          if (!isset($pattern_data[$match_string])) continue;
          
          $key = $pattern_data[$match_string];
          $simple_match = false;
        }
        
        $browser = array(
          $user_agent,
          trim(strtolower($pattern), '@'),
          null // we don't need the original match pattern, skipping that step
        );
        $browser = $value = $browser + unserialize($this->source_browsers[$key]);
        
        while (array_key_exists(3, $value))
        {
          $value = unserialize($this->source_browsers[$value[3]]);
          $browser += $value;
        }
        
        if (!empty($browser[3]))
        {
          $browser[3] = $this->source_userAgents[$browser[3]];
        }
        
        break;
      }
    }
    
    $array = array();
    
    foreach ($browser as $key => $value)
    {
      if (2 == $key) continue;
      
      if ($value === 'true')
      {
        $value = true;
      }
      elseif ($value === 'false')
      {
        $value = false;
      }
      $array[$this->source_properties[$key]] = $value;
    }
    
    return $array;
  }
  
  /**
   * User agent detection alternative to browscap.
   *
   * @param string $user_agent user agent string
   * @param array $list_exact list of entries to match exactly
   * @param array $list_preg list of entries to match by regular expression
   * @param array $bc default phpbrowscap info array
   *
   * @return array phpbrowscap info array
   */
  protected function customDetect($user_agent, array $list_exact, array $list_preg, array $bc)
  {
    foreach ($list_exact as $string_match => $new_values)
    {
      if ($string_match == $user_agent)
      {
        $bc['browser_name_regex'] = '^' . preg_quote($string_match, '@') . '$';
        
        foreach ($new_values as $name => $value)
        {
          $bc[$name] = $value;
        }
        
        return $bc;
      }
    }
    
    foreach ($list_preg as $preg_string => $new_values)
    {
      $matches = array();
      $result = preg_match($preg_string, $user_agent, $matches);
      
      if ($result)
      {
        array_shift($matches);
        
        $bc['browser_name_regex'] = trim($preg_string, '#');
        
        foreach ($new_values as $name => $value)
        {
          $bc[$name] = vsprintf($value, $matches);
        }
        
        return $bc;
      }
    }
    
    return $bc;
  }
  
  /**
   * User agent strings identified by regular expressions.
   * 
   * This information will override what browscap returns. Avoid using it if you can.
   * IF browscap can identify something it returns correct information in (almost) 100% of the cases.
   * 
   * Start and end the string with # delimiters. 'Browser' key is required.
   * 
   * @var array
   */
  protected static $custom_agents_preg_override = array(
  );
  
  /**
   * User agent strings identified by regular expressions.
   *
   * These strings will be checked only if browscap doesn't recognise the user agent.
   *
   * Start and end the string with # delimiters. 'Browser' key is required.
   *
   * @var array
   */
  protected static $custom_agents_preg = array(
    '#^Mozilla/5\.0 \(Macintosh; Intel Mac OS X (\d+)_(\d+)_\d+\) AppleWebKit/(\d|\.)+ \(KHTML, like Gecko\)$#' => array(
      'Browser' => 'Apple Mail',
      'Platform' => 'MacOSX',
      'Platform_Version' => '%1$s.%2$s',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^Mozilla/5\.0 SF/(\d+)\.([\w\.]+)$#' => array(
      'Browser' => 'SkipFish Security Scanner',
      'Version' => '%1$s.%2$s',
      'MajorVer' => '%1$s',
      'MinorVer' => '%2$s',
      'Platform' => 'Linux',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^Mozilla/5\.0 \(compatible; melog\.com .*curl\)$#' => array(
      'Browser' => 'melog.com curl', // private - crawls only the company websites, no need to populate to browscap
      'Platform' => 'Linux',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^Mozilla/5\.0 \(compatible; WBSearchBot/(\d+)\.(\d+);#' => array(
      'Browser' => 'WBSearchBot',
      'Version' => '%1$s.%2$s',
      'MajorVer' => '%1$s',
      'MinorVer' => '%2$s',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^Mozilla/5\.0 \(compatible; discobot/(\d+)\.(\d+);#' => array(
      'Browser' => 'discobot',
      'Version' => '%1$s.%2$s',
      'MajorVer' => '%1$s',
      'MinorVer' => '%2$s',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^GG PeekBot (\d+)\.(\d+) \(\s?http://gg\.pl/.*\)$#' => array(
      'Browser' => 'Gadu-Gadu Bot',
      'Version' => '%1$s.%2$s',
      'MajorVer' => '%1$s',
      'MinorVer' => '%2$s',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^W3C_Unicorn#' => array(
      'Browser' => 'W3C Validator',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '0'
    ),
    '#^SearchIndexer#' => array(
      'Browser' => 'unidentified crawler',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^Jyxobot#' => array(
      'Browser' => 'Jyxobot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^www\.monit24\.pl-m24Bot#' => array(
      'Browser' => 'www.monit24.pl',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^WSCommand(-|_)iPhone#' => array(
      'Browser' => 'iPhone app bot',
      'isBanned' => '0',
      'isMobileDevice' => '1',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^Mozilla/5\.0 \(compatible; Genieo/(\d+)\.(\d+) http://www\.genieo\.com/webfilter\.html\)$#' => array(
      'Browser' => 'Genieo bot',
      'Version' => '%1$s.%2$s',
      'MajorVer' => '%1$s',
      'MinorVer' => '%2$s',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^spray-can#' => array(
      'Browser' => 'unidentified crawler',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^ExB Language Crawler (.*) \(\+http://www\.exb\.de/crawler\)$#' => array(
      'Browser' => 'ExB Crawler',
      'Version' => '%1$s',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^CoralWebPrx#' => array(
      'Browser' => 'CoralCDN bot',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#checks\.panopta\.com#' => array(
      'Browser' => 'Panopta Monitoring',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^Jakarta Commons-HttpClient/(\d+)\.(\d+)$#' => array(
      'Browser' => 'Apache Jakarta',
      'Version' => '%1$s.%2$s',
      'MajorVer' => '%1$s',
      'MinorVer' => '%2$s',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#fairshare\.cc#' => array(
      'Browser' => 'fairshare.cc bot',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#page2rss\.com#' => array(
      'Browser' => 'page2rss.com bot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#www\.abonti\.com#' => array(
      'Browser' => 'www.abonti.com bot (suspicious)',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#coccoc\.com#' => array(
      'Browser' => 'Coc Coc bot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#siteexplorer\.info#' => array(
      'Browser' => 'siteexplorer.info bot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    '#^Mozilla/5\.0 \(compatible; .*Mail.RU_Bot/(\d+).(\d+)[);]#' => array(
      'Browser' => 'mail.ru bot',
      'Version' => '%1$s.%2$s',
      'MajorVer' => '%1$s',
      'MinorVer' => '%2$s',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    )
  );
  
  /**
   * User agent strings identified by exact, case sensitive, match.
   *
   * This information will override what browscap returns. Avoid using it if you can.
   * IF browscap can identify something it returns correct information in (almost) 100% of the cases.
   *
   * 'Browser' key is required.
   *
   * @var array
   */
  protected static $custom_agents_exact_override = array(
    
  );
  
  /**
   * User agent strings identified by exact, case sensitive, match.
   *
   * These strings will be checked only if browscap doesn't recognise the user agent.
   *
   * 'Browser' key is required.
   *
   * @var array
   */
  protected static $custom_agents_exact = array(
    'Mozilla/5.0 (compatible; OpenindexSpider; +http://openindex.io/en/webmasters/spider.html)' => array(
      'Browser' => 'Openindex Spider',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)' => array(
      'Browser' => 'E-mail address crawler',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt; DTS Agent' => array(
      'Browser' => 'E-mail address crawler',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; SEOkicks-Robot +http://www.seokicks.de/robot.html)' => array(
      'Browser' => 'SEOkicks-Robot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
   'Mozilla/5.0 (compatible; JikeSpider; +http://shoulu.jike.com/spider.html)' => array(
      'Browser' => 'JikeSpider',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'NetSprint News' => array(
      'Browser' => 'NetSprint News Crawler',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'MLBot (www.metadatalabs.com/mlbot)' => array(
      'Browser' => 'MLBot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; proximic; +http://www.proximic.com)' => array(
      'Browser' => 'Proximic Bot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/4.0 (compatible;)' => array(
      'Browser' => 'Unknown pre-fetch bot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US))' => array(
      'Browser' => 'fake IE', // double closing bracket
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; en-US)' => array(
      'Browser' => 'fake IE', // banned because I get tons of those from spammers and NONE from legit users, the UA by itself doesn't look fake
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/4.0 (compatible;MSIE 7.0;Windows NT 6.0)' => array(
      'Browser' => 'fake IE', // no spaces between parts
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'webscraper/1.0' => array(
      'Browser' => 'webscraper bot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; discoverybot/2.0; +http://discoveryengine.com/discoverybot.html)' => array(
      'Browser' => 'discoverybot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; proximic; +http://www.proximic.com/info/spider.php)' => array(
      'Browser' => 'proximic bot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; SearchmetricsBot; http://www.searchmetrics.com/en/searchmetrics-bot/)' => array(
      'Browser' => 'searchmetrics bot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; GrapeshotCrawler/2.0; +http://www.grapeshot.co.uk/crawler.php)' => array(
      'Browser' => 'grapeshot bot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.0; trendictionbot0.5.0; trendiction search; http://www.trendiction.de/bot; please let us know of any problems; web at trendiction.com) Gecko/20071127 Firefox/3.0.0.11' => array(
      'Browser' => 'trendiction bot',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'msnbot/0.01 (+http://search.msn.com/msnbot.htm)' =>  array(
      'Browser' => 'msn bot',
      'Version' => '0.01',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; SISTRIX Crawler; http://crawler.sistrix.net/)' =>  array(
      'Browser' => 'sistrix crawler',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'xpymep.exe' =>  array(
      'Browser' => 'russian banned bot',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'statsdone.com' =>  array(
      'Browser' => 'russian shady website',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'start.exe' =>  array(
      'Browser' => 'something fake',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'BuiBui-Bot/1.0 (email: buibui[at]dadapro[dot]com)' =>  array(
      'Browser' => 'BuiBui bot',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'sdhhd' =>  array(
      'Browser' => 'fake user agent name',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'dfhdf' =>  array(
      'Browser' => 'fake user agent name',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla' =>  array(
      'Browser' => 'fake generic browser',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/4.0' =>  array(
      'Browser' => 'fake generic browser',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/4.78 [en]' =>  array(
      'Browser' => 'fake generic browser',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/5.0' =>  array(
      'Browser' => 'fake generic browser',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/5.0 (compatible)' =>  array(
      'Browser' => 'fake generic browser',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/5.0 (compatible' =>  array(
      'Browser' => 'fake generic browser',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/4.0 (compatible)' =>  array(
      'Browser' => 'fake generic browser',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/4.0 (compatible; MSIE 999.1; Unknown)' =>  array(
      'Browser' => 'fake generic browser',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Mozilla/Firefox' =>  array(
      'Browser' => 'fake Firefox',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'firefox' =>  array(
      'Browser' => 'fake Firefox',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'MSIE 7.0' =>  array(
      'Browser' => 'fake IE',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'MSIE 8.0' =>  array(
      'Browser' => 'fake IE',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Internet Explorer' =>  array(
      'Browser' => 'fake IE',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'ESTATER_SPIDER' =>  array(
      'Browser' => 'real estate bad bot',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Informative string with your contact info' =>  array(
      'Browser' => 'fake user agent name',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'myinfo' =>  array(
      'Browser' => 'fake user agent name',
      'isBanned' => '1',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'Ruby' =>  array(
      'Browser' => 'ruby',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    ),
    'ruby' =>  array(
      'Browser' => 'ruby',
      'isBanned' => '0',
      'isMobileDevice' => '0',
      'isSyndicationReader' => '0',
      'Crawler' => '1'
    )
  );
}

