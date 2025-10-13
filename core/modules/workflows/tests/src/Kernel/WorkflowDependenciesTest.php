<?php

declare(strict_types=1);

namespace Drupal\Tests\workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests configuration dependencies in workflows.
 */
#[CoversClass(Workflow::class)]
#[Group('workflows')]
#[RunTestsInSeparateProcesses]
class WorkflowDependenciesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workflows',
    'workflow_type_test',
    'workflow_third_party_settings_test',
  ];

  /**
   * Tests \Drupal\workflows\Entity\Workflow::onDependencyRemoval().
   */
  public function testOnDependencyRemoval(): void {
    // Create a workflow that has a dependency on a third party setting.
    $workflow = Workflow::create([
      'id' => 'test3',
      'label' => 'Test workflow',
      'type' => 'workflow_type_complex_test',
    ]);
    $workflow->setThirdPartySetting('workflow_third_party_settings_test', 'key', 'value');
    $workflow->save();
    $this->assertSame(['workflow_third_party_settings_test', 'workflow_type_test'], $workflow->getDependencies()['module']);

    // Uninstall workflow_third_party_settings_test to ensure
    // \Drupal\workflows\Entity\Workflow::onDependencyRemoval() works as
    // expected.
    \Drupal::service('module_installer')->uninstall(['node', 'workflow_third_party_settings_test']);
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = \Drupal::entityTypeManager()->getStorage('workflow')->loadUnchanged($workflow->id());
    $this->assertSame(['workflow_type_test'], $workflow->getDependencies()['module']);
  }

}
