<?php

declare(strict_types=1);

namespace Drupal\Tests\editor\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * @group Update
 * @group editor
 * @see editor_post_update_sanitize_image_upload_settings()
 */
class EditorSanitizeImageUploadSettingsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/editor-3412361.php',
    ];
  }

  /**
   * Ensure image upload settings for Text Editor config entities are corrected.
   *
   * @see editor_post_update_sanitize_image_upload_settings()
   */
  public function testUpdateRemoveMeaninglessImageUploadSettings(): void {
    $basic_html_before = $this->config('editor.editor.basic_html');
    $this->assertSame([
      'status' => TRUE,
      'scheme' => 'public',
      'directory' => 'inline-images',
      'max_size' => '',
      'max_dimensions' => [
        'width' => 0,
        'height' => 0,
      ],
    ], $basic_html_before->get('image_upload'));
    $full_html_before = $this->config('editor.editor.full_html');
    $this->assertSame([
      'status' => TRUE,
      'scheme' => 'public',
      'directory' => 'inline-images',
      'max_size' => '',
      'max_dimensions' => [
        'width' => 0,
        'height' => 0,
      ],
    ], $full_html_before->get('image_upload'));
    $umami_basic_html_before = $this->config('editor.editor.umami_basic_html');
    $this->assertSame([
      'status' => FALSE,
      'scheme' => 'public',
      'directory' => 'inline-images',
      'max_size' => '',
      'max_dimensions' => [
        'width' => NULL,
        'height' => NULL,
      ],
    ], $umami_basic_html_before->get('image_upload'));

    $this->runUpdates();

    $basic_html_after = $this->config('editor.editor.basic_html');
    $this->assertNotSame($basic_html_before->get('image_upload'), $basic_html_after->get('image_upload'));
    $this->assertSame([
      'status' => TRUE,
      'scheme' => 'public',
      'directory' => 'inline-images',
      'max_size' => NULL,
      'max_dimensions' => [
        'width' => NULL,
        'height' => NULL,
      ],
    ], $basic_html_after->get('image_upload'));
    $full_html_after = $this->config('editor.editor.full_html');
    $this->assertNotSame($full_html_before->get('image_upload'), $full_html_after->get('image_upload'));
    $this->assertSame([
      'status' => TRUE,
      'scheme' => 'public',
      'directory' => 'inline-images',
      'max_size' => NULL,
      'max_dimensions' => [
        'width' => NULL,
        'height' => NULL,
      ],
    ], $full_html_after->get('image_upload'));
    $umami_basic_html_after = $this->config('editor.editor.umami_basic_html');
    $this->assertNotSame($umami_basic_html_before->get('image_upload'), $umami_basic_html_after->get('image_upload'));
    $this->assertSame([
      'status' => FALSE,
    ], $umami_basic_html_after->get('image_upload'));
  }

}
