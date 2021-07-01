<?php

namespace Drupal\Tests\menu_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
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
   * Asserts that the available menu names are sorted alphabetically.
   *
   * @param NodeType $node_type
   *   The node type under test.
   * @param string $operation
   *   The form operation (normally 'add' or 'edit').
   */
  private function assertMenuNamesAreSorted(NodeType $node_type, string $operation): void {
    $expected_options = [
      'admin' => 'Administration',
      'footer' => 'Footer',
      'main' => 'Main navigation',
      'tools' => 'Tools',
      'account' => 'User account menu',
    ];
    $form = $this->container->get('entity.form_builder')
      ->getForm($node_type, $operation);
    $this->assertSame($expected_options, $form['menu']['menu_options']['#options']);
  }

  /**
   * Tests node type-specific settings for Menu UI.
   */
  public function testContentTypeMenuSettings(): void {
    $this->installConfig(['node', 'system']);
    $this->assertMenuNamesAreSorted(NodeType::create(), 'add');
    $this->assertMenuNamesAreSorted($this->createContentType(), 'edit');
  }

}
