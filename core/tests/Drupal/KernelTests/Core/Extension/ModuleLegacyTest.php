<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecations from module.inc file.
 *
 * @group legacy
 */
class ModuleLegacyTest extends KernelTestBase {

  /**
   * Test deprecation of module_load_include() function.
   */
  public function testModuleLoadInclude() {
    $this->assertFalse($this->container->get('module_handler')->moduleExists('module_test'), 'Ensure module is uninstalled so we test the ability to include uninstalled code.');
    $this->expectDeprecation('module_load_include() is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. Instead, you should use \Drupal::moduleHandler()->loadInclude(). Note that including code from uninstalled extensions is no longer supported. See https://www.drupal.org/project/drupal/issues/697946');
    $filename = module_load_include('inc', 'module_test', 'module_test.file');
    $this->assertStringEndsWith("module_test.file.inc", $filename);

  }

  /**
   * Test deprecation of module_load_install() function.
   */
  public function testModuleLoadInstall() {
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('node'), 'The Node module is not installed');
    $this->expectDeprecation('module_load_install() is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Instead, you should use \Drupal::moduleHandler()->loadInclude($module, \'install\'). Note, the replacement no longer allows including code from uninstalled modules. See https://www.drupal.org/project/drupal/issues/2010380');
    $filename = module_load_install('node');
    $this->assertStringEndsWith("node.install", $filename);
  }

}
