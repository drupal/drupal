<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Menu;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\Plugin\ConfigAction\OverrideMenuLinks;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests overriding static menu links with config actions.
 */
#[Group('Menu')]
#[CoversClass(OverrideMenuLinks::class)]
#[RunTestsInSeparateProcesses]
final class OverrideMenuLinksConfigActionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['menu_test'];

  /**
   * Tests that the action only works on core.menu.static_menu_link_overrides.
   */
  public function testConfigName(): void {
    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage('This config action can only be used on the core.menu.static_menu_link_overrides config object.');
    $this->container->get('plugin.manager.config_action')
      ->applyAction(
        'overrideMenuLinks',
        'system.menu.original',
        [],
      );
  }

  /**
   * Tests overriding static menu links.
   */
  public function testOverrideLinks(): void {
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.config_action');

    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $link_manager */
    $link_manager = $this->container->get(MenuLinkManagerInterface::class);
    $link_manager->rebuild();
    $definition = $link_manager->getDefinition('menu_test.menu_name_test');
    $this->assertSame('0', $definition['weight']);
    $this->assertSame('1', $definition['enabled']);

    $logger = new TestLogger();
    $this->container->get(LoggerChannelFactoryInterface::class)
      ->get('menu')
      ->addLogger($logger);

    $action_manager->applyAction(
      'overrideMenuLinks',
      'core.menu.static_menu_link_overrides',
      [
        'menu_test.menu_name_test' => [
          'weight' => 5,
          'enabled' => FALSE,
        ],
        'not_a_link' => [
          'enabled' => TRUE,
        ],
      ],
    );
    $link_manager->rebuild();
    $definition = $link_manager->getDefinition('menu_test.menu_name_test');
    $this->assertSame('5', $definition['weight']);
    $this->assertSame('0', $definition['enabled']);

    // Trying to override a non-existent link should log a warning.
    $this->assertTrue($logger->hasRecord('The @link_id menu link was not overridden because it does not exist.', RfcLogLevel::WARNING));

    // We should be able to undo the override.
    $action_manager->applyAction(
      'overrideMenuLinks',
      'core.menu.static_menu_link_overrides',
      ['menu_test.menu_name_test' => NULL],
    );
    $link_manager->rebuild();
    $definition = $link_manager->getDefinition('menu_test.menu_name_test');
    $this->assertSame('0', $definition['weight']);
    $this->assertSame('1', $definition['enabled']);
  }

}
