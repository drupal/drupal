<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Kernel\EntityResource;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;

/**
 * Checks that all core content/config entity types have REST test coverage.
 *
 * Every entity type must have test coverage for:
 * - every format in core (json + xml)
 * - every authentication provider in core (anon, cookie, basic_auth)
 *
 * Additionally, every entity type must have the correct parent test class.
 *
 * @group rest
 */
class EntityResourceRestTestCoverageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * Entity definitions array.
   *
   * @var array
   */
  protected $definitions;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $all_modules = $this->container->get('extension.list.module')->getList();
    $stable_core_modules = array_filter($all_modules, function ($module) {
      // Filter out contrib, hidden, testing, deprecated and experimental
      // modules. We also don't need to enable modules that are already enabled.
      return $module->origin === 'core' &&
        empty($module->info['hidden']) &&
        $module->status == FALSE &&
        $module->info['package'] !== 'Testing' &&
        $module->info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] !== ExtensionLifecycle::DEPRECATED &&
        $module->info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] !== ExtensionLifecycle::EXPERIMENTAL;
    });

    $this->container->get('module_installer')->install(array_keys($stable_core_modules));

    $this->definitions = $this->container->get('entity_type.manager')->getDefinitions();

    // Entity types marked as "internal" are not exposed by the entity REST
    // resource plugin and hence also don't need test coverage.
    $this->definitions = array_filter($this->definitions, function (EntityTypeInterface $entity_type) {
      return !$entity_type->isInternal();
    });
  }

  /**
   * Tests that all core content/config entity types have REST test coverage.
   */
  public function testEntityTypeRestTestCoverage(): void {
    $tests = [
      // Test coverage for formats provided by the 'serialization' module.
      'serialization' => [
        'path' => '\Drupal\Tests\PROVIDER\Functional\Rest\CLASS',
        'class suffix' => [
          'JsonAnonTest',
          'JsonBasicAuthTest',
          'JsonCookieTest',
          'XmlAnonTest',
          'XmlBasicAuthTest',
          'XmlCookieTest',
        ],
      ],
    ];

    $problems = [];
    foreach ($this->definitions as $entity_type_id => $info) {
      $class_name_full = $info->getClass();
      $parts = explode('\\', $class_name_full);
      $class_name = end($parts);
      $module_name = $parts[1];

      foreach ($tests as $module => $info) {
        $path = $info['path'];
        $missing_tests = [];
        foreach ($info['class suffix'] as $postfix) {
          $class = str_replace(['PROVIDER', 'CLASS'], [$module_name, $class_name], $path . $postfix);
          $class_alternative = str_replace("\\Drupal\\Tests\\$module_name\\Functional", '\Drupal\FunctionalTests', $class);
          // For entities defined in the system module with Jsonapi tests in
          // another module.
          $class_entity_in_system_alternative = str_replace(['PROVIDER', 'CLASS'], [$entity_type_id, $class_name], $path . $postfix);
          if (class_exists($class) || class_exists($class_alternative) || class_exists($class_entity_in_system_alternative)) {
            continue;
          }
          $missing_tests[] = $postfix;
        }
        if (!empty($missing_tests)) {
          $missing_tests_list = implode(', ', array_map(function ($missing_test) use ($class_name) {
            return $class_name . $missing_test;
          }, $missing_tests));
          $which_normalization = $module === 'serialization' ? 'default' : $module;
          $problems[] = "$entity_type_id: $class_name ($class_name_full), $which_normalization normalization (expected tests: $missing_tests_list)";
        }
      }

      $config_entity = is_subclass_of($class_name_full, ConfigEntityInterface::class);
      $config_test = is_subclass_of($class, ConfigEntityResourceTestBase::class)
        || is_subclass_of($class_alternative, ConfigEntityResourceTestBase::class)
        || is_subclass_of($class_entity_in_system_alternative, ConfigEntityResourceTestBase::class);
      if ($config_entity && !$config_test) {
        $problems[] = "$entity_type_id: $class_name is a config entity, but the test is for content entities.";
      }
      elseif (!$config_entity && $config_test) {
        $problems[] = "$entity_type_id: $class_name is a content entity, but the test is for config entities.";
      }
    }
    $this->assertSame([], $problems);
  }

}
