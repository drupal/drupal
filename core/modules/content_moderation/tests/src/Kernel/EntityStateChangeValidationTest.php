<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * @coversDefaultClass \Drupal\content_moderation\Plugin\Validation\Constraint\ModerationStateConstraintValidator
 * @group content_moderation
 */
class EntityStateChangeValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'content_moderation',
    'user',
    'system',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');
  }

  /**
   * Test valid transitions.
   *
   * @covers ::validate
   */
  public function testValidTransition() {
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->setThirdPartySetting('content_moderation', 'enabled', TRUE);
    $node_type->save();
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->moderation_state->target_id = 'draft';
    $node->save();

    $node->moderation_state->target_id = 'published';
    $this->assertCount(0, $node->validate());
    $node->save();

    $this->assertEquals('published', $node->moderation_state->entity->id());
  }

  /**
   * Test invalid transitions.
   *
   * @covers ::validate
   */
  public function testInvalidTransition() {
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->setThirdPartySetting('content_moderation', 'enabled', TRUE);
    $node_type->save();
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->moderation_state->target_id = 'draft';
    $node->save();

    $node->moderation_state->target_id = 'archived';
    $violations = $node->validate();
    $this->assertCount(1, $violations);

    $this->assertEquals('Invalid state transition from <em class="placeholder">Draft</em> to <em class="placeholder">Archived</em>', $violations->get(0)->getMessage());
  }

  /**
   * Tests that content without prior moderation information can be moderated.
   */
  public function testLegacyContent() {
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->save();
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->save();

    $nid = $node->id();

    // Enable moderation for our node type.
    /** @var NodeType $node_type */
    $node_type = NodeType::load('example');
    $node_type->setThirdPartySetting('content_moderation', 'enabled', TRUE);
    $node_type->setThirdPartySetting('content_moderation', 'allowed_moderation_states', ['draft', 'published']);
    $node_type->setThirdPartySetting('content_moderation', 'default_moderation_state', 'draft');
    $node_type->save();

    $node = Node::load($nid);

    // Having no previous state should not break validation.
    $violations = $node->validate();

    $this->assertCount(0, $violations);

    // Having no previous state should not break saving the node.
    $node->setTitle('New');
    $node->save();
  }

  /**
   * Tests that content without prior moderation information can be translated.
   */
  public function testLegacyMultilingualContent() {
    // Enable French.
    ConfigurableLanguage::createFromLangcode('fr')->save();

    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->save();
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
      'langcode' => 'en',
    ]);
    $node->save();

    $nid = $node->id();

    $node = Node::load($nid);

    // Creating a translation shouldn't break, even though there's no previous
    // moderated revision for the new language.
    $node_fr = $node->addTranslation('fr');
    $node_fr->setTitle('Francais');
    $node_fr->save();

    // Enable moderation for our node type.
    /** @var NodeType $node_type */
    $node_type = NodeType::load('example');
    $node_type->setThirdPartySetting('content_moderation', 'enabled', TRUE);
    $node_type->setThirdPartySetting('content_moderation', 'allowed_moderation_states', ['draft', 'published']);
    $node_type->setThirdPartySetting('content_moderation', 'default_moderation_state', 'draft');
    $node_type->save();

    // Reload the French version of the node.
    $node = Node::load($nid);
    $node_fr = $node->getTranslation('fr');

    /** @var \Drupal\node\NodeInterface $node_fr */
    $node_fr->setTitle('Nouveau');
    $node_fr->save();
  }

}
