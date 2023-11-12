<?php
/**
 * These helper functions provide utilities for working with S3 providers and regions using the Providers class.
 *
 * These functions allow you to retrieve providers, regions, provider options, region options, endpoints, and verify
 * endpoints, handling exceptions gracefully and providing error callback options for robust management of S3
 * resources.
 *
 * Example usage:
 * $provider = get_provider( 'my_provider_key' );
 * $region = get_region( 'my_provider_key', 'my_region_key' );
 * $provider_options = get_provider_options();
 * $region_options = get_region_options( 'my_provider_key' );
 * $endpoint = get_endpoint( 'my_provider_key', 'my_region_key', 'my_account_id', 'custom_endpoint' );
 * $is_valid = verify_endpoint( 'my_provider_key', 'my_region_key', 'my_account_id', 'custom_endpoint' );
 *
 * Note: These functions check for the existence of the Providers class to prevent redefinition.
 *
 * @package     ArrayPress/s3-providers
 * @copyright   Copyright (c) 2023, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 * @author      David Sherlock
 */

namespace ArrayPress\Utils\S3;

use Exception;

/** Providers *************************************************************/

if ( ! function_exists( 'get_providers' ) ) {
	/**
	 * Retrieves providers or handles errors gracefully if exceptions occur.
	 *
	 * @param string|array|null $input          Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
	 * @param string            $context        Describes how the providers are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $error_callback Callback function for error handling.
	 *
	 * @return Provider[]|null An array of Provider objects or null on failure.
	 *
	 * @throws Exception If an exception occurs while retrieving the providers.
	 */
	function get_providers(
		$input = null,
		string $context = '',
		?callable $error_callback = null
	): ?array {
		try {
			$providers = new Providers( $input, $context );

			return $providers->get_providers();
		} catch ( Exception $e ) {
			if ( is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			// Handle the exception or log it if needed
			return null; // Return null on failure
		}
	}
}

if ( ! function_exists( 'get_provider' ) ) {
	/**
	 * Retrieves a specific provider by its key or handles errors gracefully if exceptions occur.
	 *
	 * @param string            $provider_key   Provider key.
	 * @param string|array|null $input          Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
	 * @param string            $context        Describes how the region options are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $error_callback Callback function for error handling.
	 *
	 * @return Provider|null The Provider object or null on failure.
	 *
	 * @throws Exception If an exception occurs while retrieving the provider.
	 */
	function get_provider(
		string $provider_key,
		$input = null,
		string $context = '',
		?callable $error_callback = null
	): ?Provider {
		try {
			$providers = new Providers( $input, $context );

			return $providers->get_provider( $provider_key );
		} catch ( Exception $e ) {
			if ( is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			// Handle the exception or log it if needed
			return null; // Return null on failure
		}
	}
}

if ( ! function_exists( 'get_provider_default_region' ) ) {
	/**
	 * Retrieves the default region for a provider or handles errors gracefully if exceptions occur.
	 *
	 * @param string            $provider_key   The provider key.
	 * @param string|array|null $input          Either a path to the JSON file containing provider data or an array of provider data. If null, it will be loaded from the default JSON file.
	 * @param string            $context        Describes how the region options are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $error_callback Callback function for error handling.
	 *
	 * @return string|null The default region string or null on failure.
	 *
	 * @throws Exception If an exception occurs while retrieving the region.
	 */
	function get_provider_default_region(
		string $provider_key,
		$input = null,
		string $context = '',
		?callable $error_callback = null
	): ?string {
		try {
			$providers = new Providers( $input, $context );

			return $providers->get_default_region( $provider_key );
		} catch ( Exception $e ) {
			if ( is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			// Handle the exception or log it if needed
			return null; // Return null on failure
		}
	}
}

/** Regions ***************************************************************/

if ( ! function_exists( 'get_regions' ) ) {
	/**
	 * Retrieves providers or handles errors gracefully if exceptions occur.
	 *
	 * @param string            $provider_key   Provider key.
	 * @param string|array|null $input          Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
	 * @param string            $context        Describes how the providers are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $error_callback Callback function for error handling.
	 *
	 * @return Provider[]|null An array of Provider objects or null on failure.
	 *
	 * @throws Exception If an exception occurs while retrieving the providers.
	 */
	function get_regions(
		string $provider_key,
		$input = null,
		string $context = '',
		?callable $error_callback = null
	): ?array {
		try {
			$providers = new Providers( $input, $context );

			return $providers->get_regions( $provider_key );
		} catch ( Exception $e ) {
			if ( is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			// Handle the exception or log it if needed
			return null; // Return null on failure
		}
	}
}

if ( ! function_exists( 'get_region' ) ) {
	/**
	 * Retrieves a specific region for a provider or handles errors gracefully if exceptions occur.
	 *
	 * @param string            $provider_key   Provider key.
	 * @param string            $region_key     Region key.
	 * @param string|array|null $input          Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
	 * @param string            $context        Describes how the region options are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $error_callback Callback function for error handling.
	 *
	 * @return Region|null The Region object or null on failure.
	 *
	 * @throws Exception If an exception occurs while retrieving the region.
	 */
	function get_region(
		string $provider_key,
		string $region_key,
		$input = null,
		string $context = '',
		?callable $error_callback = null
	): ?Region {
		try {
			$providers = new Providers( $input, $context );

			return $providers->get_region( $provider_key, $region_key );
		} catch ( Exception $e ) {
			if ( is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			// Handle the exception or log it if needed
			return null; // Return null on failure
		}
	}
}

/** Options ***************************************************************/

if ( ! function_exists( 'get_provider_options' ) ) {
	/**
	 * Retrieves provider options or handles errors gracefully if exceptions occur.
	 *
	 * This function parses the provided input, which can be either a path to a JSON file containing providers or an
	 * array of providers data. If null, it will be loaded from the default JSON file.
	 *
	 * @param string|array|null $input          Either a path to the JSON file containing providers or an array of providers data.
	 *                                          If null, it will be loaded from the default JSON file.
	 * @param string            $context        Describes how the providers are being called, useful for filtering by specific
	 *                                          plugins that use the library.
	 * @param string            $empty_label    The label for an empty option (default is empty).
	 * @param callable|null     $error_callback Callback function for error handling.
	 *
	 * @return array|false An associative array with 'bucket' and 'object' keys or false on failure.
	 *
	 * @throws Exception If an exception occurs while retrieving provider options.
	 */
	function get_provider_options(
		$input = null,
		string $context = '',
		string $empty_label = '',
		?callable $error_callback = null
	) {
		try {
			$providers = new Providers( $input, $context );

			return $providers->get_provider_options( $empty_label );
		} catch ( Exception $e ) {
			if ( is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			// Handle the exception here (e.g., log it or return a default value)
			return array(); // Return an empty array as a fallback
		}
	}
}

if ( ! function_exists( 'get_region_options' ) ) {
	/**
	 * Retrieves region options for a specific provider or handles errors gracefully if exceptions occur.
	 *
	 * @param string            $provider_key   Provider key. If empty or invalid, the first provider key will be used.
	 * @param string            $empty_label    Label for an empty option (default is empty).
	 * @param string|array|null $input          Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
	 * @param string            $context        Describes how the region options are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $error_callback Callback function for error handling.
	 *
	 * @return array An associative array of region options.
	 *
	 * @throws Exception If an exception occurs while retrieving region options.
	 */
	function get_region_options(
		string $provider_key = '',
		string $empty_label = '',
		$input = null,
		string $context = '',
		?callable $error_callback = null
	): array {
		try {
			$providers          = new Providers( $input, $context );
			$first_provider_key = $providers->get_first_provider_key();

			if ( empty( $first_provider_key ) || ( ! empty( $provider_key ) && ! $providers->provider_exists( $provider_key ) ) ) {
				throw new Exception( "Invalid provider key or no providers available." );
			}

			$provider_key = ! empty( $provider_key ) ? $provider_key : $first_provider_key;

			return $providers->get_region_options( $provider_key, $empty_label );
		} catch ( Exception $e ) {
			if ( is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			// Handle the exception here (e.g., log it or return a default value)
			return array(); // Return an empty array as a fallback
		}
	}
}

/** Endpoint **************************************************************/

if ( ! function_exists( 'get_endpoint' ) ) {
	/**
	 * Retrieves the endpoint URL for a given provider and optional region.
	 *
	 * @param string            $provider_key    The unique key identifying the provider.
	 * @param string|null       $region_key      The key of the desired region. If null, the provider's default region is used.
	 * @param string            $account_id      The account ID which can be replaced in the endpoint URL.
	 * @param string|null       $custom_endpoint The custom endpoint URL to use (optional).
	 * @param string|array|null $input           Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
	 * @param string            $context         Describes how the region options are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $error_callback  Callback function for error handling.
	 *
	 * @return string|false The complete endpoint URL for the given provider and region, or false on failure.
	 *
	 * @throws Exception When the specified region does not exist for the given provider.
	 */
	function get_endpoint(
		string $provider_key,
		string $region_key = '',
		string $account_id = '',
		?string $custom_endpoint = null,
		$input = null,
		string $context = '',
		?callable $error_callback = null
	) {
		try {
			$providers = new Providers( $input, $context );

			return $providers->get_endpoint( $provider_key, $region_key, $account_id, $custom_endpoint );
		} catch ( Exception $e ) {
			if ( is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			// Handle the exception here (e.g., log it or return a default value)
			return false; // Return false as a fallback
		}
	}
}

if ( ! function_exists( 'verify_endpoint' ) ) {
	/**
	 * Retrieves the endpoint URL for a given provider and optional region.
	 *
	 * @param string            $provider_key    The unique key identifying the provider.
	 * @param string            $region_key      The key of the desired region. If null, the provider's default region is used.
	 * @param string            $account_id      The account ID which can be replaced in the endpoint URL.
	 * @param string|null       $custom_endpoint The custom endpoint URL to use (optional).
	 * @param string|array|null $input           Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
	 * @param string            $context         Describes how the region options are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $error_callback  Callback function for error handling.
	 *
	 * @return boolean|false The complete endpoint URL for the given provider and region, or false on failure.
	 *
	 * @throws Exception When the specified region does not exist for the given provider.
	 */
	function verify_endpoint(
		string $provider_key,
		string $region_key = '',
		string $account_id = '',
		?string $custom_endpoint = null,
		$input = null,
		string $context = '',
		?callable $error_callback = null
	): bool {
		try {
			$providers = new Providers( $input, $context );

			return $providers->verify_endpoint( $provider_key, $region_key, $account_id, $custom_endpoint );
		} catch ( Exception $e ) {
			if ( is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			// Handle the exception here (e.g., log it or return a default value)
			return false; // Return false as a fallback
		}
	}
}

/** Settings **************************************************************/

if ( ! function_exists( 'has_credentials' ) ) {
	/**
	 * Checks if valid credentials are available for generating S3 signer arguments.
	 *
	 * This function determines whether the provided options contain valid credentials
	 * necessary for generating arguments required by an S3 signer.
	 *
	 * @param string        $options_getter The function used to retrieve option values (defaults to 'get_option').
	 * @param string|null   $option_prefix  An optional prefix for option keys.
	 * @param callable|null $error_callback An optional callback for handling errors and exceptions.
	 *
	 * @return bool True if valid credentials are available, false otherwise.
	 * @throws Exception If an exception occurs during credential validation.
	 */
	function has_credentials( string $options_getter = 'get_option', string $option_prefix = null, ?callable $error_callback = null ): bool {
		try {
			$settings = new Settings( $options_getter, $option_prefix );

			return $settings->has_credentials();
		} catch ( Exception $e ) {
			if ( is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			// Handle the exception or log it if needed
			return false;
		}
	}
}

if ( ! function_exists( 'get_signer_args' ) ) {
	/**
	 * Determines if the provided options are valid for generating S3 signer arguments.
	 *
	 * @param string        $options_getter The getter function to retrieve option values (defaults to 'get_option').
	 * @param string|null   $option_prefix  An optional prefix for option keys.
	 * @param array         $args           Additional arguments for generating S3 signer arguments.
	 * @param callable|null $error_callback Callback function for error handling in case of exceptions.
	 *
	 * @return array True if the S3 signer arguments can be generated successfully, false otherwise.
	 * @throws Exception
	 */
	function get_signer_args( string $options_getter = 'get_option', string $option_prefix = null, array $args = [], ?callable $error_callback = null ): ?array {
		try {
			$settings = new Settings( $options_getter, $option_prefix );

			return $settings->get_signer_args( $args );
		} catch ( Exception $e ) {
			if ( is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			// Handle the exception or log it if needed
			return null;
		}
	}
}