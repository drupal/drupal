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
  protected function setUp() {
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

    // Access the update page with a schema version that is too old for the test
    // module.
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl);
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Requirements problem');
    $assert_session->pageTextContains('Unsupported schema version: Update test with hook_update_last_removed() implementation');
    $assert_session->pageTextContains('The installed version of the Update test with hook_update_last_removed() implementation module is too old to update. Update to an intermediate version first (last removed version: 8002, installed version: 8000).');
    $assert_session->linkNotExists('Continue');

    // Set the expected schema version, updates are successful now.
    drupal_set_installed_schema_version('update_test_last_removed', 8002);

    $this->runUpdates();
    $this->assertEquals(8003, drupal_get_installed_schema_version('update_test_last_removed'));
  }

}
