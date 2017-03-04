<?php

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the importing/exporting configuration based on the install profile.
 *
 * @group config
 */
class ConfigImportInstallProfileTest extends WebTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing_config_import';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['config'];

  /**
   * A user with the 'synchronize configuration' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(['synchronize configuration']);
    $this->drupalLogin($this->webUser);
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));
  }

  /**
   * Tests config importer cannot uninstall install profiles.
   *
   * @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
   */
  public function testInstallProfileValidation() {
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($this->container->get('config.storage'), $sync);
    $core = $sync->read('core.extension');

    // Ensure install profiles can not be uninstalled.
    unset($core['module']['testing_config_import']);
    $sync->write('core.extension', $core);

    $this->drupalPostForm('admin/config/development/configuration', [], t('Import all'));
    $this->assertText('The configuration cannot be imported because it failed validation for the following reasons:');
    $this->assertText('Unable to uninstall the Testing config import profile since it is the install profile.');

    // Uninstall dependencies of testing_config_import.
    $core['module']['testing_config_import'] = 0;
    unset($core['module']['syslog']);
    unset($core['theme']['stark']);
    $core['theme']['stable'] = 0;
    $core['theme']['classy'] = 0;
    $sync->write('core.extension', $core);
    $sync->deleteAll('syslog.');
    $theme = $sync->read('system.theme');
    $theme['default'] = 'classy';
    $sync->write('system.theme', $theme);
    $this->drupalPostForm('admin/config/development/configuration', [], t('Import all'));
    $this->assertText('The configuration was imported successfully.');
    $this->rebuildContainer();
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('syslog'), 'The syslog module has been uninstalled.');
    $this->assertFalse(\Drupal::service('theme_handler')->themeExists('stark'), 'The stark theme has been uninstalled.');
    $this->assertTrue(\Drupal::service('theme_handler')->themeExists('classy'), 'The classy theme has been installed.');
  }

}
