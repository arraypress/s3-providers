# Object Storage Providers & Regions Library

The `Providers` class offers a seamless integration experience with a range of popular object storage providers. Designed to work hand-in-hand with the s3-signing library, this class provides an effortless mechanism to fetch correct endpoints based on the provider and region, enhancing the power and flexibility of your applications.

**Key Features:**

* **Extensive Provider Support:** This library encompasses a broad array of object storage providers, including AWS, Linode, CloudFlare R2, Wasabi, Backblaze, and DigitalOcean.
* **Dynamic Endpoint Fetching:** Automatically retrieve the appropriate endpoints for a chosen provider and region, eliminating manual lookup.
* **Endpoint Verification:** Ensure that your endpoints are accurate and up-to-date with the built-in verification system.
* **Flexible Configuration Options:** Beyond just regions, the class supports inputs like account IDs, making it adaptable to various use-cases.
* **Retrieve Providers & Regions:** Conveniently obtain a list of supported providers and regions, making plugin updates or app extensions a breeze.
* **Region Existence Check:** Validate if a specific region exists within a provider, preventing potential errors in applications.
* **Up-to-Date JSON Support:** The library sources providers and regions from an up-to-date JSON file. However, for customized needs, users have the flexibility to pass in their own JSON file or a structured PHP array to override default details.

## Installation and set up

The extension in question needs to have a `composer.json` file, specifically with the following:

```json 
{
  "require": {
    "arraypress/s3-providers": "*"
  },
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/arraypress/s3-providers"
    }
  ]
}
```

Once set up, run `composer install --no-dev`. This should create a new `vendors/` folder
with `arraypress/s3-providers/` inside.

## Leveraging the Object Storage Provider Library

The `Providers` class streamlines the integration with a range of popular object storage providers, including CloudFlare R2 and more. With this tool, you can effortlessly fetch correct endpoints based on provider and region, ensuring accurate and secure connections to your storage solutions. Below is a step-by-step guide to unlock its potential:

### Including the Vendor Library

Before using the `Providers` class, you need to include the Composer-generated autoload file. This file ensures that the required dependencies and classes are loaded into your PHP script. You can include it using the following code:

```php 
// Include the Composer-generated autoload file.
require_once dirname(__FILE__) . '/vendor/autoload.php';
```

### Initialization

Loading providers from a default JSON file:

```php
$providers = new ArrayPress\Utils\S3\Providers();
```

Loading providers from a specific JSON file:

```php
$providers = new ArrayPress\Utils\S3\Providers( '/path/to/providers.json' );
```

Initializing with a predefined array of provider data:

```php
$provider_data = [
    "gcp" => [
        "label" => "Google Cloud Platform",
        //... other provider data, check providers.json for required format
    ]
];
$providers = new ArrayPress\Utils\S3\Providers( $provider_data );
```

### Fetching All Providers

```php
$all_providers = $providers->get_providers();
```

Note: More examples will be added soon.

## Contributions

Contributions to this library are highly appreciated. Raise issues on GitHub or submit pull requests for bug
fixes or new features. Share feedback and suggestions for improvements.

## License

This library is licensed under
the [GNU General Public License v2.0](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html).