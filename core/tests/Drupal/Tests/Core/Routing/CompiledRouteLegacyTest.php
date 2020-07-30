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
   * @expectedDeprecation Drupal\Core\Routing\CompiledRoute::getOptions() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3159706
   */
  public function testOptionsDeprecated() {
    $this->expectNotice();
    $this->expectNoticeMessage('Undefined property: Drupal\Core\Routing\CompiledRoute::$route');
    $this->compiled_route->getOptions();
  }

  /**
   * Tests to make sure we get an array when dynamically setting.
   *
   * @covers ::getOptions
   * @expectedDeprecation Drupal\Core\Routing\CompiledRoute::getOptions() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3159706
   */
  public function testOptionsDynamicallySet() {
    $this->compiled_route->route = $this->mocked_route;
    $this->compiled_route->getOptions();
  }

  /**
   * Tests for deprecated message and no PHP error.
   *
   * @covers ::getDefaults
   * @expectedDeprecation Drupal\Core\Routing\CompiledRoute::getDefaults() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3159706
   */
  public function testDefaultsDeprecated() {
    $this->expectNotice();
    $this->expectNoticeMessage('Undefined property: Drupal\Core\Routing\CompiledRoute::$route');
    $this->compiled_route->getDefaults();
  }

  /**
   * Tests to make sure we get an array when dynamically setting.
   *
   * @covers ::getDefaults
   * @expectedDeprecation Drupal\Core\Routing\CompiledRoute::getDefaults() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3159706
   */
  public function testDefaultsDynamicallySet() {
    $this->compiled_route->route = $this->mocked_route;
    $this->compiled_route->getDefaults();
  }

  /**
   * @covers ::getRequirements
   * @expectedDeprecation Drupal\Core\Routing\CompiledRoute::getRequirements() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3159706
   */
  public function testRequirementsDeprecated() {
    $this->expectNotice();
    $this->expectNoticeMessage('Undefined property: Drupal\Core\Routing\CompiledRoute::$route');
    $this->compiled_route->getRequirements();
  }

  /**
   * Tests to make sure we get an array when dynamically setting.
   *
   * @covers ::getRequirements
   * @expectedDeprecation Drupal\Core\Routing\CompiledRoute::getRequirements() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3159706
   */
  public function testRequirementsDynamicallySet() {
    $this->compiled_route->route = $this->mocked_route;
    $this->compiled_route->getRequirements();
  }

}
