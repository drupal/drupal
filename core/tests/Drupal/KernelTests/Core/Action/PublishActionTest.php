<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Action;

use Drupal\Core\Action\Plugin\Action\Derivative\EntityPublishedActionDeriver;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Entity\Action;

/**
 * @group Action
 */
class PublishActionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'entity_test', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_mulrevpub');
  }

  /**
   * @covers \Drupal\Core\Action\Plugin\Action\Derivative\EntityPublishedActionDeriver::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitions(): void {
    $deriver = new EntityPublishedActionDeriver(\Drupal::entityTypeManager(), \Drupal::translation());
    $definitions = $deriver->getDerivativeDefinitions([
      'action_label' => 'Save',
    ]);
    $this->assertEquals([
      'type' => 'entity_test_mulrevpub',
      'label' => 'Save test entity - revisions, data table, and published interface',
      'action_label' => 'Save',
    ], $definitions['entity_test_mulrevpub']);
  }

  /**
   * @covers \Drupal\Core\Action\Plugin\Action\PublishAction::execute
   */
  public function testPublishAction(): void {
    $entity = EntityTestMulRevPub::create(['name' => 'test']);
    $entity->setUnpublished()->save();

    $action = Action::create([
      'id' => 'entity_publish_action',
      'plugin' => 'entity:publish_action:entity_test_mulrevpub',
    ]);
    $action->save();
    $this->assertFalse($entity->isPublished());
    $action->execute([$entity]);
    $this->assertTrue($entity->isPublished());
    $this->assertSame(['module' => ['entity_test']], $action->getDependencies());
  }

  /**
   * @covers \Drupal\Core\Action\Plugin\Action\UnpublishAction::execute
   */
  public function testUnpublishAction(): void {
    $entity = EntityTestMulRevPub::create(['name' => 'test']);
    $entity->setPublished()->save();

    $action = Action::create([
      'id' => 'entity_unpublish_action',
      'plugin' => 'entity:unpublish_action:entity_test_mulrevpub',
    ]);
    $action->save();
    $this->assertTrue($entity->isPublished());
    $action->execute([$entity]);
    $this->assertFalse($entity->isPublished());
    $this->assertSame(['module' => ['entity_test']], $action->getDependencies());
  }

}
