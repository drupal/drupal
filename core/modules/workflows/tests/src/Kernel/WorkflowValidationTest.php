<?php

declare(strict_types=1);

namespace Drupal\Tests\workflows\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests validation of workflow entities.
 *
 * @group workflows
 */
class WorkflowValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['workflows', 'workflow_type_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = Workflow::create([
      'id' => 'test',
      'label' => 'Test',
      'type' => 'workflow_type_test',
    ]);
    $this->entity->save();
  }

  /**
   * Tests that the workflow type plugin is validated.
   */
  public function testTypePluginIsValidated(): void {
    $this->entity->set('type', 'non_existent');
    $this->assertValidationErrors([
      'type' => "The 'non_existent' plugin does not exist.",
    ]);
  }

}
