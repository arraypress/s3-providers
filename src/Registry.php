<?php
/**
 * The Registry class manages and provides utilities related to S3 providers.
 *
 * This class offers various methods to interact with providers and their regions,
 * which are initialized from a JSON file. The class ensures methods to fetch individual
 * or all providers, retrieve endpoint URLs, determine the presence of account ID placeholders,
 * check the URL style, fetch regions, and generate options suitable for dropdown menus, among others.
 *
 * Example usage:
 * $providers = new Registry();
 * $allProviders = $providers->getProviders();
 * $endpoint = $providers->get_endpoint('provider_key', 'region_key', 'account_id');
 *
 * Note: This class checks for its own existence before being defined to prevent redefinition.
 *
 * @package     ArrayPress/s3-providers
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @since       0.1.0
 * @author      David Sherlock
 */

namespace ArrayPress\S3\Providers;

use Exception;
use function is_null;
use function is_string;
use function is_array;
use function ksort;
use function reset;
use function key;
use function array_key_exists;

/**
 * Manages S3 providers and offers utilities for interacting with them and their regions.
 *
 * If the class already exists in the namespace, it won't be redefined.
 */
if ( ! class_exists( __NAMESPACE__ . '\\Registry' ) ) :

	class Registry {

		/**
		 * Array of provider objects.
		 *
		 * @var Provider[]
		 */
		private array $providers = []; // Store initialized providers

		/**
		 * Constructs the Providers class.
		 *
		 * Initializes the providers either from a given JSON file path, an array of provider data, or the default JSON file.
		 *
		 * @param string|array|null $input   Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
		 * @param string            $context Describes how the providers are being called, useful for filtering by specific plugins that use the library.
		 *
		 * @throws Exception If the provider data is invalid.
		 */
		public function __construct( $input = null, string $context = '' ) {

			// Check if input is empty or not
			if ( empty( $input ) && ! is_null( $input ) ) {
				throw new Exception( 'Input is empty. It should either be a valid path to the JSON file, an array of provider data, or null to load from the default JSON file.' );
			}

			if ( is_string( $input ) ) {
				$providersData = Loader::load( $input );
			} elseif ( is_array( $input ) ) {
				if ( empty( $input ) ) {
					throw new Exception( 'The provided array is empty. Please provide a valid array of provider data.' );
				}
				$providersData = $input;
			} else {
				$providersData = Loader::load();
			}

			/**
			 * Filters the providers' data.
			 *
			 * Allows developers to modify the providers' data based on the context or other criteria.
			 *
			 * @param array    $providersData The original providers' data.
			 * @param string   $context       Describes how the providers are being called.
			 * @param Registry $this          The instance of the Providers class.
			 */
			if ( function_exists( 'apply_filters' ) ) {
				$providersData = apply_filters( 's3_providers_data', $providersData, $context, $this );
			}

			foreach ( $providersData as $key => $provider_data ) {
				$this->providers[ $key ] = new Provider( $key, $provider_data );
			}

			// Sort providers by key
			ksort( $this->providers );
		}

		/** Providers *************************************************************/

		/**
		 * Retrieves all the providers.
		 *
		 * @return array An array of Provider objects.
		 */
		public function getProviders(): array {
			return $this->providers;
		}

		/**
		 * Retrieves a specific provider by its key.
		 *
		 * @param string $providerKey Provider key.
		 *
		 * @return Provider The provider object.
		 * @throws Exception If provider does not exist.
		 */
		public function getProvider( string $providerKey ): Provider {
			$this->validateProvider( $providerKey );

			return $this->providers[ $providerKey ];
		}

		/**
		 * Retrieves the key of the first provider from the providers array.
		 *
		 * @return string|null The key of the first provider or null if the providers array is empty.
		 */
		public function getFirstProviderKey(): ?string {
			reset( $this->providers ); // Reset the internal pointer of the array
			$firstProviderKey = key( $this->providers ); // Get the key of the current element

			return $firstProviderKey ?: null;
		}

		/**
		 * Checks if a provider exists by its key.
		 *
		 * @param string $providerKey Provider key.
		 *
		 * @return bool True if the provider exists, otherwise false.
		 */
		public function providerExists( string $providerKey ): bool {
			return isset( $this->providers[ $providerKey ] );
		}

		/**
		 * Returns an array of providers suitable for use in dropdown menus, etc.
		 *
		 * @param string $emptyLabel Label to use for the empty option. If not provided or empty, the empty option will be omitted.
		 *
		 * @return array Associative array of providers.
		 */
		public function getProviderOptions( string $emptyLabel = '' ): array {
			$options = [];

			if ( ! empty( $emptyLabel ) ) {
				$options[''] = $emptyLabel;
			}

			if ( ! empty( $this->providers ) ) {
				foreach ( $this->providers as $providerKey => $provider ) {
					if ( ! array_key_exists( $providerKey, $options ) ) {
						$options[ $providerKey ] = $provider->getLabel();
					}
				}
			}


			return $options;
		}

		/** Regions ***************************************************************/

		/**
		 * Get all regions for a specific provider.
		 *
		 * @param string $providerKey Provider key.
		 *
		 * @return array An array of Region objects.
		 * @throws Exception If provider does not exist.
		 */
		public function getRegions( string $providerKey ): array {
			$this->validateProvider( $providerKey );

			return $this->providers[ $providerKey ]->getRegions();
		}

		/**
		 * Retrieves a specific provider region based on its key.
		 *
		 * @param string $providerKey Provider key.
		 * @param string $regionKey   The key of the region to retrieve.
		 *
		 * @return Region An array of Region objects.
		 * @throws Exception If provider does not exist.
		 */
		public function getRegion( string $providerKey, string $regionKey ): Region {
			$this->validateProvider( $providerKey );

			return $this->providers[ $providerKey ]->getRegion( $regionKey );
		}

		/**
		 * Checks if a particular region is supported by the provider.
		 *
		 * @param string $providerKey Provider key.
		 * @param string $regionKey   The key of the region to retrieve.
		 *
		 * @return bool True if the region exists for this provider, otherwise false.
		 * @throws Exception If provider does not exist.
		 */
		public function regionExists( string $providerKey, string $regionKey ): bool {
			$this->validateProvider( $providerKey );

			return $this->providers[ $providerKey ]->regionExists( $regionKey );
		}

		/**
		 * Returns an array of regions suitable for use in dropdown menus, etc.
		 *
		 * @param string $emptyLabel         Label to use for the empty option. If not provided or empty,
		 *                                   the empty option will be omitted.
		 * @param bool   $groupByContinent   If true, group regions by their respective continents.
		 *
		 * @return array Depending on the grouping flag, it either returns a simple associative array of
		 *               regions or a nested associative array grouped by continents.
		 * @throws Exception When the specified region does not exist for the given provider.
		 */
		public function getRegionOptions( string $providerKey, string $emptyLabel = '', bool $groupByContinent = false ): array {
			$this->validateProvider( $providerKey );

			return $this->providers[ $providerKey ]->getRegionOptions( $emptyLabel, $groupByContinent );
		}

		/**
		 * Retrieve the default region key for the provider.
		 *
		 * @param string $providerKey Provider key.
		 *
		 * @return string The default region key for this provider.
		 * @throws Exception If the provider does not exist.
		 */
		public function getDefaultRegion( string $providerKey ): string {
			$this->validateProvider( $providerKey );

			return $this->providers[ $providerKey ]->getDefaultRegion();
		}

		/** Endpoints *************************************************************/

		/**
		 * Retrieves the endpoint URL for a given provider and optional region.
		 *
		 * @param string      $providerKey    The unique key identifying the provider.
		 * @param string      $regionKey      The key of the desired region. If null, the provider's default region is used.
		 * @param string      $accountId      The account ID which can be replaced in the endpoint URL.
		 * @param string|null $customEndpoint The custom endpoint URL to use (optional).
		 *
		 * @return string The complete endpoint URL for the given provider and region.
		 *
		 * @throws Exception When the specified region does not exist for the given provider.
		 */
		public function getEndpoint( string $providerKey, string $regionKey = '', string $accountId = '', ?string $customEndpoint = null ): string {
			$this->validateProvider( $providerKey );

			return $this->providers[ $providerKey ]->getEndpoint( $regionKey, $accountId, $customEndpoint );
		}

		/** Validation ************************************************************/

		/**
		 * Validates the existence of a provider by its key.
		 *
		 * @param string $providerKey Provider key.
		 *
		 * @throws Exception If provider does not exist.
		 */
		private function validateProvider( string $providerKey ) {
			if ( ! isset( $this->providers[ $providerKey ] ) ) {
				throw new Exception( "The provider '{$providerKey}' does not exist." );
			}
		}

		/** Checksum **************************************************************/

		/**
		 * Calculate the SHA256 checksum of the file.
		 *
		 * @param string|null $jsonPath Path to the JSON file.
		 *
		 * @return string SHA256 checksum.
		 * @throws Exception If the file does not exist.
		 */
		public function getChecksum( string $jsonPath = null ): string {
			return Loader::getChecksum( $jsonPath );
		}

	}

endif;