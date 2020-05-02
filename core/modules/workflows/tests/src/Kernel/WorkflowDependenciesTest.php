<?php

namespace Drupal\Tests\workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests configuration dependencies in workflows.
 *
 * @coversDefaultClass \Drupal\workflows\Entity\Workflow
 *
 * @group workflows
 */
class WorkflowDependenciesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'workflows',
    'workflow_type_test',
    'workflow_third_party_settings_test',
  ];

  /**
   * Tests \Drupal\workflows\Entity\Workflow::onDependencyRemoval().
   */
  public function testOnDependencyRemoval() {
    // Create a workflow that has a dependency on a third party setting.
    $workflow = Workflow::create(['id' => 'test3', 'type' => 'workflow_type_complex_test']);
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
