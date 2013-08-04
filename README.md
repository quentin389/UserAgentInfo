See [changelog file](CHANGELOG.md) for a list of changes.

UserAgentInfo
=============

PHP class for parsing user agent strings (HTTP_USER_AGENT). Includes mobile checks, bot checks, browser types/versions and more. Based on browscap, Mobile_Detect and ua-parser. Created for high traffic websites and fast batch processing.

**please note:**

Right now the project is taken directly from the version that works on my website, so there are probably some things I should change to make it easier to adapt in other projects (see todo list).


Why another user agent detection class?
-------------------
This project was crated because I couldn't find one script that would give me all the information I needed from `HTTP_USER_AGENT` strings.

I was using browscap to identify bots, ban users, get information about browsers and user OS for internal monitoring, and some random scripts to detect and redirect mobile devices. In addition to that I had to add my own user agents to browscap detection because the project half died (it looks like it will be back on track soon though).

What's the aim of this project?
-----------------
- To **retrieve all important information** from user agent - so you won't have to user more than one script to parse your user agents for different purposes.

- To **work fast on enterprise level websites** with high traffic - to achieve that all the required information is retrieved in one go and cached, not (as in some other project) retrieved on demand.

- To **provide a single up to date source** of user agent information - I'm going to update this project as long as I need it, so it should be current for quite some time. Right now you still need a browscap.ini directive in php.ini but you can change the path to point to this project imports/browscap.ini and then you won't need to update browscap.ini file separately.

Updates
------------
Right now my goal is to update the project source parsers once a week. Updating more often is bad because this will reset the whole cache, so it's not something you want to do every day. Perhaps the updates could be done just twice a month, but that may be too long to wait for new user agents identification.

In the first phase of the project, when there is still a lot of important things on my todo list, I might update the project more often, but if not required I will not change the source data version.

Installing
-----------
1. Download.
2. Change `myUAICacheAdapter` and `myUAITimerAdapter` classes to use cache and timer from your code.
3. Include required classes if you don't have an autoloader that does that for you. (that should not be necessary, it's on todo list)
4. Optionally point your php.ini to `imports/browscap.ini`, this way you can stop updating browscap it separately.
5. Keep the classes up to date.

Usage
-----
`UserAgentInfo` is a class containing information about a single user agent string. It should provide means of identifying any data present in user agent string that can be used for practical purposes. Retrieve it by writing:

`UserAgentInfoPeer::getMy()` - to get current user info

`UserAgentInfoPeer::getOther($arbitrary_user_agent)` - to get info about any user agent

You can call those methods as many times as you want in your code, there is no need to cache the retrieved object, because it's already cached by the main class.


The most important values you can get are:

- `->getUserAgentString()` - return the source user agent string, using this is NOT the same as using `HTTP_USER_AGENT` because Mobile_Detect can take this value from other http header fields.

- `->isMobile()`, `->isMobileTablet()`, `->isMobileAndroid()`, `->isMobileAppleIos()` - allows a full user device identification for mobile redirects:
```php
if ($ua->isMobileAndroid() && !$ua->isMobileTablet()) echo 'Android Phone';
if ($ua->isMobileAndroid() && $ua->isMobileTablet()) echo 'Android Tablet';
if ($ua->isMobileAppleIos() && !$ua->isMobileTablet()) echo 'iPhone';
if ($ua->isMobileAppleIos() && $ua->isMobileTablet()) echo 'iPad';
if ($ua->isMobile() && !$ua->isMobileAndroid() && !$ua->isMobileAppleIos())
  echo 'Meh, some other mobile device';
```

- `->isBanned()` - it's a bot you probably want to ban right away; it may be an e-mail scrapper, fake user agent (someone is trying to conceal his identity), etc.

- `->isBot()` - a very useful check to both save in your logs and serve slightly different content, for example disable dynamic images loading for spiders. Be careful, never hide or show any user readable content only to bots or you'll get banned from Google!

- `->isIEVersion(...)` - separate old Internet Explorer versions from other browsers, for example to show 'you are using an outdated browser' notice.

- `->renderInfoBrowser()`, `->renderInfoDevice()`, `->renderInfoOs()` - to get a human readable information about user browser, device or operating system.

- `->renderInfoAll()` - get all the above values in one string, very useful to include if you show information about given user for your internal purposes. For example when users report bugs to via forms on your website.


Relation to other projects
----------------------------------
User agent information is retrieved from four sources:

- browscap (bc) - http://tempdownloads.browserscap.com/ - browscap contains a huge database of incredibly detailed specific user agents information but it sucks with newer user agents and even more sucks for mobile detection.

- Mobile_Detect (md) - https://github.com/serbanghita/Mobile-Detect - it detects mobile device types with very high precision.

- ua-parser (uap) with data from BrowserScope - https://github.com/tobie/ua-parser - provides good generic information about all types of browsers so it's an excellent addition to find information about things browscap does not detect.

- Some information is generated directly in UserAgentInfo. Currently those are two things:
  - Additional user agents identified in browscap format (see BrowscapWrapper class).
  -  Browser and operating system architecture information (see self::parseArchitecture()).

Todo list
---------------------------
- update source parsers once a week
- important (performance!) - do not init anything but required part of Mobile_Detect before checking user agent in cache 
- should I standardize OS name and move Windows version to ->version?
- should device family be changed to device manufacturer and version to name (same as in full browscap)?
- request to add version number to browscap get_browser()
- request to add version number to uaparser json file
- test full browscap file and see if it makes sense to use it
- should browscap be moved fully to PHP as in https://github.com/garetjax/phpbrowscap ?
- It would make sense to get rid of the php.ini setting requirement and just be able to fully control what data is served from browscap.
- make a simple HTML page to test all user agents from user-agent-examples.txt
- include files (do not rely on autoload)
- see which PHP version is required to run the script. PHP 5.0 would be the best, there is no need to push for 5.3. However, right now it may not be compatible with older PHP versions, as it was created on PHP 5.4.
