<?php

/**
 * @file
 * Definition of Drupal\views\Tests\WizardTestBase.
 */

namespace Drupal\views\Tests;

use ViewsSqlTest;

/**
 * Views UI wizard tests.
 */
abstract class WizardTestBase extends ViewsSqlTest {
  protected $profile = 'standard';

  function setUp() {
    // Enable views_ui.
    parent::setUp('views_ui');

    // Create and log in a user with administer views permission.
    $views_admin = $this->drupalCreateUser(array('administer views', 'administer blocks', 'bypass node access', 'access user profiles', 'view revisions'));
    $this->drupalLogin($views_admin);
  }
}
