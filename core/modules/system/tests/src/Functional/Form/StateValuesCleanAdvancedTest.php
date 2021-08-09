<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests proper removal of submitted form values using
 * \Drupal\Core\Form\FormState::cleanValues() when having forms with elements
 * containing buttons like "managed_file".
 *
 * @group Form
 */
class StateValuesCleanAdvancedTest extends BrowserTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * Modules to enable.
   *
   * @var array
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
  public function testFormStateValuesCleanAdvanced() {

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
