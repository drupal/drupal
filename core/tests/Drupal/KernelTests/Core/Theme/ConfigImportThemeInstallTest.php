<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Config\ConfigImporterException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests installing and uninstalling of themes via configuration import.
 *
 * @group Extension
 */
class ConfigImportThemeInstallTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Tests config imports that install and uninstall a theme with dependencies.
   */
  public function testConfigImportWithThemeWithModuleDependencies() {
    $this->container->get('module_installer')->install(['test_module_required_by_theme', 'test_another_module_required_by_theme']);
    $this->container->get('theme_installer')->install(['test_theme_depending_on_modules']);
    $this->assertTrue($this->container->get('theme_handler')->themeExists('test_theme_depending_on_modules'), 'test_theme_depending_on_modules theme installed');

    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($this->container->get('config.storage'), $sync);
    $extensions = $sync->read('core.extension');
    // Remove one of the modules the theme depends on.
    unset($extensions['module']['test_module_required_by_theme']);
    $sync->write('core.extension', $extensions);

    try {
      $this->configImporter()->validate();
      $this->fail('ConfigImporterException not thrown; an invalid import was not stopped due to missing dependencies.');
    }
    catch (ConfigImporterException $e) {
      $error_message = 'Unable to uninstall the <em class="placeholder">Test Module Required by Theme</em> module because: Required by the theme: Test Theme Depending on Modules.';
      $this->assertStringContainsString($error_message, $e->getMessage(), 'There were errors validating the config synchronization.');
      $error_log = $this->configImporter->getErrors();
      $this->assertSame($error_message, (string) $error_log[0]);
    }

    // Remove the other module and the theme.
    unset($extensions['module']['test_another_module_required_by_theme']);
    unset($extensions['theme']['test_theme_depending_on_modules']);
    $sync->write('core.extension', $extensions);
    $this->configImporter()->import();

    $this->assertFalse($this->container->get('theme_handler')->themeExists('test_theme_depending_on_modules'), 'test_theme_depending_on_modules theme uninstalled by configuration import');

    // Try installing a theme with dependencies via config import.
    $extensions['theme']['test_theme_depending_on_modules'] = 0;
    $extensions['module']['test_another_module_required_by_theme'] = 0;
    $sync->write('core.extension', $extensions);
    try {
      $this->configImporter()->validate();
      $this->fail('ConfigImporterException not thrown; an invalid import was not stopped due to missing dependencies.');
    }
    catch (ConfigImporterException $e) {
      $error_message = 'Unable to install the <em class="placeholder">Test Theme Depending on Modules</em> theme since it requires the <em class="placeholder">Test Module Required by Theme</em> module.';
      $this->assertStringContainsString($error_message, $e->getMessage(), 'There were errors validating the config synchronization.');
      $error_log = $this->configImporter->getErrors();
      $this->assertSame($error_message, (string) $error_log[0]);
    }

    $extensions['module']['test_module_required_by_theme'] = 0;
    $sync->write('core.extension', $extensions);
    $this->configImporter()->import();
    $this->assertTrue($this->container->get('theme_handler')->themeExists('test_theme_depending_on_modules'), 'test_theme_depending_on_modules theme installed');
  }

}
