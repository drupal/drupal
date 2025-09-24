<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\CKEditor5Plugin\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\ckeditor5\Plugin\CKEditor5Plugin\Image.
 *
 * @internal
 */
#[CoversClass(Image::class)]
#[Group('ckeditor5')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class ImageUrlTest extends ImageUrlTestBase {
  use ImageTestBaselineTrait;

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
