<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\content_moderation\Entity\Handler\ModerationHandler;
use Drupal\content_moderation\EntityTypeInfo;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\content_moderation\EntityTypeInfo
 *
 * @group content_moderation
 */
class EntityTypeInfoTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'content_moderation',
    'entity_test',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type info class.
   *
   * @var \Drupal\content_moderation\EntityTypeInfo
   */
  protected $entityTypeInfo;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->entityTypeInfo = $this->container->get('class_resolver')->getInstanceFromDefinition(EntityTypeInfo::class);
    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * @covers ::entityBaseFieldInfo
   */
  public function testEntityBaseFieldInfo() {
    $definition = $this->entityTypeManager->getDefinition('entity_test');
    $definition->setHandlerClass('moderation', ModerationHandler::class);

    $base_fields = $this->entityTypeInfo->entityBaseFieldInfo($definition);

    $this->assertFalse($base_fields['moderation_state']->isReadOnly());
    $this->assertTrue($base_fields['moderation_state']->isComputed());
    $this->assertTrue($base_fields['moderation_state']->isTranslatable());
  }

}
