<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Symfony\Component\Validator\ConstraintViolation;

// cspell:ignore imageresize

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Image
 * @group ckeditor5
 * @group #slow
 * @internal
 */
class ImageUrlTest extends ImageTestBase {

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
            'allowed_html' => '<p> <br> <em> <a href> <img alt height width src data-caption data-align>',
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
        'status' => FALSE,
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
   * Tests the Drupal image URL widget.
   */
  public function testImageUrlWidget(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $image_selector = '.ck-widget.image-inline';
    $src = $this->imageAttributes()['src'];

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();

    $this->pressEditorButton('Insert image via URL');
    $dialog = $page->find('css', '.ck-dialog');
    $src_input = $dialog->find('css', '.ck-image-insert-url input[type=text]');
    $src_input->setValue($src);
    $dialog->find('xpath', "//button[span[text()='Insert']]")->click();

    $this->assertNotEmpty($assert_session->waitForElementVisible('css', $image_selector));
    $this->click($image_selector);
    $this->assertVisibleBalloon('[aria-label="Image toolbar"]');

    $this->pressEditorButton('Update image URL');
    $dialog = $page->find('css', '.ck-dialog');
    $src_input = $dialog->find('css', '.ck-image-insert-url input[type=text]');
    $this->assertEquals($src, $src_input->getValue());
  }

}
