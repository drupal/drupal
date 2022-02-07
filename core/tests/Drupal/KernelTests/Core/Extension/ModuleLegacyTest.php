<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Extension\ExtensionDiscovery;
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

  /**
   * Test deprecation of drupal_required_modules() function.
   */
  public function testDrupalRequiredModules() {
    $this->expectDeprecation("drupal_required_modules() is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. There's no replacement. See https://www.drupal.org/node/3262811");
    /** @var \Drupal\Core\Extension\InfoParserInterface $parser */
    $parser = \Drupal::service('info_parser');
    $listing = new ExtensionDiscovery(\Drupal::root());
    $files = $listing->scan('module');
    // Empty as there's no install profile.
    $required = [];
    foreach ($files as $name => $file) {
      $info = $parser->parse($file->getPathname());
      if (!empty($info) && !empty($info['required']) && $info['required']) {
        $required[] = $name;
      }
    }
    $this->assertSame($required, drupal_required_modules());
  }

}
