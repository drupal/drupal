<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Entity\Menu;
use Drupal\block\Entity\Block;
use Drupal\system\MenuInterface;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests SystemMenuBlock.
 *
 * @group menu_ui
 */
class MenuBlockTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'block',
    'menu_ui',
    'user',
  ];

  /**
   * The menu for testing.
   *
   * @var \Drupal\system\MenuInterface
   */
  protected MenuInterface $menu;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    $this->setUpCurrentUser([], ['administer menu']);

    // Add a new custom menu.
    $menu_name = 'mock';
    $label = $this->randomMachineName(16);

    $this->menu = Menu::create([
      'id' => $menu_name,
      'label' => $label,
      'description' => 'Description text',
    ]);
    $this->menu->save();

  }

  /**
   * Tests the editing links for SystemMenuBlock.
   */
  public function testOperationLinks(): void {
    $block = Block::create([
      'plugin' => 'system_menu_block:' . $this->menu->id(),
      'region' => 'footer',
      'id' => 'machine_name',
      'theme' => 'stark',
    ]);

    // Test when user does have "administer menu" permission.
    $this->assertEquals([
      'menu-edit' => [
        'title' => 'Edit menu',
        'url' => $this->menu->toUrl('edit-form'),
        'weight' => 50,
      ],
    ], menu_ui_entity_operation($block));

    $this->setUpCurrentUser();
    // Test when user doesn't have "administer menu" permission.
    $this->assertEmpty(menu_ui_entity_operation($block));
  }

}
