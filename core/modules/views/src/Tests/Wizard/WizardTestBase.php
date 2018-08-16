<?php

namespace Drupal\views\Tests\Wizard;

@trigger_error('\Drupal\views\Tests\Wizard\WizardTestBase is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\views\Functional\Wizard\WizardTestBase', E_USER_DEPRECATED);

use Drupal\views\Tests\ViewTestBase;

/**
 * Views UI wizard tests.
 *
 * @deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0.
 *   Use \Drupal\Tests\views\Functional\Wizard\WizardTestBase.
 */
abstract class WizardTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'views_ui', 'block', 'rest'];

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    // Create and log in a user with administer views permission.
    $views_admin = $this->drupalCreateUser(['administer views', 'administer blocks', 'bypass node access', 'access user profiles', 'view all revisions']);
    $this->drupalLogin($views_admin);
    $this->drupalPlaceBlock('local_actions_block');
  }

}
