<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Symfony\Component\Validator\ConstraintViolation;

// cspell:ignore imageresize imageupload

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Image
 * @group ckeditor5
 * @group #slow
 * @internal
 */
class ImageTest extends ImageTestBase {

  /**
   * The sample image File entity to embed.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <br> <em> <a href> <img src alt data-entity-uuid data-entity-type height width data-caption data-align>',
          ],
        ],
        'filter_align' => ['status' => TRUE],
        'filter_caption' => ['status' => TRUE],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalInsertImage',
            'sourceEditing',
            'link',
            'italic',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [],
          ],
          'ckeditor5_imageResize' => [
            'allow_resize' => TRUE,
          ],
        ],
      ],
      'image_upload' => [
        'status' => TRUE,
        'scheme' => 'public',
        'directory' => 'inline-images',
        'max_size' => '1M',
        'max_dimensions' => ['width' => 100, 'height' => 100],
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));
    $this->adminUser = $this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
      'administer filters',
    ]);

    // Create a sample host entity to embed images in.
    $this->file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $this->file->save();
    $this->host = $this->createNode([
      'type' => 'page',
      'title' => 'Animals with strange names',
      'body' => [
        'value' => '<p>The pirate is irate.</p>',
        'format' => 'test_format',
      ],
    ]);
    $this->host->save();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Provides the relevant image attributes.
   *
   * @return string[]
   */
  protected function imageAttributes() {
    return [
      'data-entity-type' => 'file',
      'data-entity-uuid' => $this->file->uuid(),
      'src' => $this->file->createFileUrl(),
    ];
  }

  protected function addImage() {
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($image_upload_field = $page->find('css', '.ck-file-dialog-button input[type="file"]'));
    $image = $this->getTestFiles('image')[0];
    $image_upload_field->attachFile($this->container->get('file_system')->realpath($image->uri));
    // Wait for the image to be uploaded and rendered by CKEditor 5.
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.ck-widget.image > img[src*="' . $image->filename . '"]'));
  }

  /**
   * Tests the ckeditor5_imageResize and ckeditor5_imageUpload settings forms.
   */
  public function testImageSettingsForm() {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/config/content/formats/manage/test_format');

    // The image resize and upload plugin settings forms should be present.
    $assert_session->elementExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-imageresize"]');
    $assert_session->elementExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-image"]');

    // Removing the drupalImageInsert button from the toolbar must remove the
    // plugin settings forms too.
    $this->triggerKeyUp('.ckeditor5-toolbar-item-drupalInsertImage', 'ArrowUp');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementNotExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-imageresize"]');
    $assert_session->elementNotExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-image"]');

    // Re-adding the drupalImageInsert button to the toolbar must re-add the
    // plugin settings forms too.
    $this->triggerKeyUp('.ckeditor5-toolbar-item-drupalInsertImage', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-imageresize"]');
    $assert_session->elementExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-image"]');
  }

}
