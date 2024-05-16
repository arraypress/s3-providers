<?php
/**
 * S3 Path Processor Class
 *
 * This class serves as a comprehensive utility for interacting with S3 services.
 * It encapsulates the functionality for validating S3 settings, parsing file paths, and generating
 * pre-signed URLs for S3 objects. Designed to simplify the integration and usage of S3 services,
 * the Processor class abstracts the complexities involved in S3 operations, providing a streamlined
 * and user-friendly interface.
 *
 * Key functionalities include:
 * - Validating configuration settings for S3 operations.
 * - Parsing S3 paths to extract bucket and object details.
 * - Generating pre-signed URLs for secure, temporary access to S3 objects.
 *
 * @since       1.0.0
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @package     ArrayPress/s3-providers
 * @author      David Sherlock
 */

namespace ArrayPress\S3\Providers;

use Exception;
use function ArrayPress\S3\getObjectUrl;
use function ArrayPress\S3\parsePath;
use function call_user_func;
use function in_array;
use function is_callable;
use function trim;

/**
 * S3 Utility Class (Processor)
 *
 * Streamlines the process of generating pre-signed URLs for S3 objects. The class is designed
 * to simplify interactions with S3 by handling the complexities of path parsing, settings validation,
 * and URL signing. It acts as a high-level interface to the underlying S3 functionalities.
 *
 * If the class already exists in the namespace, it won't be redefined.
 */
if ( ! class_exists( __NAMESPACE__ . '\\Processor' ) ) :

	class Processor {

		/**
		 * The S3 file name or path.
		 *
		 * @var string
		 */
		private string $path;

		/**
		 * Function name for getting options.
		 *
		 * @var string
		 */
		private string $optionsGetter;

		/**
		 * Prefix for the options.
		 *
		 * @var string
		 */
		private string $optionPrefix;

		/**
		 * Optional callback for error handling.
		 *
		 * @var string
		 */
		private string $errorCallback;

		/**
		 * Array of settings arguments.
		 *
		 * @var object|null
		 */
		private ?object $settings = null;

		/**
		 * List of allowed file extensions.
		 *
		 * @var array
		 */
		private array $allowedExtensions;

		/**
		 * List of disallowed protocols.
		 *
		 * @var array
		 */
		private array $disallowedProtocols;

		/**
		 * Constructor for the S3 Processor class.
		 *
		 * @param string        $path                The S3 file name or path.
		 * @param string        $optionsGetter       Function name for getting options.
		 * @param string        $optionPrefix        Prefix for the options.
		 * @param callable|null $errorCallback       Optional callback for error handling.
		 * @param array         $allowedExtensions   List of allowed file extensions.
		 * @param array         $disallowedProtocols List of disallowed protocols.
		 */
		public function __construct(
			string $path = '',
			string $optionsGetter = '',
			string $optionPrefix = '',
			?callable $errorCallback = null,
			array $allowedExtensions = [],
			array $disallowedProtocols = []
		) {
			$this->path                = $path;
			$this->optionsGetter       = $optionsGetter;
			$this->optionPrefix        = $optionPrefix;
			$this->errorCallback       = $errorCallback;
			$this->allowedExtensions   = $allowedExtensions;
			$this->disallowedProtocols = $disallowedProtocols;

			try {
				$settings       = new Settings( $this->optionsGetter, $this->optionPrefix );
				$this->settings = $settings->getSignerProperties();
			} catch ( Exception $e ) {
				$this->handleError( $e );

				return;
			}
		}

		/** Setters ***************************************************************/

		/**
		 * Sets the S3 file name or path.
		 *
		 * This method updates the path property of the class.
		 *
		 * @param string $path The new S3 file name or path.
		 */
		public function setPath( string $path ): void {
			$this->path = $path;
		}

		/**
		 * Sets the list of allowed file extensions.
		 *
		 * This method ensures that the list of allowed extensions is unique and updates
		 * the class's configuration accordingly. File extensions should be provided without
		 * leading dots (e.g., 'jpg' instead of '.jpg').
		 *
		 * @param array $allowedExtensions An array of allowed file extensions.
		 */
		public function setAllowedExtensions( array $allowedExtensions ): void {
			$this->allowedExtensions = array_unique( $allowedExtensions );
		}

		/**
		 * Adds a single allowed file extension to the list of allowed extensions.
		 *
		 * This method ensures that the added extension is unique within the list of allowed
		 * extensions. It does not add the extension if it already exists in the list.
		 * File extensions should be provided without leading dots (e.g., 'jpg' instead of '.jpg').
		 *
		 * @param string $extension The file extension to add to the list of allowed extensions.
		 */
		public function addAllowedExtension( string $extension ): void {
			$extension = trim( $extension );
			if ( ! in_array( $extension, $this->allowedExtensions ) ) {
				$this->allowedExtensions[] = $extension;
			}
		}

		/**
		 * Adds a protocol to the list of disallowed protocols.
		 *
		 * This method ensures that the added protocol is unique within the list of disallowed
		 * protocols. It does not add the protocol if it already exists in the list.
		 *
		 * @param string $protocol The protocol to add to the list of disallowed protocols.
		 */
		public function addDisallowedProtocol( string $protocol ): void {
			$protocol = trim( $protocol );
			if ( ! in_array( $protocol, $this->disallowedProtocols ) ) {
				$this->disallowedProtocols[] = $protocol;
			}
		}

		/**
		 * Sets the list of disallowed protocols in S3 paths.
		 *
		 * This method updates the class's configuration with a new list of protocols that
		 * should not be allowed in S3 paths (e.g., 'http://', 'ftp://'). It's used to prevent
		 * security risks associated with unwanted protocols.
		 *
		 * @param array $disallowedProtocols An array of disallowed protocols.
		 */
		public function setDisallowedProtocols( array $disallowedProtocols ): void {
			if ( ! empty( $disallowedProtocols ) ) {
				$this->disallowedProtocols = $disallowedProtocols;
			}
		}

		/** Main ******************************************************************/

		/**
		 * Generates a pre-signed S3 URL based on the provided arguments and settings.
		 *
		 * @return string|null The pre-signed URL or null if not valid.
		 */
		public function getSignedURL(): ?string {
			try {

				// Ensure settings arguments are available
				if ( ! $this->settings ) {
					return null;
				}

				// Adjustments for parse_path
				$path = parsePath(
					$this->path,
					$this->settings->defaultBucket ?? '',
					$this->allowedExtensions,
					$this->disallowedProtocols,
					$this->errorCallback,
				);

				if ( ! $path ) {
					return null; // Path parsing failed
				}

				return getObjectUrl(
					$this->settings->accessKey,
					$this->settings->secretKey,
					$this->settings->endpoint,
					$path->bucket,
					$path->objectKey,
					$this->settings->duration,
					$this->settings->extraQueryString,
					$this->settings->region,
					$this->settings->usePathStyle,
					$this->errorCallback
				);
			} catch ( Exception $e ) {
				$this->handleError( $e );

				return null;
			}
		}

		/** Error Handling ********************************************************/

		/**
		 * Handles errors by invoking the error callback if available.
		 *
		 * @param Exception $e The exception to handle.
		 */
		private function handleError( Exception $e ): void {
			if ( is_callable( $this->errorCallback ) ) {
				call_user_func( $this->errorCallback, $e );
			}
		}
	}

endif;
