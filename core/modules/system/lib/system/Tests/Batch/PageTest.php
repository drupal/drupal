<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Batch\PageTest.
 */

namespace Drupal\system\Tests\Batch;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the Batch API Progress page.
 */
class PageTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Batch progress page',
      'description' => 'Test the content of the progress page.',
      'group' => 'Batch API',
    );
  }

  function setUp() {
    parent::setUp('batch_test');
  }

  /**
   * Tests that the batch API progress page uses the correct theme.
   */
  function testBatchProgressPageTheme() {
    // Make sure that the page which starts the batch (an administrative page)
    // is using a different theme than would normally be used by the batch API.
    variable_set('theme_default', 'bartik');
    variable_set('admin_theme', 'seven');
    // Log in as an administrator who can see the administrative theme.
    $admin_user = $this->drupalCreateUser(array('view the administration theme'));
    $this->drupalLogin($admin_user);
    // Visit an administrative page that runs a test batch, and check that the
    // theme that was used during batch execution (which the batch callback
    // function saved as a variable) matches the theme used on the
    // administrative page.
    $this->drupalGet('admin/batch-test/test-theme');
    // The stack should contain the name of the theme used on the progress
    // page.
    $this->assertEqual(batch_test_stack(), array('seven'), t('A progressive batch correctly uses the theme of the page that started the batch.'));
  }
}
