<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\StateValuesCleanAdvancedTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Tests $form_state clearance with form elements having buttons.
 */
class StateValuesCleanAdvancedTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file', 'form_test');

  /**
   * An image file path for uploading.
   */
  protected $image;

  public static function getInfo() {
    return array(
      'name' => 'Form state values clearance (advanced)',
      'description' => 'Test proper removal of submitted form values using form_state_values_clean() when having forms with elements containing buttons like "managed_file".',
      'group' => 'Form API',
    );
  }

  /**
   * Tests form_state_values_clean().
   */
  function testFormStateValuesCleanAdvanced() {

    // Get an image for uploading.
    $image_files = $this->drupalGetTestFiles('image');
    $this->image = current($image_files);

    // Check if the physical file is there.
    $this->assertTrue(is_file($this->image->uri), "The image file we're going to upload exists.");

    // "Browse" for the desired file.
    $edit = array('files[image]' => drupal_realpath($this->image->uri));

    // Post the form.
    $this->drupalPostForm('form_test/form-state-values-clean-advanced', $edit, t('Submit'));

    // Expecting a 200 HTTP code.
    $this->assertResponse(200, 'Received a 200 response for posted test file.');
    $this->assertRaw(t('You WIN!'), 'Found the success message.');
  }
}
