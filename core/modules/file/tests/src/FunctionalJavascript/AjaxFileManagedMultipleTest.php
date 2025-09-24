<?php

declare(strict_types=1);

namespace Drupal\Tests\file\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests ajax upload to managed files.
 */
#[Group('file')]
#[RunTestsInSeparateProcesses]
class AjaxFileManagedMultipleTest extends WebDriverTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file_test', 'file', 'file_module_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests if managed file form element works well with multiple files upload.
   */
  public function testMultipleFilesUpload(): void {
    $file_system = \Drupal::service('file_system');
    $file_storage = \Drupal::entityTypeManager()->getStorage('file');
    $page = $this->getSession()->getPage();

    $this->drupalGet(Url::fromRoute('file_module_test.managed_test', ['multiple' => TRUE]));

    $paths = [];
    foreach (array_slice($this->drupalGetTestFiles('image'), 0, 2) as $image) {
      $paths[] = $image->filename;
      $page->attachFileToField('files[nested_file][]', $file_system->realpath($image->uri));
      $this->assertSession()->assertWaitOnAjaxRequest();
    }

    // Save entire form.
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('The file ids are 1,2.');
    $this->assertCount(2, $file_storage->loadByProperties(['filename' => $paths]));
  }

}
