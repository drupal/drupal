<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the moderation state field.
 */
#[Group('content_moderation')]
#[RunTestsInSeparateProcesses]
class ModerationStateFieldTest extends KernelTestBase {

  use ContentModerationTestTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workflows',
    'content_moderation',
    'node',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('workflow');
    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
  }

  /**
   * Tests that the moderation state field target bundle is always correct.
   */
  public function testFieldDefinitionBundles(): void {
    $workflow = $this->createEditorialWorkflow();

    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $workflow_type */
    $workflow_type = $workflow->getTypePlugin();

    $node_types = [];

    for ($i = 0; $i < 3; ++$i) {
      $node_type = $this->createContentType(create_body: FALSE);

      $workflow_type->addEntityTypeAndBundle('node', $node_type->id());

      $node_types[] = $node_type;
    }

    $workflow->save();

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');

    $this->assertNotEmpty($node_types);

    foreach ($node_types as $node_type) {
      $field_definitions = $entity_field_manager->getFieldDefinitions('node', $node_type->id());

      $this->assertSame($node_type->id(), $field_definitions['moderation_state']->getTargetBundle());
    }
  }

}
