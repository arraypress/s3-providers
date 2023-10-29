<?php
/**
 * The Providers class manages and provides utilities related to S3 providers.
 *
 * This class offers various methods to interact with providers and their regions,
 * which are initialized from a JSON file. The class ensures methods to fetch individual
 * or all providers, retrieve endpoint URLs, determine the presence of account ID placeholders,
 * check the URL style, fetch regions, and generate options suitable for dropdown menus, among others.
 *
 * Example usage:
 * $providers = new Providers();
 * $allProviders = $providers->get_providers();
 * $endpoint = $providers->get_endpoint('provider_key', 'region_key', 'account_id');
 *
 * Note: This class checks for its own existence before being defined to prevent redefinition.
 *
 * @package     ArrayPress/s3-providers
 * @copyright   Copyright (c) 2023, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 * @author      David Sherlock
 */

namespace ArrayPress\Utils\S3;

use Exception;

/**
 * Manages S3 providers and offers utilities for interacting with them and their regions.
 *
 * If the class already exists in the namespace, it won't be redefined.
 */
if ( ! class_exists( __NAMESPACE__ . '\\Providers' ) ) :

	class Providers {

		/**
		 * Array of provider objects.
		 *
		 * @var Provider[]
		 */
		private array $providers = array(); // Store initialized providers

		/**
		 * Constructs the Providers class.
		 *
		 * @param string|array|null $input Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
		 *
		 * @throws Exception If the provider data is invalid.
		 */
		public function __construct( $input = null ) {
			// Check if input is empty or not
			if ( empty( $input ) && ! is_null( $input ) ) {
				throw new Exception( 'Input is empty. It should either be a valid path to the JSON file, an array of provider data, or null to load from the default JSON file.' );
			}

			if ( is_string( $input ) ) {
				$providers_data = Loader::load( $input );
			} elseif ( is_array( $input ) ) {
				// Ensure the array is not empty
				if ( empty( $input ) ) {
					throw new Exception( 'The provided array is empty. Please provide a valid array of provider data.' );
				}
				$providers_data = $input;
			} else {
				$providers_data = Loader::load();
			}

			foreach ( $providers_data as $key => $provider_data ) {
				$this->providers[ $key ] = new Provider( $key, $provider_data );
			}
		}

		/** Providers *************************************************************/

		/**
		 * Retrieves all the providers.
		 *
		 * @return array An array of Provider objects.
		 */
		public function get_providers(): array {
			return $this->providers;
		}

		/**
		 * Retrieves a specific provider by its key.
		 *
		 * @param string $provider_key Provider key.
		 *
		 * @return Provider The provider object.
		 * @throws Exception If provider does not exist.
		 */
		public function get_provider( string $provider_key ): Provider {
			$this->validate_provider( $provider_key );

			return $this->providers[ $provider_key ];
		}

		/**
		 * Checks if a provider exists by its key.
		 *
		 * @param string $provider_key Provider key.
		 *
		 * @return bool True if the provider exists, otherwise false.
		 */
		public function provider_exists( string $provider_key ): bool {
			return isset( $this->providers[ $provider_key ] );
		}

		/**
		 * Returns an array of providers suitable for use in dropdown menus, etc.
		 *
		 * @param string $empty_label Label to use for the empty option. If not provided or empty, the empty option will be omitted.
		 *
		 * @return array Associative array of providers.
		 */
		public function get_provider_options( string $empty_label = '' ): array {
			$options = array();

			if ( ! empty( $empty_label ) ) {
				$options[''] = $empty_label;
			}

			foreach ( $this->providers as $provider_key => $provider_obj ) {
				if ( ! array_key_exists( $provider_key, $options ) ) {
					$options[ $provider_key ] = $provider_obj->get_label();
				}
			}

			return $options;
		}

		/**
		 * Retrieves the key of the first provider from the providers array.
		 *
		 * @return string|null The key of the first provider or null if the providers array is empty.
		 */
		public function get_first_provider(): ?string {
			reset( $this->providers ); // Reset the internal pointer of the array
			$first_provider_key = key( $this->providers ); // Get the key of the current element

			return $first_provider_key ?: null;
		}

		/**
		 * Validates the existence of a provider by its key.
		 *
		 * @param string $provider_key Provider key.
		 *
		 * @throws Exception If provider does not exist.
		 */
		private function validate_provider( string $provider_key ) {
			if ( ! isset( $this->providers[ $provider_key ] ) ) {
				throw new Exception( "The provider '{$provider_key}' does not exist." );
			}
		}

		/**
		 * Retrieves the continents for a given provider.
		 *
		 * @param string $provider_key Provider key.
		 *
		 * @return array An array of continents for the provider.
		 * @throws Exception If provider does not exist.
		 */
		public function get_provider_continents( string $provider_key ): array {
			$this->validate_provider( $provider_key );

			return $this->providers[ $provider_key ]->get_continents();
		}

		/** Regions ***************************************************************/

		/**
		 * Get all regions for a specific provider.
		 *
		 * @param string $provider_key Provider key.
		 *
		 * @return array An array of Region objects.
		 * @throws Exception If provider does not exist.
		 */
		public function get_regions( string $provider_key ): array {
			$this->validate_provider( $provider_key );

			return $this->providers[ $provider_key ]->get_regions();
		}

		/**
		 * Retrieves a specific provider region based on its key.
		 *
		 * @param string $provider_key Provider key.
		 * @param string $region_key   The key of the region to retrieve.
		 *
		 * @return Region An array of Region objects.
		 * @throws Exception If provider does not exist.
		 */
		public function get_region( string $provider_key, string $region_key ): Region {
			$this->validate_provider( $provider_key );

			return $this->providers[ $provider_key ]->get_region( $region_key );
		}

		/**
		 * Checks if a particular region is supported by the provider.
		 *
		 * @param string $provider_key Provider key.
		 * @param string $region_key   The key of the region to retrieve.
		 *
		 * @return bool True if the region exists for this provider, otherwise false.
		 * @throws Exception If provider does not exist.
		 */
		public function region_exists( string $provider_key, string $region_key ): bool {
			$this->validate_provider( $provider_key );

			return $this->providers[ $provider_key ]->region_exists( $region_key );
		}

		/**
		 * Retrieves the regions of the first provider in the list.
		 *
		 * @return array An array of regions associated with the first provider.
		 *               If no provider is found or the provider has no regions, an empty array is returned.
		 * @throws Exception If provider does not exist.
		 */
		public function get_first_provider_regions(): array {
			return $this->get_regions( $this->get_first_provider() );
		}

		/**
		 * Retrieves the region options of the first provider in the list.
		 *
		 * @param string $empty_label        Label to use for the empty option. If not provided or empty,
		 *                                   the empty option will be omitted.
		 * @param bool   $group_by_continent If true, group regions by their respective continents.
		 *
		 * @return array Depending on the grouping flag, it either returns a simple associative array of
		 *               regions or a nested associative array grouped by continents.
		 * @throws Exception When the specified region does not exist for the given provider.
		 */
		public function get_first_provider_region_options( string $empty_label = '', bool $group_by_continent = false ): array {
			return $this->get_region_options( $this->get_first_provider(), $empty_label, $group_by_continent );
		}

		/**
		 * Returns an array of regions suitable for use in dropdown menus, etc.
		 *
		 * @param string $empty_label        Label to use for the empty option. If not provided or empty,
		 *                                   the empty option will be omitted.
		 * @param bool   $group_by_continent If true, group regions by their respective continents.
		 *
		 * @return array Depending on the grouping flag, it either returns a simple associative array of
		 *               regions or a nested associative array grouped by continents.
		 * @throws Exception When the specified region does not exist for the given provider.
		 */
		public function get_region_options( string $provider_key, string $empty_label = '', bool $group_by_continent = false ): array {
			$this->validate_provider( $provider_key );

			return $this->providers[ $provider_key ]->get_region_options( $empty_label, $group_by_continent );
		}

		/** Endpoints *************************************************************/

		/**
		 * Retrieves the endpoint URL for a given provider and optional region.
		 *
		 * @param string      $provider_key The unique key identifying the provider.
		 * @param string|null $region_key   The key of the desired region. If null, the provider's default region is used.
		 * @param string      $account_id   The account ID which can be replaced in the endpoint URL.
		 *
		 * @return string The complete endpoint URL for the given provider and region.
		 *
		 * @throws Exception When the specified region does not exist for the given provider.
		 */
		public function get_endpoint( string $provider_key, string $region_key = null, string $account_id = '' ): string {
			$this->validate_provider( $provider_key );

			$provider   = $this->providers[ $provider_key ];
			$region_key = $region_key ?: $provider->get_default_region();

			if ( ! $provider->region_exists( $region_key ) ) {
				throw new Exception( "The region '{$region_key}' does not exist for the provider '{$provider_key}'." );
			}

			if ( $provider->has_account_id() && empty( $account_id ) ) {
				throw new Exception( "An account ID is required for the provider '{$provider_key}' in the region '{$region_key}'." );
			}

			return $provider->get_endpoint( $region_key, $account_id );
		}

		/**
		 * Verifies if the endpoint for the given region and account ID is accessible.
		 *
		 * This method checks the accessibility of the domain derived from the endpoint
		 * URL of the provider for a given region and account ID. If the endpoint domain
		 * is accessible and returns a 200 OK response, the method will return true.
		 * If there is an exception, or if the domain is not accessible, it will return false.
		 *
		 * @param string      $provider_key The unique key identifying the provider.
		 * @param string|null $region_key   The key representing the region.
		 * @param string      $account_id   (Optional) The account ID to replace in the endpoint URL.
		 *
		 * @return string True if the endpoint is valid and accessible, otherwise false.
		 * @throws Exception
		 */
		public function verify_endpoint( string $provider_key, string $region_key = null, string $account_id = '' ): string {
			$this->validate_provider( $provider_key );

			$provider   = $this->providers[ $provider_key ];
			$region_key = $region_key ?: $provider->get_default_region();

			if ( ! $provider->region_exists( $region_key ) ) {
				throw new Exception( "The region '{$region_key}' does not exist for the provider '{$provider_key}'." );
			}

			if ( $provider->has_account_id() && empty( $account_id ) ) {
				throw new Exception( "An account ID is required for the provider '{$provider_key}' in the region '{$region_key}'." );
			}

			return $provider->verify_endpoint( $region_key, $account_id );
		}

		/** Helper ****************************************************************/

		/**
		 * Checks if {account_id} placeholder exists in the endpoint URL/URI.
		 *
		 * @param string $provider_key
		 *
		 * @return bool True if {account_id} exists, otherwise false.
		 * @throws Exception If provider does not exist.
		 */
		public function has_account_id( string $provider_key ): bool {
			$this->validate_provider( $provider_key );

			return $this->providers[ $provider_key ]->has_account_id();
		}

		/**
		 * Indicates if the provider's endpoint uses a path-style URL.
		 *
		 * @param string $provider_key
		 *
		 * @return bool True if path-style, otherwise false.
		 * @throws Exception If provider does not exist.
		 */
		public function is_path_style( string $provider_key ): bool {
			$this->validate_provider( $provider_key );

			return $this->providers[ $provider_key ]->get_path_style();
		}

		/** Loader ****************************************************************/

		/**
		 * Calculate the SHA256 checksum of the file.
		 *
		 * @param string|null $json_path Path to the JSON file.
		 *
		 * @return string SHA256 checksum.
		 * @throws Exception If the file does not exist.
		 */
		public function get_checksum( string $json_path = null ): string {
			return Loader::get_checksum( $json_path );
		}

	}

endif;