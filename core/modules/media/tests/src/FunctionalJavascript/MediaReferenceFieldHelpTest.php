<?php

declare(strict_types=1);

namespace Drupal\Tests\media\FunctionalJavascript;

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
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'media_library',
  ];

  /**
   * Tests our custom help texts when creating a field.
   *
   * @see media_form_field_ui_field_storage_add_form_alter()
   */
  public function testFieldCreationHelpText(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $type = $this->drupalCreateContentType([
      'type' => 'foo',
    ]);
    $this->drupalGet("/admin/structure/types/manage/{$type->id()}/fields/add-field");

    $field_groups = [
      'file_upload',
      'field_ui:entity_reference:media',
    ];

    $help_text = 'Use Media reference fields for most files, images, audio, videos, and remote media. Use File or Image reference fields when creating your own media types, or for legacy files and images created before installing the Media module.';

    // Choose a boolean field, none of the description containers should be
    // visible.
    $assert_session->elementExists('css', "[name='new_storage_type'][value='boolean']");
    $page->find('css', "[name='new_storage_type'][value='boolean']")->getParent()->click();
    $page->pressButton('Continue');
    $assert_session->pageTextNotContains($help_text);
    $page->pressButton('Back');

    // Select each of the Reference, File upload field groups and verify their
    // descriptions are now visible and match the expected text.
    foreach ($field_groups as $field_group) {
      $assert_session->elementExists('css', "[name='new_storage_type'][value='$field_group']");
      $page->find('css', "[name='new_storage_type'][value='$field_group']")->getParent()->click();

      $page->pressButton('Continue');
      $assert_session->pageTextContains($help_text);
      $page->pressButton('Back');
    }
  }

}
