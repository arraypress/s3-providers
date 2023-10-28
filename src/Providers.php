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
		 * Constructs the Providers class by initializing providers from a JSON file.
		 *
		 * @param string|null $json_path Path to the JSON file containing providers. Defaults to 'providers.json' in the current directory.
		 *
		 * @throws Exception If JSON file does not exist or has invalid data.
		 */
		public function __construct( $json_path = null ) {

			$json_file = $json_path ?: __DIR__ . '/providers.json';

			if ( ! file_exists( $json_file ) ) {
				throw new Exception( "The JSON file '{$json_file}' does not exist." );
			}

			$data = json_decode( file_get_contents( $json_file ), true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new Exception( 'Invalid JSON format.' );
			}

			if ( ! isset( $data['providers'] ) || ! is_array( $data['providers'] ) || empty( $data['providers'] ) ) {
				throw new Exception( "The JSON data either does not contain the 'providers' key or it's not a valid array or it's empty." );
			}

			// Initialize providers directly from the JSON data
			foreach ( $data['providers'] as $key => $provider_data ) {
				$this->providers[ $key ] = new Provider( $key, $provider_data );
			}
		}

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
			$this->provider_exists( $provider_key );

			return $this->providers[ $provider_key ];
		}

		/**
		 * Validates the existence of a provider by its key.
		 *
		 * @param string $provider_key Provider key.
		 *
		 * @throws Exception If provider does not exist.
		 */
		private function provider_exists( string $provider_key ) {
			if ( ! isset( $this->providers[ $provider_key ] ) ) {
				throw new Exception( "The provider '{$provider_key}' does not exist." );
			}
		}

		/**
		 * Returns an array of providers suitable for use in dropdown menus, etc.
		 *
		 * @param string $empty_label Label to use for the empty option. If not provided or empty, the empty option will be omitted.
		 *
		 * @return array Associative array of providers.
		 */
		public function get_options( string $empty_label = '' ): array {
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
		 * Get all regions for a specific provider.
		 *
		 * @param string $provider_key Provider key.
		 *
		 * @return array An array of Region objects.
		 * @throws Exception If provider does not exist.
		 */
		public function get_regions( string $provider_key ): array {
			$this->provider_exists( $provider_key );

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
			$this->provider_exists( $provider_key );

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
			$this->provider_exists( $provider_key );

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
			$this->provider_exists( $provider_key );

			return $this->providers[ $provider_key ]->get_region_options( $empty_label, $group_by_continent );
		}

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
			$this->provider_exists( $provider_key );

			$provider = $this->providers[ $provider_key ];
			$region   = $region ?: $provider->get_default_region();

			if ( ! $provider->region_exists( $region_key ) ) {
				throw new Exception( "The region '{$region}' does not exist for the provider '{$provider_key}'." );
			}

			if ( $provider->has_account_id() && empty( $account_id ) ) {
				throw new Exception( "An account ID is required for the provider '{$provider_key}' in the region '{$region_key}'." );
			}

			return $provider->get_endpoint( $region_key, $account_id );
		}

		/**
		 * Retrieves the continents for a given provider.
		 *
		 * @param string $provider_key Provider key.
		 *
		 * @return array An array of continents for the provider.
		 * @throws Exception If provider does not exist.
		 */
		public function get_continents( string $provider_key ): array {
			$this->provider_exists( $provider_key );

			return $this->providers[ $provider_key ]->get_continents();
		}

		/**
		 * Checks if {account_id} placeholder exists in the endpoint URL/URI.
		 *
		 * @param string $provider_key
		 *
		 * @return bool True if {account_id} exists, otherwise false.
		 * @throws Exception If provider does not exist.
		 */
		public function has_account_id( string $provider_key ): bool {
			$this->provider_exists( $provider_key );

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
			$this->provider_exists( $provider_key );

			return $this->providers[ $provider_key ]->get_path_style();
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

	}

endif;