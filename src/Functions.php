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
 * @since       0.1.0
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @package     ArrayPress/s3-providers
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\S3\Providers;

use Exception;
use InvalidArgumentException;
use function call_user_func;
use function is_callable;

/** Providers *************************************************************/

if ( ! function_exists( 'getProviders' ) ) {
	/**
	 * Retrieves providers or handles errors gracefully if exceptions occur.
	 *
	 * @param string|array|null $input         Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
	 * @param string            $context       Describes how the providers are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $errorCallback Callback function for error handling.
	 *
	 * @return Provider[]|null An array of Provider objects or null on failure.
	 *
	 * @throws Exception If an exception occurs while retrieving the providers.
	 */
	function getProviders(
		$input = null,
		string $context = '',
		?callable $errorCallback = null
	): ?array {
		try {
			$registry = new Registry( $input, $context );

			return $registry->getProviders();
		} catch ( Exception $e ) {
			if ( is_callable( $errorCallback ) ) {
				call_user_func( $errorCallback, $e );
			}

			// Handle the exception or log it if needed
			return null; // Return null on failure
		}
	}
}

if ( ! function_exists( 'getProviderOptions' ) ) {
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
	 * @param string            $emptyLabel     The label for an empty option (default is empty).
	 * @param callable|null     $errorCallback  Callback function for error handling.
	 *
	 * @return array|false An associative array with 'bucket' and 'object' keys or false on failure.
	 *
	 * @throws Exception If an exception occurs while retrieving provider options.
	 */
	function getProviderOptions(
		$input = null,
		string $context = '',
		string $emptyLabel = '',
		?callable $errorCallback = null
	) {
		try {
			$registry = new Registry( $input, $context );

			return $registry->getProviderOptions( $emptyLabel );
		} catch ( Exception $e ) {
			if ( is_callable( $errorCallback ) ) {
				call_user_func( $errorCallback, $e );
			}

			// Handle the exception here (e.g., log it or return a default value)
			return array(); // Return an empty array as a fallback
		}
	}
}

if ( ! function_exists( 'getProvider' ) ) {
	/**
	 * Retrieves a specific provider by its key or handles errors gracefully if exceptions occur.
	 *
	 * @param string            $providerKey   Provider key.
	 * @param string|array|null $input         Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
	 * @param string            $context       Describes how the region options are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $errorCallback Callback function for error handling.
	 *
	 * @return Provider|null The Provider object or null on failure.
	 *
	 * @throws Exception If an exception occurs while retrieving the provider.
	 */
	function getProvider(
		string $providerKey,
		$input = null,
		string $context = '',
		?callable $errorCallback = null
	): ?Provider {
		try {
			$registry = new Registry( $input, $context );

			return $registry->getProvider( $providerKey );
		} catch ( Exception $e ) {
			if ( is_callable( $errorCallback ) ) {
				call_user_func( $errorCallback, $e );
			}

			// Handle the exception or log it if needed
			return null; // Return null on failure
		}
	}
}

if ( ! function_exists( 'getProviderDefaultRegion' ) ) {
	/**
	 * Retrieves the default region for a provider or handles errors gracefully if exceptions occur.
	 *
	 * @param string            $providerKey   The provider key.
	 * @param string|array|null $input         Either a path to the JSON file containing provider data or an array of provider data. If null, it will be loaded from the default JSON file.
	 * @param string            $context       Describes how the region options are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $errorCallback Callback function for error handling.
	 *
	 * @return string|null The default region string or null on failure.
	 *
	 * @throws Exception If an exception occurs while retrieving the region.
	 */
	function getProviderDefaultRegion(
		string $providerKey,
		$input = null,
		string $context = '',
		?callable $errorCallback = null
	): ?string {
		try {
			$registry = new Registry( $input, $context );

			return $registry->getDefaultRegion( $providerKey );
		} catch ( Exception $e ) {
			if ( is_callable( $errorCallback ) ) {
				call_user_func( $errorCallback, $e );
			}

			// Handle the exception or log it if needed
			return null; // Return null on failure
		}
	}
}

if ( ! function_exists( 'getProviderRegionOptions' ) ) {
	/**
	 * Retrieve region options for a specified provider.
	 *
	 * This function fetches available region options for a given storage provider, such as AWS, DigitalOcean, etc.
	 * It provides a mechanism to fetch these regions by interfacing with the `getRegionOptions` function.
	 * If no provider key is provided, it defaults to the provider specified in the options retrieved by the
	 * `optionsGetter` callback.
	 *
	 * @since 1.0.0
	 *
	 * @param string            $providerKey   The key or identifier for the storage provider. If empty, defaults to the
	 *                                         value from the options.
	 * @param string            $emptyLabel    An optional label to be used for representing an empty or default choice in
	 *                                         the returned options.
	 * @param string            $optionsGetter The function used to retrieve option values (defaults to 'get_option').
	 * @param string|null       $optionKey     The option key for retrieving the provider (defaults to 's3_provider').
	 * @param string|null       $defaultValue  An optional default value to use if the option is not found.
	 * @param callable|null     $errorCallback An optional callback for handling errors and exceptions.
	 * @param string|array|null $input         Either a path to the JSON file containing providers or an array of providers
	 *                                         data. If null, it will be loaded from the default JSON file.
	 * @param string            $context       Describes how the region options are being retrieved, useful for filtering
	 *                                         by specific plugins that use the library.
	 *
	 * @return array An associative array of region identifiers and their human-readable labels.
	 *
	 * @throws Exception
	 */
	function getProviderRegionOptions(
		string $providerKey = '',
		string $emptyLabel = '',
		string $optionsGetter = 'get_option',
		?string $optionKey = 's3_provider',
		?string $defaultValue = '',
		?callable $errorCallback = null,
		$input = null,
		string $context = ''
	): array {
		try {
			if ( is_callable( $optionsGetter ) ) {
				if ( empty( $providerKey ) ) {
					$providerKey = is_null( $defaultValue ) ?
						call_user_func( $optionsGetter, $optionKey ) :
						call_user_func( $optionsGetter, $optionKey, $defaultValue );
				}
			} else {
				throw new InvalidArgumentException( "The provided optionsGetter is not callable." );
			}

			// Fetch region options using the helper function
			return getRegionOptions(
				$providerKey,
				$emptyLabel,
				$input,
				$context,
				$errorCallback
			);
		} catch ( Exception $e ) {
			if ( is_callable( $errorCallback ) ) {
				call_user_func( $errorCallback, $e );
			}

			// Handle the exception or log it if needed
			return []; // Return an empty array as a fallback
		}
	}
}

/** Regions ***************************************************************/

if ( ! function_exists( 'getRegions' ) ) {
	/**
	 * Retrieves providers or handles errors gracefully if exceptions occur.
	 *
	 * @param string            $providerKey   Provider key.
	 * @param string|array|null $input         Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
	 * @param string            $context       Describes how the providers are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $errorCallback Callback function for error handling.
	 *
	 * @return Provider[]|null An array of Provider objects or null on failure.
	 *
	 * @throws Exception If an exception occurs while retrieving the providers.
	 */
	function getRegions(
		string $providerKey,
		$input = null,
		string $context = '',
		?callable $errorCallback = null
	): ?array {
		try {
			$registry = new Registry( $input, $context );

			return $registry->getRegions( $providerKey );
		} catch ( Exception $e ) {
			if ( is_callable( $errorCallback ) ) {
				call_user_func( $errorCallback, $e );
			}

			// Handle the exception or log it if needed
			return null; // Return null on failure
		}
	}
}

if ( ! function_exists( 'getRegionOptions' ) ) {
	/**
	 * Retrieves region options for a specific provider or handles errors gracefully if exceptions occur.
	 *
	 * @param string            $providerKey   Provider key. If empty or invalid, the first provider key will be used.
	 * @param string            $emptyLabel    Label for an empty option (default is empty).
	 * @param string|array|null $input         Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
	 * @param string            $context       Describes how the region options are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $errorCallback Callback function for error handling.
	 *
	 * @return array An associative array of region options.
	 *
	 * @throws Exception If an exception occurs while retrieving region options.
	 */
	function getRegionOptions(
		string $providerKey = '',
		string $emptyLabel = '',
		$input = null,
		string $context = '',
		?callable $errorCallback = null
	): array {
		try {
			$registry           = new Registry( $input, $context );
			$first_provider_key = $registry->getFirstProviderKey();

			if ( empty( $first_provider_key ) || ( ! empty( $providerKey ) && ! $registry->providerExists( $providerKey ) ) ) {
				throw new Exception( "Invalid provider key or no providers available." );
			}

			$providerKey = ! empty( $providerKey ) ? $providerKey : $first_provider_key;

			return $registry->getRegionOptions( $providerKey, $emptyLabel );
		} catch ( Exception $e ) {
			if ( is_callable( $errorCallback ) ) {
				call_user_func( $errorCallback, $e );
			}

			// Handle the exception here (e.g., log it or return a default value)
			return array(); // Return an empty array as a fallback
		}
	}
}

if ( ! function_exists( 'getRegion' ) ) {
	/**
	 * Retrieves a specific region for a provider or handles errors gracefully if exceptions occur.
	 *
	 * @param string            $providerKey   Provider key.
	 * @param string            $regionKey     Region key.
	 * @param string|array|null $input         Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
	 * @param string            $context       Describes how the region options are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $errorCallback Callback function for error handling.
	 *
	 * @return Region|null The Region object or null on failure.
	 *
	 * @throws Exception If an exception occurs while retrieving the region.
	 */
	function getRegion(
		string $providerKey,
		string $regionKey,
		$input = null,
		string $context = '',
		?callable $errorCallback = null
	): ?Region {
		try {
			$registry = new Registry( $input, $context );

			return $registry->getRegion( $providerKey, $regionKey );
		} catch ( Exception $e ) {
			if ( is_callable( $errorCallback ) ) {
				call_user_func( $errorCallback, $e );
			}

			// Handle the exception or log it if needed
			return null; // Return null on failure
		}
	}
}

/** Endpoint **************************************************************/

if ( ! function_exists( 'getEndpoint' ) ) {
	/**
	 * Retrieves the endpoint URL for a given provider and optional region.
	 *
	 * @param string            $providerKey    The unique key identifying the provider.
	 * @param string|null       $regionKey      The key of the desired region. If null, the provider's default region is used.
	 * @param string            $accountId      The account ID which can be replaced in the endpoint URL.
	 * @param string|null       $customEndpoint The custom endpoint URL to use (optional).
	 * @param string|array|null $input          Either a path to the JSON file containing providers or an array of providers data. If null, it will be loaded from the default JSON file.
	 * @param string            $context        Describes how the region options are being retrieved, useful for filtering by specific plugins that use the library.
	 * @param callable|null     $errorCallback  Callback function for error handling.
	 *
	 * @return string|false The complete endpoint URL for the given provider and region, or false on failure.
	 *
	 * @throws Exception When the specified region does not exist for the given provider.
	 */
	function getEndpoint(
		string $providerKey,
		string $regionKey = '',
		string $accountId = '',
		?string $customEndpoint = null,
		$input = null,
		string $context = '',
		?callable $errorCallback = null
	) {
		try {
			$registry = new Registry( $input, $context );

			return $registry->getEndpoint( $providerKey, $regionKey, $accountId, $customEndpoint );
		} catch ( Exception $e ) {
			if ( is_callable( $errorCallback ) ) {
				call_user_func( $errorCallback, $e );
			}

			// Handle the exception here (e.g., log it or return a default value)
			return false; // Return false as a fallback
		}
	}
}

/** Settings **************************************************************/

if ( ! function_exists( 'hasCredentials' ) ) {
	/**
	 * Checks if valid credentials are available for generating S3 signer arguments.
	 *
	 * This function determines whether the provided options contain valid credentials
	 * necessary for generating arguments required by an S3 signer.
	 *
	 * @param string        $optionsGetter The function used to retrieve option values (defaults to 'get_option').
	 * @param string|null   $optionPrefix  An optional prefix for option keys.
	 * @param callable|null $errorCallback An optional callback for handling errors and exceptions.
	 *
	 * @return bool True if valid credentials are available, false otherwise.
	 * @throws Exception If an exception occurs during credential validation.
	 */
	function hasCredentials( string $optionsGetter = 'get_option', string $optionPrefix = null, ?callable $errorCallback = null ): bool {
		try {
			$settings = new Settings( $optionsGetter, $optionPrefix );

			return $settings->hasCredentials();
		} catch ( Exception $e ) {
			if ( is_callable( $errorCallback ) ) {
				call_user_func( $errorCallback, $e );
			}

			// Handle the exception or log it if needed
			return false;
		}
	}
}

if ( ! function_exists( 'processPath' ) ) {
	/**
	 * Processes an S3 file path using the Processor class to generate a pre-signed URL or perform other operations.
	 *
	 * This helper function initializes the Processor class with given options and path, then
	 * attempts to generate a pre-signed URL or perform other path-related operations.
	 *
	 * @param string        $path                The S3 file name or path to be processed.
	 * @param string        $optionsGetter       The function name used to retrieve option values (defaults to 'get_option').
	 * @param string|null   $optionPrefix        An optional prefix for option keys.
	 * @param callable|null $errorCallback       Callback function for error handling in case of exceptions.
	 * @param array         $allowedExtensions   List of allowed file extensions for S3 paths.
	 * @param array         $disallowedProtocols List of disallowed protocols in S3 paths.
	 *
	 * @return string|null A pre-signed URL if processing is successful, null otherwise.
	 * @throws Exception If an error occurs during the processing of the S3 path.
	 */
	function processPath(
		string $path,
		string $optionsGetter = 'get_option',
		?string $optionPrefix = null,
		?callable $errorCallback = null,
		array $allowedExtensions = [],
		array $disallowedProtocols = []
	): ?string {
		try {
			$processor = new Processor(
				$path,
				$optionsGetter,
				$optionPrefix,
				$errorCallback,
				$allowedExtensions,
				$disallowedProtocols
			);

			return $processor->getSignedURL();
		} catch ( Exception $e ) {
			if ( is_callable( $errorCallback ) ) {
				call_user_func( $errorCallback, $e );
			}

			// Optionally log the exception or handle it as needed
			return null;
		}
	}
}