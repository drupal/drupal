<?php

namespace Drupal\Tests\file\FunctionalJavascript;

use Drupal\Core\Url;

/**
 * Tests the widget visibility settings for the Claro theme.
 *
 * The widget is intentionally tested with Claro as the default theme to test
 * the changes added in _claro_preprocess_file_and_image_widget().
 *
 * @see _claro_preprocess_file_and_image_widget()
 *
 * @group file
 */
class FileFieldWidgetClaroThemeTest extends FileFieldWidgetTest {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * Tests that the field widget visibility settings are respected on the form.
   */
  public function testWidgetDefaultVisibilitySettings(): void {
    // Set up an article node with all field storage settings set to TRUE.
    $type_name = 'article';
    $field_name = 'test_file_field_1';
    $field_storage_settings = [
      'display_field' => TRUE,
      'display_default' => TRUE,
    ];
    $field_settings = [];
    $widget_settings = [];
    $field_storage = $this->createFileField($field_name, 'node', $type_name, $field_storage_settings, $field_settings, $widget_settings);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $test_file = current($this->getTestFiles('text'));
    $test_file_path = \Drupal::service('file_system')->realpath($test_file->uri);

    // Fill out the form accordingly.
    $this->drupalGet("node/add/$type_name");
    $title = 'Fake Article Name 01';
    $page->findField('title[0][value]')->setValue($title);
    $page->attachFileToField('files[' . $field_name . '_0]', $test_file_path);
    $remove_button = $assert_session->waitForElementVisible('css', '[name="' . $field_name . '_0_remove_button"]');
    $this->assertNotNull($remove_button);
    $type = $assert_session->fieldExists("{$field_name}[0][display]")->getAttribute('type');
    $this->assertEquals($type, 'checkbox');
    $assert_session->checkboxChecked("{$field_name}[0][display]");

    // Now, submit the same form and ensure that value is retained.
    $this->submitForm([], 'Save');
    $node = $this->drupalGetNodeByTitle($title, TRUE);
    $this->assertEquals(1, $node->get($field_name)->getValue()[0]['display'], 'The checkbox is enabled.');
    $this->drupalGet(Url::fromRoute('entity.node.edit_form', ['node' => $node->id()]));
    $assert_session->checkboxChecked("{$field_name}[0][display]");

    // Submit the form again with the disabled value of the checkbox.
    $this->submitForm([
      "{$field_name}[0][display]" => FALSE,
    ], 'Save');
    $node = $this->drupalGetNodeByTitle($title, TRUE);
    $this->assertEquals(0, $node->get($field_name)->getValue()[0]['display'], 'The checkbox is disabled.');
    $this->drupalGet(Url::fromRoute('entity.node.edit_form', ['node' => $node->id()]));
    $assert_session->checkboxNotChecked("{$field_name}[0][display]");

    // Disable the field settings and ensure that the form is updated.
    $field_storage->setSetting('display_default', FALSE);
    $field_storage->save();
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet("node/add/$type_name");
    $title = 'Fake Article Name 02';
    $page->findField('title[0][value]')->setValue($title);
    $page->attachFileToField('files[' . $field_name . '_0]', $test_file_path);
    $remove_button = $assert_session->waitForElementVisible('css', '[name="' . $field_name . '_0_remove_button"]');
    $this->assertNotNull($remove_button);
    $type = $assert_session->fieldExists("{$field_name}[0][display]")->getAttribute('type');
    $this->assertEquals($type, 'checkbox');
    $assert_session->checkboxNotChecked("{$field_name}[0][display]");

    // Submit the same form and ensure that value is retained.
    $this->submitForm([], 'Save');
    $node = $this->drupalGetNodeByTitle($title, TRUE);
    $this->assertEquals(0, $node->get($field_name)->getValue()[0]['display'], 'The checkbox is disabled.');
    $this->drupalGet(Url::fromRoute('entity.node.edit_form', ['node' => $node->id()]));
    $assert_session->checkboxNotChecked("{$field_name}[0][display]");

    // Check the checkbox and ensure that it is submitted properly.
    $this->submitForm([
      "{$field_name}[0][display]" => TRUE,
    ], 'Save');
    $node = $this->drupalGetNodeByTitle($title, TRUE);
    $this->assertEquals(1, $node->get($field_name)->getValue()[0]['display'], 'The checkbox is disabled because display_default option is marked as false.');
    $this->drupalGet(Url::fromRoute('entity.node.edit_form', ['node' => $node->id()]));
    $assert_session->checkboxChecked("{$field_name}[0][display]");
  }

}
