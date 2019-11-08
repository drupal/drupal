<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\Component\Utility\Html;

/**
 * Tests related to media reference fields.
 *
 * @group media
 */
class MediaReferenceFieldHelpTest extends MediaJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test our custom help texts when creating a field.
   *
   * @see media_form_field_ui_field_storage_add_form_alter()
   */
  public function testFieldCreationHelpText() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $type = $this->drupalCreateContentType([
      'type' => 'foo',
    ]);
    $this->drupalGet("/admin/structure/types/manage/{$type->id()}/fields/add-field");

    $field_types = [
      'file',
      'image',
      'field_ui:entity_reference:media',
    ];
    $description_ids = array_map(function ($item) {
      return '#edit-description-' . Html::cleanCssIdentifier($item);
    }, $field_types);

    // Choose a boolean field, none of the description containers should be
    // visible.
    $assert_session->optionExists('edit-new-storage-type', 'boolean');
    $page->selectFieldOption('edit-new-storage-type', 'boolean');
    foreach ($description_ids as $description_id) {
      $this->assertFalse($assert_session->elementExists('css', $description_id)->isVisible());
    }
    // Select each of the file, image, and media fields and verify their
    // descriptions are now visible and match the expected text.
    $help_text = 'Use Media reference fields for most files, images, audio, videos, and remote media. Use File or Image reference fields when creating your own media types, or for legacy files and images created before enabling the Media module.';
    foreach ($field_types as $field_name) {
      $assert_session->optionExists('edit-new-storage-type', $field_name);
      $page->selectFieldOption('edit-new-storage-type', $field_name);
      $field_description_element = $assert_session->elementExists('css', '#edit-description-' . Html::cleanCssIdentifier($field_name));
      $this->assertTrue($field_description_element->isVisible());
      $this->assertSame($help_text, $field_description_element->getText());
    }
  }

}
