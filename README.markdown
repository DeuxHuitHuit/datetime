# Date and Time 2.2

![Date and Time Field](http://projekte.nilshoerrmann.de/extensions/datetime/datetime.png)

Date and Time provides advanced date and time management for Symphony. It offers an easy interface providing a calendar widget that helps creating multiple dates and date ranges. The field respects the system settings and displays date and time accordingly. Nevertheless, it is capable to read and understand [relative date formats](http://www.php.net/manual/en/datetime.formats.php).

## Installation

Date and Time contains two components:

- The Date and Time field itself which provides the calendar widget,
- [Stage](https://github.com/nilshoerrmann/stage/) which offers the basic interface for adding and ordering dates.

If you are working with Git, please don't forget to pull the Stage submodule as well. If you are not using Git and want to install this extension using FTP, please just download a copy of the `master` branch and add [Stage](https://github.com/nilshoerrmann/stage/) to `lib/stage/` manually. More information about [installing and updating extensions](http://symphony-cms.com/learn/tasks/view/install-an-extension/) can be found in the Symphony documentation at <http://symphony-cms.com/learn/>. 

All interface related components of Date and Time are JavaScript based. If you are upgrading from an earlier version, please make sure to clear your browser cache to avoid interface issues. If another extension or the Symphony core throws a JavaScript error, Date and Time will stop working.

## Updating

To update Date and Time to a newer version perform the following steps:

- Make sure that you have a working backup of your Symphony install.
- Update the Date and Time folder by either updating the repository and submodule or by replacing the files manually.
- Log into the backend and enable the Date and Time extension to run the update script.

## Field Settings

Date and Time offers the following field settings:

- allow creation of new items: this will add a Create New button to the interface
- allow sorting of items: this will enable item dragging and reordering
- allow time editing: this will display date and time in the interface
- allow date ranges: this will enable range editing
- pre-populate this field with today's date: this will automatically add the current date to new entries

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

## Example Data Source Output

    <date-and-time>
        <date timeline="1" type="range">
            <start iso="2011-12-06T10:00:00+01:00" time="10:00" weekday="2" offset="+0100">2011-12-06</start>
            <end iso="2011-12-24T18:00:00+01:00" time="18:00" weekday="6" offset="+0100">2011-12-24</end>
        </date>
        <date timeline="2" type="exact">
            <start iso="2011-12-25T09:00:00+01:00" time="09:00" weekday="7" offset="+0100">2011-12-25</start>
        </date>
    </date-and-time>
            
## Acknowledgement

This extension is not a work of a single person, a lot of people tested it and [contributed to it](https://github.com/nilshoerrmann/datetime/contributors). The initial layout of the date widget was inspired by Scott Hughes' [calendar mock-up for Symphony 2.0](http://symphony-cms.com/discuss/thread/103/) and Rowan Lewis' [calendar overlay](https://github.com/rowan-lewis/calendaroverlay/).
