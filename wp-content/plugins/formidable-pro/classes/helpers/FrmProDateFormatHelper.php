<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Reads dates whose format is not known in advance.
 *
 * A value arriving from an import or from the REST API was very often written
 * somewhere else, so it cannot be assumed to match the format the site displays
 * dates in. Everything here is about working out how to read such a value
 * without guessing, and it is deliberately kept away from the submission path,
 * where the configured format is still enforced strictly.
 *
 * @since 6.35
 */
class FrmProDateFormatHelper {

	/**
	 * Reads a date string without guessing at the order of its parts.
	 *
	 * Every slash separated date looks American to strtotime(), so on a site
	 * using d/m/Y it turns 26/04/2021 into false, which gmdate() then renders
	 * as 1970-01-01, and it silently turns 05/04/2021 into May 4 instead of
	 * April 5. Both are wrong, and the silent one is worse.
	 *
	 * The formats the site actually uses are tried first, strictly, so a date
	 * written the way the site displays dates is always read correctly. Only
	 * what is left over falls through to strtotime, which keeps values that
	 * were already being read correctly working the same way.
	 *
	 * @since 6.35
	 *
	 * @param mixed  $date_str    Date in any supported format.
	 * @param string $to_format   Format to return the date in.
	 * @param string $from_format Format to try before any other, for a caller
	 *                            that knows the format better than the site
	 *                            settings do. See self::infer_date_format_from_values().
	 *
	 * @return string Empty string when the date cannot be read.
	 */
	public static function parse_date_in_any_format( $date_str, $to_format = 'Y-m-d', $from_format = '' ) {
		if ( ! is_string( $date_str ) ) {
			return '';
		}

		$date_str = trim( $date_str );

		if ( '' === $date_str ) {
			return '';
		}

		$formats = self::get_date_format_separators();

		if ( '' !== $from_format ) {
			$formats = array( $from_format => self::get_format_separators( $from_format ) ) + $formats;
		}

		// Every separator a format contains has to appear in the value, so
		// 26/04/2021 rules out Y-m-d and every textual format without either
		// one being tried.
		$separators = (string) preg_replace( '/[a-z0-9]/i', '', $date_str );

		foreach ( $formats as $format => $required ) {
			if ( ! self::separators_are_present( $required, $separators ) ) {
				continue;
			}

			$date = self::strict_date_from_format( $date_str, $format );

			if ( $date ) {
				return $date->format( $to_format );
			}
		}

		$timestamp = strtotime( $date_str );

		return false === $timestamp ? '' : gmdate( $to_format, $timestamp );
	}

	/**
	 * Checks the value carries every separator a format needs.
	 *
	 * This only rules formats out. A format whose separators are all present
	 * still has to be parsed to know whether it really fits.
	 *
	 * @since 6.35
	 *
	 * @param string $required   Separators the format contains.
	 * @param string $separators Separators found in the value.
	 *
	 * @return bool
	 */
	private static function separators_are_present( $required, $separators ) {
		$length = strlen( $required );

		for ( $i = 0; $i < $length; $i++ ) {
			if ( ! str_contains( $separators, $required[ $i ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns the separators a date format contains, dropping the format
	 * characters themselves and the backslashes that escape a literal letter.
	 *
	 * @since 6.35
	 *
	 * @param string $format Date format.
	 *
	 * @return string
	 */
	private static function get_format_separators( $format ) {
		return (string) preg_replace( '/[a-z\\\\]/i', '', $format );
	}

	/**
	 * The parsing formats with the separators each one needs, worked out once
	 * per set of site settings rather than once per value being read.
	 *
	 * @since 6.35
	 *
	 * @return array Separators indexed by date format.
	 */
	private static function get_date_format_separators() {
		static $cache = array();

		// The site formats are part of the key so a settings change, including
		// one made by a test, is picked up rather than served from the cache.
		$key = FrmProAppHelper::get_settings()->date_format . '|' . get_option( 'date_format' );

		if ( ! isset( $cache[ $key ] ) ) {
			$separators = array();

			foreach ( self::get_date_parsing_formats() as $format ) {
				$separators[ $format ] = self::get_format_separators( $format );
			}

			$cache[ $key ] = $separators;
		}

		return $cache[ $key ];
	}

	/**
	 * Works out the day and month order of a whole column of dates.
	 *
	 * A single date like 05/04/2021 cannot say whether it means April 5 or
	 * May 4, but a column of them usually can: any part above 12 has to be a
	 * day, and that settles the order for every other row in the column. So a
	 * file containing 26/04/2021 tells us the whole column is day first, even
	 * on a site configured the American way.
	 *
	 * The order is only reported when the column proves it. Where every value
	 * is genuinely ambiguous, or the column contradicts itself, an empty string
	 * comes back and the caller falls back to the site's own format.
	 *
	 * @since 6.35
	 *
	 * @param array $values Raw date strings from one column.
	 *
	 * @return string A date format, or an empty string when the column cannot prove one.
	 */
	public static function infer_date_format_from_values( $values ) {
		if ( ! is_array( $values ) ) {
			return '';
		}

		$separator = '';
		$rows      = array();

		foreach ( $values as $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}

			$value = trim( $value );

			// Anything that is not three numbers and one separator, such as a
			// textual month, tells us nothing about the order.
			if ( '' === $value || ! preg_match( '#^(\d{1,4})([/.-])(\d{1,2})\2(\d{1,4})$#', $value, $matches ) ) {
				continue;
			}

			if ( ! $separator ) {
				$separator = $matches[2];
			} elseif ( $separator !== $matches[2] ) {
				// A column mixing separators is not one format.
				return '';
			}

			$rows[] = array( $matches[1], $matches[3], $matches[4] );
		}

		if ( ! $rows ) {
			return '';
		}

		$year_slot = self::find_year_slot( $rows );

		if ( null === $year_slot ) {
			return '';
		}

		$first_slot  = 0 === $year_slot ? 1 : 0;
		$second_slot = 0 === $year_slot ? 2 : 1;
		$year_token  = self::find_year_token( $rows, $year_slot );

		if ( ! $year_token ) {
			return '';
		}

		$first_is_day  = false;
		$second_is_day = false;

		foreach ( $rows as $row ) {
			if ( (int) $row[ $first_slot ] > 12 ) {
				$first_is_day = true;
			}

			if ( (int) $row[ $second_slot ] > 12 ) {
				$second_is_day = true;
			}
		}

		// No value above 12 proves nothing, and a value above 12 in both slots
		// means the column disagrees with itself.
		if ( $first_is_day === $second_is_day ) {
			return '';
		}

		$format  = self::build_date_format( $separator, $year_token, $year_slot, $first_is_day );
		$swapped = self::build_date_format( $separator, $year_token, $year_slot, ! $first_is_day );

		return self::format_beats_alternative( $rows, $separator, $format, $swapped ) ? $format : '';
	}

	/**
	 * Finds which of the three slots holds the year.
	 *
	 * @since 6.35
	 *
	 * @param array $rows Date parts, three per row.
	 *
	 * @return int|null Null when the rows disagree about where the year is.
	 */
	private static function find_year_slot( $rows ) {
		$year_slot = null;

		foreach ( $rows as $row ) {
			if ( 4 === strlen( $row[0] ) ) {
				$slot = 0;
			} elseif ( 4 === strlen( $row[2] ) ) {
				$slot = 2;
			} else {
				// A two digit year on both ends, so this row cannot say.
				continue;
			}

			if ( null === $year_slot ) {
				$year_slot = $slot;
			} elseif ( $year_slot !== $slot ) {
				return null;
			}
		}

		// With no four digit year anywhere, a trailing two digit year is the
		// only common arrangement.
		return $year_slot ?? 2;
	}

	/**
	 * Picks the year format character, requiring every year to be the same width.
	 *
	 * @since 6.35
	 *
	 * @param array $rows      Date parts, three per row.
	 * @param int   $year_slot Slot holding the year.
	 *
	 * @return string Empty string when the widths disagree.
	 */
	private static function find_year_token( $rows, $year_slot ) {
		$widths = array();

		foreach ( $rows as $row ) {
			$widths[ strlen( $row[ $year_slot ] ) ] = true;
		}

		if ( count( $widths ) > 1 ) {
			return '';
		}

		return isset( $widths[4] ) ? 'Y' : 'y';
	}

	/**
	 * Assembles a date format from the pieces worked out about a column.
	 *
	 * The day and month use j and n because those accept a leading zero as well
	 * as a bare digit, so one format covers both 26/4/2021 and 26/04/2021.
	 *
	 * @since 6.35
	 *
	 * @param string $separator  Character between the parts.
	 * @param string $year_token Y or y.
	 * @param int    $year_slot  Slot holding the year.
	 * @param bool   $day_first  Whether the day comes before the month.
	 *
	 * @return string
	 */
	private static function build_date_format( $separator, $year_token, $year_slot, $day_first ) {
		$order = $day_first ? array( 'j', 'n' ) : array( 'n', 'j' );

		if ( 0 === $year_slot ) {
			array_unshift( $order, $year_token );
		} else {
			$order[] = $year_token;
		}

		return implode( $separator, $order );
	}

	/**
	 * Confirms a format reads the column better than the opposite order does.
	 *
	 * A row that reads under neither order, such as 31/02/2021, is broken data
	 * rather than evidence, so it must not overturn the conclusion.
	 *
	 * @since 6.35
	 *
	 * @param array  $rows      Date parts, three per row.
	 * @param string $separator Character between the parts.
	 * @param string $format    Format the column appears to use.
	 * @param string $swapped   Same format with the day and month exchanged.
	 *
	 * @return bool
	 */
	private static function format_beats_alternative( $rows, $separator, $format, $swapped ) {
		$reads         = 0;
		$swapped_reads = 0;

		foreach ( $rows as $row ) {
			$date_str = implode( $separator, $row );

			if ( self::strict_date_from_format( $date_str, $format ) ) {
				++$reads;
			}

			if ( self::strict_date_from_format( $date_str, $swapped ) ) {
				++$swapped_reads;
			}
		}

		return $reads > 0 && $reads >= $swapped_reads;
	}

	/**
	 * Rewrites a day first numeric format with each of the other separators.
	 *
	 * The separator a site displays dates with says nothing about the order it
	 * means, so a site set to d.m.Y should still read 26/04/2021 as day first.
	 * Only the order carries over. The lenient j and n are used so one variant
	 * covers both 26/4/2021 and 26/04/2021.
	 *
	 * Month first formats deliberately get no variants, because of a long
	 * standing quirk in how PHP reads a date it has not been given a format for.
	 * strtotime() splits on the separator:
	 *
	 *     05/04/2021  is read month first, as May 4
	 *     05-04-2021  is read day first, as April 5
	 *     05.04.2021  is read day first, as April 5
	 *
	 * So a dash or dot separated date has always been read day first here, on
	 * every site, whatever its own format said. Generating month first variants
	 * would quietly turn April 5 into May 4 on an m/d/Y site for values that
	 * have imported the other way round for years, so those values are left to
	 * strtotime and its convention. A day first site loses nothing by this: its
	 * variants agree with strtotime for dashes and dots anyway, and the one case
	 * they change, a slash separated date on a day first site, is the case
	 * strtotime gets wrong.
	 *
	 * @since 6.35
	 *
	 * @param string $site_format Format from the site settings.
	 *
	 * @return array Empty unless the format is three numeric parts, day before month.
	 */
	private static function get_separator_variants( $site_format ) {
		if ( ! preg_match( '#^([djmnyY])([/.-])([djmnyY])\2([djmnyY])$#', $site_format, $matches ) ) {
			return array();
		}

		$lenient = array(
			'd' => 'j',
			'j' => 'j',
			'm' => 'n',
			'n' => 'n',
			'Y' => 'Y',
			'y' => 'y',
		);
		$tokens  = array();

		// The pattern above only matches these characters, so every one of them
		// has an entry above.
		foreach ( array( $matches[1], $matches[3], $matches[4] ) as $token ) {
			$tokens[] = $lenient[ $token ];
		}

		if ( ! self::is_day_before_month( $tokens ) ) {
			return array();
		}

		$year_position = array_search( 'Y', $tokens, true );

		if ( false === $year_position ) {
			$year_position = array_search( 'y', $tokens, true );
		}

		// The year is the one part whose width is not a matter of order, so a
		// site displaying 26/4/21 should still read 05/04/2021 day first rather
		// than losing to strtotime over two digits against four. The site's own
		// width is tried first.
		$year_tokens = 'y' === $tokens[ $year_position ] ? array( 'y', 'Y' ) : array( 'Y', 'y' );
		$variants    = array();

		foreach ( array( '/', '-', '.' ) as $separator ) {
			foreach ( $year_tokens as $year_token ) {
				$tokens[ $year_position ] = $year_token;
				$variants[]               = implode( $separator, $tokens );
			}
		}

		return $variants;
	}

	/**
	 * Whether a format writes the day ahead of the month, by comparing where the
	 * two sit in the format rather than any date values.
	 *
	 * @since 6.35
	 *
	 * @param array $tokens Format characters in the order the format uses them.
	 *
	 * @return bool False when either part is missing from the format.
	 */
	private static function is_day_before_month( $tokens ) {
		$day_position   = array_search( 'j', $tokens, true );
		$month_position = array_search( 'n', $tokens, true );

		if ( false === $day_position || false === $month_position ) {
			return false;
		}

		return $day_position < $month_position;
	}

	/**
	 * Formats to try, in order, when reading a date of unknown format.
	 *
	 * The db format comes first because it is never ambiguous, then the two
	 * formats the site itself uses, then those same formats rewritten with the
	 * other separators, then formats that strtotime either misreads or cannot
	 * read at all.
	 *
	 * @since 6.35
	 *
	 * @return array
	 */
	private static function get_date_parsing_formats() {
		$formats      = array( 'Y-m-d' );
		$variants     = array();
		$site_formats = array( FrmProAppHelper::get_settings()->date_format, get_option( 'date_format' ) );

		foreach ( $site_formats as $site_format ) {
			if ( ! $site_format || ! is_string( $site_format ) ) {
				continue;
			}

			// A stored value may carry a time along with the date.
			$formats[] = $site_format;
			$formats[] = $site_format . ' H:i:s';
			$formats[] = $site_format . ' H:i';

			$variants = array_merge( $variants, self::get_separator_variants( $site_format ) );
		}

		// A site set to d.m.Y has told us it puts the day first, so read
		// 26/04/2021 that way too rather than letting strtotime call it American.
		$formats = array_merge( $formats, $variants );

		$formats = array_merge(
			$formats,
			array(
				'Y-m-d H:i:s',
				'Y-m-d\TH:i:s',
				'Y/m/d',
				'j F Y',
				'F j, Y',
				'j M Y',
				'M j, Y',
				'd-M-Y',
			)
		);

		/**
		 * Filters the formats used to read a date of unknown format.
		 *
		 * @since 6.35
		 *
		 * @param array $formats Date formats, tried in order.
		 */
		$formats = apply_filters( 'frm_date_parsing_formats', $formats );

		return array_unique( (array) $formats );
	}

	/**
	 * Reads a date in one exact format, rejecting anything the format does not
	 * describe.
	 *
	 * Parsing with date_create_from_format() alone is lenient: given the format
	 * m/d/Y it reads 26/04/2021 as month 26 and rolls it forward into 2023
	 * rather than failing. That rollover is why an earlier attempt at this fix
	 * started rejecting valid dates. Checking the parse warnings is what makes
	 * a mismatched format fail cleanly so the next format gets a turn.
	 *
	 * @since 6.35
	 *
	 * @param string $date_str Date string to read.
	 * @param string $format   Format the date must match exactly.
	 *
	 * @return DateTime|false
	 */
	private static function strict_date_from_format( $date_str, $format ) {
		try {
			// The leading ! zeroes out the parts the format does not set, so a
			// date only format does not inherit the current time.
			$date = date_create_from_format( '!' . $format, $date_str );
		} catch ( Error $e ) {
			// A ValueError is thrown if this string contains null bytes.
			return false;
		}

		if ( ! $date ) {
			return false;
		}

		$errors = DateTime::getLastErrors();

		// PHP 8.2 and later return false instead of an array for a clean parse.
		if ( is_array( $errors ) && ( ! empty( $errors['warning_count'] ) || ! empty( $errors['error_count'] ) ) ) {
			return false;
		}

		// A four digit year token takes two digits without complaint, so 26/04/21
		// read with j/n/Y comes back as the year 21 rather than failing. Only trust
		// it when the value really carries four digits somewhere.
		if ( str_contains( $format, 'Y' ) && ! preg_match( '/\d{4}/', $date_str ) ) {
			return false;
		}

		return $date;
	}
}
