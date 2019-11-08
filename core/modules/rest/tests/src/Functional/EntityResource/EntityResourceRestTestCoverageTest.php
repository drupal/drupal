<?php

namespace Drupal\Tests\rest\Functional\EntityResource;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Checks that all core content/config entity types have REST test coverage.
 *
 * Every entity type must have test coverage for:
 * - every format in core (json + xml + hal_json)
 * - every authentication provider in core (anon, cookie, basic_auth)
 *
 * @group rest
 */
class EntityResourceRestTestCoverageTest extends BrowserTestBase {

  /**
   * Entity definitions array.
   *
   * @var array
   */
  protected $definitions;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $all_modules = $this->container->get('extension.list.module')->getList();
    $stable_core_modules = array_filter($all_modules, function ($module) {
      // Filter out contrib, hidden, testing, and experimental modules. We also
      // don't need to enable modules that are already enabled.
      return $module->origin === 'core' &&
        empty($module->info['hidden']) &&
        $module->status == FALSE &&
        $module->info['package'] !== 'Testing' &&
        $module->info['package'] !== 'Core (Experimental)';
    });

    $this->container->get('module_installer')->install(array_keys($stable_core_modules));
    $this->rebuildContainer();

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
  public function testEntityTypeRestTestCoverage() {
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
      // Test coverage for formats provided by the 'hal' module.
      'hal' => [
        'path' => '\Drupal\Tests\PROVIDER\Functional\Hal\CLASS',
        'class suffix' => [
          'HalJsonAnonTest',
          'HalJsonBasicAuthTest',
          'HalJsonCookieTest',
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
          if (class_exists($class) || class_exists($class_alternative)) {
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
    }
    $all = count($this->definitions);
    $good = $all - count($problems);
    $this->assertSame([], $problems, $this->getLlamaMessage($good, $all));
  }

  /**
   * Message from Llama.
   *
   * @param int $g
   *   A count of entities with test coverage.
   * @param int $a
   *   A count of all entities.
   *
   * @return string
   *   An information about progress of REST test coverage.
   */
  protected function getLlamaMessage($g, $a) {
    return "
☼
      ________________________
     /           Hi!          \\
    |  It's llame to not have  |
    |   complete REST tests!   |
    |                          |
    |     Progress: $g/$a.     |
    | ________________________/
    |/
//  o
l'>
ll
llama
|| ||
'' ''
";
  }

}
