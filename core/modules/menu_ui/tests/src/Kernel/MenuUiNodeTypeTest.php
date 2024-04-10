<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\system\Entity\Menu;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests menu settings when creating and editing content types.
 *
 * @group menu_ui
 */
class MenuUiNodeTypeTest extends KernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'menu_ui',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * Asserts that the available menu names are sorted alphabetically by label.
   *
   * @param \Drupal\node\Entity\NodeType $node_type
   *   The node type under test.
   */
  private function assertMenuNamesAreSorted(NodeType $node_type): void {
    // The available menus should be sorted by label, not machine name.
    $expected_options = [
      'b' => 'X',
      'c' => 'Y',
      'a' => 'Z',
    ];
    $form = $this->container->get('entity.form_builder')
      ->getForm($node_type, $node_type->isNew() ? 'add' : 'edit');
    $this->assertSame($expected_options, $form['menu']['menu_options']['#options']);
  }

  /**
   * Tests node type-specific settings for Menu UI.
   */
  public function testContentTypeMenuSettings(): void {
    $this->installConfig(['node']);
    Menu::create(['id' => 'a', 'label' => 'Z'])->save();
    Menu::create(['id' => 'b', 'label' => 'X'])->save();
    Menu::create(['id' => 'c', 'label' => 'Y'])->save();
    $this->assertMenuNamesAreSorted(NodeType::create());
    $this->assertMenuNamesAreSorted($this->createContentType());
  }

}
