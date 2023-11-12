<?php
/**
 * S3 Settings Validator Class
 *
 * A utility class for validating and managing settings related to S3 storage and pre-signing options.
 * This class ensures that the provided settings are valid and conform to required specifications.
 *
 * This class includes methods to validate various configuration options such as access keys, secret keys, storage
 * providers, regions, custom endpoints, and more. It also provides a convenient way to retrieve arguments for signing
 * classes used in S3 pre-signing operations.
 *
 * @package     ArrayPress/Utils/S3
 * @copyright   Copyright (c) 2023, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 * @author      David Sherlock
 */

namespace ArrayPress\Utils\S3;

use InvalidArgumentException;
use Exception;

/**
 * S3 Settings Validator Class
 *
 * Manages and validates configuration settings for Amazon S3 storage and pre-signing operations.
 * This class ensures that the settings provided are valid and adhering to the required specifications.
 *
 * If the class already exists in the namespace, it won't be redefined.
 */
if ( ! class_exists( __NAMESPACE__ . '\\Settings' ) ) :

	/**
	 * Class Settings
	 *
	 * Manages the validation and configuration of settings related to Amazon S3 storage and pre-signing options.
	 */
	class Settings {

		/**
		 * @var string Access Key ID
		 */
		private string $access_key;

		/**
		 * @var string Secret Key
		 */
		private string $secret_key;

		/**
		 * @var string Storage Provider
		 */
		private string $provider;

		/**
		 * @var string Region
		 */
		private string $region;

		/**
		 * @var string Custom Region
		 */
		private string $custom_region;

		/**
		 * @var mixed Custom Endpoint
		 */
		private string $custom_endpoint;

		/**
		 * @var string Account ID
		 */
		private string $account_id;

		/**
		 * @var Boolean Is Path Style?
		 */
		private bool $is_path_style;

		/**
		 * @var string Default Bucket
		 */
		private string $default_bucket;

		/**
		 * @var int Period
		 */
		private int $period;

		/**
		 * @var string Extra Query String
		 */
		private string $extra_query_string;

		/**
		 * @var Provider Provider object
		 */
		private Provider $provider_obj;

		/**
		 * Constructor for the Validator class.
		 *
		 * Initializes an instance of the Validator class with options retrieved using a specified getter function.
		 *
		 * @param string      $options_getter The getter function to retrieve option values (defaults to 'get_option').
		 * @param string|null $option_prefix  An optional prefix for option keys.
		 *
		 * @throws InvalidArgumentException If the options getter function is empty.
		 * @throws Exception If an error occurs while retrieving an option.
		 */
		public function __construct( string $options_getter = 'get_option', string $option_prefix = null ) {
			if ( empty( $options_getter ) ) {
				throw new InvalidArgumentException( 'Options getter function cannot be empty.' );
			}

			$options = array(
				'access_key'         => '',
				'secret_key'         => '',
				'provider'           => '',
				'region'             => '',
				'custom_region'      => '',
				'custom_endpoint'    => '',
				'account_id'         => '',
				'is_path_style'      => false,
				'default_bucket'     => '',
				'period'             => 5,
				'extra_query_string' => ''
			);

			// Iterate over the defaults array and map options to class variables
			foreach ( $options as $key => $default ) {
				// Build the option key with the prefix (if set)
				$option_key = $option_prefix ? $option_prefix . $key : $key;

				// Check if the option is defined in wp-config.php
				if ( defined( $option_key ) ) {
					$option_value = constant( $option_key );
				} else {
					try {
						// Use the callback to retrieve the option value
						$option_value = call_user_func( $options_getter, $option_key );
					} catch ( Exception $e ) {
						throw new Exception( "Error retrieving option '$option_key': " . $e->getMessage() );
					}
				}

				// Use a ternary expression to handle string trimming and default values
				$this->{$key} = is_string( $option_value ) ? trim( $option_value ) : ( $option_value ?? $default );
			}


			$this->validate_options();

			$this->setup_custom_properties();

		}

		/**
		 * Set up custom properties based on the storage provider's requirements.
		 *
		 * This method initializes custom properties based on the chosen storage provider's configuration. It sets the
		 * `$provider_obj` property to the storage provider instance, and it may modify the `$region` and `$is_path_style`
		 * properties depending on the provider's requirements.
		 *
		 * If the provider requires a custom endpoint and the custom region is not empty, this method sets the `$region` property
		 * to the lowercase value of the custom region. Otherwise, it sets the `$is_path_style` property based on the provider's
		 * path style configuration.
		 */
		protected function setup_custom_properties() {
			$this->provider_obj = get_provider( $this->provider );

			if ( $this->provider_obj->requires_custom_endpoint() && ! empty( $this->custom_region ) ) {
				$this->region = strtolower( $this->custom_region );
			} else {
				$this->is_path_style = $this->provider_obj->is_path_style();
			}
		}

		/**
		 * Validate the options for the storage configuration.
		 *
		 * @throws Exception If any required options are missing or invalid.
		 */
		protected function validate_options() {
			if ( empty( $this->access_key ) ) {
				throw new Exception( "Access Key is required and cannot be empty." );
			}

			if ( empty( $this->secret_key ) ) {
				throw new Exception( "Secret Key is required and cannot be empty." );
			}

			if ( empty( $this->provider ) ) {
				throw new Exception( "Storage Provider is required and cannot be empty." );
			}

			// Check if the specified storage provider exists
			$provider = get_provider( $this->provider );
			if ( empty( $provider ) ) {
				throw new Exception( "Invalid Storage Provider specified." );
			}

			// Check if the storage provider requires an account ID and if it's provided
			if ( $provider->requires_account_id() && empty( $this->account_id ) ) {
				throw new Exception( "Account ID is required for this Storage Provider." );
			}

			if ( $provider->requires_custom_endpoint() ) {
				// Check if a custom endpoint and custom region are provided and valid
				if ( empty( $this->custom_endpoint ) || ! Validation::endpoint( $this->custom_endpoint ) ) {
					throw new Exception( "Custom Endpoint is required and must be a valid URL for this Storage Provider." );
				}

				if ( empty( $this->custom_region ) || ! Validation::region( $this->custom_region ) ) {
					throw new Exception( "Custom Region is required and must be a valid region for this Storage Provider." );
				}
			} else {
				// If not a custom endpoint, use the default region from the provider
				if ( empty( $this->region ) ) {
					$this->region = $provider->get_default_region();
				}

				// Check if the specified region is valid for the provider
				if ( ! $provider->region_exists( $this->region ) ) {
					throw new Exception( "Region is required and must be a valid region for this Storage Provider." );
				}
			}

			// Check if a default bucket is provided and if it's valid
			if ( ! empty( $this->default_bucket ) && ! Validation::bucket( $this->default_bucket ) ) {
				throw new Exception( "Invalid Default Bucket specified." );
			}

			// Check if the specified period is a positive integer
			if ( ! Validation::period( $this->period ) ) {
				throw new Exception( "Period must be a positive integer." );
			}

			// Check if the extra query string, if provided, is valid
			if ( ! empty( $this->extra_query_string ) && ! Validation::extra_query_string( $this->extra_query_string ) ) {
				throw new Exception( "Invalid extra query string specified." );
			}
		}

		/**
		 * Check if the stored credentials for the storage provider are valid.
		 *
		 * @return bool True if the credentials are valid, otherwise false.
		 */
		public function has_credentials() {
			if ( $this->provider_obj->requires_account_id() ) {
				return ! empty( $this->account_id ) && ! empty( $this->access_key ) && ! empty( $this->secret_key );
			} else {
				return ! empty( $this->access_key ) && ! empty( $this->secret_key );
			}
		}

		/**
		 * Get the arguments for the signing class.
		 *
		 * @param array $args Additional arguments to merge with the defaults.
		 *
		 * @return array An array of signing class arguments.
		 * @throws Exception
		 */
		public function get_signer_args( array $args = [] ): array {
			return array_merge(
				[
					'access_key'         => $this->access_key,
					'secret_key'         => $this->secret_key,
					'endpoint'           => $this->get_endpoint(),
					'region'             => $this->region,
					'use_path_style'     => $this->is_path_style,
					'extra_query_string' => $this->extra_query_string,
					'period'             => $this->period
				],
				$args
			);
		}

		/**
		 * Get the appropriate endpoint URL for the current storage configuration.
		 *
		 * This method generates the endpoint URL based on the selected storage provider, region, account ID,
		 * and custom endpoint (if specified). It ensures that the generated endpoint URL conforms to
		 * the requirements of the chosen storage provider.
		 *
		 * @return string The endpoint URL for the current storage configuration.
		 * @throws Exception
		 */
		protected function get_endpoint(): string {
			return get_endpoint( $this->provider, $this->region, $this->account_id, $this->custom_endpoint );
		}

	}

endif;