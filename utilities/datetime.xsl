<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">


<!--
	DATE AND TIME
	An XSL template to format dates and times.
	
	Written by Nils Hörrmann, http://www.nilshoerrmann.de.
	Based on Allen Chang's date and time utility for Symphony, http://symphony-cms.com/download/xslt-utilities/view/20506/.

	$date     - [required] takes an ISO date (2005-12-01)
	$time     - [optional] takes an time string (12:30), defaults to $date/@time
	$weekday  - [optional] takes a number (monday = 1, tuesday = 2 ...), defaults to $date/@weekday
	$format   - [optional] takes a format string with the following options:
	
				'Y': year in 4 digits, e. g. 1981, 1992, 2008
				'y': year in 2 digits, e. g. 81, 92, 08
				'M': month as a full word, e. g. January, March, September
				'm': month in 3 letters, e. g. Jan, Mar, Sep
				'N': month in digits without leading zero
				'n': month in digits with leading zero
				'D': day with suffix and no leading zero, e. g. 1st, 23rd
				'd': day in digits with leading zero, e. g. 01, 09, 12, 25
				'x': day in digits with no leading zero, e. g. 1, 9, 12, 25
				'T': time in 24-hours, e. g. 18:30
				'h': time in 24-hours with no leading zero, e. g. 4:25
				't': time in 12-hours, e. g. 6:30pm
				'W': weekday as a full word, e. g. Monday, Tuesday
				'w': weekday in 3 letters, e. g. Mon, Tue, Wed
				'rfc2822': returns a RFC 2822 formatted date
				'iso8601': returns a ISO 8601 formatted date
				
	$lang     - [optional] takes a language name, e. g. 'en', 'de'
	$timezone - [optional] takes a timezone offset, e. g. '+0200', defaults to $date/@offset

	A backslash used in date formats escapes the following character.
	For month names and weekdays a special data source, data.datetime.php, is needed. 
-->
<xsl:template name="format-date">
	<xsl:param name="date" />
	<xsl:param name="time" select="$date/@time" />
	<xsl:param name="weekday" select="$date/@weekday" />
	<xsl:param name="format" select="'m D, Y'" />
	<xsl:param name="lang" select="'en'" />
	<xsl:param name="timezone" select="$date/@offset" />
	<xsl:call-template name="date-formatter">
		<xsl:with-param name="date" select="$date" />
		<xsl:with-param name="time" select="$time" />
		<xsl:with-param name="weekday" select="$weekday" />
		<xsl:with-param name="format" select="$format" />
		<xsl:with-param name="lang" select="$lang" />
		<xsl:with-param name="timezone" select="$timezone" />
	</xsl:call-template>
</xsl:template>


<!--
	GERMAN VARIANT
	German equivalent to the main format-date template	
-->
<xsl:template name="formatiere-datum">
	<xsl:param name="datum" />
	<xsl:param name="zeit" select="$datum/@time" />
	<xsl:param name="wochentag" select="$datum/@weekday" />
	<xsl:param name="format" select="'x. M Y'" />
	<xsl:param name="sprache" select="'de'" />
	<xsl:param name="zeitzone" select="$datum/@offset" />
	<xsl:call-template name="date-formatter">
		<xsl:with-param name="date" select="$datum" />
		<xsl:with-param name="time" select="$zeit" />
		<xsl:with-param name="weekday" select="$wochentag" />
		<xsl:with-param name="format" select="$format" />
		<xsl:with-param name="lang" select="$sprache" />
		<xsl:with-param name="timezone" select="$zeitzone" />
	</xsl:call-template>
</xsl:template>


<!--
	DATE FORMATTER
	Prepares date for formatting
-->
<xsl:template name="date-formatter">
	<xsl:param name="date" />
	<xsl:param name="time" />
	<xsl:param name="weekday" />
	<xsl:param name="format" />
	<xsl:param name="timezone" />
	<xsl:param name="lang" select="'en'" />
	<xsl:choose>
		<xsl:when test="$format = 'rfc2822' ">
			<xsl:call-template name="format-rfc2822">
				<xsl:with-param name="date" select="$date" />
				<xsl:with-param name="time" select="$time" />
				<xsl:with-param name="weekday" select="$weekday" />
				<xsl:with-param name="timezone" select="$timezone" />
			</xsl:call-template>
		</xsl:when>
		<xsl:when test="$format = 'iso8601' ">
			<xsl:call-template name="format-iso8601">
				<xsl:with-param name="date" select="$date" />
				<xsl:with-param name="time" select="$time" />
				<xsl:with-param name="timezone" select="$timezone" />
			</xsl:call-template>
		</xsl:when>
		<xsl:when test="string-length($format) &lt;= 20">
			<xsl:call-template name="date-controller">
				<xsl:with-param name="date" select="$date" />
				<xsl:with-param name="time" select="$time" />
				<xsl:with-param name="weekday" select="$weekday" />
				<xsl:with-param name="format" select="$format" />
				<xsl:with-param name="lang" select="$lang" />
			</xsl:call-template>
		</xsl:when>
		<xsl:otherwise>
			<xsl:call-template name="lang-error">
				<xsl:with-param name="lang" select="$lang" />
				<xsl:with-param name="code" select="'1'" />
			</xsl:call-template>	
			<xsl:value-of select="string-length($format)" />
			<xsl:text>.</xsl:text>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>


<!--
	DATE CONTROLLER
	Executes date formatting
-->
<xsl:template name="date-controller">
	<xsl:param name="date" />
	<xsl:param name="time" />
	<xsl:param name="weekday" />
	<xsl:param name="format" />
	<xsl:param name="lang" />
	<xsl:param name="letter" select="substring($format, 1, 1)" />
	<xsl:param name="tletter" select="translate($letter, 'DMNTWY', 'dmntwy')" />

	<xsl:choose>
		<xsl:when test="$tletter = 'y'">
			<xsl:call-template name="format-year">
				<xsl:with-param name="date" select="$date" />
				<xsl:with-param name="format" select="$letter" />
			</xsl:call-template>
		</xsl:when>
		<xsl:when test="$tletter = 'm'">
			<xsl:call-template name="format-month">
				<xsl:with-param name="date" select="$date" />
				<xsl:with-param name="format" select="$letter" />
				<xsl:with-param name="lang" select="$lang" />
			</xsl:call-template>
		</xsl:when>
		<xsl:when test="$tletter = 'n'">
			<xsl:call-template name="format-month">
				<xsl:with-param name="date" select="$date" />
				<xsl:with-param name="format" select="$letter" />
				<xsl:with-param name="lang" select="$lang" />
			</xsl:call-template>
		</xsl:when>
		<xsl:when test="$tletter = 'd'">
			<xsl:call-template name="format-day">
				<xsl:with-param name="date" select="$date" />
				<xsl:with-param name="format" select="$letter" />
			</xsl:call-template>
		</xsl:when>
		<xsl:when test="$tletter = 'x'">
			<xsl:call-template name="format-day">
				<xsl:with-param name="date" select="$date" />
				<xsl:with-param name="format" select="$letter" />
			</xsl:call-template>
		</xsl:when>
		<xsl:when test="$tletter = 't'">
			<xsl:call-template name="format-time">
				<xsl:with-param name="time" select="$time" />
				<xsl:with-param name="format" select="$letter" />
			</xsl:call-template>
		</xsl:when>
		<xsl:when test="$tletter = 'h'">
			<xsl:call-template name="format-time">
				<xsl:with-param name="time" select="$time" />
				<xsl:with-param name="format" select="$letter" />
			</xsl:call-template>
		</xsl:when>
		<xsl:when test="$tletter = 'w'">
			<xsl:call-template name="format-weekday">
				<xsl:with-param name="weekday" select="$weekday" />
				<xsl:with-param name="format" select="$letter" />
				<xsl:with-param name="lang" select="$lang" />
			</xsl:call-template>
		</xsl:when>
		<xsl:when test="$tletter = '\'">
			<xsl:value-of select="substring($format, 2, 1)" />
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="$letter" />
		</xsl:otherwise>
	</xsl:choose>
	
	<xsl:variable name="offset">
		<xsl:choose>
			<xsl:when test="$tletter = '\'">3</xsl:when>
			<xsl:otherwise>2</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<xsl:if test="$letter = not('')">
		<xsl:call-template name="date-controller">
			<xsl:with-param name="date" select="$date" />
			<xsl:with-param name="time" select="$time" />
			<xsl:with-param name="weekday" select="$weekday" />
			<xsl:with-param name="format" select="substring($format, $offset)" />
			<xsl:with-param name="timezone" select="$timezone" />
			<xsl:with-param name="lang" select="$lang" />
		</xsl:call-template>
	</xsl:if>

</xsl:template>


<!--
	FORMAT YEAR
	Returns formatted year
	
	$date    - date, e. g. '2010-07-17'
	$format  - format:
	           'y': two digit representation of the year
	           'Y': full numeric representation of the year
-->
<xsl:template name="format-year">
	<xsl:param name="date" />
	<xsl:param name="format" select="'y'" />
	<xsl:param name="year" select="substring($date, 1, 4)" />
	<xsl:choose>
		<xsl:when test="$format = 'y' and $year != ''">
			<xsl:value-of select="substring($year, 3)" />
		</xsl:when>
		<xsl:when test="$format = 'Y' and $year != ''">
			<xsl:value-of select="$year" />
		</xsl:when>
		<xsl:otherwise>Unknown Year</xsl:otherwise>
	</xsl:choose>
</xsl:template>


<!--
	FORMAT MONTH	
	Returns formatted month as string or number
	
	$date    - date, e. g. '2010-07-17'
	$format  - format:
	           'm': abbreviated name
	           'M': full name
	           'n': number with leading zero
	           'N': number without leading zero
	$lang    - language, e. g. 'en', 'de'
-->
<xsl:template name="format-month">
	<xsl:param name="date" />
	<xsl:param name="month" select="format-number(substring($date, 6, 2), '##')" />
	<xsl:param name="format" select="'m'" />
	<xsl:param name="lang" select="'en'" />
	<xsl:param name="name" select="/data/datetime/language[@id = $lang]/months/month[@id = $month]" />
	<xsl:choose>
		<xsl:when test="$format = 'm' and $name">
			<xsl:value-of select="$name/@abbr" />
		</xsl:when>
		<xsl:when test="$format = 'M' and $name">
			<xsl:value-of select="$name" />
		</xsl:when>
		<xsl:when test="$format = 'n'">
			<xsl:value-of select="format-number($month, '00')" />
		</xsl:when>
		<xsl:when test="$format = 'N'">
			<xsl:value-of select="format-number($month, '0')" />
		</xsl:when>
		<xsl:otherwise>Unknown Month</xsl:otherwise>
	</xsl:choose>
</xsl:template>


<!--
	FORMAT DAY
	Returns formatted day as number
	
	$date    - date, e. g. '2010-07-17'
	$format  - format:
	           'd': day number with leading zero
	           'x': day number without leading zero
	           'D': day number with suffix and without leading zero
-->
<xsl:template name="format-day">
	<xsl:param name="date" />
	<xsl:param name="format" select="'d'" />
	<xsl:param name="day" select="format-number(substring($date, 9, 2),'00')" />
	<xsl:choose>
		<xsl:when test="$format = 'd'">
			<xsl:value-of select="$day" />
		</xsl:when>
		<xsl:when test="$format = 'x'">
			<xsl:value-of select="format-number($day, '0')" />
		</xsl:when>
		<xsl:when test="$format = 'D'">
			<xsl:value-of select="format-number($day, '0')" />
			<sup>
				<xsl:choose>
					<xsl:when test="(substring($day, 2) = 1) and not(substring($day, 1, 1) = 1)">st</xsl:when>
					<xsl:when test="(substring($day, 2) = 2) and not(substring($day, 1, 1) = 1)">nd</xsl:when>
					<xsl:when test="(substring($day, 2) = 3) and not(substring($day, 1, 1) = 1)">rd</xsl:when>
					<xsl:otherwise>th</xsl:otherwise>
				</xsl:choose>
			</sup>
		</xsl:when>
	</xsl:choose>
</xsl:template>


<!--
	FORMAT TIME
	Returns formatted time
	
	$time    - time, e. g. '17:59'
	$format  - format:
	           'T': time in 24-hour format with leading zero
	           'h': time in 24-hour format without leading zero
	           't': time in 12-hour format with lowercase ante meridiem and post meridiem
-->
<xsl:template name="format-time">
	<xsl:param name="time" />
	<xsl:param name="hour" select="substring-before($time, ':')" />
	<xsl:param name="minute" select="substring-after($time, ':')" />
	<xsl:param name="format" select="'T'" />
	<xsl:choose>
		<xsl:when test="$format = 'T'">
			<xsl:value-of select="$time" />
		</xsl:when>
		<xsl:when test="$format = 'h'">
			<xsl:choose>
				<xsl:when test="starts-with($time, '0')">
					<xsl:value-of select="substring($time, 2)" />		
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$time" />		
				</xsl:otherwise>
			</xsl:choose>
		</xsl:when>
		<xsl:when test="$format = 't'">
			<xsl:choose>
				<xsl:when test="$hour mod 12 = 0">12</xsl:when>
				<xsl:otherwise><xsl:value-of select="($hour mod 12)" /></xsl:otherwise>
			</xsl:choose>
			<xsl:value-of select="concat(':',$minute)" />
			<xsl:choose>
				<xsl:when test="$hour &lt; 12">am</xsl:when>
				<xsl:otherwise>pm</xsl:otherwise>
			</xsl:choose>
		</xsl:when>
	</xsl:choose>
</xsl:template>


<!--
	FORMAT WEEKDAY
	Returns formatted weekday as string
	
	$weekday - weekday as number, e. g. 1 (Monday), 2 (Tuesday), ...
	$format  - format:
	           'w': abbreviated weekday name
	           'W': weekday name
-->
<xsl:template name="format-weekday">
	<xsl:param name="weekday" />
	<xsl:param name="format" select="'w'" />
	<xsl:param name="lang" select="'en'" />
	<xsl:param name="name" select="/data/datetime/language[@id = $lang]/weekdays/day[@id = $weekday]" />
	<xsl:choose>
		<xsl:when test="$format = 'w' and $name != ''">
			<xsl:value-of select="$name/@abbr" />
		</xsl:when>
		<xsl:when test="$format = 'W' and $name != ''">
			<xsl:value-of select="$name" />
		</xsl:when>
		<xsl:otherwise>Unknown Weekday</xsl:otherwise>
	</xsl:choose>
</xsl:template>


<!--
	FORMAT RFC2822
	Returns formatted date as string using RFC2822 format
	(e. g. Mon, 21 Sep 1981 18:30:00 +0100)
	
	$date     - date, e. g. 2010-07-17
	$time     - time, e. g. 17:59
	$weekday  - weekday, e. g. 0 (Sunday), 1 (Monday)
	$timezone - timezone
-->
<xsl:template name="format-rfc2822">
	<xsl:param name="date" />
	<xsl:param name="time" />
	<xsl:param name="weekday" />
	<xsl:param name="timezone" />
	<xsl:call-template name="date-formatter">
		<xsl:with-param name="date" select="$date" />
		<xsl:with-param name="time" select="$time" />
		<xsl:with-param name="weekday" select="$weekday" />
		<xsl:with-param name="format" select="'w, d m Y'" />
		<xsl:with-param name="lang" select="'en'" />
	</xsl:call-template>
	<xsl:value-of select="concat(' ', $time, ':00 ', substring-before($timezone, ':'), substring-after($timezone, ':'))" />
</xsl:template>


<!--
	FORMAT RFC2822
	Returns formatted date as string using ISO8601 format
	(e. g. 1981-09-21T18:30:00+01:00)
	
	$date     - date, e. g. 2010-07-17
	$time     - time, e. g. 17:59
	$timezone - timezone
-->
<xsl:template name="format-iso8601">
	<xsl:param name="date" />
	<xsl:param name="time" />
	<xsl:param name="timezone" />
	
	<xsl:value-of select="$date" />
	<xsl:text>T</xsl:text>
	<xsl:value-of select="substring-before($time, ':')" />
	<xsl:text>:</xsl:text>
	<xsl:value-of select="substring-after($time, ':')" />
	<xsl:text>:00</xsl:text>
	<xsl:value-of select="substring($timezone, 1, 3)" />
	<xsl:text>:</xsl:text>
	<xsl:value-of select="substring-after($timezone, 4, 2)" />
</xsl:template>


</xsl:stylesheet>