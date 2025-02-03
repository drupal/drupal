<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests instantiating migrate source plugins using I18nQueryTrait.
 *
 * @group migrate_drupal
 */
class I18nQueryTraitTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'menu_link_content',
    'migrate',
    'migrate_drupal',
    'taxonomy',
  ];

  /**
   * Tests instantiating migrate source plugins using I18nQueryTrait.
   *
   * I18nQueryTrait was originally in the content_translation module, which
   * could lead to fatal errors instantiating the source plugins that use it
   * when the content_translation module was not installed.
   *
   * @param string $plugin_id
   *   The ID of a Migrate source plugin that uses I18nQueryTrait.
   *
   * @dataProvider providerI18nQueryTraitPlugins
   */
  public function testMigrateSourcePluginUsingI18nQueryTraitDiscovery(string $plugin_id): void {
    // Namespace for uninstalled module content_translation needs to be removed
    // for this test.
    $this->disablePsr4ForUninstalledModules(['content_translation']);

    $migration = $this->createMock(MigrationInterface::class);
    $this->assertInstanceOf(SourcePluginBase::class, \Drupal::service('plugin.manager.migrate.source')->createInstance($plugin_id, [], $migration));
  }

  /**
   * Removes PSR-4 namespaces from class loader for uninstalled modules.
   *
   * TestRunnerKernel registers namespaces for all modules, including
   * uninstalled modules. This method removes the PSR-4 namespace for the list
   * of modules passed in after confirming they are all uninstalled.
   *
   * @param string[] $remove_psr4_modules
   *   List of machine names of modules that are uninstalled and whose PSR-4
   *   namespaces should be removed from the class loader.
   */
  protected function disablePsr4ForUninstalledModules(array $remove_psr4_modules): void {
    /** @var \Drupal\Core\Extension\ModuleExtensionList $module_list */
    $module_list = \Drupal::service('extension.list.module');
    $available_modules = $module_list->getAllAvailableInfo();
    $installed_modules = $module_list->getAllInstalledInfo();
    $prefixes = $this->classLoader->getPrefixesPsr4();
    foreach ($remove_psr4_modules as $module) {
      $this->assertArrayHasKey($module, $available_modules);
      $this->assertArrayNotHasKey($module, $installed_modules);
      if (isset($prefixes["Drupal\\$module\\"])) {
        // Cannot actually remove the PSR4 prefix from the class loader, so set
        // the path to a wrong location.
        $this->classLoader->setPsr4("Drupal\\$module\\", '');
      }
    }
  }

  /**
   * Provides data for testMigrateSourcePluginUsingI18nQueryTraitDiscovery().
   */
  public static function providerI18nQueryTraitPlugins(): array {
    return [
      ['d6_box_translation'],
      ['d7_block_custom_translation'],
      ['d6_menu_link_translation'],
      ['d7_menu_link_translation'],
      ['d7_term_localized_translation'],
    ];
  }

}
