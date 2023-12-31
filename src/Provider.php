<?php
/**
 * Represents a cloud storage provider, detailing its regions, characteristics, and related information.
 *
 * This class serves as a means to encapsulate information about a storage provider, such as its regions,
 * homepage, endpoint structures, and other relevant details. It offers utilities to fetch and interpret
 * these details in a structured manner.
 *
 * @package     ArrayPress/s3-providers
 * @copyright   Copyright (c) 2023, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 * @author      David Sherlock
 * @description Provides methods for fetching, interpreting, and managing information related to storage providers.
 */

namespace ArrayPress\Utils\S3;

use Exception;

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
		 * Default region key for this provider.
		 *
		 * @var string
		 */
		private string $default_region;

		/**
		 * Indicates if the endpoint uses a path-style URL.
		 *
		 * @var bool
		 */
		private bool $use_path_style;

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
		 * @param array  $data The array containing the details of the provider.
		 *
		 * @throws Exception When required data is missing or invalid.
		 */
		public function __construct( string $key, array $data ) {
			$this->key = Sanitize::key( $key );

			if ( empty( $this->key ) ) {
				throw new Exception( "Missing or invalid 'key' for provider." );
			}

			$this->label = Sanitize::html( $data['label'] ?? '' );
			if ( empty( $this->label ) ) {
				throw new Exception( "Missing or invalid 'label' for provider '{$this->key}'." );
			}

			$this->supplier       = Sanitize::html( $data['supplier'] ?? '' );
			$this->homepage       = Sanitize::url( $data['homepage'] ?? '' );
			$this->default_region = Sanitize::key( $data['defaultRegion'] ?? '' );
			$this->endpoint       = $data['endpoint'] ?? '';

			// Set path_style to false by default if not provided or invalid.
			$this->use_path_style = isset( $data['usePathStyle'] ) && Sanitize::bool( $data['usePathStyle'] );

			if ( ! isset( $data['regions'] ) || ! is_array( $data['regions'] ) ) {
				throw new Exception( "Invalid or missing 'regions' data for provider '{$this->key}'." );
			}

			foreach ( $data['regions'] as $continent => $region_group ) {
				foreach ( $region_group as $region_data ) {
					if ( empty( $region_data['region'] ) ) {
						throw new Exception( "Missing 'region' key in regions data for provider '{$this->key}'." );
					}

					if ( empty( $region_data['label'] ) ) {
						throw new Exception( "Missing 'label' key in regions data for provider '{$this->key}'." );
					}

					$region_obj                              = new Region(
						$continent,
						$region_data['label'],
						$region_data['region']
					);
					$this->regions[ $region_data['region'] ] = $region_obj; // Use region as a unique identifier
				}
			}
		}

		/**
		 * Retrieves the label (human-readable name) for this provider.
		 *
		 * @return string Provider key.
		 */
		public function get_key(): string {
			return $this->key;
		}

		/**
		 * Retrieves the label (human-readable name) for this provider.
		 *
		 * @return string Provider label.
		 */
		public function get_label(): string {
			return $this->label;
		}

		/**
		 * Retrieves the supplier name for this provider.
		 *
		 * @return string Supplier name.
		 */
		public function get_supplier(): string {
			return $this->supplier;
		}

		/**
		 * Retrieves all the regions associated with this provider.
		 *
		 * @return array Region An array of Region objects.
		 */
		public function get_regions(): array {
			return $this->regions;
		}

		/**
		 * Retrieves the homepage URL of the provider.
		 *
		 * @return string Homepage URL.
		 */
		public function get_homepage(): string {
			return $this->homepage;
		}

		/**
		 * Retrieves the default region for this provider.
		 *
		 * @return string The key of the default region.
		 */
		public function get_default_region(): string {
			return $this->default_region;
		}

		/**
		 * Indicates if the provider's endpoint uses a path-style URL.
		 *
		 * @return bool True if path-style, otherwise false.
		 */
		public function use_path_style(): bool {
			return $this->use_path_style;
		}

		/**
		 * Retrieves the endpoint URL for a given region.
		 *
		 * @param string      $region_key      The key representing the region.
		 * @param string      $account_id      The Account ID to be replaced in the endpoint URL, if necessary.
		 * @param string|null $custom_endpoint Optionally, a custom endpoint URL to be used.
		 *
		 * @return string The constructed endpoint URL for the given region.
		 *
		 * @throws Exception When the provider requires a custom endpoint, but none is provided.
		 *                   When the provider requires an Account ID, but none is provided.
		 *                   When the specified region does not exist for the provider and no custom endpoint is provided.
		 */
		public function get_endpoint( string $region_key = '', string $account_id = '', ?string $custom_endpoint = null ): string {
			if ( $this->requires_custom_endpoint() && empty( $custom_endpoint ) ) {
				throw new Exception( "A custom endpoint is required for the provider '{$this->key}' when using region '{$region_key}'." );
			}

			if ( $this->requires_account_id() && empty( $account_id ) ) {
				throw new Exception( "An Account ID is required for the provider '{$this->key}' when using region '{$region_key}'." );
			}

			if ( empty( $region_key ) && ! $this->requires_custom_endpoint() ) {
				$region_key = $this->get_default_region();
			}

			if ( ! $this->region_exists( $region_key ) && empty( $custom_endpoint ) ) {
				throw new Exception( "The region '{$region_key}' does not exist for the provider '{$this->key}'." );
			}

			// Use the custom endpoint if provided; otherwise, use the default
			$endpoint = ! empty( $custom_endpoint ) ? trim( $custom_endpoint ) : $this->endpoint;

			$endpoint = str_replace( '{region}', $region_key, $endpoint );
			$endpoint = str_replace( '{account_id}', $account_id, $endpoint );

			return $endpoint;
		}

		/**
		 * Checks if a particular region is supported by the provider.
		 *
		 * @param string $region_key The key of the region to check.
		 *
		 * @return bool True if the region exists for this provider, otherwise false.
		 */
		public function region_exists( string $region_key ): bool {
			return isset( $this->regions[ $region_key ] );
		}

		/**
		 * Checks if the default region is supported by the provider.
		 *
		 * @return bool True if the default region exists for this provider, otherwise false.
		 */
		public function default_region_exists(): bool {
			return $this->region_exists( $this->get_default_region() );
		}

		/**
		 * Retrieves a specific region based on its key.
		 *
		 * @param string $region_key The key of the region to retrieve.
		 *
		 * @return Region|null The Region object if it exists, null otherwise.
		 */
		public function get_region( string $region_key ): ?Region {
			return $this->regions[ $region_key ] ?? null;
		}

		/**
		 * Checks if {account_id} placeholder exists in the endpoint URL/URI.
		 *
		 * @return bool True if {account_id} exists, otherwise false.
		 */
		public function requires_account_id(): bool {
			return strpos( $this->endpoint, '{account_id}' ) !== false;
		}

		/**
		 * Checks if example.com exists in the endpoint URL/URI.
		 *
		 * @return bool True if exists, otherwise false.
		 */
		public function requires_custom_endpoint(): bool {
			return 'custom' === strtolower( $this->get_key() );
		}

		/**
		 * Retrieves the list of continents that the provider operates in.
		 *
		 * @return array The list of continent names.
		 */
		public function get_supported_continents(): array {
			$continents = array();
			foreach ( $this->regions as $region ) {
				if ( ! in_array( $region->get_continent(), $continents ) ) {
					$continents[] = $region->get_continent();
				}
			}

			return $continents;
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
		 */
		public function get_region_options( string $empty_label = '', bool $group_by_continent = false ): array {
			$options = array();

			if ( ! empty( $empty_label ) ) {
				$options[''] = $empty_label;
			}

			foreach ( $this->regions as $region_key => $region_obj ) {
				// Use sprintf to format the label with the region in (eu-west) style
				$label = sprintf( '%s (%s)', $region_obj->get_label(), $region_obj->get_region() );


				if ( $group_by_continent ) {
					// Group by continent using `get_continent()` method from the Region object
					$continent = $region_obj->get_continent();

					// Create a continent array if not already created
					if ( ! isset( $options[ $continent ] ) ) {
						$options[ $continent ] = [];
					}

					$options[ $continent ][ $region_key ] = $label;
				} else {
					$options[ $region_key ] = $label;
				}
			}

			return $options;
		}

	}

endif;