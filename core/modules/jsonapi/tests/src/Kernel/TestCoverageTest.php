<?php

namespace Drupal\Tests\jsonapi\Kernel;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\jsonapi\Functional\ConfigEntityResourceTestBase;

/**
 * Checks that all core content/config entity types have JSON:API test coverage.
 *
 * @group jsonapi
 * @group #slow
 */
class TestCoverageTest extends KernelTestBase {

  /**
   * Entity definitions array.
   *
   * @var array
   */
  protected $definitions;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $all_modules = \Drupal::service('extension.list.module')->getList();
    $stable_core_modules = array_filter($all_modules, function ($module) {
      // Filter out contrib, hidden, testing, experimental, and deprecated
      // modules. We also don't need to enable modules that are already enabled.
      return $module->origin === 'core'
        && empty($module->info['hidden'])
        && $module->status == FALSE
        && $module->info['package'] !== 'Testing'
        && $module->info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] !== ExtensionLifecycle::EXPERIMENTAL
        && $module->info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] !== ExtensionLifecycle::DEPRECATED;
    });

    $this->container->get('module_installer')->install(array_keys($stable_core_modules));

    $this->definitions = $this->container->get('entity_type.manager')->getDefinitions();

    // Entity types marked as "internal" are not exposed by JSON:API and hence
    // also don't need test coverage.
    $this->definitions = array_filter($this->definitions, function (EntityTypeInterface $entity_type) {
      return !$entity_type->isInternal();
    });
  }

  /**
   * Tests that all core entity types have JSON:API test coverage.
   */
  public function testEntityTypeRestTestCoverage() {
    $problems = [];
    foreach ($this->definitions as $entity_type_id => $info) {
      $class_name_full = $info->getClass();
      $parts = explode('\\', $class_name_full);
      $class_name = end($parts);
      $module_name = $parts[1];

      $possible_paths = [
        'Drupal\Tests\jsonapi\Functional\CLASSTest',
        '\Drupal\Tests\\' . $module_name . '\Functional\Jsonapi\CLASSTest',
      ];
      foreach ($possible_paths as $path) {
        $missing_tests = [];
        $class = str_replace('CLASS', $class_name, $path);
        if (class_exists($class)) {
          break;
        }
        $missing_tests[] = $class;
      }
      if (!empty($missing_tests)) {
        $missing_tests_list = implode(', ', $missing_tests);
        $problems[] = "$entity_type_id: $class_name ($class_name_full) (expected tests: $missing_tests_list)";
      }
      else {
        $config_entity = is_subclass_of($class_name_full, ConfigEntityInterface::class);
        $config_test = is_subclass_of($class, ConfigEntityResourceTestBase::class);
        if ($config_entity && !$config_test) {
          $problems[] = "$entity_type_id: $class_name is a config entity, but the test is for content entities.";
        }
        elseif (!$config_entity && $config_test) {
          $problems[] = "$entity_type_id: $class_name is a content entity, but the test is for config entities.";
        }
      }
    }

    $this->assertSame([], $problems);
  }

}
