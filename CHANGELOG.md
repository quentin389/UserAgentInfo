See **[readme](README.md)** for important information about this project.

UserAgentInfo uses other project to get the data it needs.
See **[list of those projects](README.md#relation-to-other-projects)**.

Changelog
=========

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
