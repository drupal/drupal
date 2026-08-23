<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;
use Drupal\TestTools\Extension\InfoWriterTrait;
use Drupal\user\UserInterface;

/**
 * Tests the update script access and functionality.
 */
abstract class UpdateScriptTestBase extends BrowserTestBase {
  use InfoWriterTrait;
  use RequirementsPageTrait;

  protected const HANDBOOK_MESSAGE = 'Review the suggestions for resolving this incompatibility to repair your installation, and then re-run update.php.';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'update_script_test',
    'dblog',
    'language',
    'test_module_required_by_theme',
    'test_another_module_required_by_theme',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The URL to the status report page.
   */
  protected Url $statusReportUrl;

  /**
   * URL to the update.php script.
   */
  protected Url $updateUrl;

  /**
   * A user with the necessary permissions to administer software updates.
   */
  protected UserInterface $updateUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->updateUrl = Url::fromRoute('system.db_update');
    $this->statusReportUrl = Url::fromRoute('system.status');
    $this->updateUser = $this->drupalCreateUser([
      'administer software updates',
      'access site in maintenance mode',
      'administer themes',
    ]);
  }

  /**
   * Enables an extension using the UI.
   *
   * @param string $extension_type
   *   The extension type.
   * @param array $extension_machine_names
   *   An array of the extension machine names.
   * @param array $extension_names
   *   An array of extension names.
   */
  protected function enableExtensions(string $extension_type, array $extension_machine_names, array $extension_names): void {
    if ($extension_type === 'module') {
      $edit = [];
      foreach ($extension_machine_names as $extension_machine_name) {
        $edit["modules[$extension_machine_name][enable]"] = $extension_machine_name;
      }
      $this->drupalGet('admin/modules');
      $this->submitForm($edit, 'Install');
    }
    elseif ($extension_type === 'theme') {
      $this->drupalGet('admin/appearance');
      foreach ($extension_names as $extension_name) {
        $this->click("a[title~=\"$extension_name\"]");
      }
    }
  }

  /**
   * Enables extensions via the UI.
   *
   * @param array $extension_info
   *   An array of extension information arrays. The array is keyed by 'module'
   *   and 'theme'.
   */
  protected function enableMissingExtensions(array $extension_info): void {
    $edit = [];
    foreach ($extension_info as $info) {
      if ($info['type'] === 'module') {
        $machine_name = $info['machine_name'];
        $edit["modules[$machine_name][enable]"] = $machine_name;
      }
      if (!empty($edit)) {
        $this->drupalGet('admin/modules');
        $this->submitForm($edit, 'Install');
      }
    }

    if (isset($extension_info['theme'])) {
      $this->drupalGet('admin/appearance');
      foreach ($extension_info as $info) {
        if ($info['type' === 'theme']) {
          $this->click('a[title~="' . $info['name'] . '"]');
        }
      }
    }
  }

  /**
   * Helper function to run updates via the browser.
   */
  protected function runUpdates($maintenance_mode): void {
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $schema_version = $update_registry->getInstalledVersion('update_script_test');
    $this->assertEquals(8001, $schema_version, 'update_script_test is initially installed with schema version 8001.');

    // Set the installed schema version to one less than the current update.
    $update_registry->setInstalledVersion('update_script_test', $schema_version - 1);
    $schema_version = $update_registry->getInstalledVersion('update_script_test');
    $this->assertEquals(8000, $schema_version, 'update_script_test schema version overridden to 8000.');

    // Click through update.php with 'administer software updates' permission.
    $this->drupalLogin($this->updateUser);
    if ($maintenance_mode) {
      $this->assertSession()->pageTextContains('Operating in maintenance mode.');
    }
    else {
      $this->assertSession()->pageTextNotContains('Operating in maintenance mode.');
    }
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();

    // Verify that updates were completed successfully.
    $this->assertSession()->pageTextContains('Updates were attempted.');
    $this->assertSession()->linkExists('site');
    $this->assertSession()->pageTextContains('The update_script_test_update_8001() update was executed successfully.');

    // Verify that no 7.x updates were run.
    $this->assertSession()->pageTextNotContains('The update_script_test_update_7200() update was executed successfully.');
    $this->assertSession()->pageTextNotContains('The update_script_test_update_7201() update was executed successfully.');

    // Verify that there are no links to different parts of the workflow.
    $this->assertSession()->linkNotExists('Administration pages');
    $this->assertSession()->elementNotExists('xpath', '//main//a[contains(@href, "update.php")]');
    $this->assertSession()->linkNotExists('logged');

    // Verify the front page can be visited following the upgrade.
    $this->clickLink('Front page');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Asserts that an installed extension's config setting is correct.
   *
   * @param string $extension_type
   *   The extension type, either 'module' or 'theme'.
   * @param array $extension_machine_names
   *   An array of the extension machine names.
   *
   * @internal
   */
  protected function assertInstalledExtensionsConfig(string $extension_type, array $extension_machine_names): void {
    $extension_config = $this->container->get('config.factory')->get('core.extension');
    foreach ($extension_machine_names as $extension_machine_name) {
      $this->assertSame(0, $extension_config->get("$extension_type.$extension_machine_name"));
    }
  }

  /**
   * Asserts particular errors are not shown on update and status report pages.
   *
   * @param array $unexpected_error_texts
   *   An array of the error texts that should not be shown.
   * @param string $extension_type
   *   The extension type, either 'module' or 'theme'.
   * @param array $extension_machine_names
   *   An array of  the extension machine names.
   *
   * @internal
   */
  protected function assertUpdateWithNoErrors(array $unexpected_error_texts, string $extension_type, array $extension_machine_names): void {
    $assert_session = $this->assertSession();
    foreach ($unexpected_error_texts as $unexpected_error_text) {
      $assert_session->pageTextNotContains($unexpected_error_text);
    }
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    foreach ($unexpected_error_texts as $unexpected_error_text) {
      $assert_session->pageTextNotContains($unexpected_error_text);
    }
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $assert_session->pageTextContains('No pending updates.');
    $this->assertInstalledExtensionsConfig($extension_type, $extension_machine_names);
  }

  /**
   * Asserts errors are shown on the update and status report pages.
   *
   * @param array $expected_error_texts
   *   The expected error texts.
   * @param string $extension_type
   *   The extension type, either 'module' or 'theme'.
   * @param array $extension_machine_names
   *   The extension machine names.
   * @param array $test_error_urls
   *   The URLs in the error texts.
   *
   * @internal
   */
  protected function assertErrorOnUpdates(array $expected_error_texts, string $extension_type, array $extension_machine_names, array $test_error_urls): void {
    $assert_session = $this->assertSession();
    foreach ($expected_error_texts as $expected_error_text) {
      $assert_session->pageTextContains($expected_error_text);
    }
    foreach ($test_error_urls as $test_error_url) {
      $assert_session->linkByHrefExists($test_error_url);
    }

    // Reload the update page to ensure the extension with the breaking values
    // has not been uninstalled or otherwise affected.
    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet($this->updateUrl, ['external' => TRUE]);
      foreach ($expected_error_texts as $expected_error_text) {
        $assert_session->pageTextContains($expected_error_text);
      }
      $assert_session->linkNotExists('Continue');
    }
    $this->assertInstalledExtensionsConfig($extension_type, $extension_machine_names);
  }

}
