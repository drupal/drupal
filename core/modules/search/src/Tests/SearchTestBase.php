<?php

namespace Drupal\search\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Defines the common search test code.
 */
abstract class SearchTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'search', 'dblog');

  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
  }

  /**
   * Simulates submission of a form using GET instead of POST.
   *
   * Forms that use the GET method cannot be submitted with
   * WebTestBase::drupalPostForm(), which explicitly uses POST to submit the
   * form. So this method finds the form, verifies that it has input fields and
   * a submit button matching the inputs to this method, and then calls
   * WebTestBase::drupalGet() to simulate the form submission to the 'action'
   * URL of the form (if set, or the current URL if not).
   *
   * See WebTestBase::drupalPostForm() for more detailed documentation of the
   * function parameters.
   *
   * @param string $path
   *   Location of the form to be submitted: either a Drupal path, absolute
   *   path, or NULL to use the current page.
   * @param array $edit
   *   Form field data to submit. Unlike drupalPostForm(), this does not support
   *   file uploads.
   * @param string $submit
   *   Value of the submit button to submit clicking. Unlike drupalPostForm(),
   *   this does not support AJAX.
   * @param string $form_html_id
   *   (optional) HTML ID of the form, to disambiguate.
   */
  protected function submitGetForm($path, $edit, $submit, $form_html_id = NULL) {
    if (isset($path)) {
      $this->drupalGet($path);
    }

    if ($this->parse()) {
      // Iterate over forms to find one that matches $edit and $submit.
      $edit_save = $edit;
      $xpath = '//form';
      if (!empty($form_html_id)) {
        $xpath .= "[@id='" . $form_html_id . "']";
      }
      $forms = $this->xpath($xpath);
      foreach ($forms as $form) {
        // Try to set the fields of this form as specified in $edit.
        $edit = $edit_save;
        $post = array();
        $upload = array();
        $submit_matches = $this->handleForm($post, $edit, $upload, $submit, $form);
        if (!$edit && $submit_matches) {
          // Everything matched, so "submit" the form.
          $action = isset($form['action']) ? $this->getAbsoluteUrl((string) $form['action']) : NULL;
          $this->drupalGet($action, array('query' => $post));
          return;
        }
      }

      // We have not found a form which contained all fields of $edit and
      // the submit button.
      foreach ($edit as $name => $value) {
        $this->fail(SafeMarkup::format('Failed to set field @name to @value', array('@name' => $name, '@value' => $value)));
      }
      $this->assertTrue($submit_matches, format_string('Found the @submit button', array('@submit' => $submit)));
      $this->fail(format_string('Found the requested form fields at @path', array('@path' => $path)));
    }
  }
}
