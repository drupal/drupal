<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;

/**
 * Tests that a module implementing hook_update_8000() causes an error to be
 * displayed on update.
 *
 * @group Update
 */
class InvalidUpdateHookTest extends BrowserTestBase {

  use RequirementsPageTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'update_test_invalid_hook',
    'update_script_test',
    'dblog',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * URL for the upgrade script.
   *
   * @var string
   */
  private $updateUrl;

  /**
   * A user account with upgrade permission.
   *
   * @var \Drupal\user\UserInterface
   */
  private $updateUser;

  protected function setUp(): void {
    parent::setUp();
    require_once $this->root . '/core/includes/update.inc';

    $this->updateUrl = $GLOBALS['base_url'] . '/update.php';
    $this->updateUser = $this->drupalCreateUser([
      'administer software updates',
    ]);
  }

  public function testInvalidUpdateHook() {
    // Confirm that a module with hook_update_8000() cannot be updated.
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));
    $this->assertText('Some of the pending updates cannot be applied because their dependencies were not met.');
  }

}
