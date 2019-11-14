<?php

namespace Drupal\Tests\search\Functional;

@trigger_error(__NAMESPACE__ . '\SearchTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\BrowserTestBase. See https://www.drupal.org/node/2979950.', E_USER_DEPRECATED);

use Drupal\Tests\BrowserTestBase;

/**
 * Defines the common search test code.
 *
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Tests\BrowserTestBase instead.
 *
 * @see https://www.drupal.org/node/2979950
 */
abstract class SearchTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'search', 'dblog'];

  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }
  }

  /**
   * Submission of a form via press submit button.
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
   *
   * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
   *   \Drupal\Tests\BrowserTestBase::drupalPostForm() instead.
   *
   * @see https://www.drupal.org/node/2979950
   */
  protected function submitGetForm($path, $edit, $submit, $form_html_id = NULL) {
    @trigger_error(__CLASS__ . '::' . __FUNCTION__ . '() is deprecated in Drupal 8.6.x, for removal before the Drupal 9.0.0 release. Use \Drupal\Tests\BrowserTestBase::drupalPostForm() instead. See https://www.drupal.org/node/2979950.', E_USER_DEPRECATED);
    $this->drupalPostForm($path, $edit, $submit, [], $form_html_id);
  }

}
