<?php

namespace Drupal\Tests\quickedit\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;

/**
 * Tests quickedit.
 *
 * @group quickedit
 */
class FieldTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'ckeditor',
    'contextual',
    'quickedit',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a text format and associate CKEditor.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
    ]);
    $filtered_html_format->save();

    Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
    ])->save();

    // Create note type with body field.
    $node_type = NodeType::create(['type' => 'page', 'name' => 'Page']);
    $node_type->save();
    node_add_body_field($node_type);

    $account = $this->drupalCreateUser([
      'access content',
      'administer nodes',
      'edit any page content',
      'use text format filtered_html',
      'access contextual links',
      'access in-place editing',
    ]);
    $this->drupalLogin($account);

  }

  /**
   * Tests that quickeditor works correctly for field with CKEditor.
   */
  public function testFieldWithCkeditor() {
    $body_value = '<p>Sapere aude</p>';
    $node = Node::create([
      'type' => 'page',
      'title' => 'Page node',
      'body' => [['value' => $body_value, 'format' => 'filtered_html']],
    ]);
    $node->save();

    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $this->drupalGet('node/' . $node->id());

    // Wait "Quick edit" button for node.
    $this->assertSession()->waitForElement('css', '[data-quickedit-entity-id="node/' . $node->id() . '"] .contextual .quickedit');
    // Click by "Quick edit".
    $this->clickContextualLink('[data-quickedit-entity-id="node/' . $node->id() . '"]', 'Quick edit');
    // Switch to body field.
    $page->find('css', '[data-quickedit-field-id="node/' . $node->id() . '/body/en/full"]')->click();
    // Wait and click by "Blockquote" button from editor for body field.
    $this->assertSession()->waitForElementVisible('css', '.cke_button.cke_button__blockquote')->click();
    // Wait and click by "Save" button after body field was changed.
    $this->assertSession()->waitForElementVisible('css', '.quickedit-toolgroup.ops [type="submit"][aria-hidden="false"]')->click();
    // Wait until the save occurs and the editor UI disappears.
    $this->assertSession()->assertNoElementAfterWait('css', '.cke_button.cke_button__blockquote');
    // Ensure that the changes take effect.
    $assert->responseMatches("|<blockquote>\s*$body_value\s*</blockquote>|");
  }

}
