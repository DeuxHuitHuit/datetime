<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!--
	DATE AND TIME
	
	- Author: Nils Hörrmann <http://nilshoerrmann.de> based on a utility by Allen Chang <http://chaoticpattern.com>
	- See: <http://symphony-cms.com/download/xslt-utilities/view/20506/>
	- Version: 2.1
	- Release date: 13th December 2011
	
	# Example usage
	
		<xsl:call-template name="datetime">
			<xsl:with-param name="date" select="date" />
		</xsl:call-template>	
	
	# Required Parameters:

	- date:              Expects a Symphony date node (as provided by the core date field or the Date and Time extension)
	
	# Optional Parameters:
	
	- time:              Takes an time string (12:30), defaults to $date/@time
	- weekday:           Takes a number (monday = 1, tuesday = 2 ...), defaults to $date/@weekday
	- format:            Takes a format string with the following options:
	
	                     Y: Year in 4 digits, e. g. 1981, 1992, 2008
	                     y: Year in 2 digits, e. g. 81, 92, 08
	                     M: Month as a full word, e. g. January, March, September
	                     m: Month in 3 letters, e. g. Jan, Mar, Sep
	                     N: Month in digits without leading zero
	                     n: Month in digits with leading zero
	                     D: Day with suffix and no leading zero, e. g. 1st, 23rd
	                     d: Day in digits with leading zero, e. g. 01, 09, 12, 25
	                     x: Day in digits with no leading zero, e. g. 1, 9, 12, 25
	                     T: Time in 24-hours, e. g. 18:30
	                     h: Time in 24-hours with no leading zero, e. g. 4:25
	                     t: Time in 12-hours, e. g. 6:30pm
	                     W: Weekday as a full word, e. g. Monday, Tuesday
	                     w: Weekday in 3 letters, e. g. Mon, Tue, Wed
	                     rfc2822: RFC 2822 formatted date
	                     iso8601: ISO 8601 formatted date
				
	- lang:              Takes a language name, e. g. 'en', 'de'

	# Special characters
	
	- A backslash used in date formats escapes the following character.
	- An underscore represents a non-breakable space.
	
	# Month names and weekdays
	
	For month names and weekdays a special data source, data.datetime.php, is needed. It is bundled with the Date and Time extension, see: <https://github.com/nilshoerrmann/datetime/blob/master/data-sources/data.datetime.php>
	
	# Change log
	
	## Version 2.1
	
	- Bug fixes
	
	## Version 2.0
	
	- Simplified templates
	- Renamed main template from `format-date` to `datetime` for consistency reasons within the kit
	- Removed timezone for now (this should be reintroduced as full timezone support)
	
	## Version 1.1
	
	- A few cosmetic updates
	
	## Version 1.0
	
	- Initial release
	
-->

<!-- 
	DEPRECATED TEMPLATES
-->

<!-- Please use `<xsl:call-template name="datetime" /> instead -->
<xsl:template name="format-date">
	<xsl:param name="date" />
	<xsl:param name="format" select="'m D, Y'" />
	<xsl:param name="lang" select="'en'" />
	<xsl:call-template name="datetime">
		<xsl:with-param name="date" select="$date" />
		<xsl:with-param name="format" select="$format" />
		<xsl:with-param name="lang" select="$lang" />
	</xsl:call-template>
</xsl:template>

<!-- Please use `<xsl:call-template name="datetime" /> instead -->
<xsl:template name="formatiere-datum">
	<xsl:param name="datum" />
	<xsl:param name="format" select="'x. M Y'" />
	<xsl:param name="sprache" select="'de'" />
	<xsl:call-template name="datetime">
		<xsl:with-param name="date" select="$datum" />
		<xsl:with-param name="format" select="$format" />
		<xsl:with-param name="lang" select="$sprache" />
	</xsl:call-template>
</xsl:template>

<!--
	Date and time parser
-->

<!-- Parse Symphony date node -->
<xsl:template name="datetime">
	<xsl:param name="date" />
	<xsl:param name="format" select="'m D, Y'" />
	<xsl:param name="lang" select="'en'" />
	
	<!-- Parse date -->
	<xsl:variable name="year" select="substring($date, 1, 4)" />
	<xsl:variable name="month" select="substring($date, 6, 2)" />
	<xsl:variable name="day" select="substring($date, 9, 2)" />

	<!-- Format date -->
	<xsl:call-template name="datetime-formatter">
		<xsl:with-param name="year" select="$year" />
		<xsl:with-param name="month" select="$month" />
		<xsl:with-param name="day" select="$day" />
		<xsl:with-param name="time" select="$date/@time" />
		<xsl:with-param name="weekday" select="$date/@weekday" />
		<xsl:with-param name="format" select="$format" />
		<xsl:with-param name="lang" select="$lang" />
	</xsl:call-template>
</xsl:template>

<!-- Parse Twitter date string -->
<xsl:template name="datetime-twitter">
	<xsl:param name="date" />
	<xsl:param name="format" select="'m D, Y'" />
	<xsl:param name="lang" select="'en'" />

	<!-- Parse date -->
	<xsl:variable name="year" select="substring($date, 27, 4)" />
	<xsl:variable name="month">
		<xsl:if test="/data/datetime/language[@id = 'en']/months/month[@abbr = substring($date, 5, 3)]/@id &lt; 10">0</xsl:if>
		<xsl:value-of select="/data/datetime/language[@id = 'en']/months/month[@abbr = substring($date, 5, 3)]/@id" />
	</xsl:variable>
	<xsl:variable name="day" select="substring($date, 9, 2)" />
	<xsl:variable name="time" select="substring($date, 12, 5)" />
	<xsl:variable name="weekday" select="/data/datetime/language[@id = 'en']/weekdays/weekday[@abbr = substring($date, 1, 3)]/@id" />
	
	<!-- Format date -->
	<xsl:call-template name="datetime-formatter">
		<xsl:with-param name="year" select="$year" />
		<xsl:with-param name="month" select="$month" />
		<xsl:with-param name="day" select="$day" />
		<xsl:with-param name="time" select="$time" />
		<xsl:with-param name="weekday" select="$weekday" />
		<xsl:with-param name="format" select="$format" />
		<xsl:with-param name="lang" select="$lang" />
	</xsl:call-template>
</xsl:template>

<!-- Parse ISO 8601 date string -->
<xsl:template name="datetime-iso8601">
	<xsl:param name="date" />
	<xsl:param name="format" select="'m D, Y'" />
	<xsl:param name="lang" select="'en'" />

	<!-- Parse date -->
	<xsl:variable name="year" select="substring($date, 1, 4)" />
	<xsl:variable name="month" select="substring($date, 6, 2)" />
	<xsl:variable name="day" select="substring($date, 9, 2)" />
	<xsl:variable name="time" select="substring($date, 12, 5)" />
	
	<!-- Format date -->
	<xsl:call-template name="datetime-formatter">
		<xsl:with-param name="year" select="$year" />
		<xsl:with-param name="month" select="$month" />
		<xsl:with-param name="day" select="$day" />
		<xsl:with-param name="time" select="$time" />
		<xsl:with-param name="format" select="$format" />
		<xsl:with-param name="lang" select="$lang" />
	</xsl:call-template>
</xsl:template>

<!--
	Date and time formatter
-->
<xsl:template name="datetime-formatter">
	<xsl:param name="year" />
	<xsl:param name="month" />
	<xsl:param name="day" />
	<xsl:param name="time" />
	<xsl:param name="weekday" />
	<xsl:param name="format" />
	<xsl:param name="lang" select="'en'" />
	
	<!-- Get format -->
	<xsl:variable name="datetime-format">
		<xsl:choose>

			<!-- RFC 2822: Thu, 17 Jul 1980 17:59:00 +0100 -->
			<xsl:when test="$format = 'rfc2822' ">w, d m Y T:00 -0000</xsl:when>
			
			<!-- ISO 8601: 1980-07-17T17:59:00+01:00 -->
			<xsl:when test="$format = 'iso8601' ">Y-n-d\TT:00-00:00</xsl:when>
			
			<!-- Custom date format -->
			<xsl:otherwise>
				<xsl:value-of select="$format"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<!-- Format date -->
	<xsl:call-template name="datetime-processor">
		<xsl:with-param name="year" select="$year" />
		<xsl:with-param name="month" select="$month" />
		<xsl:with-param name="day" select="$day" />
		<xsl:with-param name="time" select="$time" />
		<xsl:with-param name="weekday" select="$weekday" />
		<xsl:with-param name="format" select="$datetime-format" />
		<xsl:with-param name="lang" select="$lang" />
	</xsl:call-template>
</xsl:template>

<!--
	Date and time processor
-->
<xsl:template name="datetime-processor">
	<xsl:param name="year" />
	<xsl:param name="month" />
	<xsl:param name="day" />
	<xsl:param name="time" />
	<xsl:param name="weekday" />
	<xsl:param name="format" />
	<xsl:param name="lang" />
	
	<xsl:variable name="string" select="substring($format, 1, 1)" />

	<!-- Process date -->
	<xsl:choose>
	
		<!-- Year in 4 digits, e. g. 1981, 1992, 2008 -->
		<xsl:when test="$string = 'Y'">
			<xsl:value-of select="$year" />
		</xsl:when>
		
		<!-- Year in 2 digits, e. g. 81, 92, 08 -->
		<xsl:when test="$string = 'y'">
			<xsl:value-of select="substring($year, 3)" />
		</xsl:when>
		
		<!-- Month as a full word, e. g. January, March, September -->
		<xsl:when test="$string = 'M'">
			<xsl:value-of select="/data/datetime/language[@id = $lang]/months/month[@id = number($month)]" />
		</xsl:when>
		
		<!-- Month in 3 letters, e. g. Jan, Mar, Sep -->
		<xsl:when test="$string = 'm'">
			<xsl:value-of select="/data/datetime/language[@id = $lang]/months/month[@id = number($month)]/@abbr" />
		</xsl:when>
		
		<!-- Month in digits without leading zero -->
		<xsl:when test="$string = 'N'">
			<xsl:value-of select="format-number($month, '#0')"/>
		</xsl:when>
		
		<!-- Month in digits with leading zero -->
		<xsl:when test="$string = 'n'">
			<xsl:value-of select="format-number($month, '00')"/>
		</xsl:when>

		<!-- Day with suffix and no leading zero, e. g. 1st, 23rd -->
		<xsl:when test="$string = 'D'">
			<xsl:value-of select="format-number($day, '#0')" />
			<sup>
				<xsl:choose>
					<xsl:when test="(substring($day, 2) = 1) and not(substring($day, 1, 1) = 1)">st</xsl:when>
					<xsl:when test="(substring($day, 2) = 2) and not(substring($day, 1, 1) = 1)">nd</xsl:when>
					<xsl:when test="(substring($day, 2) = 3) and not(substring($day, 1, 1) = 1)">rd</xsl:when>
					<xsl:otherwise>th</xsl:otherwise>
				</xsl:choose>
			</sup>
		</xsl:when>
		
		<!-- Day in digits with leading zero, e. g. 01, 09, 12, 25 -->
		<xsl:when test="$string = 'd'">
			<xsl:value-of select="format-number($day, '00')" />
		</xsl:when>
		
		<!-- Day in digits with no leading zero, e. g. 1, 9, 12, 25 -->
		<xsl:when test="$string = 'x'">
			<xsl:value-of select="format-number($day, '#0')" />
		</xsl:when>
		
		<!-- Time in 24-hours, e. g. 18:30 -->
		<xsl:when test="$string = 'T'">
			<xsl:value-of select="$time" />
		</xsl:when>
		
		<!-- Time in 12-hours, e. g. 6:30pm -->
		<xsl:when test="$string = 't'">
			<xsl:variable name="hour" select="substring($time, 1, 2)" />
			<xsl:variable name="minutes" select="substring($time, 4, 2)" />
			<xsl:choose>
				<xsl:when test="$hour mod 12 = 0">12</xsl:when>
				<xsl:otherwise><xsl:value-of select="($hour mod 12)" /></xsl:otherwise>
			</xsl:choose>
			<xsl:value-of select="concat(':', $minutes)" />
			<xsl:choose>
				<xsl:when test="$hour &lt; 12">am</xsl:when>
				<xsl:otherwise>pm</xsl:otherwise>
			</xsl:choose>
		</xsl:when>
		
		<!-- Time in 24-hours with no leading zero, e. g. 4:25 -->
		<xsl:when test="$string = 'h'">
			<xsl:value-of select="format-number(substring-before($time, ':'), '#0')" />
			<xsl:value-of select="substring($time, 3)" />
		</xsl:when>
		
		<!-- Weekday as a full word, e. g. Monday, Tuesday -->
		<xsl:when test="$string = 'W'">
			<xsl:value-of select="/data/datetime/language[@id = $lang]/weekdays/day[@id = $weekday]" />
		</xsl:when>
		
		<!-- Weekday in 3 letters, e. g. Mon, Tue, Wed -->
		<xsl:when test="$string = 'w'">
			<xsl:value-of select="/data/datetime/language[@id = $lang]/weekdays/day[@id = $weekday]/@abbr" />
		</xsl:when>
		
		<!-- Non-breaking space -->
		<xsl:when test="$string = '_'">
			<xsl:text>&#160;</xsl:text>
		</xsl:when>
		
		<!-- Escaped letter -->
		<xsl:when test="$string = '\'">
			<xsl:value-of select="substring($format, 2, 1)" />
		</xsl:when>
		
		<!-- Letter -->
		<xsl:otherwise>
			<xsl:value-of select="$string" />
		</xsl:otherwise>
	</xsl:choose>
	
	<!-- Get offset -->
	<xsl:variable name="offset">
		<xsl:choose>
			<xsl:when test="$string = '\'">3</xsl:when>
			<xsl:otherwise>2</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<!-- Loop -->
	<xsl:if test="$string != ''">
		<xsl:call-template name="datetime-processor">
			<xsl:with-param name="year" select="$year" />
			<xsl:with-param name="month" select="$month" />
			<xsl:with-param name="day" select="$day" />
			<xsl:with-param name="time" select="$time" />
			<xsl:with-param name="weekday" select="$weekday" />
			<xsl:with-param name="format" select="substring($format, $offset)" />
			<xsl:with-param name="lang" select="$lang" />
		</xsl:call-template>
	</xsl:if>
</xsl:template>


</xsl:stylesheet>
