<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\locale\LocaleProjectRepository;
use Drupal\locale\LocaleSource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests building the translatable project information.
 */
#[Group('locale')]
#[RunTestsInSeparateProcesses]
class LocaleBuildTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'locale',
    'locale_test',
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
    $projects = \Drupal::service(LocaleProjectRepository::class)->buildProjects();
    $this->assertSame('locale_test', $projects['locale_test']->name);
    $this->assertSame('all', $projects['locale_test']->core);
    $this->assertSame(0, $projects['locale_test']->getWeight());

    $projects['locale_test']->setLangcode('de');
    $this->assertSame('/all/locale_test/locale_test-1.2.de.po', \Drupal::service(LocaleSource::class)->buildServerPattern($projects['locale_test'], '/%core/%project/%project-%version.%language.po'));

    // Alter both the name and core value of the project.
    \Drupal::state()->set('locale.test_system_info_alter_name_core', TRUE);
    $module_list->reset();

    // Confirm the name and core value are changed in $module->info.
    $module = $module_list->get('locale_test');
    $this->assertSame('locale_test_alter', $module->info['name']);
    $this->assertSame('8.6.7', $module->info['core']);
    $this->assertSame('locale_test', $module->getName());

    // Confirm the name and core value are not changed in the project.
    $projects = \Drupal::service(LocaleProjectRepository::class)->buildProjects();
    $this->assertSame('locale_test', $projects['locale_test']->name);
    $this->assertSame('all', $projects['locale_test']->core);

    $projects['locale_test']->setLangcode('de');
    $this->assertSame('/all/locale_test/locale_test-1.2.de.po', \Drupal::service(LocaleSource::class)->buildServerPattern($projects['locale_test'], '/%core/%project/%project-%version.%language.po'));

    \Drupal::state()->set('locale.test_projects_alter.weight', TRUE);
    $projects = \Drupal::service(LocaleProjectRepository::class)->buildProjects();
    $this->assertSame(100, $projects['locale_test']->getWeight());
  }

  /**
   * Tests deprecated function locale_translation_get_projects().
   */
  #[IgnoreDeprecations]
  public function testLocaleTranslationGetProjects() : void {
    \Drupal::state()->set('locale.test_system_info_alter', TRUE);
    $this->expectUserDeprecationMessage('locale_translation_get_projects() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(LocaleProjectRepository::class)->getAll() or \Drupal::service(LocaleProjectRepository::class)->getMultiple($project_names) instead. See https://www.drupal.org/node/3569330');
    $this->container->get('module_handler')->loadInclude('locale', 'translation.inc');
    $this->assertCount(2, locale_translation_get_projects());
    $this->assertCount(1, locale_translation_get_projects(['locale_test']));
    $this->assertCount(0, locale_translation_get_projects(['does_not_exist']));
  }

}
