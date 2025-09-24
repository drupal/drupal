<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the removal of internal Form API elements from submitted form values.
 */
#[Group('Form')]
#[RunTestsInSeparateProcesses]
class StateValuesCleanAdvancedTest extends BrowserTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An image file path for uploading.
   *
   * @var string|bool
   */
  protected $image;

  /**
   * Tests \Drupal\Core\Form\FormState::cleanValues().
   */
  public function testFormStateValuesCleanAdvanced(): void {

    // Get an image for uploading.
    $image_files = $this->drupalGetTestFiles('image');
    $this->image = current($image_files);

    // Check if the physical file is there.
    $this->assertFileExists($this->image->uri);

    // "Browse" for the desired file.
    $edit = ['files[image]' => \Drupal::service('file_system')->realpath($this->image->uri)];

    // Post the form.
    $this->drupalGet('form_test/form-state-values-clean-advanced');
    $this->submitForm($edit, 'Submit');

    // Expecting a 200 HTTP code.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");
  }

}
