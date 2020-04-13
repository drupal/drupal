<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests that modules can define their last removed update function.
 *
 * @group system
 */
class UpdatePathLastRemovedTest extends BrowserTestBase {
  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update_test_last_removed'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * URL for the upgrade script.
   *
   * @var string
   */
  protected $updateUrl;

  /**
   * A user account with upgrade permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $updateUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once $this->root . '/core/includes/update.inc';

    $this->updateUrl = Url::fromRoute('system.db_update');
    $this->updateUser = $this->drupalCreateUser(['administer software updates']);
  }

  /**
   * Tests that a module with a too old schema version can not be updated.
   */
  public function testLastRemovedVersion() {
    drupal_set_installed_schema_version('update_test_last_removed', 8000);
    drupal_set_installed_schema_version('system', 8804);

    // Access the update page with a schema version that is too old for system
    // and the test module, only the generic core message should be shown.
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl);
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Requirements problem');
    $assert_session->pageTextContains('The version of Drupal you are trying to update from is too old');
    $assert_session->pageTextContains('Updating to Drupal 9 is only supported from Drupal version 8.8.0 or higher. If you are trying to update from an older version, first update to the latest version of Drupal 8');
    $assert_session->pageTextNotContains('Unsupported schema version: Update test with hook_update_last_removed() implementation');

    $assert_session->linkNotExists('Continue');

    // Update the installed version of system and then assert that now,
    // the test module is shown instead.
    drupal_set_installed_schema_version('system', 8805);
    $this->drupalGet($this->updateUrl);

    $assert_session->pageTextNotContains('The version of Drupal you are trying to update from is too old');

    $assert_session->pageTextContains('Unsupported schema version: Update test with hook_update_last_removed() implementation');
    $assert_session->pageTextContains('The installed version of the Update test with hook_update_last_removed() implementation module is too old to update. Update to an intermediate version first (last removed version: 8002, installed version: 8000).');
    $assert_session->linkNotExists('Continue');

    // Set the expected schema version for the node and test module, updates are
    // successful now.
    drupal_set_installed_schema_version('update_test_last_removed', 8002);

    $this->runUpdates();
    $this->assertEquals(8003, drupal_get_installed_schema_version('update_test_last_removed'));

  }

}
