<?php
/**
 * Handles the representation and manipulation of S3 regions.
 *
 * @package     ArrayPress/s3-providers
 * @copyright   Copyright (c) 2023, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 * @author      David Sherlock
 * @description Provides methods for fetching and interpreting information related to S3 regions.
 */

namespace ArrayPress\Utils\S3;

/**
 * Represents an S3 region and provides related utilities.
 *
 * If the class already exists in the namespace, it won't be redefined.
 */
if ( ! class_exists( __NAMESPACE__ . '\\Region' ) ) :

	class Region {

		/**
		 * Represents the continent associated with the region.
		 *
		 * @var string
		 */
		private string $continent;

		/**
		 * Represents a human-readable label for the region.
		 *
		 * @var string
		 */
		private string $label;

		/**
		 * Represents the machine-readable key/identifier for the region.
		 *
		 * @var string
		 */
		private string $region;

		/**
		 * Region constructor.
		 *
		 * @param string $continent Continent associated with the region.
		 * @param string $label     Human-readable label for the region.
		 * @param string $region    Machine-readable key/identifier for the region.
		 */
		public function __construct( string $continent, string $label, string $region ) {
			$this->continent = Sanitize::key( $continent );
			$this->label     = Sanitize::html( $label );
			$this->region    = Sanitize::html( $region );
		}

		/**
		 * Retrieves the machine-readable key/identifier for the region.
		 *
		 * @return string Region identifier.
		 */
		public function get_region(): string {
			return $this->region;
		}

		/**
		 * Retrieves the continent associated with the region.
		 *
		 * @return string Continent name.
		 */
		public function get_continent(): string {
			return $this->continent;
		}

		/**
		 * Retrieves the human-readable label for the region.
		 *
		 * @return string Region label.
		 */
		public function get_label(): string {
			return $this->label;
		}

		/**
		 * Retrieves the human-readable label for the region.
		 *
		 * @return string Region label.
		 */
		public function is_auto(): string {
			return 'auto' === strtolower( $this->region );
		}
	}

endif;