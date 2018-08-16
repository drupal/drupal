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
    'workflows',
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

  /**
   * Test the correct entity types have moderation added.
   *
   * @covers ::entityTypeAlter
   *
   * @dataProvider providerTestEntityTypeAlter
   */
  public function testEntityTypeAlter($entity_type_id, $moderatable) {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $this->assertSame($moderatable, $entity_types[$entity_type_id]->hasHandlerClass('moderation'));
  }

  /**
   * Provides test data for testEntityTypeAlter().
   *
   * @return array
   *   An array of test cases, where each test case is an array with the
   *   following values:
   *   - An entity type ID.
   *   - Whether the entity type is moderatable or not.
   */
  public function providerTestEntityTypeAlter() {
    $tests = [];
    $tests['non_internal_non_revisionable'] = ['entity_test', FALSE];
    $tests['non_internal_revisionable'] = ['entity_test_rev', TRUE];
    $tests['internal_non_revisionable'] = ['entity_test_no_label', FALSE];
    $tests['internal_revisionable'] = ['content_moderation_state', FALSE];
    return $tests;
  }

}
