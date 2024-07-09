<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;

/**
 * Tests that update hooks are properly run.
 *
 * @group Update
 */
class EquivalentUpdateTest extends BrowserTestBase {

  use RequirementsPageTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['equivalent_update_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The update URL.
   *
   * @var string
   */
  protected $updateUrl;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    require_once $this->root . '/core/includes/update.inc';
    $this->user = $this->drupalCreateUser([
      'administer software updates',
      'access site in maintenance mode',
    ]);
    $this->updateUrl = Url::fromRoute('system.db_update');
  }

  /**
   * Tests that update hooks are properly run.
   */
  public function testUpdateHooks() {
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $this->drupalLogin($this->user);

    // Verify that the 100000 schema is in place due to
    // equivalent_update_test_update_100000().
    $this->assertEquals(100000, $update_registry->getInstalledVersion('equivalent_update_test'));

    // Remove the update and implement hook_update_last_removed().
    \Drupal::state()->set('equivalent_update_test_last_removed', TRUE);

    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('Schema version 100001.');
    // Run the update hooks.
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();

    // Ensure schema has changed.
    $this->resetAll();
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $this->assertEquals(100001, $update_registry->getInstalledVersion('equivalent_update_test'));

    // Set the first update to run.
    \Drupal::state()->set('equivalent_update_test_update_100002', TRUE);

    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('Schema version 100002.');
    // Run the update hooks.
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();

    // Ensure schema has changed.
    $this->resetAll();
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $this->assertEquals(100002, $update_registry->getInstalledVersion('equivalent_update_test'));
    $this->assertSame(100002, $update_registry->getEquivalentUpdate('equivalent_update_test', 100101)->ran_update);

    \Drupal::state()->set('equivalent_update_test_update_100002', FALSE);
    \Drupal::state()->set('equivalent_update_test_update_100101', FALSE);

    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->assertSession()->pageTextContains('The version of the Equivalent Update test module that you are attempting to update to is missing update 100101 (which was marked as an equivalent by 100002). Update to at least Drupal Core 11.1.0.');

    \Drupal::state()->set('equivalent_update_test_update_100101', TRUE);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('Schema version 100101.');
    // Run the update hooks.
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Update 100101 for the equivalent_update_test module has been skipped because the equivalent change was already made in update 100002.');

    // Ensure that the schema has changed.
    $this->resetAll();
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $this->assertEquals(100101, $update_registry->getInstalledVersion('equivalent_update_test'));
    $this->assertNull($update_registry->getEquivalentUpdate('equivalent_update_test', 100101));

    \Drupal::state()->set('equivalent_update_test_update_100201', TRUE);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('Schema version 100201.');
    // Run the update hooks.
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Update 100201 for the equivalent_update_test module has been skipped because the equivalent change was already made in update 100201.');

    \Drupal::state()->set('equivalent_update_test_update_100301', TRUE);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('Schema version 100301.');
    // Run the update hooks.
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Update 100302 for the equivalent_update_test module has been skipped because the equivalent change was already made in update 100301.');

    \Drupal::state()->set('equivalent_update_test_update_100400', TRUE);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('Schema version 100400.');
    // Run the update hooks.
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();

    \Drupal::state()->set('equivalent_update_test_update_100400', FALSE);
    \Drupal::state()->set('equivalent_update_test_update_100401', TRUE);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->assertSession()->pageTextContains('The version of the Equivalent Update test module that you are attempting to update to is missing update 100402 (which was marked as an equivalent by 100400). Update to at least Drupal Core 11.2.0.');

    \Drupal::state()->set('equivalent_update_test_update_100400', TRUE);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('Schema version 100401.');
    // Run the update hooks.
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();

    \Drupal::state()->set('equivalent_update_test_update_100400', FALSE);
    \Drupal::state()->set('equivalent_update_test_update_100401', FALSE);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->assertSession()->pageTextContains('The version of the Equivalent Update test module that you are attempting to update to is missing update 100402 (which was marked as an equivalent by 100401). Update to at least Drupal Core 11.2.0.');

    \Drupal::state()->set('equivalent_update_test_update_100402', TRUE);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('Schema version 100402.');
    // Run the update hooks.
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();
    // Ensure that the schema has changed.
    $this->resetAll();
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $this->assertEquals(100402, $update_registry->getInstalledVersion('equivalent_update_test'));

    \Drupal::state()->set('equivalent_update_test_update_100501', TRUE);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('Schema version 100501.');
    // Run the update hooks.
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('LogicException: Cannot mark the update 100302 as an equivalent since it is less than the current update 100501 for the equivalent_update_test module');
  }

  /**
   * Tests that module uninstall removes skipped update information.
   */
  public function testModuleUninstall() {
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');

    // Verify that the 100000 schema is in place due to
    // equivalent_update_test_update_last_removed().
    $this->assertEquals(100000, $update_registry->getInstalledVersion('equivalent_update_test'));

    // Set the first update to run.
    \Drupal::state()->set('equivalent_update_test_update_100002', TRUE);

    $this->drupalLogin($this->user);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('Schema version 100002.');
    // Run the update hooks.
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();

    // Ensure that the schema has changed.
    $this->resetAll();
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $this->assertEquals(100002, $update_registry->getInstalledVersion('equivalent_update_test'));
    $this->assertSame(100002, $update_registry->getEquivalentUpdate('equivalent_update_test', 100101)->ran_update);

    \Drupal::service('module_installer')->uninstall(['equivalent_update_test']);

    $this->assertNull($update_registry->getEquivalentUpdate('equivalent_update_test', 100101));
    $this->assertEmpty($update_registry->getAllEquivalentUpdates());
  }

}
