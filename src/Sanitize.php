<?php
/**
 * Utility class for sanitizing various data types.
 *
 * The `Sanitize` class provides static methods to sanitize and validate different
 * types of data including strings, booleans, and URLs. It ensures that the provided
 * data conforms to expected formats and is safe for use within the application.
 *
 * @package     arraypress/s3-providers
 * @copyright   Copyright (c) 2023, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 * @author      David Sherlock
 * @description Provides methods for sanitization.
 */

namespace ArrayPress\Utils\S3;

/**
 * Check if the class `Sanitize` is defined, and if not, define it.
 */
if ( ! class_exists( __NAMESPACE__ . '\\Sanitize' ) ) :

	class Sanitize {

		/**
		 * Sanitizes a key string by ensuring it contains only lowercase letters, numbers, underscores, or hyphens.
		 *
		 * @param mixed $key The input key to be sanitized.
		 *
		 * @return string The sanitized key.
		 */
		public static function key( $key ): string {
			$sanitized_key = '';

			if ( is_scalar( $key ) ) {
				$sanitized_key = trim( $key );
				$sanitized_key = strtolower( $sanitized_key );
				$sanitized_key = preg_replace( '/[^a-z0-9_\-]/', '', $sanitized_key );
			}

			return $sanitized_key;
		}

		/**
		 * Validates and sanitizes a boolean value.
		 *
		 * @param mixed $data The input data to be validated and sanitized.
		 *
		 * @return bool The sanitized boolean value.
		 */
		public static function bool( $data ): bool {
			return filter_var( $data, FILTER_VALIDATE_BOOLEAN );
		}

		/**
		 * Sanitizes a string by converting special characters to their respective HTML entities.
		 *
		 * @param string $data The input string to be sanitized.
		 *
		 * @return string The sanitized string with special characters converted to HTML entities.
		 */
		public static function html( string $data ): string {
			return htmlspecialchars( $data, ENT_QUOTES, 'UTF-8' );
		}

		/**
		 * Sanitize a given URL.
		 *
		 * @param string $url The raw URL to sanitize.
		 *
		 * @return string The sanitized URL or an empty string if invalid.
		 */
		public static function url( string $url ): string {
			$clean_url = filter_var( $url, FILTER_SANITIZE_URL );
			if ( filter_var( $clean_url, FILTER_VALIDATE_URL ) ) {
				return $clean_url;
			}

			return ''; // Return an empty string if the URL is not valid.
		}

	}

endif;