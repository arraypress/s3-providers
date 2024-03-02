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
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 * @author      David Sherlock
 * @description Manages and streamlines the process of retrieving, validating, and verifying storage provider data from
 *              JSON sources.
 */

namespace ArrayPress\S3\Providers;

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
		private static ?string $cachedChecksum = null;

		/**
		 * The cached SHA256 checksum of the loaded file.
		 *
		 * @var string|null
		 */
		private static ?string $jsonVersion = null; // To store the version of the loaded JSON file

		/**
		 * Load providers and metadata from a JSON file, including version information.
		 *
		 * This method reads a specified JSON file, validates its structure, and returns the provider data
		 * along with metadata such as the file version. It's designed to ensure both the integrity and
		 * authenticity of the data, facilitating version management and update checks. If the JSON file
		 * contains a 'version' key, it is stored for potential comparisons to determine if the loaded data
		 * is up-to-date. This method is integral for applications that rely on external JSON data sources
		 * for configuration or operational data.
		 *
		 * @param string|null $jsonPath Path to the JSON file containing providers and optional version information.
		 *
		 * @return object An object containing the provider data under 'providers' key and possibly a 'version' key
		 *                for the data version. The structure is validated to ensure it contains at least the 'providers'
		 *                key with valid data.
		 * @throws Exception If the JSON file does not exist, cannot be read, or contains invalid or improperly
		 *                   structured JSON data. Also throws if the 'providers' key is missing or empty, or if
		 *                   the 'version' key is missing in cases where version control is expected.
		 *
		 * Usage Example:
		 * ```php
		 * try {
		 *     $providersData = Loader::load('path/to/providers.json');
		 *     echo "Providers loaded successfully.";
		 *     if (Loader::isVersionNewer('1.0.1')) {
		 *         echo "Loaded data is newer than version 1.0.1.";
		 *     }
		 * } catch (Exception $e) {
		 *     echo "Error loading providers: " . $e->getMessage();
		 * }
		 * ```
		 *
		 * Note: Ensure the JSON file is correctly formatted and includes the necessary keys ('providers',
		 * optionally 'version') to avoid exceptions.
		 */
		public static function load( string $jsonPath = null ): ?object {
			$jsonData    = self::loadJsonFile( $jsonPath );
			$decodedData = self::validateJson( $jsonData );

			// Store the version for later comparison, if available
			self::$jsonVersion = $decodedData->version ?? null;

			return $decodedData->providers;
		}

		/**
		 * Load the content of the provided JSON file.
		 *
		 * @param string|null $jsonPath Path to the JSON file.
		 *
		 * @return string Content of the JSON file.
		 * @throws Exception If JSON file does not exist.
		 */
		private static function loadJsonFile( string $jsonPath = null ): string {
			$jsonFile = self::resolveFilePath( $jsonPath );

			return file_get_contents( $jsonFile );
		}

		/**
		 * Resolve the path to the JSON file and check its existence.
		 *
		 * @param string|null $jsonPath Path to the JSON file.
		 *
		 * @return string The resolved file path.
		 * @throws Exception If the file does not exist.
		 */
		private static function resolveFilePath( string $jsonPath = null ): string {
			$jsonFile = $jsonPath ?: __DIR__ . '/providers.json';

			if ( ! file_exists( $jsonFile ) ) {
				throw new Exception( "The JSON file '{$jsonFile}' does not exist." );
			}

			return $jsonFile;
		}

		/**
		 * Decode the provided JSON data string and validate its structure.
		 *
		 * @param string $jsonData JSON data as string.
		 *
		 * @return object Decoded and validated JSON data.
		 * @throws Exception If JSON format is invalid or structure is not as expected.
		 */
		private static function validateJson( string $jsonData ): object {
			$data = json_decode( $jsonData, false );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new Exception( 'Invalid JSON format.' );
			}

			if ( empty( $data->providers ) ) {
				throw new Exception( "The JSON data either does not contain the 'providers' key or it's not a valid object or it's empty." );
			}

			// Check if the 'version' key exists
			if ( empty( $data->version ) ) {
				throw new Exception( "The JSON data does not contain a 'version' key." );
			}

			return $data;
		}

		/**
		 * Compares the loaded JSON version against a specified version string to determine if the loaded version is newer.
		 *
		 * This method is crucial for version management, allowing the application to check if an update or a more
		 * recent version of the JSON data is available. It leverages the PHP `version_compare` function to
		 * accurately compare semantic versioning strings. Before invoking this method, ensure that a JSON file has been
		 * successfully loaded and validated using `load` to set the `jsonVersion` property.
		 *
		 * @param string $otherVersion The version string to compare against the loaded JSON version.
		 *
		 * @return bool Returns true if the loaded JSON version is newer than the specified version string, false otherwise.
		 * @throws Exception If no JSON version has been loaded before calling this method, indicating that a JSON file
		 *                   needs to be loaded and validated to set the `jsonVersion` property.
		 *
		 * Example Usage:
		 * ```php
		 * if ( Loader::isVersionNewer( '2.0.0' ) ) {
		 *     echo "A newer version of the JSON data is loaded.";
		 * } else {
		 *     echo "The loaded JSON version is not newer than '2.0.0'.";
		 * }
		 * ```
		 */
		public static function isVersionNewer( string $otherVersion ): bool {
			if ( self::$jsonVersion === null ) {
				throw new Exception( "No JSON version loaded. Please load a JSON file first." );
			}

			return version_compare( self::$jsonVersion, $otherVersion, '>' );
		}

		/**
		 * Calculate the SHA256 checksum of the file.
		 *
		 * @param string|null $jsonPath Path to the JSON file.
		 *
		 * @return string SHA256 checksum.
		 * @throws Exception If the file does not exist.
		 */
		public static function getChecksum( string $jsonPath = null ): string {
			if ( self::$cachedChecksum ) {
				return self::$cachedChecksum;
			}

			$jsonFile             = self::resolveFilePath( $jsonPath );
			self::$cachedChecksum = hash_file( 'sha256', $jsonFile );

			return self::$cachedChecksum;
		}

	}

endif;