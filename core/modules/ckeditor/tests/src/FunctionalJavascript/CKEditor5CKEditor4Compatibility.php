<?php

namespace Drupal\Tests\ckeditor\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Ensures that CKEditor 5 can be used on the same page with CKEditor 4.
 *
 * @group ckeditor
 * @internal
 */
class CKEditor5CKEditor4Compatibility extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor',
    'node',
    'ckeditor5',
    'ckeditor5_test',
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

    $this->drupalCreateContentType(['type' => 'page']);

    $this->drupalLogin($this->drupalCreateUser([
      'administer filters',
      'create page content',
      'edit own page content',
    ]));

    $current_user_roles = $this->loggedInUser->getRoles(TRUE);

    // Create text format, text editor and text fields for CKEditor 5 and 4.
    foreach ([5 => 'ckeditor5', 4 => 'ckeditor'] as $version => $text_editor_plugin_id) {
      $format_id = sprintf('test_format_for_ckeditor%d', $version);
      $field_name = sprintf('field_text_ckeditor%d', $version);

      FilterFormat::create([
        'format' => $format_id,
        'name' => sprintf('CKEditor %d editor', $version),
        'roles' => $current_user_roles,
        'filters' => [
          'filter_html' => [
            'status' => TRUE,
            'settings' => [
              'allowed_html' => '<p> <br> <h2> <h3> <h4> <h5> <h6> <strong> <em>',
            ],
          ],
        ],
      ])->save();
      Editor::create([
        'editor' => $text_editor_plugin_id,
        'format' => $format_id,
        'settings' => $version === 4 ? [] : [
          'toolbar' => [
            'items' => ['heading', 'bold', 'italic'],
          ],
          'plugins' => [
            'ckeditor5_heading' => [
              'enabled_headings' => [
                'heading2',
                'heading3',
                'heading4',
                'heading5',
                'heading6',
              ],
            ],
          ],
        ],
        'image_upload' => [
          'status' => FALSE,
        ],
      ])->save();
      if ($version === 5) {
        $this->assertSame([], array_map(
          function (ConstraintViolation $v) {
            return (string) $v->getMessage();
          },
          iterator_to_array(CKEditor5::validatePair(
            Editor::load($format_id),
            FilterFormat::load($format_id)
          ))
        ));
      }
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => 'text_long',
      ]);
      $field_storage->save();
      FieldConfig::create([
        'field_storage' => $field_storage,
        'entity_type' => 'node',
        'bundle' => 'page',
      ])->save();

      // Add the new field to the default form display.
      EntityFormDisplay::load('node.page.default')
        ->setComponent($field_name, ['type' => 'text_textarea'])
        ->save();
    }
  }

  /**
   * Ensures that CKEditor 5 and CKEditor 4 can be used on the same page.
   */
  public function testCkeCompatibility() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('node/add/page');
    $page->selectFieldOption('field_text_ckeditor4[0][format]', 'test_format_for_ckeditor4');
    $page->selectFieldOption('field_text_ckeditor5[0][format]', 'test_format_for_ckeditor5');

    $this->assertNotEmpty($assert_session->waitForElement('css', '.cke_wysiwyg_frame'));
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
  }

}
