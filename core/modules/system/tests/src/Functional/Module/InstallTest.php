<?php

namespace Drupal\Tests\system\Functional\Module;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the installation of modules.
 *
 * @group Module
 */
class InstallTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['module_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Verify that drupal_get_schema() can be used during module installation.
   */
  public function testGetSchemaAtInstallTime() {
    // @see module_test_install()
    $value = Database::getConnection()->select('module_test', 'mt')->fields('mt', ['data'])->execute()->fetchField();
    $this->assertIdentical($value, 'varchar');
  }

  /**
   * Tests enabling User module once more.
   *
   * Regression: The installer might enable a module twice due to automatic
   * dependency resolution. A bug caused the stored weight for User module to
   * be an array.
   */
  public function testEnableUserTwice() {
    \Drupal::service('module_installer')->install(['user'], FALSE);
    $this->assertIdentical($this->config('core.extension')->get('module.user'), 0);
  }

  /**
   * Tests recorded schema versions of early installed modules in the installer.
   */
  public function testRequiredModuleSchemaVersions() {
    $version = drupal_get_installed_schema_version('system', TRUE);
    $this->assertGreaterThan(0, $version);
    $version = drupal_get_installed_schema_version('user', TRUE);
    $this->assertGreaterThan(0, $version);

    $post_update_key_value = \Drupal::keyValue('post_update');
    $existing_updates = $post_update_key_value->get('existing_updates', []);
    $this->assertContains('module_test_post_update_test', $existing_updates);
  }

  /**
   * Ensures that post update functions are removed on uninstall.
   */
  public function testUninstallPostUpdateFunctions() {
    \Drupal::service('module_installer')->uninstall(['module_test']);

    $post_update_key_value = \Drupal::keyValue('post_update');
    $existing_updates = $post_update_key_value->get('existing_updates', []);
    $this->assertNotContains('module_test_post_update_test', $existing_updates);
  }

  /**
   * Tests that an exception is thrown when a module name is too long.
   */
  public function testModuleNameLength() {
    $module_name = 'invalid_module_name_over_the_maximum_allowed_character_length';
    $message = new FormattableMarkup('Exception thrown when enabling module %name with a name length over the allowed maximum', ['%name' => $module_name]);
    try {
      $this->container->get('module_installer')->install([$module_name]);
      $this->fail($message);
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(ExtensionNameLengthException::class, $e);
    }

    // Since for the UI, the submit callback uses FALSE, test that too.
    $message = new FormattableMarkup('Exception thrown when enabling as if via the UI the module %name with a name length over the allowed maximum', ['%name' => $module_name]);
    try {
      $this->container->get('module_installer')->install([$module_name], FALSE);
      $this->fail($message);
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(ExtensionNameLengthException::class, $e);
    }
  }

}
