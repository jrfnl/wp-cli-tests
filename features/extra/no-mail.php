<?php
/**
 * This file is copied as amu-plugin into new WP installs to reroute normal
 * mails into log entries.
 */

/**
 * Replace WP native pluggable wp_mail function for test purposes.
 *
 * @param string $to Email address.
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- WP native function.
 */
function wp_mail( $to ) {
	// Log for testing purposes
	WP_CLI::log( "WP-CLI test suite: Sent email to {$to}." );
}
// phpcs:enable
