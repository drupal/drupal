<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;

// cspell:ignore imageresize

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Image
 * @group ckeditor5
 * @internal
 */
abstract class ImageTestBase extends CKEditor5TestBase {

  use CKEditor5TestTrait;
  use TestFileCreationTrait;

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A host entity with a body field to embed images in.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $host;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Provides the relevant image attributes.
   *
   * @return string[]
   *   An associative array with the image source, width, and height.
   */
  protected function imageAttributes() {
    return [
      'src' => base_path() . 'core/misc/druplicon.png',
      'width' => '88',
      'height' => '100',
    ];
  }

  /**
   * Helper to format attributes.
   *
   * @param bool $reverse
   *   Reverse attributes when printing them.
   *
   * @return string
   *   A space-separated string of image attributes.
   */
  protected function imageAttributesAsString($reverse = FALSE) {
    $string = [];
    foreach ($this->imageAttributes() as $key => $value) {
      $string[] = $key . '="' . $value . '"';
    }
    if ($reverse) {
      $string = array_reverse($string);
    }
    return implode(' ', $string);
  }

  /**
   * Add an image to the CKEditor 5 editable zone.
   */
  protected function addImage() {
    $page = $this->getSession()->getPage();
    $src = $this->imageAttributes()['src'];
    $this->waitForEditor();
    $this->pressEditorButton('Insert image via URL');
    $dialog = $page->find('css', '.ck-dialog');
    $src_input = $dialog->find('css', '.ck-image-insert-url input[type=text]');
    $src_input->setValue($src);
    $dialog->find('xpath', "//button[span[text()='Insert']]")->click();
    // Wait for the image to be uploaded and rendered by CKEditor 5.
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.ck-widget.image > img[src="' . $src . '"]'));
  }

}
