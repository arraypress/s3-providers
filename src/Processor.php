<?php
/**
 * S3 Processor Class
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
 * @package     ArrayPress/Utils/S3
 * @copyright   Copyright (c) 2023, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 * @author      David Sherlock
 */

namespace ArrayPress\Utils\S3;

use Exception;
use InvalidArgumentException;

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
		 * @var string
		 */
		private string $file_name;

		/**
		 * Function name for getting options.
		 * @var string
		 */
		private string $options_getter;

		/**
		 * Prefix for the options.
		 * @var string
		 */
		private string $option_prefix;

		/**
		 * Optional callback for error handling.
		 * @var string
		 */
		private string $error_callback;

		/**
		 * Array of settings arguments.
		 * @var array|null
		 */
		private ?array $settings_args = null;

		/**
		 * List of allowed file extensions.
		 * @var array
		 */
		private array $allowed_extensions;

		/**
		 * List of disallowed protocols.
		 * @var array
		 */
		private array $disallowed_protocols;

		/**
		 * Constructor for the S3 Processor class.
		 *
		 * Initializes the class with the provided arguments, sets up the settings, and
		 * prepares it for generating signed S3 URLs.
		 *
		 * @param array $args Array of arguments to initialize the class.
		 */
		public function __construct( array $args ) {
			// Default argument values
			$defaults = [
				'file_name'            => '',
				'options_getter'       => '',
				'option_prefix'        => '',
				'error_callback'       => null,
				'allowed_extensions'   => [],
				'disallowed_protocols' => []
			];

			// Merge defaults with provided arguments
			$args = array_merge( $defaults, $args );

			// Set class properties
			$this->file_name      = trim( $args['file_name'] );
			$this->options_getter = trim( $args['options_getter'] );
			$this->option_prefix  = trim( $args['option_prefix'] );
			$this->error_callback = trim( $args['error_callback'] );

			$this->allowed_extensions   = $args['allowed_extensions'];
			$this->disallowed_protocols = $args['disallowed_protocols'];

			// Initialize settings and handle any exceptions
			try {
				$settings            = new Settings( $this->options_getter, $this->option_prefix );
				$this->settings_args = $settings->get_signer_args();
			} catch ( Exception $e ) {
				$this->handle_error( $e );

				return;
			}
		}

		/**
		 * Generates a pre-signed S3 URL based on the provided arguments and settings.
		 *
		 * @param array $additional_args Additional arguments for the signer.
		 *
		 * @return string|null The pre-signed URL or null if not valid.
		 */
		public function get_signed_url( array $additional_args = [] ): ?string {
			try {
				// Ensure settings arguments are available
				if ( ! $this->settings_args ) {
					return null;
				}

				// Parse the path to get bucket and object key
				$default_bucket = $this->settings_args['default_bucket'] ?? '';

				// Adjustments for parse_path
				$parsed_path = parse_path(
					$this->file_name,
					$default_bucket,
					$this->allowed_extensions,
					$this->error_callback,
					$this->disallowed_protocols
				);

				if ( ! $parsed_path ) {
					return null; // Path parsing failed
				}

				// Merge settings, parsed path, and additional arguments
				$signer_args = array_merge( $this->settings_args, $parsed_path, $additional_args );

				// Generate and return the pre-signed URL
				return get_object_url( $signer_args, '', '', null, $this->error_callback );
			} catch ( Exception $e ) {
				$this->handle_error( $e );

				return null;
			}
		}

		/**
		 * Handles errors by invoking the error callback if available.
		 *
		 * @param Exception $e The exception to handle.
		 */
		private function handle_error( Exception $e ): void {
			if ( is_callable( $this->error_callback ) ) {
				call_user_func( $this->error_callback, $e );
			}
		}
	}

endif;
