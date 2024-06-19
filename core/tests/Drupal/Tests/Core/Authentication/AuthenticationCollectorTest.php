<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Authentication;

use Drupal\Core\Authentication\AuthenticationCollector;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\Authentication\AuthenticationCollector
 * @group Authentication
 */
class AuthenticationCollectorTest extends UnitTestCase {

  /**
   * Tests adding, getting, and order of priorities.
   *
   * @covers ::addProvider
   * @covers ::getSortedProviders
   * @covers ::getProvider
   * @covers ::isGlobal
   */
  public function testAuthenticationCollector(): void {
    $providers = [];
    $global = [];
    $authentication_collector = new AuthenticationCollector();
    $priorities = [2, 0, -8, 10, 1, 3, -5, 0, 6, -10, -4];
    foreach ($priorities as $priority) {
      $provider_id = $this->randomMachineName();
      $provider = new TestAuthenticationProvider($provider_id);
      $providers[$priority][$provider_id] = $provider;
      $global[$provider_id] = rand(0, 1) > 0.5;
      $authentication_collector->addProvider($provider, $provider_id, $priority, $global[$provider_id]);
    }
    // Sort the $providers array by priority (highest number is lowest priority)
    // and compare with AuthenticationCollector::getSortedProviders().
    krsort($providers);

    // Merge nested providers from $providers into $sorted_providers.
    $sorted_providers = array_merge(...$providers);
    $this->assertEquals($sorted_providers, $authentication_collector->getSortedProviders());

    // Test AuthenticationCollector::getProvider() and
    // AuthenticationCollector::isGlobal().
    foreach ($sorted_providers as $provider) {
      $this->assertEquals($provider, $authentication_collector->getProvider($provider->providerId));
      $this->assertEquals($global[$provider->providerId], $authentication_collector->isGlobal($provider->providerId));
    }
  }

}

/**
 * A simple provider for unit testing AuthenticationCollector.
 */
class TestAuthenticationProvider implements AuthenticationProviderInterface {

  /**
   * The provider id.
   *
   * @var string
   */
  public $providerId;

  /**
   * Constructor.
   */
  public function __construct($provider_id) {
    $this->providerId = $provider_id;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    return NULL;
  }

}
