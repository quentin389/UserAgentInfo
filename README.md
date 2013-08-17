See **[changelog file](CHANGELOG.md)** for a list of changes.

Information about **[performance and scaling](#performance-and-scaling)** of UserAgentInfo.

UserAgentInfo uses other project to get the data it needs.
See **[list of those projects](#relation-to-other-projects)**.

UserAgentInfo
=============

PHP class for parsing user agent strings (HTTP_USER_AGENT). Includes mobile checks, bots and banned bots checks, browser types/versions and more. Based on browscap, Mobile_Detect and ua-parser. Created for high traffic websites and fast batch processing.

**please note:**

It's a new project and there are still some major things to do (see: [todo list](#todo-list)).


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
2. Choose what cache you want to use and either choose one of the existing cache classes (see `cache/` directory) or write your own class that `implements UaiCacheInterface` and put it in `cache/` directory.
3. Change UserAgentInfoConfig::CACHE_* variables to reflect your cache choices
4. `require_once '/your_directory_structure/UserAgentInfo/UserAgentInfoPeer.class.php';`
5. Optionally point your php.ini to `imports/browscap.ini`, this way you can stop updating browscap it separately.
6. Keep the classes up to date.

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

- `->isBanned()` - it's a bot you may want to look at very closely and probably ban; it may be an e-mail scrapper, malicious bot with badly set user agent string, etc.

**note:**

`->isbanned` used to be a part of browscap project, but it was removed. Right now the only source of ban information is a list of user agents I've added by hand. It's not a very long list, but I'm working on adding the original list from browscap to the project too.

**important note:**

When adding a bot to `->isBanned()` list we always verify what the bot is and use our best judgement on whether this bot should be universally banned or not. Having said that, the decisions are still **arbitrary**, so there may be sitiuations in which you wouldn't agree that a certain bot should be banned. In such case feel free to report it as an issue.

- `->isBot()` - a very useful check to both save in your logs and serve slightly different content, for example disable dynamic images loading for spiders. Be careful, never hide or show any user readable content only to bots or you'll get banned from Google!

- `->isIEVersion(...)` - separate old Internet Explorer versions from other browsers, for example to show 'you are using an outdated browser' notice.

- `->renderInfoBrowser()`, `->renderInfoDevice()`, `->renderInfoOs()` - to get a human readable information about user browser, device or operating system.

- `->renderInfoAll()` - get all the above values in one string, very useful to include if you show information about given user for your internal purposes. For example when users report bugs to via forms on your website.

Performance and scaling
-------------------------

### Test - simple bulk retrieval 1-by-1
Bulk retrieval of data from [example user agent strings](imports/user-agent-examples.txt) (2494 unique entries).
Each user agent checked using `UserAgentInfoPeer::getOther($user_agent_string)`.
Test performed on Ubuntu virtual machine on a high end host machine.

With empty cache:
- `UserAgentInfoPeer` total time is `156.366 sec (62.7 ms per entry)` of which
    - `0.704 sec (0.3 ms per entry)` is checking data in the cache (`->get()` calls with empty results)
    - `153.678 sec (61.7 ms per entry)` is a total parsing time, of which
        - `111.083 sec (44.6 ms per entry)` is get_browser() time
        - `0.508 sec (0.2 ms per entry)` is custom browscap strings time
        - `14.560 sec (5.8 ms per entry)` is ua-parser time
        - `27.525 sec (11 ms per entry)` is Mobile_Detect time (this was not measured directly, the actual parsing may be much faster)

With the cache filled, all entries retrieved from the cache:
- `UserAgentInfoPeer` total time is `1.161 sec (0.5 ms per entry)` of which
    - `0.669 (0.3 ms per entry)` is getting data from the cache (this includes unserializing, `UserAgentInfo` objects are returned)

As you can see, retrieving information from `browscap` is slow (note: I'm using 'standard' browscap file at the moment). Even if you try to user only lighter
projects - `ua-parser` or `Mobile_Detect` to get a as much info about browsers at possible, this will still take some time for each request. This means that
the logical way to go is to use cache for user agent information. This way **you will be limited only by the speed and performance of your cache**.

If you take that approach, you will be able use all information available from user agent strings at will and you won't have to worry about performance problems.
Even bulk analysis of user agents won't be an issue (for example, you can preform cron checks on IP+browser pairs to check for bots).

Of course, a question remains, what's the cache hit ratio when you choose to use it for user agent string detection?

### Test - cache hit ratio
My `UserAgentInfo` was running for about a week without any changes or cache resets on a set of websites with more than 1.5 million user visits per month.
During that time:
- There were an average of 2,478 script calls per minute (each script call uses `UserAgentInfo`), which gives a `total of 24,978,240 calls`.
- I've accumulated `20,282 UserAgentInfo cached objects`.
- The total size of those objects when saved in cache is around `12 MB (around 620 bytes per object)`.

That means that the number of calls that did not use cache was below 0.09% which is a great result.
Moreover, the most popular user agent strings were cached right away.

As you can imagine, the **number of unique user agents does not grow proportionally to the website traffic**. The number of popular browsers is quite limited,
so the larger your website gets the lower chance of seeing a new user agent. This means that the more users you server the more difference using
`UserAgentInfo` makes.

### Conclusion
As long as you want to just check if a browser is mobile or not, or do some other one simple check based on user agent string, if you know what you're doing,
there is no need to use any advanced scripts.

However, `UserAgentInfo` delivers a very good average performance (limited by the performance of your cache system) while reliably providing
as much information about the user as possible.

Switching to `UserAgentInfo` gives you many interesting opportunities you might have not thought about before.

An example:

By using `UserAgentInfoPeer::getMy()->isBot()` to completely disable session for all bots you can speed up your website and save a huge amount of disk operations.
That's because bots (in general) do not use cookies and thus PHP will, by default, create a new session for each bot call that is made to your website.
So it's entirely possible that more than 90% of your current sessions come from bot calls, and will never be used. 


Relation to other projects
----------------------------------
UserAgentInfo relies on multiple other projects to get its user agent information. Thanks to that it offers detection better than any other project that relies on its own parser, or only a single external parser.

The used projects are:

- browscap (bc) - http://tempdownloads.browserscap.com/ - browscap contains a huge database of incredibly detailed specific user agents information but it sucks with newer user agents and sucks even more for mobile detection.

- Mobile_Detect (md) - https://github.com/serbanghita/Mobile-Detect - it detects mobile device types with very high precision.

- ua-parser (uap) with data from BrowserScope - https://github.com/tobie/ua-parser - provides good generic information about all types of browsers so it's an excellent addition to find information about things browscap does not detect.

- Some information is generated directly in UserAgentInfo. Currently those are two things:
  - Additional user agents identified in browscap format (see BrowscapWrapper class).
  -  Browser and operating system architecture information (see self::parseArchitecture()).

Todo list
---------------------------
- update source parsers once a week
- browscap removed `isBanned` property - fix it by adding `isBanned` from older browscap versions
- add tests
- add more example classes to `cache/` (memcache, memcached, APC)
- should browscap be moved fully to PHP as in https://github.com/garetjax/phpbrowscap ?
  - It would make sense to get rid of the php.ini setting requirement and just be able to fully control what data is served from browscap.
  - If I'm gonna parse browscap.ini I should merge identical entries with just version changed - I'm gonna match using pregs anyway so there is
  - no need to have 20 entries instead of 1.
- should I standardize OS name and move Windows version to ->version?
- should device family be changed to device manufacturer and version to name (same as in full browscap)?
- request to add version number to browscap get_browser()
- request to add version number to uaparser json file
- test full browscap file and see if it makes sense to use it, if so, add it to device detection and add 'rendering engine' property
- see which PHP version is required to run the script. PHP 5.0 would be the best, there is no need to push for 5.3. However, right now it may not be compatible with older PHP versions, as it was created on PHP 5.4.
- Internet Explorer vs. Chrome Frame
- Add batch retrieval from cache (batch save could also be implemented, but that seems kinda weird... although... O.o)
