<?php

namespace Drupal\Tests\menu_link_content\Kernel;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the menu link content delete function.
 *
 * @group menu_link_content
 */
class MenuLinkContentDeleteTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_link_content', 'link', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('menu_link_content');
  }

  /**
   * Tests the MenuLinkContent::preDelete function.
   */
  public function testMenuLinkContentDelete() {
    // Add new menu items in a hierarchy.
    $parent = MenuLinkContent::create([
      'title' => $this->randomMachineName(8),
      'link' => [['uri' => 'internal:/']],
      'menu_name' => 'main',
    ]);
    $parent->save();
    $child1 = MenuLinkContent::create([
      'title' => $this->randomMachineName(8),
      'link' => [['uri' => 'internal:/']],
      'menu_name' => 'main',
      'parent' => 'menu_link_content:' . $parent->uuid(),
    ]);
    $child1->save();
    $child2 = MenuLinkContent::create([
      'title' => $this->randomMachineName(8),
      'link' => [['uri' => 'internal:/']],
      'menu_name' => 'main',
      'parent' => 'menu_link_content:' . $child1->uuid(),
    ]);
    $child2->save();

    // Delete the middle child.
    $child1->delete();
    // Refresh $child2.
    $child2 = MenuLinkContent::load($child2->id());
    // Test the reference in the child.
    $this->assertSame('menu_link_content:' . $parent->uuid(), $child2->getParentId());
  }

}
