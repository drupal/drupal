<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that hook_update_8000() is disallowed.
 */
#[Group('Update')]
#[RunTestsInSeparateProcesses]
class InvalidUpdateHookTest extends BrowserTestBase {

  use RequirementsPageTrait;

  /**
   * {@inheritdoc}
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
   */
  private string $updateUrl;

  /**
   * A user account with upgrade permission.
   */
  private UserInterface $updateUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once $this->root . '/core/includes/update.inc';

    $this->updateUrl = Url::fromRoute('system.db_update')->setAbsolute()->toString();
    $this->updateUser = $this->drupalCreateUser([
      'administer software updates',
    ]);
  }

  /**
   * Tests updating from a module with a hook_update_8000().
   */
  public function testInvalidUpdateHook(): void {
    // Confirm that a module with hook_update_8000() cannot be updated.
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('Some of the pending updates cannot be applied because their dependencies were not met.');
  }

}
