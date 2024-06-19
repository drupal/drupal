<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Unit;

use Drupal\content_moderation\Entity\Handler\ModerationHandler;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\content_moderation\ModerationInformation;
use Drupal\Tests\UnitTestCase;
use Drupal\workflows\WorkflowInterface;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\content_moderation\ModerationInformation
 * @group content_moderation
 */
class ModerationInformationTest extends UnitTestCase {

  /**
   * Builds a mock user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The mocked user.
   */
  protected function getUser() {
    return $this->prophesize(AccountInterface::class)->reveal();
  }

  /**
   * Returns a mock Entity Type Manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The mocked entity type manager.
   */
  protected function getEntityTypeManager() {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getHandler(Argument::any(), 'moderation')->willReturn(new ModerationHandler());
    return $entity_type_manager->reveal();
  }

  /**
   * Sets up content moderation and entity type bundle info mocking.
   *
   * @param string $bundle
   *   The bundle ID.
   * @param string|null $workflow
   *   The workflow ID. If nul no workflow information is added to the bundle.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The mocked entity type manager.
   */
  public function setupModerationBundleInfo($bundle, $workflow = NULL) {
    $bundle_info_array = [];
    if ($workflow) {
      $bundle_info_array['workflow'] = $workflow;
    }
    $bundle_info = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $bundle_info->getBundleInfo("test_entity_type")->willReturn([$bundle => $bundle_info_array]);
    $bundle_info->getBundleInfo("unmoderated_test_type")->willReturn([$bundle => []]);

    return $bundle_info->reveal();
  }

  /**
   * @covers ::isModeratedEntityType
   */
  public function testIsModeratedEntityType(): void {
    $moderation_information = new ModerationInformation($this->getEntityTypeManager(), $this->setupModerationBundleInfo('test_bundle', 'workflow'));

    $moderated_entity_type = $this->prophesize(EntityTypeInterface::class);
    $moderated_entity_type->id()->willReturn('test_entity_type');

    $unmoderated_entity_type = $this->prophesize(EntityTypeInterface::class);
    $unmoderated_entity_type->id()->willReturn('unmoderated_test_type');

    $this->assertTrue($moderation_information->isModeratedEntityType($moderated_entity_type->reveal()));
    $this->assertFalse($moderation_information->isModeratedEntityType($unmoderated_entity_type->reveal()));
  }

  /**
   * @dataProvider providerWorkflow
   * @covers ::isModeratedEntity
   */
  public function testIsModeratedEntity($workflow, $expected): void {
    $moderation_information = new ModerationInformation($this->getEntityTypeManager(), $this->setupModerationBundleInfo('test_bundle', $workflow));

    $entity_type = new ContentEntityType([
      'id' => 'test_entity_type',
      'bundle_entity_type' => 'entity_test_bundle',
      'handlers' => ['moderation' => ModerationHandler::class],
    ]);
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->getEntityType()->willReturn($entity_type);
    $entity->getEntityTypeId()->willReturn($entity_type->id());
    $entity->bundle()->willReturn('test_bundle');

    $this->assertEquals($expected, $moderation_information->isModeratedEntity($entity->reveal()));
  }

  /**
   * @dataProvider providerWorkflow
   * @covers ::getWorkflowForEntity
   */
  public function testGetWorkflowForEntity($workflow): void {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    if ($workflow) {
      $workflow_entity = $this->prophesize(WorkflowInterface::class)->reveal();
      $workflow_storage = $this->prophesize(EntityStorageInterface::class);
      $workflow_storage->load('workflow')->willReturn($workflow_entity)->shouldBeCalled();
      $entity_type_manager->getStorage('workflow')->willReturn($workflow_storage->reveal());
    }
    else {
      $workflow_entity = NULL;
    }
    $moderation_information = new ModerationInformation($entity_type_manager->reveal(), $this->setupModerationBundleInfo('test_bundle', $workflow));
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->getEntityTypeId()->willReturn('test_entity_type');
    $entity->bundle()->willReturn('test_bundle');

    $this->assertEquals($workflow_entity, $moderation_information->getWorkflowForEntity($entity->reveal()));
  }

  /**
   * @dataProvider providerWorkflow
   * @covers ::shouldModerateEntitiesOfBundle
   */
  public function testShouldModerateEntities($workflow, $expected): void {
    $entity_type = new ContentEntityType([
      'id' => 'test_entity_type',
      'bundle_entity_type' => 'entity_test_bundle',
      'handlers' => ['moderation' => ModerationHandler::class],
    ]);

    $moderation_information = new ModerationInformation($this->getEntityTypeManager(), $this->setupModerationBundleInfo('test_bundle', $workflow));

    $this->assertEquals($expected, $moderation_information->shouldModerateEntitiesOfBundle($entity_type, 'test_bundle'));
  }

  /**
   * Data provider for several tests.
   */
  public static function providerWorkflow() {
    return [
      [NULL, FALSE],
      ['workflow', TRUE],
    ];
  }

}
