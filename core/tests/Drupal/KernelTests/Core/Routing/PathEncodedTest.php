<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Routing;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests URL generation and routing for route paths with encoded characters.
 */
#[Group('path')]
#[Group('routing')]
#[RunTestsInSeparateProcesses]
class PathEncodedTest extends KernelTestBase {

  use PathAliasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'path_encoded_test', 'path_alias'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('path_alias');
  }

  /**
   * Test PathEncodedTestController.
   */
  public function testGetEncoded(): void {
    $route_paths = [
      'path_encoded_test.colon' => '/hi/llama:party',
      'path_encoded_test.at_sign' => '/blog/@Dries',
      'path_encoded_test.parentheses' => '/cat(box)',
    ];
    foreach ($route_paths as $route_name => $path) {
      $this->drupalGet(Url::fromRoute($route_name));
      $this->assertSession()->pageTextContains('PathEncodedTestController works');
    }
  }

  /**
   * Test PathEncodedTestController.
   */
  public function testAliasToEncoded(): void {
    $route_paths = [
      'path_encoded_test.colon' => '/hi/llama:party',
      'path_encoded_test.at_sign' => '/blog/@Dries',
      'path_encoded_test.parentheses' => '/cat(box)',
    ];
    $aliases = [];
    foreach ($route_paths as $route_name => $path) {
      $aliases[$route_name] = $this->randomMachineName();
      $this->createPathAlias($path, '/' . $aliases[$route_name]);
    }
    foreach ($route_paths as $route_name => $path) {
      // The alias may be only a suffix of the generated path when the test is
      // run with Drupal installed in a subdirectory.
      $this->assertMatchesRegularExpression('@/' . rawurlencode($aliases[$route_name]) . '$@', Url::fromRoute($route_name)->toString());
      $this->drupalGet(Url::fromRoute($route_name));
      $this->assertSession()->pageTextContains('PathEncodedTestController works');
    }
  }

}
