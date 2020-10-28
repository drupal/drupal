<?php

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\CompiledRoute;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Routing\CompiledRoute
 * @group Routing
 * @group legacy
 */
class CompiledRouteLegacyTest extends UnitTestCase {

  /**
   * A compiled route object for testing purposes.
   *
   * @var \Drupal\Core\Routing\CompiledRoute
   */
  private $compiled_route;

  /**
   * @var \Symfony\Component\Routing\Route
   *   A mocked Route object.
   */
  private $mocked_route;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // We can pass in dummy values because we're not doing anything in this
    // test except for verifying the deprecation notices are being thrown.
    $this->compiled_route = new CompiledRoute(0, "", 0, "", "", [], []);
    $this->mocked_route = $this->createMock('Symfony\Component\Routing\Route');

    $this->mocked_route->expects($this->any())
      ->method('getDefaults')
      ->willReturn([]);

    $this->mocked_route->expects($this->any())
      ->method('getRequirements')
      ->willReturn([]);

    $this->mocked_route->expects($this->any())
      ->method('getOptions')
      ->willReturn([]);

  }

  /**
   * Tests for deprecated message and no PHP error.
   *
   * @covers ::getOptions
   */
  public function testOptionsDeprecated() {
    $this->expectDeprecation('Drupal\Core\Routing\CompiledRoute::getOptions() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3159706');
    if (PHP_VERSION_ID >= 80000) {
      $this->expectWarning();
      $this->expectWarningMessage('Undefined property: Drupal\Core\Routing\CompiledRoute::$route');
    }
    else {
      $this->expectNotice();
      $this->expectNoticeMessage('Undefined property: Drupal\Core\Routing\CompiledRoute::$route');
    }
    $this->compiled_route->getOptions();
  }

  /**
   * Tests to make sure we get an array when dynamically setting.
   *
   * @covers ::getOptions
   */
  public function testOptionsDynamicallySet() {
    $this->expectDeprecation('Drupal\Core\Routing\CompiledRoute::getOptions() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3159706');
    $this->compiled_route->route = $this->mocked_route;
    $this->compiled_route->getOptions();
  }

  /**
   * Tests for deprecated message and no PHP error.
   *
   * @covers ::getDefaults
   */
  public function testDefaultsDeprecated() {
    $this->expectDeprecation('Drupal\Core\Routing\CompiledRoute::getDefaults() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3159706');
    if (PHP_VERSION_ID >= 80000) {
      $this->expectWarning();
      $this->expectWarningMessage('Undefined property: Drupal\Core\Routing\CompiledRoute::$route');
    }
    else {
      $this->expectNotice();
      $this->expectNoticeMessage('Undefined property: Drupal\Core\Routing\CompiledRoute::$route');
    }
    $this->compiled_route->getDefaults();
  }

  /**
   * Tests to make sure we get an array when dynamically setting.
   *
   * @covers ::getDefaults
   */
  public function testDefaultsDynamicallySet() {
    $this->expectDeprecation('Drupal\Core\Routing\CompiledRoute::getDefaults() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3159706');
    $this->compiled_route->route = $this->mocked_route;
    $this->compiled_route->getDefaults();
  }

  /**
   * @covers ::getRequirements
   */
  public function testRequirementsDeprecated() {
    $this->expectDeprecation('Drupal\Core\Routing\CompiledRoute::getRequirements() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3159706');
    if (PHP_VERSION_ID >= 80000) {
      $this->expectWarning();
      $this->expectWarningMessage('Undefined property: Drupal\Core\Routing\CompiledRoute::$route');
    }
    else {
      $this->expectNotice();
      $this->expectNoticeMessage('Undefined property: Drupal\Core\Routing\CompiledRoute::$route');
    }
    $this->compiled_route->getRequirements();
  }

  /**
   * Tests to make sure we get an array when dynamically setting.
   *
   * @covers ::getRequirements
   */
  public function testRequirementsDynamicallySet() {
    $this->expectDeprecation('Drupal\Core\Routing\CompiledRoute::getRequirements() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3159706');
    $this->compiled_route->route = $this->mocked_route;
    $this->compiled_route->getRequirements();
  }

}
