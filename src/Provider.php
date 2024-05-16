<?php
/**
 * Represents a cloud storage provider, detailing its regions, characteristics, and related information.
 *
 * This class serves as a means to encapsulate information about a storage provider, such as its regions,
 * homepage, endpoint structures, and other relevant details. It offers utilities to fetch and interpret
 * these details in a structured manner.
 *
 * @since       1.0.0
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @package     ArrayPress/s3-providers
 * @author      David Sherlock
 * @description Provides methods for fetching, interpreting, and managing information related to storage providers.
 */

namespace ArrayPress\S3\Providers;

use ArrayPress\S3\Sanitize;
use Exception;

use function trim;
use function str_replace;
use function preg_replace;
use function strpos;
use function strtolower;
use function in_array;
use function sprintf;

/**
 * Represents an S3 provider and provides related utilities.
 *
 * If the class already exists in the namespace, it won't be redefined.
 */
if ( ! class_exists( __NAMESPACE__ . '\\Provider' ) ) :

	class Provider {

		/**
		 * The unique key of the provider.
		 *
		 * @var string
		 */
		private string $key;

		/**
		 * The human-readable name of the provider.
		 *
		 * @var string
		 */
		private string $label;

		/**
		 * The supplier or source name of the data.
		 *
		 * @var string
		 */
		private string $supplier;

		/**
		 * Array of regions that the provider operates in.
		 *
		 * @var Region[]
		 */
		private array $regions = array();

		/**
		 * Homepage URL of the provider.
		 *
		 * @var string
		 */
		private string $homepage;

		/**
		 * Homepage URL of the provider dashboard.
		 *
		 * @var string
		 */
		private string $dashboard;

		/**
		 * Default region key for this provider.
		 *
		 * @var string
		 */
		private string $defaultRegion;

		/**
		 * Indicates if the endpoint uses a path-style URL.
		 *
		 * @var bool
		 */
		private bool $usePathStyle;

		/**
		 * Base endpoint URL structure for this provider.
		 *
		 * @var string
		 */
		private string $endpoint;

		/**
		 * Provider constructor.
		 *
		 * @param string $key  The unique key of the provider.
		 * @param object $data The object containing the details of the provider.
		 *
		 * @throws Exception When required data is missing or invalid.
		 */
		public function __construct( string $key, object $data ) {
			$this->key = Sanitize::key( $key );

			if ( empty( $this->key ) ) {
				throw new Exception( "Missing or invalid 'key' for provider." );
			}

			$this->label = Sanitize::html( $data->label ?? '' );
			if ( empty( $this->label ) ) {
				throw new Exception( "Missing or invalid 'label' for provider '{$this->key}'." );
			}

			$this->supplier      = Sanitize::html( $data->supplier ?? '' );
			$this->homepage      = Sanitize::url( $data->homepage ?? '' );
			$this->dashboard     = Sanitize::url( $data->dashboard ?? '' );
			$this->defaultRegion = Sanitize::key( $data->defaultRegion ?? '' );
			$this->endpoint      = $data->endpoint ?? '';

			// Set path_style to false by default if not provided or invalid.
			$this->usePathStyle = isset( $data->usePathStyle ) && Sanitize::bool( $data->usePathStyle );

			if ( empty( $data->regions ) ) {
				throw new Exception( "Invalid or missing 'regions' data for provider '{$this->key}'." );
			}

			foreach ( $data->regions as $continent => $regionGroup ) {
				foreach ( $regionGroup as $regionData ) {
					if ( empty( $regionData->region ) ) {
						throw new Exception( "Missing 'region' key in regions data for provider '{$this->key}'." );
					}

					if ( empty( $regionData->label ) ) {
						throw new Exception( "Missing 'label' key in regions data for provider '{$this->key}'." );
					}

					$this->regions[ $regionData->region ] = new Region(
						$continent,
						$regionData->label,
						$regionData->region
					);
				}
			}
		}

		/**
		 * Retrieves the label (human-readable name) for this provider.
		 *
		 * @return string Provider key.
		 */
		public function getKey(): string {
			return $this->key;
		}

		/**
		 * Retrieves the label (human-readable name) for this provider.
		 *
		 * @return string Provider label.
		 */
		public function getLabel(): string {
			return $this->label;
		}

		/**
		 * Retrieves the supplier name for this provider.
		 *
		 * @return string Supplier name.
		 */
		public function getSupplier(): string {
			return $this->supplier;
		}

		/**
		 * Retrieves all the regions associated with this provider.
		 *
		 * @return array Region An array of Region objects.
		 */
		public function getRegions(): array {
			return $this->regions;
		}

		/**
		 * Retrieves the homepage URL of the provider.
		 *
		 * @return string Homepage URL.
		 */
		public function getHomepage(): string {
			return $this->homepage;
		}

		/**
		 * Retrieves the homepage URL of the provider.
		 *
		 * @return string Homepage URL.
		 */
		public function getDashboard(): string {
			return $this->dashboard;
		}

		/**
		 * Retrieves the default region for this provider.
		 *
		 * @return string The key of the default region.
		 */
		public function getDefaultRegion(): string {
			return $this->defaultRegion;
		}

		/**
		 * Indicates if the provider's endpoint uses a path-style URL.
		 *
		 * @return bool True if path-style, otherwise false.
		 */
		public function usePathStyle(): bool {
			return $this->usePathStyle;
		}

		/**
		 * Retrieves the endpoint URL for a given region.
		 *
		 * @param string      $regionKey      The key representing the region.
		 * @param string      $accountId      The Account ID to be replaced in the endpoint URL, if necessary.
		 * @param string|null $customEndpoint Optionally, a custom endpoint URL to be used.
		 *
		 * @return string The constructed endpoint URL for the given region.
		 *
		 * @throws Exception When the provider requires a custom endpoint, but none is provided.
		 *                   When the provider requires an Account ID, but none is provided.
		 *                   When the specified region does not exist for the provider and no custom endpoint is provided.
		 */
		public function getEndpoint( string $regionKey = '', string $accountId = '', ?string $customEndpoint = null ): string {
			if ( $this->requiresCustomEndpoint() && empty( $customEndpoint ) ) {
				throw new Exception( "A custom endpoint is required for the provider '{$this->key}' when using region '{$regionKey}'." );
			}

			if ( $this->requiresAccountId() && empty( $accountId ) ) {
				throw new Exception( "An Account ID is required for the provider '{$this->key}' when using region '{$regionKey}'." );
			}

			if ( empty( $regionKey ) && ! $this->requiresCustomEndpoint() ) {
				$regionKey = $this->getDefaultRegion();
			}

			if ( ! $this->regionExists( $regionKey ) && empty( $customEndpoint ) ) {
				throw new Exception( "The region '{$regionKey}' does not exist for the provider '{$this->key}'." );
			}

			// Use the custom endpoint if provided; otherwise, use the default
			$endpoint = $this->requiresCustomEndpoint() && ! empty( $customEndpoint )
				? trim( $customEndpoint )
				: $this->endpoint;

			// Handle the case where the region is 'auto'
			if ( $regionKey === 'auto' ) {
				$regionKey = '';
			}

			// Replace placeholders in the endpoint URL with actual values
			$endpoint = str_replace( [ '{region}', '{account_id}' ], [ $regionKey, $accountId ], $endpoint );

			// Remove any trailing dots caused by empty placeholders
			$endpoint = preg_replace( '/\.+/', '.', $endpoint );

			return trim( $endpoint, '.' );
		}

		/**
		 * Checks if a particular region is supported by the provider.
		 *
		 * @param string $regionKey The key of the region to check.
		 *
		 * @return bool True if the region exists for this provider, otherwise false.
		 */
		public function regionExists( string $regionKey ): bool {
			return isset( $this->regions[ $regionKey ] );
		}

		/**
		 * Checks if the default region is supported by the provider.
		 *
		 * @return bool True if the default region exists for this provider, otherwise false.
		 */
		public function defaultRegionExists(): bool {
			return $this->regionExists( $this->getDefaultRegion() );
		}

		/**
		 * Retrieves a specific region based on its key.
		 *
		 * @param string $regionKey The key of the region to retrieve.
		 *
		 * @return Region|null The Region object if it exists, null otherwise.
		 */
		public function getRegion( string $regionKey ): ?Region {
			return $this->regions[ $regionKey ] ?? null;
		}

		/**
		 * Checks if {account_id} placeholder exists in the endpoint URL/URI.
		 *
		 * @return bool True if {account_id} exists, otherwise false.
		 */
		public function requiresAccountId(): bool {
			return strpos( $this->endpoint, '{account_id}' ) !== false;
		}

		/**
		 * Checks if example.com exists in the endpoint URL/URI.
		 *
		 * @return bool True if exists, otherwise false.
		 */
		public function requiresCustomEndpoint(): bool {
			return 'custom' === strtolower( $this->getKey() );
		}

		/**
		 * Retrieves the list of continents that the provider operates in.
		 *
		 * @return array The list of continent names.
		 */
		public function getSupportedContinents(): array {
			$continents = array();
			foreach ( $this->regions as $region ) {
				if ( ! in_array( $region->getContinent(), $continents ) ) {
					$continents[] = $region->getContinent();
				}
			}

			return $continents;
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
		 */
		public function getRegionOptions( string $emptyLabel = '', bool $groupByContinent = false ): array {
			$options = array();

			if ( ! empty( $emptyLabel ) ) {
				$options[''] = $emptyLabel;
			}

			foreach ( $this->regions as $regionKey => $regionObj ) {

				// Use sprintf to format the label with the region in (eu-west) style
				$label = sprintf( '%s (%s)', $regionObj->getLabel(), $regionObj->getRegion() );

				if ( $groupByContinent ) {
					$continent = $regionObj->getContinent();

					// Create a continent array if not already created
					if ( ! isset( $options[ $continent ] ) ) {
						$options[ $continent ] = [];
					}

					$options[ $continent ][ $regionKey ] = $label;
				} else {
					$options[ $regionKey ] = $label;
				}
			}

			return $options;
		}

	}

endif;