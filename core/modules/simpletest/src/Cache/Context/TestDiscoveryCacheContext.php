<?php

namespace Drupal\simpletest\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;
use Drupal\simpletest\TestDiscovery;

/**
 * Defines the TestDiscoveryCacheContext service.
 *
 * Cache context ID: 'test_discovery'.
 */
class TestDiscoveryCacheContext implements CacheContextInterface {

  /**
   * The test discovery service.
   *
   * @var \Drupal\simpletest\TestDiscovery
   */
  protected $testDiscovery;

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The hash of discovered test information.
   *
   * Services should not be stateful, but we only keep this information per
   * request. That way we don't perform a file scan every time we need this
   * hash. The test scan results are unlikely to change during the request.
   *
   * @var string
   */
  protected $hash;

  /**
   * Construct a test discovery cache context.
   *
   * @param \Drupal\simpletest\TestDiscovery $test_discovery
   *   The test discovery service.
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   */
  public function __construct(TestDiscovery $test_discovery, PrivateKey $private_key) {
    $this->testDiscovery = $test_discovery;
    $this->privateKey = $private_key;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Test discovery');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    if (empty($this->hash)) {
      $tests = $this->testDiscovery->getTestClasses();
      $this->hash = $this->hash(serialize($tests));
    }
    return $this->hash;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

  /**
   * Hashes the given string.
   *
   * @param string $identifier
   *   The string to be hashed.
   *
   * @return string
   *   The hash.
   */
  protected function hash($identifier) {
    return hash('sha256', $this->privateKey->get() . Settings::getHashSalt() . $identifier);
  }

}
