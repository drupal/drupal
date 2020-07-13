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
    'asm89/stack-cors' => ['test'],
    'behat/mink' => ['tests', 'driver-testsuite'],
    'behat/mink-browserkit-driver' => ['tests'],
    'behat/mink-goutte-driver' => ['tests'],
    'behat/mink-selenium2-driver' => ['tests'],
    'composer/ca-bundle' => ['tests'],
    'composer/composer' => ['bin', 'tests'],
    'composer/installers' => ['tests'],
    'composer/semver' => ['tests'],
    'composer/spdx-licenses' => ['tests'],
    'composer/xdebug-handler' => ['tests'],
    'doctrine/annotations' => ['tests'],
    'doctrine/instantiator' => ['tests'],
    'doctrine/lexer' => ['tests'],
    'doctrine/reflection' => ['tests'],
    'drupal/coder' => [
      'coder_sniffer/Drupal/Test',
      'coder_sniffer/DrupalPractice/Test',
    ],
    'drupal/core' => [],
    'drupal/core-project-message' => [],
    'drupal/core-vendor-hardening' => [],
    'easyrdf/easyrdf' => ['test', 'scripts'],
    'egulias/email-validator' => ['documentation', 'Tests'],
    'fabpot/goutte' => ['Goutte/Tests'],
    'guzzlehttp/guzzle' => ['tests'],
    'guzzlehttp/promises' => ['tests'],
    'guzzlehttp/psr7' => ['tests'],
    'instaclick/php-webdriver' => ['doc', 'test'],
    'justinrainbow/json-schema' => ['demo', 'tests'],
    'laminas/laminas-diactoros' => ['test'],
    'laminas/laminas-escaper' => ['test'],
    'laminas/laminas-feed' => ['test'],
    'laminas/laminas-stdlib' => ['test'],
    'laminas/laminas-zendframework-bridge' => ['test'],
    'masterminds/html5' => ['bin', 'test'],
    'mikey179/vfsstream' => ['examples', 'src/test'],
    'myclabs/deep-copy' => ['doc', 'tests'],
    'pear/archive_tar' => ['docs', 'tests'],
    'pear/console_getopt' => ['tests'],
    'pear/pear-core-minimal' => ['tests'],
    'pear/pear_exception' => ['tests'],
    'phar-io/manifest' => ['examples', 'tests'],
    'phar-io/version' => ['tests'],
    'phpdocumentor/reflection-common' => ['tests'],
    'phpdocumentor/reflection-docblock' => ['tests'],
    'phpdocumentor/type-resolver' => ['tests'],
    'phpspec/prophecy' => ['fixtures', 'spec', 'tests'],
    'phpunit/php-code-coverage' => ['tests'],
    'phpunit/php-file-iterator' => ['tests'],
    'phpunit/php-text-template' => [],
    'phpunit/php-timer' => ['tests'],
    'phpunit/php-token-stream' => ['tests'],
    'phpunit/phpunit' => ['tests'],
    'psr/container' => [],
    'psr/http-factory' => [],
    'psr/http-message' => [],
    'psr/log' => [],
    'ralouphie/getallheaders' => ['tests'],
    'sebastian/code-unit-reverse-lookup' => ['tests'],
    'sebastian/comparator' => ['tests'],
    'sebastian/diff' => ['tests'],
    'sebastian/environment' => ['tests'],
    'sebastian/exporter' => ['tests'],
    'sebastian/global-state' => ['tests'],
    'sebastian/object-enumerator' => ['tests'],
    'sebastian/object-reflector' => ['tests'],
    'sebastian/recursion-context' => ['tests'],
    'sebastian/resource-operations' => ['tests'],
    'sebastian/type' => ['tests'],
    'sebastian/version' => [],
    'seld/jsonlint' => ['tests'],
    'seld/phar-utils' => [],
    'squizlabs/php_codesniffer' => ['tests'],
    'stack/builder' => ['tests'],
    'symfony/browser-kit' => ['Tests'],
    'symfony/console' => ['Tests'],
    'symfony/css-selector' => ['Tests'],
    'symfony/debug' => ['Tests'],
    'symfony/dependency-injection' => ['Tests'],
    'symfony/dom-crawler' => ['Tests'],
    'symfony/error-handler' => ['Tests'],
    'symfony/event-dispatcher' => ['Tests'],
    'symfony/event-dispatcher-contracts' => [],
    'symfony/filesystem' => ['Tests'],
    'symfony/finder' => ['Tests'],
    'symfony/http-foundation' => ['Tests'],
    'symfony/http-kernel' => ['Tests'],
    'symfony/lock' => ['Tests'],
    'symfony/mime' => ['Tests'],
    'symfony/phpunit-bridge' => ['Tests'],
    'symfony/polyfill-ctype' => [],
    'symfony/polyfill-iconv' => [],
    'symfony/polyfill-intl-idn' => [],
    'symfony/polyfill-mbstring' => [],
    'symfony/polyfill-php72' => [],
    'symfony/polyfill-php73' => [],
    'symfony/polyfill-php80' => [],
    'symfony/process' => ['Tests'],
    'symfony/psr-http-message-bridge' => ['Tests'],
    'symfony/routing' => ['Tests'],
    'symfony/serializer' => ['Tests'],
    'symfony/service-contracts' => ['Test', 'Tests'],
    'symfony/translation' => ['Tests'],
    'symfony/translation-contracts' => ['Test', 'Tests'],
    'symfony/validator' => ['Test', 'Tests', 'Resources'],
    'symfony/var-dumper' => ['Tests'],
    'symfony/yaml' => ['Tests'],
    'symfony-cmf/routing' => ['tests'],
    'theseer/tokenizer' => ['tests'],
    'twig/twig' => ['doc', 'lib/Twig/Test', 'src/Test', 'tests'],
    'typo3/phar-stream-wrapper' => ['tests'],
    'webmozart/assert' => ['tests'],
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
        isset($this->configData[$package]) ? $this->configData[$package] : [],
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
    return isset($paths[$package]) ? $paths[$package] : [];
  }

}
