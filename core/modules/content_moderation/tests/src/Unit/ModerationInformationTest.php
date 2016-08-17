<?php

namespace Drupal\Tests\content_moderation\Unit;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\content_moderation\ModerationInformation;

/**
 * @coversDefaultClass \Drupal\content_moderation\ModerationInformation
 * @group content_moderation
 */
class ModerationInformationTest extends \PHPUnit_Framework_TestCase {

  /**
   * Builds a mock user.
   *
   * @return AccountInterface
   *   The mocked user.
   */
  protected function getUser() {
    return $this->prophesize(AccountInterface::class)->reveal();
  }

  /**
   * Returns a mock Entity Type Manager.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_bundle_storage
   *   Entity bundle storage.
   *
   * @return EntityTypeManagerInterface
   *   The mocked entity type manager.
   */
  protected function getEntityTypeManager(EntityStorageInterface $entity_bundle_storage) {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('entity_test_bundle')->willReturn($entity_bundle_storage);
    return $entity_type_manager->reveal();
  }

  /**
   * Sets up content moderation and entity manager mocking.
   *
   * @param bool $status
   *   TRUE if content_moderation should be enabled, FALSE if not.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The mocked entity type manager.
   */
  public function setupModerationEntityManager($status) {
    $bundle = $this->prophesize(ConfigEntityInterface::class);
    $bundle->getThirdPartySetting('content_moderation', 'enabled', FALSE)->willReturn($status);

    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage->load('test_bundle')->willReturn($bundle->reveal());

    return $this->getEntityTypeManager($entity_storage->reveal());
  }

  /**
   * @dataProvider providerBoolean
   * @covers ::isModeratedEntity
   */
  public function testIsModeratedEntity($status) {
    $moderation_information = new ModerationInformation($this->setupModerationEntityManager($status), $this->getUser());

    $entity_type = new ContentEntityType([
      'id' => 'test_entity_type',
      'bundle_entity_type' => 'entity_test_bundle',
    ]);
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->getEntityType()->willReturn($entity_type);
    $entity->bundle()->willReturn('test_bundle');

    $this->assertEquals($status, $moderation_information->isModeratedEntity($entity->reveal()));
  }

  /**
   * @covers ::isModeratedEntity
   */
  public function testIsModeratedEntityForNonBundleEntityType() {
    $entity_type = new ContentEntityType([
      'id' => 'test_entity_type',
    ]);
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->getEntityType()->willReturn($entity_type);
    $entity->bundle()->willReturn('test_entity_type');

    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_type_manager = $this->getEntityTypeManager($entity_storage->reveal());
    $moderation_information = new ModerationInformation($entity_type_manager, $this->getUser());

    $this->assertEquals(FALSE, $moderation_information->isModeratedEntity($entity->reveal()));
  }

  /**
   * @dataProvider providerBoolean
   * @covers ::shouldModerateEntitiesOfBundle
   */
  public function testShouldModerateEntities($status) {
    $entity_type = new ContentEntityType([
      'id' => 'test_entity_type',
      'bundle_entity_type' => 'entity_test_bundle',
    ]);

    $moderation_information = new ModerationInformation($this->setupModerationEntityManager($status), $this->getUser());

    $this->assertEquals($status, $moderation_information->shouldModerateEntitiesOfBundle($entity_type, 'test_bundle'));
  }

  /**
   * Data provider for several tests.
   */
  public function providerBoolean() {
    return [
      [FALSE],
      [TRUE],
    ];
  }

}
