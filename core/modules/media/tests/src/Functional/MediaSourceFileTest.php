<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Functional;

use Drupal\field\Entity\FieldConfig;

/**
 * Tests the file media source.
 *
 * @group media
 */
class MediaSourceFileTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that it's possible to change the allowed file extensions.
   */
  public function testSourceFieldSettingsEditing(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $media_type = $this->createMediaType('file');
    $media_type_id = $media_type->id();
    $this->assertSame('txt doc docx pdf', FieldConfig::load("media.$media_type_id.field_media_file")->get('settings')['file_extensions']);

    $this->drupalGet("admin/structure/media/manage/$media_type_id/fields/media.$media_type_id.field_media_file");

    // File extension field exists.
    $assert_session->fieldExists('Allowed file extensions');

    // Add another extension.
    $page->fillField('settings[file_extensions]', 'txt, doc, docx, pdf, odt');

    $page->pressButton('Save settings');
    $this->drupalGet("admin/structure/media/manage/$media_type_id/fields/media.$media_type_id.field_media_file");

    // Verify that new extension is present.
    $assert_session->fieldValueEquals('settings[file_extensions]', 'txt, doc, docx, pdf, odt');
    $this->assertSame('txt doc docx pdf odt', FieldConfig::load("media.$media_type_id.field_media_file")->get('settings')['file_extensions']);
  }

  /**
   * Ensure source field deletion is not possible.
   */
  public function testPreventSourceFieldDeletion(): void {
    $media_type = $this->createMediaType('file');
    $media_type_id = $media_type->id();

    $this->drupalGet("admin/structure/media/manage/$media_type_id/fields/media.$media_type_id.field_media_file/delete");
    $this->assertSession()->statusCodeEquals(403);
  }

}
