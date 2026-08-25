<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nieuw_Calendar_Ical {
	public static function register() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_output' ) );
	}

	public static function maybe_output() {
		if ( empty( $_GET['nieuw_calendar_ical'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$settings = nieuw_calendar_get_settings();
		$events   = nieuw_calendar_get_events();
		$tz       = new DateTimeZone( $settings['timezone'] ? $settings['timezone'] : 'UTC' );
		$utc      = new DateTimeZone( 'UTC' );
		$stamp    = gmdate( 'Ymd\THis\Z' );

		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="nieuw-calendar.ics"' );

		echo "BEGIN:VCALENDAR\r\n";
		echo "VERSION:2.0\r\n";
		echo "PRODID:-//Nieuw Ark//Nieuw Calendar//EN\r\n";
		echo "CALSCALE:GREGORIAN\r\n";
		echo "METHOD:PUBLISH\r\n";
		echo "X-WR-CALNAME:Nieuw Calendar\r\n";
		echo 'X-WR-TIMEZONE:' . self::esc( $settings['timezone'] ) . "\r\n";

		foreach ( $events as $event ) {
			echo "BEGIN:VEVENT\r\n";
			echo 'UID:' . absint( $event['id'] ) . "@nieuw-calendar\r\n";
			echo 'DTSTAMP:' . $stamp . "\r\n";
			if ( ! empty( $event['allDay'] ) ) {
				echo 'DTSTART;VALUE=DATE:' . self::ics_date( $event['startDate'] ) . "\r\n";
				$end = DateTime::createFromFormat( 'Y-m-d', $event['endDate'] ?: $event['startDate'], $tz );
				if ( $end ) {
					$end->modify( '+1 day' );
					echo 'DTEND;VALUE=DATE:' . $end->format( 'Ymd' ) . "\r\n";
				}
			} else {
				$start = self::to_utc( $event['startDate'], $event['startTime'] ?: '00:00', $tz, $utc );
				$end   = self::to_utc( $event['endDate'] ?: $event['startDate'], $event['endTime'] ?: ( $event['startTime'] ?: '00:00' ), $tz, $utc );
				echo 'DTSTART:' . $start . "\r\n";
				echo 'DTEND:' . $end . "\r\n";
			}
			echo 'SUMMARY:' . self::esc( $event['title'] ) . "\r\n";
			if ( ! empty( $event['description'] ) ) {
				echo 'DESCRIPTION:' . self::esc( $event['description'] ) . "\r\n";
			}
			if ( ! empty( $event['location'] ) ) {
				echo 'LOCATION:' . self::esc( $event['location'] ) . "\r\n";
			}
			echo "END:VEVENT\r\n";
		}
		echo "END:VCALENDAR\r\n";
		exit;
	}

	private static function ics_date( $ymd ) {
		return str_replace( '-', '', $ymd );
	}

	private static function to_utc( $date, $time, DateTimeZone $tz, DateTimeZone $utc ) {
		$dt = DateTime::createFromFormat( 'Y-m-d H:i', $date . ' ' . $time, $tz );
		if ( ! $dt ) {
			return gmdate( 'Ymd\THis\Z' );
		}
		$dt->setTimezone( $utc );
		return $dt->format( 'Ymd\THis\Z' );
	}

	private static function esc( $value ) {
		$value = str_replace( array( '\\', "\n", ',', ';' ), array( '\\\\', '\\n', '\\,', '\\;' ), (string) $value );
		return $value;
	}
}
