<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\content_moderation\Entity\ModerationState;
use Drupal\content_moderation\Entity\ModerationStateTransition;

/**
 * Ensures that content moderation schema is correct.
 *
 * @group content_moderation
 */
class ContentModerationSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_moderation',
    'node',
    'user',
    'block_content',
    'system',
  ];

  /**
   * Tests content moderation default schema.
   */
  public function testContentModerationDefaultConfig() {
    $this->installConfig(['content_moderation']);
    $typed_config = \Drupal::service('config.typed');
    $moderation_states = ModerationState::loadMultiple();
    foreach ($moderation_states as $moderation_state) {
      $this->assertConfigSchema($typed_config, $moderation_state->getEntityType()->getConfigPrefix() . '.' . $moderation_state->id(), $moderation_state->toArray());
    }
    $moderation_state_transitions = ModerationStateTransition::loadMultiple();
    foreach ($moderation_state_transitions as $moderation_state_transition) {
      $this->assertConfigSchema($typed_config, $moderation_state_transition->getEntityType()->getConfigPrefix() . '.' . $moderation_state_transition->id(), $moderation_state_transition->toArray());
    }

  }

  /**
   * Tests content moderation third party schema for node types.
   */
  public function testContentModerationNodeTypeConfig() {
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['content_moderation']);
    $typed_config = \Drupal::service('config.typed');
    $moderation_states = ModerationState::loadMultiple();
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->setThirdPartySetting('content_moderation', 'enabled', TRUE);
    $node_type->setThirdPartySetting('content_moderation', 'allowed_moderation_states', array_keys($moderation_states));
    $node_type->setThirdPartySetting('content_moderation', 'default_moderation_state', '');
    $node_type->save();
    $this->assertConfigSchema($typed_config, $node_type->getEntityType()->getConfigPrefix() . '.' . $node_type->id(), $node_type->toArray());
  }

  /**
   * Tests content moderation third party schema for block content types.
   */
  public function testContentModerationBlockContentTypeConfig() {
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('user');
    $this->installConfig(['content_moderation']);
    $typed_config = \Drupal::service('config.typed');
    $moderation_states = ModerationState::loadMultiple();
    $block_content_type = BlockContentType::create([
      'id' => 'basic',
      'label' => 'basic',
      'revision' => TRUE,
    ]);
    $block_content_type->setThirdPartySetting('content_moderation', 'enabled', TRUE);
    $block_content_type->setThirdPartySetting('content_moderation', 'allowed_moderation_states', array_keys($moderation_states));
    $block_content_type->setThirdPartySetting('content_moderation', 'default_moderation_state', '');
    $block_content_type->save();
    $this->assertConfigSchema($typed_config, $block_content_type->getEntityType()->getConfigPrefix() . '.' . $block_content_type->id(), $block_content_type->toArray());
  }

}
