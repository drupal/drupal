<?php

namespace Drupal\Tests\system\Functional\Module;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests class loading for modules.
 *
 * @group Module
 */
class ClassLoaderTest extends BrowserTestBase {

  /**
   * The expected result from calling the module-provided class' method.
   *
   * @var string
   */
  protected $expected = 'Drupal\\module_autoload_test\\SomeClass::testMethod() was invoked.';

  /**
   * {@inheritdoc}
   */
  protected $apcuEnsureUniquePrefix = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that module-provided classes can be loaded when a module is enabled.
   *
   * @see \Drupal\module_autoload_test\SomeClass
   */
  public function testClassLoading() {
    // Enable the module_test and module_autoload_test modules.
    \Drupal::service('module_installer')->install(['module_test', 'module_autoload_test'], FALSE);
    $this->resetAll();
    // Check twice to test an unprimed and primed system_list() cache.
    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet('module-test/class-loading');
      $this->assertSession()->statusCodeEquals(200);
      $this->assertText($this->expected, 'Autoloader loads classes from an enabled module.');
    }
  }

  /**
   * Tests that module-provided classes can't be loaded if module not installed.
   *
   * @see \Drupal\module_autoload_test\SomeClass
   */
  public function testClassLoadingNotInstalledModules() {
    // Enable the module_test module.
    \Drupal::service('module_installer')->install(['module_test'], FALSE);
    $this->resetAll();
    // Check twice to test an unprimed and primed system_list() cache.
    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet('module-test/class-loading');
      $this->assertSession()->statusCodeEquals(200);
      $this->assertNoText($this->expected, 'Autoloader does not load classes from a disabled module.');
    }
  }

  /**
   * Tests that module-provided classes can't be loaded from disabled modules.
   *
   * @see \Drupal\module_autoload_test\SomeClass
   */
  public function testClassLoadingDisabledModules() {
    // Enable the module_test and module_autoload_test modules.
    \Drupal::service('module_installer')->install(['module_test', 'module_autoload_test'], FALSE);
    $this->resetAll();
    // Ensure that module_autoload_test is disabled.
    $this->container->get('module_installer')->uninstall(['module_autoload_test'], FALSE);
    $this->resetAll();
    // Check twice to test an unprimed and primed system_list() cache.
    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet('module-test/class-loading');
      $this->assertSession()->statusCodeEquals(200);
      $this->assertNoText($this->expected, 'Autoloader does not load classes from a disabled module.');
    }
  }

  /**
   * Ensures the negative caches in the class loader don't result in crashes.
   */
  public function testMultipleModules() {
    $this->drupalLogin($this->rootUser);
    $edit = [
      "modules[module_install_class_loader_test1][enable]" => TRUE,
      "modules[module_install_class_loader_test2][enable]" => TRUE,
    ];
    $this->drupalPostForm('admin/modules', $edit, t('Install'));
    $this->rebuildContainer();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('module_install_class_loader_test2'), 'The module_install_class_loader_test2 module has been installed.');
  }

}
