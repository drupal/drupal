<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests building the translatable project information.
 *
 * @group locale
 */
class LocaleBuildTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'locale',
    'locale_test',
    'system',
  ];

  /**
   * Checks if a list of translatable projects gets built.
   */
  public function testBuildProjects(): void {
    $this->container->get('module_handler')->loadInclude('locale', 'compare.inc');
    /** @var \Drupal\Core\Extension\ExtensionList $module_list */
    $module_list = \Drupal::service('extension.list.module');

    // Make the test modules look like a normal custom module. I.e. make the
    // modules not hidden. locale_test_system_info_alter() modifies the project
    // info of the locale_test and locale_test_translate modules.
    \Drupal::state()->set('locale.test_system_info_alter', TRUE);

    // Confirm the project name and core value before the module is altered.
    $projects = locale_translation_build_projects();
    $this->assertSame('locale_test', $projects['locale_test']->name);
    $this->assertSame('all', $projects['locale_test']->core);

    $projects['locale_test']->langcode = 'de';
    $this->assertSame('/all/locale_test/locale_test-1.2.de.po', locale_translation_build_server_pattern($projects['locale_test'], '/%core/%project/%project-%version.%language.po'));

    // Alter both the name and core value of the project.
    \Drupal::state()->set('locale.test_system_info_alter_name_core', TRUE);
    drupal_static_reset('locale_translation_project_list');
    $module_list->reset();

    // Confirm the name and core value are changed in $module->info.
    $module = $module_list->get('locale_test');
    $this->assertSame('locale_test_alter', $module->info['name']);
    $this->assertSame('8.6.7', $module->info['core']);
    $this->assertSame('locale_test', $module->getName());

    // Confirm the name and core value are not changed in the project.
    $projects = locale_translation_build_projects();
    $this->assertSame('locale_test', $projects['locale_test']->name);
    $this->assertSame('all', $projects['locale_test']->core);

    $projects['locale_test']->langcode = 'de';
    $this->assertSame('/all/locale_test/locale_test-1.2.de.po', locale_translation_build_server_pattern($projects['locale_test'], '/%core/%project/%project-%version.%language.po'));
  }

}
