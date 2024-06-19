<?php

declare(strict_types=1);

namespace Drupal\Tests\config\Functional;

use Drupal\config_test\Entity\ConfigTest;
use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\Site\Settings;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

// cspell:ignore suis

/**
 * Tests configuration objects before and after module install and uninstall.
 *
 * The installation and removal of configuration objects in install, disable
 * and uninstall functionality is tested.
 *
 * @group config
 * @group #slow
 */
class ConfigInstallWebTest extends BrowserTestBase {

  /**
   * The admin user used in this test.
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer modules',
      'administer themes',
      'administer site configuration',
    ]);

    // Ensure the global variable being asserted by this test does not exist;
    // a previous test executed in this request/process might have set it.
    unset($GLOBALS['hook_config_test']);
  }

  /**
   * Tests module re-installation.
   */
  public function testIntegrationModuleReinstallation(): void {
    $default_config = 'config_integration_test.settings';
    $default_configuration_entity = 'config_test.dynamic.config_integration_test';

    // Install the config_test module we're integrating with.
    \Drupal::service('module_installer')->install(['config_test']);

    // Verify the configuration does not exist prior to installation.
    $config_static = $this->config($default_config);
    $this->assertTrue($config_static->isNew());
    $config_entity = $this->config($default_configuration_entity);
    $this->assertTrue($config_entity->isNew());

    // Install the integration module.
    \Drupal::service('module_installer')->install(['config_integration_test']);
    $this->resetAll();

    // Verify that default module config exists.
    \Drupal::configFactory()->reset($default_config);
    \Drupal::configFactory()->reset($default_configuration_entity);
    $config_static = $this->config($default_config);
    $this->assertFalse($config_static->isNew());
    $this->assertSame('default setting', $config_static->get('foo'));
    $config_entity = $this->config($default_configuration_entity);
    $this->assertFalse($config_entity->isNew());
    $this->assertSame('Default integration config label', $config_entity->get('label'));

    // Customize both configuration objects.
    $config_static->set('foo', 'customized setting')->save();
    $config_entity->set('label', 'Customized integration config label')->save();

    // @todo FIXME: Setting config keys WITHOUT SAVING retains the changed config
    //   object in memory. Every new call to $this->config() MUST revert in-memory changes
    //   that haven't been saved!
    //   In other words: This test passes even without this reset, but it shouldn't.
    $this->container->get('config.factory')->reset();

    // Disable and uninstall the integration module.
    $this->container->get('module_installer')->uninstall(['config_integration_test']);

    // Verify the integration module's config was uninstalled.
    $config_static = $this->config($default_config);
    $this->assertTrue($config_static->isNew());

    // Verify the integration config still exists.
    $config_entity = $this->config($default_configuration_entity);
    $this->assertFalse($config_entity->isNew());
    $this->assertSame('Customized integration config label', $config_entity->get('label'));

    // Reinstall the integration module.
    try {
      \Drupal::service('module_installer')->install(['config_integration_test']);
      $this->fail('Expected PreExistingConfigException not thrown.');
    }
    catch (PreExistingConfigException $e) {
      $this->assertEquals('config_integration_test', $e->getExtension());
      $this->assertEquals([StorageInterface::DEFAULT_COLLECTION => ['config_test.dynamic.config_integration_test']], $e->getConfigObjects());
      $this->assertEquals('Configuration objects (config_test.dynamic.config_integration_test) provided by config_integration_test already exist in active configuration', $e->getMessage());
    }

    // Delete the configuration entity so that the install will work.
    $config_entity->delete();
    \Drupal::service('module_installer')->install(['config_integration_test']);

    // Verify the integration module's config was re-installed.
    \Drupal::configFactory()->reset($default_config);
    \Drupal::configFactory()->reset($default_configuration_entity);
    $config_static = $this->config($default_config);
    $this->assertFalse($config_static->isNew());
    $this->assertSame('default setting', $config_static->get('foo'));

    // Verify the integration config is using the default.
    $config_entity = \Drupal::config($default_configuration_entity);
    $this->assertFalse($config_entity->isNew());
    $this->assertSame('Default integration config label', $config_entity->get('label'));
  }

  /**
   * Tests pre-existing configuration detection.
   */
  public function testPreExistingConfigInstall(): void {
    $this->drupalLogin($this->adminUser);

    // Try to install config_install_fail_test and config_test. Doing this
    // will install the config_test module first because it is a dependency of
    // config_install_fail_test.
    // @see \Drupal\system\Form\ModulesListForm::submitForm()
    $this->drupalGet('admin/modules');
    $this->submitForm([
      'modules[config_test][enable]' => TRUE,
      'modules[config_install_fail_test][enable]' => TRUE,
    ], 'Install');
    $this->assertSession()->responseContains('Unable to install Configuration install fail test, <em class="placeholder">config_test.dynamic.dotted.default</em> already exists in active configuration.');

    // Uninstall the config_test module to test the confirm form.
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm(['uninstall[config_test]' => TRUE], 'Uninstall');
    $this->submitForm([], 'Uninstall');

    // Try to install config_install_fail_test without selecting config_test.
    // The user is shown a confirm form because the config_test module is a
    // dependency.
    // @see \Drupal\system\Form\ModulesListConfirmForm::submitForm()
    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[config_install_fail_test][enable]' => TRUE], 'Install');
    $this->submitForm([], 'Continue');
    $this->assertSession()->responseContains('Unable to install Configuration install fail test, <em class="placeholder">config_test.dynamic.dotted.default</em> already exists in active configuration.');

    // Test that collection configuration clashes during a module install are
    // reported correctly.
    \Drupal::service('module_installer')->install(['language']);
    $this->rebuildContainer();
    ConfigurableLanguage::createFromLangcode('fr')->save();
    \Drupal::languageManager()
      ->getLanguageConfigOverride('fr', 'config_test.dynamic.dotted.default')
      ->set('label', 'Je suis Charlie')
      ->save();

    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[config_install_fail_test][enable]' => TRUE], 'Install');
    $this->assertSession()->responseContains('Unable to install Configuration install fail test, <em class="placeholder">config_test.dynamic.dotted.default, language/fr/config_test.dynamic.dotted.default</em> already exist in active configuration.');

    // Test installing a theme through the UI that has existing configuration.
    // This relies on the fact the config_test has been installed and created
    // the config_test.dynamic.dotted.default configuration and the translation
    // override created still exists.
    $this->drupalGet('admin/appearance');
    $url = $this->xpath("//a[contains(@href,'config_clash_test_theme') and contains(@href,'/install?')]/@href")[0];
    $this->drupalGet($this->getAbsoluteUrl($url->getText()));
    $this->assertSession()->responseContains('Unable to install config_clash_test_theme, <em class="placeholder">config_test.dynamic.dotted.default, language/fr/config_test.dynamic.dotted.default</em> already exist in active configuration.');

    // Test installing a theme through the API that has existing configuration.
    try {
      \Drupal::service('theme_installer')->install(['config_clash_test_theme']);
      $this->fail('Expected PreExistingConfigException not thrown.');
    }
    catch (PreExistingConfigException $e) {
      $this->assertEquals('config_clash_test_theme', $e->getExtension());
      $this->assertEquals([StorageInterface::DEFAULT_COLLECTION => ['config_test.dynamic.dotted.default'], 'language.fr' => ['config_test.dynamic.dotted.default']], $e->getConfigObjects());
      $this->assertEquals('Configuration objects (config_test.dynamic.dotted.default, language/fr/config_test.dynamic.dotted.default) provided by config_clash_test_theme already exist in active configuration', $e->getMessage());
    }
  }

  /**
   * Tests unmet dependencies detection.
   */
  public function testUnmetDependenciesInstall(): void {
    $this->drupalLogin($this->adminUser);
    // We need to install separately since config_install_dependency_test does
    // not depend on config_test and order is important.
    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[config_test][enable]' => TRUE], 'Install');
    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[config_install_dependency_test][enable]' => TRUE], 'Install');
    $this->assertSession()->responseContains('Unable to install <em class="placeholder">Config install dependency test</em> due to unmet dependencies: <em class="placeholder">config_test.dynamic.other_module_test_with_dependency (config_other_module_config_test, config_test.dynamic.dotted.english)</em>');

    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[config_test_language][enable]' => TRUE], 'Install');
    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[config_install_dependency_test][enable]' => TRUE], 'Install');
    $this->assertSession()->responseContains('Unable to install <em class="placeholder">Config install dependency test</em> due to unmet dependencies: <em class="placeholder">config_test.dynamic.other_module_test_with_dependency (config_other_module_config_test)</em>');

    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[config_other_module_config_test][enable]' => TRUE], 'Install');
    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[config_install_dependency_test][enable]' => TRUE], 'Install');
    $this->rebuildContainer();
    $this->assertInstanceOf(ConfigTest::class, \Drupal::entityTypeManager()->getStorage('config_test')->load('other_module_test_with_dependency'));
  }

  /**
   * Tests config_requirements().
   */
  public function testConfigModuleRequirements(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[config][enable]' => TRUE], 'Install');

    $directory = Settings::get('config_sync_directory');
    try {
      \Drupal::service('file_system')->deleteRecursive($directory);
    }
    catch (FileException $e) {
      // Ignore failed deletes.
    }
    $this->drupalGet('/admin/reports/status');
    $this->assertSession()->pageTextContains("The directory $directory does not exist.");
  }

}
