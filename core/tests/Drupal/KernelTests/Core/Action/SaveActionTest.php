<?php

namespace Drupal\KernelTests\Core\Action;

use Drupal\Core\Action\Plugin\Action\Derivative\EntityChangedActionDeriver;
use Drupal\entity_test\Entity\EntityTestMulChanged;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Entity\Action;

/**
 * @group Action
 */
class SaveActionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'entity_test', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test_mul_changed');
  }

  /**
   * @covers \Drupal\Core\Action\Plugin\Action\Derivative\EntityChangedActionDeriver::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitions() {
    $deriver = new EntityChangedActionDeriver(\Drupal::entityTypeManager());
    $this->assertArraySubset([
      'entity_test_mul_changed' => [
        'type' => 'entity_test_mul_changed',
        'label' => 'Save test entity - data table',
        'action_label' => 'Save',
      ],
    ], $deriver->getDerivativeDefinitions([
      'action_label' => 'Save',
    ]));
  }

  /**
   * @covers \Drupal\Core\Action\Plugin\Action\SaveAction::execute
   */
  public function testSaveAction() {
    $entity = EntityTestMulChanged::create(['name' => 'test']);
    $entity->save();
    $saved_time = $entity->getChangedTime();

    $action = Action::create([
      'id' => 'entity_save_action',
      'plugin' => 'entity:save_action:entity_test_mul_changed',
    ]);
    $action->save();
    $action->execute([$entity]);
    $this->assertNotSame($saved_time, $entity->getChangedTime());
    $this->assertArraySubset(['module' => ['entity_test']], $action->getDependencies());
  }

}
