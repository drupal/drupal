<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Unit\Menu;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests existence of shortcut local tasks.
 *
 * @group shortcut
 */
class ShortcutLocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->directoryList = [
      'shortcut' => 'core/modules/shortcut',
      'user' => 'core/modules/user',
    ];
    parent::setUp();

    // Add services required for user local tasks.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->any())
      ->method('getDefinitions')
      ->willReturn([]);
    $this->container->set('entity_type.manager', $entity_type_manager);
    $this->container->set('string_translation', $this->getStringTranslationStub());
  }

  /**
   * Checks shortcut listing local tasks.
   *
   * @dataProvider getShortcutPageRoutes
   */
  public function testShortcutPageLocalTasks($route) {
    $tasks = [
      0 => ['shortcut.set_switch', 'entity.user.canonical', 'entity.user.edit_form'],
    ];
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getShortcutPageRoutes() {
    return [
      ['entity.user.canonical'],
      ['entity.user.edit_form'],
      ['shortcut.set_switch'],
    ];
  }

}
