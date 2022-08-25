<?php

namespace Drupal\Tests\quickedit\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Layout Builder integration with Quick Edit.
 *
 * @group quickedit
 * @group legacy
 */
class LayoutBuilderQuickEditTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'node',
    'quickedit',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create two nodes.
    $this->createContentType([
      'type' => 'bundle_with_section_field',
      'name' => 'Bundle with section field',
    ]);
    $this->createNode([
      'type' => 'bundle_with_section_field',
    ]);
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
  }

  /**
   * Tests Quick Edit integration with a block from a different entity type.
   */
  public function testPlaceFieldBlockFromDifferentEntityType() {
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'access in-place editing',
    ]));

    // Place a field block for a user entity field.
    $this->drupalGet('node/1/layout');
    $page->clickLink('Add block');
    $page->clickLink('Name');
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);
  }

}
