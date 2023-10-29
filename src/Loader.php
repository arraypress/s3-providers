<?php
/**
 * Manages and facilitates the retrieval of storage providers from a JSON data source.
 *
 * The Loader class provides a suite of utilities tailored for fetching storage provider data
 * from JSON files. It offers a structured approach to loading, validating, and checksum verification
 * of the data to ensure its integrity and reliability. With built-in caching, it optimizes checksum
 * calculations to reduce the overhead on repetitive reads. The class also encompasses error-handling mechanisms
 * to manage situations like the absence of a specified file or encountering malformed JSON content.
 *
 * Common use-cases include:
 * - Loading a list of storage providers from a JSON file for further processing.
 * - Verifying the authenticity of a loaded file by comparing its SHA256 checksum.
 *
 * // Load providers from a default or specified JSON file.
 * $providers = Loader::load('path/to/providers.json');
 *
 * // Obtain the SHA256 checksum of the loaded JSON file.
 * $checksum = Loader::get_checksum('path/to/providers.json');
 *
 * @package     ArrayPress/s3-providers
 * @copyright   Copyright (c) 2023, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 * @author      David Sherlock
 * @description Manages and streamlines the process of retrieving, validating, and verifying storage provider data from
 *              JSON sources.
 */

namespace ArrayPress\Utils\S3;

use Exception;

/**
 * Handles the loading and validation of S3 region data from JSON sources.
 *
 * If the class already exists in the namespace, it won't be redefined.
 */
if ( ! class_exists( __NAMESPACE__ . '\\Loader' ) ) :

	class Loader {

		/**
		 * The cached SHA256 checksum of the loaded file.
		 *
		 * @var string|null
		 */
		private static ?string $cached_checksum = null;

		/**
		 * Load providers from a JSON file.
		 *
		 * @param string|null $json_path Path to the JSON file containing providers.
		 *
		 * @return array An associative array of provider data.
		 * @throws Exception If JSON file does not exist or has invalid data.
		 */
		public static function load( string $json_path = null ): array {
			$json_data = self::load_json_file( $json_path );

			return self::validate_json( $json_data );
		}

		/**
		 * Load the content of the provided JSON file.
		 *
		 * @param string|null $json_path Path to the JSON file.
		 *
		 * @return string Content of the JSON file.
		 * @throws Exception If JSON file does not exist.
		 */
		private static function load_json_file( string $json_path = null ): string {
			$json_file = self::resolve_file_path( $json_path );

			return file_get_contents( $json_file );
		}

		/**
		 * Resolve the path to the JSON file and check its existence.
		 *
		 * @param string|null $json_path Path to the JSON file.
		 *
		 * @return string The resolved file path.
		 * @throws Exception If the file does not exist.
		 */
		private static function resolve_file_path( string $json_path = null ): string {
			$json_file = $json_path ?: __DIR__ . '/providers.json';

			if ( ! file_exists( $json_file ) ) {
				throw new Exception( "The JSON file '{$json_file}' does not exist." );
			}

			return $json_file;
		}

		/**
		 * Decode the provided JSON data string and validate its structure.
		 *
		 * @param string $json_data JSON data as string.
		 *
		 * @return array Decoded and validated JSON data.
		 * @throws Exception If JSON format is invalid or structure is not as expected.
		 */
		private static function validate_json( string $json_data ): array {
			$data = json_decode( $json_data, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new Exception( 'Invalid JSON format.' );
			}

			if ( ! is_array( $data['providers'] ) || empty( $data['providers'] ) ) {
				throw new Exception( "The JSON data either does not contain the 'providers' key or it's not a valid array or it's empty." );
			}

			return $data['providers'];
		}

		/**
		 * Calculate the SHA256 checksum of the file.
		 *
		 * @param string|null $json_path Path to the JSON file.
		 *
		 * @return string SHA256 checksum.
		 * @throws Exception If the file does not exist.
		 */
		public static function get_checksum( string $json_path = null ): string {
			if ( self::$cached_checksum ) {
				return self::$cached_checksum;
			}

			$json_file             = self::resolve_file_path( $json_path );
			self::$cached_checksum = hash_file( 'sha256', $json_file );

			return self::$cached_checksum;
		}

	}

endif;
