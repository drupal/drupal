<?php

namespace Drupal\FunctionalTests\Routing;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests url generation and routing for route paths with encoded characters.
 *
 * @group routing
 */
class PathEncodedTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'path_encoded_test'];

  public function testGetEncoded() {
    $route_paths = [
      'path_encoded_test.colon' => '/hi/llamma:party',
      'path_encoded_test.atsign' => '/bloggy/@Dries',
      'path_encoded_test.parens' => '/cat(box)',
    ];
    foreach ($route_paths as $route_name => $path) {
      $this->drupalGet(Url::fromRoute($route_name));
      $this->assertSession()->pageTextContains('PathEncodedTestController works');
    }
  }

  public function testAliasToEncoded() {
    $route_paths = [
      'path_encoded_test.colon' => '/hi/llamma:party',
      'path_encoded_test.atsign' => '/bloggy/@Dries',
      'path_encoded_test.parens' => '/cat(box)',
    ];
    /** @var \Drupal\Core\Path\AliasStorageInterface $alias_storage */
    $alias_storage = $this->container->get('path.alias_storage');
    $aliases = [];
    foreach ($route_paths as $route_name => $path) {
      $aliases[$route_name] = $this->randomMachineName();
      $alias_storage->save($path, '/' . $aliases[$route_name]);
    }
    foreach ($route_paths as $route_name => $path) {
      // The alias may be only a suffix of the generated path when the test is
      // run with Drupal installed in a subdirectory.
      $this->assertRegExp('@/' . rawurlencode($aliases[$route_name]) . '$@', Url::fromRoute($route_name)->toString());
      $this->drupalGet(Url::fromRoute($route_name));
      $this->assertSession()->pageTextContains('PathEncodedTestController works');
    }
  }

}
