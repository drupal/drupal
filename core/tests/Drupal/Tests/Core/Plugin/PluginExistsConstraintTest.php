<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\Plugin\Validation\Constraint\PluginExistsConstraint;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Tests Drupal\Core\Plugin\Plugin\Validation\Constraint\PluginExistsConstraint.
 */
#[CoversClass(PluginExistsConstraint::class)]
#[Group('Plugin')]
#[Group('Validation')]
class PluginExistsConstraintTest extends UnitTestCase {

  /**
   * Tests missing option.
   *
   * @legacy-covers ::create
   */
  public function testMissingOption(): void {
    $this->expectException(MissingOptionsException::class);
    $this->expectExceptionMessage('The option "manager" must be set for constraint "Drupal\Core\Plugin\Plugin\Validation\Constraint\PluginExistsConstraint".');
    $container = $this->createMock(ContainerInterface::class);
    PluginExistsConstraint::create($container, [], 'test_plugin_id', []);
  }

  /**
   * Tests plugin is created when manager option is set to a valid service ID.
   *
   * @legacy-covers ::create
   * @legacy-covers ::__construct
   */
  public function testManagerOption(): void {
    $container = $this->createMock(ContainerInterface::class);
    $manager = $this->createMock(PluginManagerInterface::class);
    $container->expects($this->any())
      ->method('get')
      ->with('plugin.manager.mock')
      ->willReturn($manager);
    $constraint = PluginExistsConstraint::create($container, ['manager' => 'plugin.manager.mock'], 'test_plugin_id', []);
    $this->assertSame($manager, $constraint->pluginManager);
  }

}
