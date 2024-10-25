<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\Extension\Extension;
use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\ValidationResult;
use Drupal\Tests\package_manager\Traits\ComposerInstallersTrait;

/**
 * @covers \Drupal\package_manager\Validator\EnabledExtensionsValidator
 * @group package_manager
 * @internal
 */
class EnabledExtensionsValidatorTest extends PackageManagerKernelTestBase {

  use ComposerInstallersTrait;

  /**
   * Data provider for testExtensionRemoved().
   *
   * @return mixed[][]
   *   The test cases.
   */
  public static function providerExtensionRemoved(): array {
    $summary = t('The update cannot proceed because the following enabled Drupal extension was removed during the update.');
    return [
      'module' => [
        [
          [
            'name' => 'drupal/test_module2',
            'version' => '1.3.1',
            'type' => 'drupal-module',
          ],
        ],
        [
          ValidationResult::createError([t("'test_module2' module (provided by <code>drupal/test_module2</code>)")], $summary),
        ],
      ],
      'module and theme' => [
        [
          [
            'name' => 'drupal/test_module1',
            'version' => '1.3.1',
            'type' => 'drupal-module',
          ],
          [
            'name' => 'drupal/test_theme',
            'version' => '1.3.1',
            'type' => 'drupal-theme',
          ],
        ],
        [
          ValidationResult::createError([
            t("'test_module1' module (provided by <code>drupal/test_module1</code>)"),
            t("'test_theme' theme (provided by <code>drupal/test_theme</code>)"),
          ], t('The update cannot proceed because the following enabled Drupal extensions were removed during the update.')),
        ],
      ],
      'profile' => [
        [
          [
            'name' => 'drupal/test_profile',
            'version' => '1.3.1',
            'type' => 'drupal-profile',
          ],
        ],
        [
          ValidationResult::createError([t("'test_profile' profile (provided by <code>drupal/test_profile</code>)")], $summary),
        ],
      ],
      'theme' => [
        [
          [
            'name' => 'drupal/test_theme',
            'version' => '1.3.1',
            'type' => 'drupal-theme',
          ],
        ],
        [
          ValidationResult::createError([t("'test_theme' theme (provided by <code>drupal/test_theme</code>)")], $summary),
        ],
      ],
    ];
  }

  /**
   * Tests that error is raised if Drupal modules, profiles or themes are removed.
   *
   * @param array $packages
   *   Packages that will be added to the active directory, and removed from the
   *   stage directory.
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   *
   * @dataProvider providerExtensionRemoved
   */
  public function testExtensionRemoved(array $packages, array $expected_results): void {
    $project_root = $this->container->get(PathLocator::class)->getProjectRoot();
    $this->installComposerInstallers($project_root);

    $active_manipulator = new ActiveFixtureManipulator();
    $stage_manipulator = $this->getStageFixtureManipulator();
    foreach ($packages as $package) {
      $active_manipulator->addPackage($package, FALSE, TRUE);
      $stage_manipulator->removePackage($package['name']);
    }
    $active_manipulator->commitChanges();

    foreach ($packages as $package) {
      $extension_name = str_replace('drupal/', '', $package['name']);
      $extension = self::createExtension($project_root, $package['type'], $extension_name);

      if ($extension->getType() === 'theme') {
        /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
        $theme_handler = $this->container->get('theme_handler');
        $theme_handler->addTheme($extension);
        $this->assertArrayHasKey($extension_name, $theme_handler->listInfo());
      }
      else {
        /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
        $module_handler = $this->container->get('module_handler');
        $module_list = $module_handler->getModuleList();
        $module_list[$extension_name] = $extension;
        $module_handler->setModuleList($module_list);
        $this->assertArrayHasKey($extension_name, $module_handler->getModuleList());
      }
    }
    $this->assertResults($expected_results, PreApplyEvent::class);
  }

  /**
   * Returns a mocked extension object for a package.
   *
   * @param string $project_root
   *   The project root directory.
   * @param string $package_type
   *   The package type (e.g., `drupal-module` or `drupal-theme`).
   * @param string $extension_name
   *   The name of the extension.
   *
   * @return \Drupal\Core\Extension\Extension
   *   An extension object.
   */
  private static function createExtension(string $project_root, string $package_type, string $extension_name): Extension {
    $type = match ($package_type) {
      'drupal-theme' => 'theme',
      'drupal-profile' => 'profile',
      default => 'module',
    };
    $subdirectory = match ($type) {
      'theme' => 'themes',
      'profile' => 'profiles',
      'module' => 'modules',
    };
    return new Extension($project_root, $type, "$subdirectory/contrib/$extension_name/$extension_name.info.yml");
  }

}
