See **[readme](README.md)** for important information about this project.

UserAgentInfo uses other project to get the data it needs.
See **[list of those projects](README.md#relation-to-other-projects)**.

Changelog
=========

### version 1.3 - September 1, 2013
- Migrated from `get_browser()` to `phpbrowscap` class:
  * It's much faster than `get_browser()`.
  * It's standalone / php configuration independent - doesn't require php.ini setting.
  * It leverages opcache to work even faster.
  * It fixes several user agent match errors in `get_browser()`.
- Restored browscap source file version checking (was temporarily removed in version 1.2)
- Added cache wrappers for `memcache`, `memcached` and `APC` (pull request [13](https://github.com/quentin389/UserAgentInfo/pull/13) from [Ignas Bernotas](https://github.com/ignasbernotas))
- Updated Mobile_Detect.php from version 2.6.6 to 2.6.9
  * New detection rules.
  * Some methods and properties are now static.
  * Bug fix: existing headers do not persist when passing new headers array. (not relevant)
- Removed fix for Mobile_Detect giving '_' in version numbers. (when did that get fixed?)
- Moved `BrowscapWrapper.class.php` to `imports` folder. It makes more sense to have it there, even though it's not strictly an external class.
- Removed some user agents from the custom list in `BrowscapWrapper` as they are now identified correctly.
- Removed a few user agent matches from the custom list of which I wasn't sure if they should be banned or not. 

### version 1.2 - August 17, 2013
- Change in browscap.ini version 5020 to temporarily fix a bug with parsing user agents with "+" (plus) sign in the match string. See: https://groups.google.com/forum/#!topic/browscap/s9zGyRBIvK0
  This also allowed me to remove some user agent overrides from BrowscapWrapper.class.php
- Added `require_once` for required files.
- Removed timers from the code.
- Changed getting the user agent string to just calling `$_SERVER['HTTP_USER_AGENT']`.
- Added UserAgentInfoConfig class for easier configuration.
- Changed the cache classes for easier configuration.
- Updated readme file to reflect the changes above, and added more explanation about `->isBanned()`.
- Temporary fix for issue https://github.com/quentin389/UserAgentInfo/issues/2

### version 1.1.2 - August 12, 2013
- fix for **critical** bug introduced in verson 1.1 (`UserAgentInfoPeer::getMy()` was checking empty user agent string, and never returning any useful data)

### version 1.1.1 - August 10, 2013
A small update to detection rules.
- updated regexes.json in ua-parser
- updated BrowscapWrapper rules for "Ruby" and "WSCommand"
- removed '@version' string from all but one file

### version 1.1 - August 6, 2013

- Updated Mobile_Detect.php from version 2.6.3 to 2.6.6:
  - New detection rules.
  - Fix for float number comparison.
  - Changes to code style.
- Updated browscap.ini from version 5019 to 5020. This is a first update since March, but it seems that the project is finally getting back on track, so I'm counting on more updates pretty soon.
- "WSCommand" bot was misidentified as a user. Added an exception.
- Removed timers that were not required.
- Moved some operations from `init()` to after we retrieve `UserAgentInfo` object from cache. Thanks to that they may not get executed at all.
- Added performance test results to [README.md](README.md#performance-and-scaling)
