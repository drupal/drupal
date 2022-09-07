<?php

namespace Drupal\Tests\ckeditor\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests the inline errors fragment link to a CKEditor-enabled textarea.
 *
 * @group ckeditor
 * @group legacy
 */
class FormErrorHandlerCKEditorTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ckeditor',
    'inline_form_errors',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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

    // Create a node type for testing.
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    $field_storage = FieldStorageConfig::loadByName('node', 'body');

    // Create a body field instance for the 'page' node type.
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => 'Body',
      'settings' => ['display_summary' => TRUE],
      'required' => TRUE,
    ])->save();

    // Assign widget settings for the 'default' form mode.
    EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('body', ['type' => 'text_textarea_with_summary'])
      ->save();

    $account = $this->drupalCreateUser([
      'administer nodes',
      'create page content',
      'use text format filtered_html',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests if the fragment link to a textarea works with CKEditor enabled.
   */
  public function testFragmentLink() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();
    $ckeditor_id = '#cke_edit-body-0-value';

    $this->drupalGet('node/add/page');

    // Only enter a title in the node add form and leave the body field empty.
    $edit = ['edit-title-0-value' => 'Test inline form error with CKEditor'];

    $this->submitForm($edit, 'Save');

    $this->assertSession()->waitForElement('css', '#cke_edit-body-0-value');
    // Add a bottom margin to the title field to be sure the body field is not
    // visible.
    $session->executeScript("document.getElementById('edit-title-0-value').style.marginBottom = window.innerHeight*2 + 'px';");

    // Check that the CKEditor-enabled body field is currently not visible in
    // the viewport.
    $web_assert->assertNotVisibleInViewport('css', $ckeditor_id, 'topLeft', 'CKEditor-enabled body field is not visible.');

    // Check if we can find the error fragment link within the errors summary
    // message.
    $errors_link = $this->assertSession()->waitForElementVisible('css', '.messages--error a[href="#edit-body-0-value"]');
    $this->assertNotEmpty($errors_link, 'Error fragment link is visible.');

    $errors_link->click();

    // Check that the CKEditor-enabled body field is visible in the viewport.
    $web_assert->assertVisibleInViewport('css', $ckeditor_id, 'topLeft', 'CKEditor-enabled body field is visible.');
  }

}
