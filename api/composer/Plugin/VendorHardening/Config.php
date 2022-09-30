<?php

namespace Drupal\Composer\Plugin\VendorHardening;

use Composer\Package\RootPackageInterface;

/**
 * Determine configuration.
 *
 * Default configuration is merged with the root package's
 * extra:drupal-core-vendor-hardening configuration.
 *
 * @internal
 */
class Config {

  /**
   * The default configuration which will always be merged with user config.
   *
   * @var array
   */
  protected static $defaultConfig = [
    'behat/mink' => ['tests', 'driver-testsuite'],
    'behat/mink-selenium2-driver' => ['tests'],
    'composer/composer' => ['bin'],
    'drupal/coder' => [
      'coder_sniffer/Drupal/Test',
      'coder_sniffer/DrupalPractice/Test',
    ],
    'doctrine/instantiator' => ['tests'],
    'easyrdf/easyrdf' => ['scripts'],
    'egulias/email-validator' => ['documentation', 'tests'],
    'friends-of-behat/mink-browserkit-driver' => ['tests'],
    'guzzlehttp/promises' => ['tests'],
    'guzzlehttp/psr7' => ['tests'],
    'instaclick/php-webdriver' => ['doc', 'test'],
    'justinrainbow/json-schema' => ['demo'],
    'masterminds/html5' => ['bin', 'test'],
    'mikey179/vfsstream' => ['src/test'],
    'myclabs/deep-copy' => ['doc'],
    'pear/archive_tar' => ['docs', 'tests'],
    'pear/console_getopt' => ['tests'],
    'pear/pear-core-minimal' => ['tests'],
    'pear/pear_exception' => ['tests'],
    'phar-io/manifest' => ['examples', 'tests'],
    'phar-io/version' => ['tests'],
    'phpdocumentor/reflection-docblock' => ['tests'],
    'phpspec/prophecy' => ['fixtures', 'spec', 'tests'],
    'phpunit/php-code-coverage' => ['tests'],
    'phpunit/php-timer' => ['tests'],
    'phpunit/php-token-stream' => ['tests'],
    'phpunit/phpunit' => ['tests'],
    'sebastian/code-unit-reverse-lookup' => ['tests'],
    'sebastian/comparator' => ['tests'],
    'sebastian/diff' => ['tests'],
    'sebastian/environment' => ['tests'],
    'sebastian/exporter' => ['tests'],
    'sebastian/global-state' => ['tests'],
    'sebastian/object-enumerator' => ['tests'],
    'sebastian/object-reflector' => ['tests'],
    'sebastian/recursion-context' => ['tests'],
    'seld/jsonlint' => ['tests'],
    'squizlabs/php_codesniffer' => ['tests'],
    'stack/builder' => ['tests'],
    'symfony/browser-kit' => ['Tests'],
    'symfony/console' => ['Tests'],
    'symfony/css-selector' => ['Tests'],
    'symfony/debug' => ['Tests'],
    'symfony/dependency-injection' => ['Tests'],
    'symfony/dom-crawler' => ['Tests'],
    'symfony/filesystem' => ['Tests'],
    'symfony/finder' => ['Tests'],
    'symfony/event-dispatcher' => ['Tests'],
    'symfony/http-foundation' => ['Tests'],
    'symfony/http-kernel' => ['Tests'],
    'symfony/phpunit-bridge' => ['Tests'],
    'symfony/process' => ['Tests'],
    'symfony/psr-http-message-bridge' => ['Tests'],
    'symfony/routing' => ['Tests'],
    'symfony/serializer' => ['Tests'],
    'symfony/translation' => ['Tests'],
    'symfony/var-dumper' => ['Tests'],
    'symfony/validator' => ['Tests', 'Resources'],
    'symfony/yaml' => ['Tests'],
    'symfony-cmf/routing' => ['Test', 'Tests'],
    'theseer/tokenizer' => ['tests'],
    'twig/twig' => ['doc', 'ext', 'test', 'tests'],
  ];

  /**
   * The root package.
   *
   * @var Composer\Package\RootPackageInterface
   */
  protected $rootPackage;

  /**
   * Configuration gleaned from the root package.
   *
   * @var array
   */
  protected $configData = [];

  /**
   * Construct a Config object.
   *
   * @param \Composer\Package\RootPackageInterface $root_package
   *   Composer package object for the root package.
   */
  public function __construct(RootPackageInterface $root_package) {
    $this->rootPackage = $root_package;
  }

  /**
   * Gets the configured list of directories to remove from the root package.
   *
   * This is stored in composer.json extra:drupal-core-vendor-hardening.
   *
   * @return array[]
   *   An array keyed by package name. Each array value is an array of paths,
   *   relative to the package.
   */
  public function getAllCleanupPaths() {
    if ($this->configData) {
      return $this->configData;
    }

    // Get the root package config.
    $package_config = $this->rootPackage->getExtra();
    if (isset($package_config['drupal-core-vendor-hardening'])) {
      $this->configData = array_change_key_case($package_config['drupal-core-vendor-hardening'], CASE_LOWER);
    }

    // Ensure the values are arrays.
    $this->configData = array_map(function ($paths) {
      return (array) $paths;
    }, $this->configData);

    // Merge root config with defaults.
    foreach (array_change_key_case(static::$defaultConfig, CASE_LOWER) as $package => $paths) {
      $this->configData[$package] = array_merge(
        $this->configData[$package] ?? [],
        $paths);
    }
    return $this->configData;
  }

  /**
   * Get a list of paths to remove for the given package.
   *
   * @param string $package
   *   The package name.
   *
   * @return string[]
   *   Array of paths to remove, relative to the package.
   */
  public function getPathsForPackage($package) {
    $package = strtolower($package);
    $paths = $this->getAllCleanupPaths();
    return $paths[$package] ?? [];
  }

}
