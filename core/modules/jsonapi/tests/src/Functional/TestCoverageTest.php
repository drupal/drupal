<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Checks that all core content/config entity types have JSON:API test coverage.
 *
 * @group jsonapi
 */
class TestCoverageTest extends BrowserTestBase {

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
  protected function setUp(): void {
    parent::setUp();

    $all_modules = \Drupal::service('extension.list.module')->getList();
    $stable_core_modules = array_filter($all_modules, function ($module) {
      // Filter out contrib, hidden, testing, and experimental modules. We also
      // don't need to enable modules that are already enabled.
      return $module->origin === 'core'
        && empty($module->info['hidden'])
        && $module->status == FALSE
        && $module->info['package'] !== 'Testing'
        && $module->info['package'] !== 'Core (Experimental)';
    });

    $this->container->get('module_installer')->install(array_keys($stable_core_modules));
    $this->rebuildContainer();

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
          continue 2;
        }
        $missing_tests[] = $class;
      }
      if (!empty($missing_tests)) {
        $missing_tests_list = implode(', ', $missing_tests);
        $problems[] = "$entity_type_id: $class_name ($class_name_full) (expected tests: $missing_tests_list)";
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
      _________________________
     /           Hi!           \\
    |  It's llame to not have   |
    |  complete JSON:API tests! |
    |                           |
    |     Progress: $g/$a.      |
    | _________________________/
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
