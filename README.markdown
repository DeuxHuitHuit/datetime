# Date and Time

A field for Symphony managing single or multiple dates as well as date ranges.

- Version: 2.1.1
- Date: 27th July 2011
- Requirements: Symphony CMS 2.2.1 or newer, <http://github.com/symphony/symphony-2/tree/master>
- Author: Nils Hörrmann, post@nilshoerrmann.de
- Constributors: [A list of contributors can be found in the commit history](http://github.com/nilshoerrmann/datetime/commits/master)
- GitHub Repository: <http://github.com/nilshoerrmann/datetime>

## Data Source Filtering

Version 2.0 introduces new filter options in the data source editor:

- prefixing a filter with `start:` will only check start dates,
- prefixing a filter with `end:` will only check end dates,
- prefixing a filter with `strict:` will check, if start **and** end date are in the given filter range,
- filters without prefixes will check, if start **or** end date are in the given filter range.

As of version 2.1 there is an additional filter mode:

- prefixing a filter with `extended:` will work like the unprefixed filter but will additionally fetch single dates (those without end date) that lie in the past. This is helpful if you like to display banners only between certain dates and if the user didn't enter an end date. Using the `extended` filter, this will be interpreted as "display for ever".

Filters separated by comma will find all dates that match one of the given dates or ranges.  
Filters separated by `+` will only find dates that match all of the given dates or ranges.

This extensions accepts all relative dates known to [PHP's DateTime class](http://www.php.net/manual/en/datetime.formats.php) for filtering. It also allows the creation of filter ranges with `to` or `earlier than` and `later than`.

## Release Notes

**Version 2.1.1**

- Safari compatibility for keyboard interactions.

**Version 2.1**

- Added French translation. (Thanks, Nicolas!)
- Added new `extended` filter mode. (Thanks, Nicolas!)
- Access end dates via tab key. (Thanks, Brendan!)

**Version 2.0.1**

- Corrected version number.

**Version 2.0**

- Implemented Stage.
- Implemented new calendar.
- Symphony 2.2 compatibility.
- General code clean-up.
- A lot of tiny bug fixes.

**Version 1.5.1**

- Section Schema compatibility. (Thanks, Brendan!)

**Version 1.5**

- Added Norwegian translation. (Thanks, Frode!)
- Updated data source filtering. (Thanks, John!)

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

## Acknowledgement

This extension is based on and inspired by Scott Hughes' [calendar mock-up](http://symphony-cms.com/community/discussions/103/) and Rowan Lewis' [calendar overlay](http://github.com/rowan-lewis/calendaroverlay/).
