See **[readme](README.md)** for important information about this project.

UserAgentInfo uses other project to get the data it needs.
See **[list of those projects](README.md#relation-to-other-projects)**.

Changelog
=========

### version 1.2 - august 17, 2013
- Change in browscap.ini version 5020 to temporarily fix a bug with parsing user agents with "+" (plus) sign in the match string. See: https://groups.google.com/forum/#!topic/browscap/s9zGyRBIvK0
  This also allowed me to remove some user agent overrides from BrowscapWrapper.class.php
- Added `require_once` for required files
- removed timers from the code
- changed getting the user agent string to just calling `$_SERVER['HTTP_USER_AGENT']`
- added UserAgentInfoConfig class for easier configuration
- changed the cache classes for easier configuration
- updated readme file to reflect the changes above, and added more explanation about `->isBanned()`

### version 1.1.2 - august 12, 2013
- fix for **critical** bug introduced in verson 1.1 (`UserAgentInfoPeer::getMy()` was checking empty user agent string, and never returning any useful data)

### version 1.1.1 - august 10, 2013
A small update to detection rules.
- updated regexes.json in ua-parser
- updated BrowscapWrapper rules for "Ruby" and "WSCommand"
- removed '@version' string from all but one file

### version 1.1 - august 6, 2013

- Updated Mobile_Detect.php from version 2.6.3 to 2.6.6:
  - New detection rules.
  - Fix for float number comparison.
  - Changes to code style.
- Updated browscap.ini from version 5019 to 5020. This is a first update since March, but it seems that the project is finally getting back on track, so I'm counting on more updates pretty soon.
- "WSCommand" bot was misidentified as a user. Added an exception.
- Removed timers that were not required.
- Moved some operations from `init()` to after we retrieve `UserAgentInfo` object from cache. Thanks to that they may not get executed at all.
- Added performance test results to [README.md](README.md#performance-and-scaling)
