<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Module;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Form\ModulesListForm;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the modules list form.
 */
#[Group('Module')]
#[RunTestsInSeparateProcesses]
class ModulesListFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system_test',
    'user',
  ];

  /**
   * Test module checkboxes for various version dependencies.
   */
  public function testModuleVersionDependencies(): void {
    $dependencies = [
      // Alternating between being compatible and incompatible with
      // 8.x-2.4-beta3.
      // The first is always a compatible.
      'common_test',
      // Branch incompatibility.
      'common_test (1.x)',
      // Branch compatibility.
      'common_test (2.x)',
      // Another branch incompatibility.
      'common_test (>2.x)',
      // Another branch compatibility.
      'common_test (<=2.x)',
      // Another branch incompatibility.
      'common_test (<2.x)',
      // Another branch compatibility.
      'common_test (>=2.x)',
      // Nonsense, misses a dash. Incompatible with everything.
      'common_test (=8.x2.x, >=2.4)',
      // Core version is optional. Compatible.
      'common_test (=8.x-2.x, >=2.4-alpha2)',
      // Test !=, explicitly incompatible.
      'common_test (=2.x, !=2.4-beta3)',
      // Three operations. Compatible.
      'common_test (=2.x, !=2.3, <2.5)',
      // Testing extra version. Incompatible.
      'common_test (<=2.4-beta2)',
      // Testing extra version. Compatible.
      'common_test (>2.4-beta2)',
      // Testing extra version. Incompatible.
      'common_test (>2.4-rc0)',
    ];
    foreach ($dependencies as $i => $dependency) {
      \Drupal::state()->set('system_test.dependency', $dependency);
      $form_object = ModulesListForm::create($this->container);
      $form = $form_object->buildForm([], new FormState());
      $disabled = $form['modules']['Testing']['module_test']['enable']['#disabled'];
      if ($i % 2 === 0) {
        $this->assertFalse($disabled);
      }
      else {
        $this->assertTrue($disabled);
      }
    }
  }

}
