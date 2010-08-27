# Date and Time

A field for Symphony managing single or multiple dates as well as date ranges.

- Version: 1.4
- Date: 27th August 2010
- Requirements: Symphony CMS 2.0.6 or newer, <http://github.com/symphony/symphony-2/tree/master>
- Author: Nils Hörrmann, post@nilshoerrmann.de
- Constributors: [A list of contributors can be found in the commit history](http://github.com/nilshoerrmann/datetime/commits/master)
- GitHub Repository: <http://github.com/nilshoerrmann/datetime>

This extension is based on and inspired by Scott Hughes' [calendar mock-up](http://symphony-cms.com/community/discussions/103/) and Rowan Lewis' [calendar overlay](http://github.com/rowan-lewis/calendaroverlay/). It uses [dateJS](http://www.datejs.com/) for date calculations.

## Please note

Using this extension in conjunction with the **Localisation Manager** and setting the language of an author to something else but system standard **may break the Date and Time field**. For some reasons yet to be fully understood the calendar will show all dates as 01 January 1970. _Changing the author’s language back to system standard will fix this issue._

## Change log

**Version 1.4**

- Added Italian translation, improved date localisation. (Thanks, Simone!)

**Version 1.3**

- Added German translation, improved date handling.
- Fixed a lot of bugs and overall improvements. (Thanks, Brendan!)

**Version 1.2**

- Added support for data source grouping (calendar view).

**Version 1.1**

- Added support for data source filtering.

**Version 1.0**

- Initial release.
