<?php

declare(strict_types=1);

namespace Drupal\Tests\config\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the importing/exporting configuration based on the install profile.
 *
 * @group config
 */
class ConfigImportInstallProfileTest extends BrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing_config_import';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with the 'synchronize configuration' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(['synchronize configuration']);
    $this->drupalLogin($this->webUser);
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));
  }

  /**
   * Tests config importer can uninstall install profiles.
   *
   * Install profiles can be uninstalled when none of the modules or themes
   * they contain are installed.
   *
   * @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
   */
  public function testInstallProfileValidation(): void {
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($this->container->get('config.storage'), $sync);
    $core = $sync->read('core.extension');

    // Ensure install profiles can not be uninstalled.
    unset($core['module']['testing_config_import']);
    $sync->write('core.extension', $core);

    $this->drupalGet('admin/config/development/configuration');
    $this->submitForm([], 'Import all');
    $this->assertSession()->pageTextContains('The configuration cannot be imported because it failed validation for the following reasons:');
    $this->assertSession()->pageTextContains("The install profile 'Testing config import' is providing the following module(s): testing_config_import_module");

    // Uninstall dependencies of testing_config_import.
    unset($core['module']['syslog']);
    unset($core['module']['testing_config_import_module']);
    unset($core['theme']['stark']);
    $core['module']['testing_config_import'] = 0;
    $core['theme']['test_theme_theme'] = 0;
    $sync->write('core.extension', $core);
    $sync->deleteAll('syslog.');
    $theme = $sync->read('system.theme');
    $theme['default'] = 'test_theme_theme';
    $sync->write('system.theme', $theme);
    $this->drupalGet('admin/config/development/configuration');
    $this->submitForm([], 'Import all');
    $this->assertSession()->pageTextContains('The configuration was imported successfully.');
    $this->rebuildContainer();
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('syslog'), 'The syslog module has been uninstalled.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('testing_config_import_module'), 'The testing_config_import_module module has been uninstalled.');
    $this->assertFalse(\Drupal::service('theme_handler')->themeExists('stark'), 'The stark theme has been uninstalled.');
    $this->assertTrue(\Drupal::service('theme_handler')->themeExists('test_theme_theme'), 'The test_theme_theme theme has been installed.');

    // Uninstall testing_config_import profile without removing the profile key.
    unset($core['module']['testing_config_import']);
    $sync->write('core.extension', $core);
    $this->drupalGet('admin/config/development/configuration');
    $this->submitForm([], 'Import all');
    $this->assertSession()->pageTextContains('The configuration cannot be imported because it failed validation for the following reasons:');
    $this->assertSession()->pageTextContains('The install profile testing_config_import is not in the list of installed modules.');

    // Uninstall testing_config_import profile properly.
    unset($core['profile']);
    $sync->write('core.extension', $core);
    $this->drupalGet('admin/config/development/configuration');
    $this->submitForm([], 'Import all');
    $this->assertSession()->pageTextContains('The configuration was imported successfully.');
    $this->rebuildContainer();
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('testing_config_import'), 'The testing_config_import profile has been uninstalled.');
  }

}
