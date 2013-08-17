<?php

/**
 * Retrieves information from browscap using get_browser() and adds new user agents, not present in original browscap file.
 * 
 * The list of new user agents is in this class, saved as an array, so it's very easy to add your own entries there.
 * 
 * This project and the list that goes with it was created because of the lack of updates to the original browscap files.
 * 
 * Big thanks to Gary Keith - the creator of browscap - who has been maintaining and updating his project for many many years.
 * 
 * 
 * If you want you can update browscap.ini as a part of this package. Just point your PHP to imports/browscap.ini. This file will be kept up to date.
 * Alternatively the whole browscap parsing may get moved to PHP to eliminate a need for php.ini directives completely. But that's on todo list.
 * 
 * I'm currently using standard php_browscap.ini file (there are also 'full' and 'light' versions available).
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
   * Simplified browscap version getter.
   * 
   * We need to regenarate all the UAs information when browscap source file changes,
   * but it doesn't provide any information about its version. We could use md5 but that's something
   * that cannot be just called every time you request a user agent info. So, filesize seems like an
   * OK approximation.
   * 
   * This method probably won't work if you don't set 'browscap' php.ini directive as an absolute path.
   * 
   * @return integer
   */
  public static function getBrowscapVersion()
  {
    // this is a temporary fix for an issue https://github.com/quentin389/UserAgentInfo/issues/2
    return 'fake';
    
    // return filesize(ini_get('browscap'));
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
   * Return browscap info object for given user agent.
   * The source data is taken either from original browscap files or from the custom user agents arrays present in this class.
   * 
   * There is no cache on this level, this will always make the get_browser call so never call it by hand (browscap is slow!).
   * 
   * @param string $user_agent
   * 
   * @throws Exception if get_browser fails
   * 
   * @return stdClass the browscap info object, which unfortunately lacks its own base class
   */
  public static function getInfo($user_agent)
  {
    $user_agent_info = get_browser($user_agent);
    
    if (false === $user_agent_info)
    {
      throw new Exception('get_browser function did not return any data - maybe you do not include browscap.ini? (php path: `' . ini_get('browscap') . '`)');
    }
    
    $user_agent_info = self::customDetect($user_agent, self::$custom_agents_exact_override, self::$custom_agents_preg_override, $user_agent_info);
    
    if (self::DEFAULT_NAME == $user_agent_info->browser)
    {
      $user_agent_info = self::customDetect($user_agent, self::$custom_agents_exact, self::$custom_agents_preg, $user_agent_info);
    }
    
    return $user_agent_info;
  }
  
  /**
   * User agent strings identified by regular expressions.
   * 
   * This information will override what browscap returns. Avoid using it if you can.
   * IF browscap can identify something it returns correct information in (almost) 100% of the cases.
   * 
   * Start and end the string with # delimiters. 'browser' key is required.
   * 
   * @var array
   */
  protected static $custom_agents_preg_override = array(
    '#yacybot#' => array(
      'browser' => 'YaCy-Bot',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    )
  );
  
  /**
   * User agent strings identified by regular expressions.
   *
   * These strings will be checked only if browscap doesn't recognise the user agent.
   *
   * Start and end the string with # delimiters. 'browser' key is required.
   *
   * @var array
   */
  protected static $custom_agents_preg = array(
    '#^Mozilla/5\.0 \(Macintosh; Intel Mac OS X (\d+)_(\d+)_\d+\) AppleWebKit/(\d|\.)+ \(KHTML, like Gecko\)$#' => array(
      'browser' => 'Apple Mail',
      'platform' => 'MacOSX',
      'platform_version' => '%1$s.%2$s',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^Opera/9\.80 \(Windows NT 6\.2; W(in|OW)64.*\) Presto/(\d|\.)+ Version/\d+\.(\d+)$#' => array(
      'browser' => 'Opera',
      'version' => '12.%3$s',
      'majorver' => '12',
      'minorver' => '%3$s',
      'platform' => 'Win8',
      'platform_version' => '6.2',
      'win64' => '1',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    '#^Opera/9\.80 \(Windows NT 6\.2\) Presto/(\d|\.)+ Version/\d+\.(\d+)$#' => array(
      'browser' => 'Opera',
      'version' => '12.%2$s',
      'majorver' => '12',
      'minorver' => '%2$s',
      'platform' => 'Win8',
      'platform_version' => '6.2',
      'win32' => '1',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    '#^Mozilla/5\.0 SF/(\d+)\.([\w\.]+)$#' => array(
      'browser' => 'SkipFish Security Scanner',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'platform' => 'Linux',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#Ezooms/(\d+)\.([\w\.]+)#' => array(
      'browser' => 'Ezooms',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
  		'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^check_http/v\d+\.[\w\.]+ \(nagios-plugins (\d+)\.([\w\.]+)\)$#' => array(
			'browser' => 'Nagios',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'platform' => 'Linux',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^Mozilla/(\d+)\.(\d+) \[en\] \(X11; U; SunOS (\w+) sun4u\)$#' => array(
      'browser' => 'Netscape Navigator',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'platform' => 'Solaris',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    '#^Mozilla/(\d+)\.(\d+) \[en\] \(Windows NT 5\.0; U\)#' => array(
      'browser' => 'Netscape Navigator',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'platform' => 'Win2000',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^Mozilla/5\.0 \(compatible; melog\.com .*curl\)$#' => array(
      'browser' => 'melog.com curl', // private - crawls only the company websites, no need to populate to browscap
      'platform' => 'Linux',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#Yahoo Pipes (\d+)\.(\d+)#' => array(
      'browser' => 'Yahoo Pipes (YQL)',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1' // technically it's NOT a crawler
    ),
    '#^Mozilla/5\.0 \(compatible; WBSearchBot/(\d+)\.(\d+);#' => array(
      'browser' => 'WBSearchBot',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^Mozilla/5\.0 \(compatible; spbot/(\d+)\.(\d+);#' => array(
      'browser' => 'spbot',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^Mozilla/5\.0 \(compatible; discobot/(\d+)\.(\d+);#' => array(
      'browser' => 'discobot',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^GG PeekBot (\d+)\.(\d+) \(\s?http://gg\.pl/.*\)$#' => array(
      'browser' => 'Gadu-Gadu Bot',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#Google Wireless Transcoder#' => array(
      'browser' => 'Google Wireless Transcoder',
      'isbanned' => '0',
      'ismobiledevice' => '1',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    '#^Mozilla/5\.0 \(i(Pad|Phone);.*AppleWebKit#' => array(
      'browser' => 'Safari',
      'platform' => 'iPhone OSX',
      'isbanned' => '0',
      'ismobiledevice' => '1',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    '#^Mozilla/5\.0 \(Linux;[^)]+Android[^)]+generic\) AppleWebKit.*Mobile Safari.*$#' => array(
      'browser' => 'Android',
      'platform' => 'Android',
      'isbanned' => '0',
      'ismobiledevice' => '1',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    '#^Mozilla/5\.0 \(PlayBook;[^)]+RIM Tablet OS[^)]+\) AppleWebKit.*Safari.*$#' => array(
      'browser' => 'BlackBerry',
      'platform' => 'BlackBerry OS',
      'isbanned' => '0',
      'ismobiledevice' => '1',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    '#^Mozilla/5\.0 \(SAMSUNG; SAMSUNG-GT-\w+/\w+; U; Bada/\d+\.\d+; [\w-]+\) AppleWebKit/\d+\.\d+ \(KHTML, like Gecko\) Dolfin/(\d+)\.(\d+) #' => array(
      'browser' => 'Dolfin',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'platform' => 'Bada',
      'isbanned' => '0',
      'ismobiledevice' => '1',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    '#^Mozilla/5\.0 \(X11; Linux x86_64\) AppleWebKit/\d+\.\d+ \(KHTML, like Gecko; Google Web Preview\) Chrome/(\d+)\.(\d+\.\d+) Safari/\d+\.\d+$#' => array(
      'browser' => 'Chrome',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'platform' => 'Linux',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    '#^W3C_Unicorn#' => array(
      'browser' => 'W3C Validator',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    '#^SearchIndexer#' => array(
      'browser' => 'unidentified crawler',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^Jyxobot#' => array(
      'browser' => 'Jyxobot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^www\.monit24\.pl-m24Bot#' => array(
      'browser' => 'www.monit24.pl',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^WSCommand(-|_)iPhone#' => array(
      'browser' => 'iPhone app bot',
      'isbanned' => '0',
      'ismobiledevice' => '1',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^Mozilla/5\.0 \(compatible; Genieo/(\d+)\.(\d+) http://www\.genieo\.com/webfilter\.html\)$#' => array(
      'browser' => 'Genieo bot',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^spray-can#' => array(
      'browser' => 'unidentified crawler',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^ExB Language Crawler (.*) \(\+http://www\.exb\.de/crawler\)$#' => array(
      'browser' => 'ExB Crawler',
      'version' => '%1$s',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^CoralWebPrx#' => array(
      'browser' => 'CoralCDN bot',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#checks\.panopta\.com#' => array(
      'browser' => 'Panopta Monitoring',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^Microsoft Office Mobile/(\d+)\.(\d+)$#' => array(
      'browser' => 'Microsoft Office Mobile',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'isbanned' => '0',
      'ismobiledevice' => '1',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^Jakarta Commons-HttpClient/(\d+)\.(\d+)$#' => array(
      'browser' => 'Apache Jakarta',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#fairshare\.cc#' => array(
      'browser' => 'fairshare.cc bot',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#page2rss\.com#' => array(
      'browser' => 'page2rss.com bot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#www\.abonti\.com#' => array(
      'browser' => 'www.abonti.com bot (suspicious)',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#coccoc\.com#' => array(
      'browser' => 'Coc Coc bot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#siteexplorer\.info#' => array(
      'browser' => 'siteexplorer.info bot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^Mozilla/5\.0 \(compatible; .*Mail.RU_Bot/(\d+).(\d+)[);]#' => array(
      'browser' => 'mail.ru bot',
      'version' => '%1$s.%2$s',
      'majorver' => '%1$s',
      'minorver' => '%2$s',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '#^Mozilla/5\.0 \(Windows NT 6\.3; (WOW|Win)64;.*Trident/7\.0;.*rv:11\.(\d+).*\) like Gecko$#' => array(
      'browser' => 'IE',
      'version' => '11.%2$s',
      'majorver' => '11',
      'minorver' => '%2$s',
      'platform' => 'Win8.1',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    '#^Mozilla/5\.0 \(Windows NT 6\.3;.*Trident/7\.0;.*rv[: ]11\.(\d+).*\) like Gecko$#' => array(
      'browser' => 'IE',
      'version' => '11.%1$s',
      'majorver' => '11',
      'minorver' => '%1$s',
      'platform' => 'Win8.1',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
  );
  
  /**
   * User agent strings identified by exact, case sensitive, match.
   *
   * This information will override what browscap returns. Avoid using it if you can.
   * IF browscap can identify something it returns correct information in (almost) 100% of the cases.
   *
   * 'browser' key is required.
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
   * 'browser' key is required.
   *
   * @var array
   */
  protected static $custom_agents_exact = array(
    'Mozilla/5.0 (compatible; OpenindexSpider; +http://openindex.io/en/webmasters/spider.html)' => array(
      'browser' => 'Openindex Spider',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)' => array(
      'browser' => 'E-mail address crawler',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt; DTS Agent' => array(
      'browser' => 'E-mail address crawler',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; SEOkicks-Robot +http://www.seokicks.de/robot.html)' => array(
      'browser' => 'SEOkicks-Robot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
   'Mozilla/5.0 (compatible; JikeSpider; +http://shoulu.jike.com/spider.html)' => array(
      'browser' => 'JikeSpider',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534+ (KHTML, like Gecko) BingPreview/1.0b' => array(
      'browser' => 'Safari',
      'platform' => 'Win7',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    'NetSprint News' => array(
      'browser' => 'NetSprint News Crawler',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'MLBot (www.metadatalabs.com/mlbot)' => array(
      'browser' => 'MLBot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; proximic; +http://www.proximic.com)' => array(
      'browser' => 'Proximic Bot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/4.0 (compatible;)' => array(
      'browser' => 'Unknown pre-fetch bot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0(iPad; U; CPU iPhone OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B314 Safari/531.21.10gin_lib.cc' => array(
      'browser' => 'Safari',
      'version' => '4.0',
      'majorver' => '4',
      'platform' => 'iOS',
      'isbanned' => '0',
      'ismobiledevice' => '1',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.2; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)' => array(
      'browser' => 'IE',
      'version' => '8.0',
      'majorver' => '8',
      'platform' => 'Win8',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '0'
    ),
    'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.2 (KHTML, like Gecko) Chrome/6.0' => array(
      'browser' => 'fake Chrome',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US))' => array(
      'browser' => 'fake IE',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; el-GR)' => array(
      'browser' => 'fake IE',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; en-US)' => array(
      'browser' => 'fake IE',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/4.0 (compatible;MSIE 7.0;Windows NT 6.0)' => array(
      'browser' => 'fake IE',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    '# Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; OfficeLiveConnector.1.3; OfficeLivePatch.0.0; MS-RTC LM 8; Zune 4.0)' => array(
      'browser' => 'fake IE',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'webscraper/1.0' => array(
      'browser' => 'webscraper bot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; discoverybot/2.0; +http://discoveryengine.com/discoverybot.html)' => array(
      'browser' => 'discoverybot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; proximic; +http://www.proximic.com/info/spider.php)' => array(
      'browser' => 'proximic bot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; SearchmetricsBot; http://www.searchmetrics.com/en/searchmetrics-bot/)' => array(
      'browser' => 'searchmetrics bot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; GrapeshotCrawler/2.0; +http://www.grapeshot.co.uk/crawler.php)' => array(
      'browser' => 'grapeshot bot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.0; trendictionbot0.5.0; trendiction search; http://www.trendiction.de/bot; please let us know of any problems; web at trendiction.com) Gecko/20071127 Firefox/3.0.0.11' => array(
      'browser' => 'trendiction bot',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'msnbot/0.01 (+http://search.msn.com/msnbot.htm)' =>  array(
      'browser' => 'msn bot',
      'version' => '0.01',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (compatible; SISTRIX Crawler; http://crawler.sistrix.net/)' =>  array(
      'browser' => 'sistrix crawler',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'xpymep.exe' =>  array(
      'browser' => 'russian banned bot',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'statsdone.com' =>  array(
      'browser' => 'russian shady website',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'start.exe' =>  array(
      'browser' => 'something fake',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'BuiBui-Bot/1.0 (email: buibui[at]dadapro[dot]com)' =>  array(
      'browser' => 'BuiBui bot',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'sdhhd' =>  array(
      'browser' => 'fake user agent name',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'dfhdf' =>  array(
      'browser' => 'fake user agent name',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla' =>  array(
      'browser' => 'fake generic browser',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/4.0' =>  array(
      'browser' => 'fake generic browser',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/4.78 [en]' =>  array(
      'browser' => 'fake generic browser',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0' =>  array(
      'browser' => 'fake generic browser',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (compatible)' =>  array(
      'browser' => 'fake generic browser',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/5.0 (compatible' =>  array(
      'browser' => 'fake generic browser',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/4.0 (compatible)' =>  array(
      'browser' => 'fake generic browser',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/4.0 (compatible; MSIE 999.1; Unknown)' =>  array(
      'browser' => 'fake generic browser',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Mozilla/Firefox' =>  array(
      'browser' => 'fake Firefox',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'firefox' =>  array(
      'browser' => 'fake Firefox',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'MSIE 7.0' =>  array(
      'browser' => 'fake IE',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'MSIE 8.0' =>  array(
      'browser' => 'fake IE',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Internet Explorer' =>  array(
      'browser' => 'fake IE',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'ESTATER_SPIDER' =>  array(
      'browser' => 'real estate bad bot',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Informative string with your contact info' =>  array(
      'browser' => 'fake user agent name',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'myinfo' =>  array(
      'browser' => 'fake user agent name',
      'isbanned' => '1',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'Ruby' =>  array(
      'browser' => 'ruby',
      'isbanned' => '0',
      'ismobiledevice' => '0',
      'issyndicationreader' => '0',
      'crawler' => '1'
    ),
    'ruby' =>  array(
        'browser' => 'ruby',
        'isbanned' => '0',
        'ismobiledevice' => '0',
        'issyndicationreader' => '0',
        'crawler' => '1'
    )
  );
  
  /**
   * User agent detection alternative to browscap.
   * 
   * @param string $user_agent user agent string
   * @param array $list_exact list of entries to match exactly
   * @param array $list_preg list of entries to match by regular expression
   * @param stdClass $ua_object default object as returned by get_browser()
   * 
   * @return stdClass get_browser() compatible object
   */
  protected static function customDetect($user_agent, array $list_exact, array $list_preg, stdClass $ua_object)
  {
    foreach ($list_exact as $string_match => $new_values)
    {
      if ($string_match == $user_agent)
      {
        $ua_object->browser_name_regex = '#^' . preg_quote($string_match, '#') . '$#';
        
        foreach ($new_values as $name => $value)
        {
          $ua_object->$name = $value;
        }
        
        return $ua_object;
      }
    }
    
    foreach ($list_preg as $preg_string => $new_values)
    {
      $matches = array();
      $result = preg_match($preg_string, $user_agent, $matches);
      
      if ($result)
      {
        array_shift($matches);
        
        $ua_object->browser_name_regex = $preg_string;
        
        foreach ($new_values as $name => $value)
        {
          $ua_object->$name = vsprintf($value, $matches);
        }
        
        return $ua_object;
      }
    }
    
    return $ua_object;
  }
}

