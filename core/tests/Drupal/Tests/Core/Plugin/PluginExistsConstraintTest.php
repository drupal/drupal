<?php

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\Plugin\Validation\Constraint\PluginExistsConstraint;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @group Plugin
 * @group Validation
 *
 * @coversDefaultClass \Drupal\Core\Plugin\Plugin\Validation\Constraint\PluginExistsConstraint
 */
class PluginExistsConstraintTest extends UnitTestCase {

  /**
   * Tests missing option.
   *
   * @covers ::create
   */
  public function testMissingOption(): void {
    $this->expectException(MissingOptionsException::class);
    $this->expectExceptionMessage('The option "manager" must be set for constraint "Drupal\Core\Plugin\Plugin\Validation\Constraint\PluginExistsConstraint".');
    $container = $this->createMock(ContainerInterface::class);
    PluginExistsConstraint::create($container, [], 'test_plugin_id', []);
  }

  /**
   * Tests with different option keys.
   *
   * @testWith ["value"]
   *           ["manager"]
   *
   * @covers ::create
   * @covers ::__construct
   */
  public function testOption(string $option_key): void {
    $container = $this->createMock(ContainerInterface::class);
    $manager = $this->createMock(PluginManagerInterface::class);
    $container->expects($this->any())
      ->method('get')
      ->with('plugin.manager.mock')
      ->willReturn($manager);
    $constraint = PluginExistsConstraint::create($container, [$option_key => 'plugin.manager.mock'], 'test_plugin_id', []);
    $this->assertSame($manager, $constraint->pluginManager);
  }

}
