<?php

namespace Drupal\Tests\locale\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for updating the interface translations of projects.
 *
 * @group locale
 */
class LocaleUpdateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'locale',
    'locale_test',
    'system',
  ];

  /**
   * Checks if a list of translatable projects gets build.
   */
  public function testUpdateProjects() {
    $this->container->get('module_handler')->loadInclude('locale', 'compare.inc');

    // Make the test modules look like a normal custom module. I.e. make the
    // modules not hidden. locale_test_system_info_alter() modifies the project
    // info of the locale_test and locale_test_translate modules.
    \Drupal::state()->set('locale.test_system_info_alter', TRUE);

    // Check if interface translation data is collected from hook_info.
    $projects = locale_translation_project_list();
    $this->assertArrayNotHasKey('locale_test_translate', $projects);
    $this->assertEquals('core/modules/locale/test/test.%language.po', $projects['locale_test']['info']['interface translation server pattern']);
    $this->assertEquals('locale_test', $projects['locale_test']['name']);
  }

}
