<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\workflows\Entity\Workflow;

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
    'workflows',
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
    $node_type->save();
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->moderation_state->value = 'draft';
    $node->save();

    $node->moderation_state->value = 'published';
    $this->assertCount(0, $node->validate());
    $node->save();

    $this->assertEquals('published', $node->moderation_state->value);
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
    $node_type->save();
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->moderation_state->value = 'draft';
    $node->save();

    $node->moderation_state->value = 'archived';
    $violations = $node->validate();
    $this->assertCount(1, $violations);

    $this->assertEquals('Invalid state transition from <em class="placeholder">Draft</em> to <em class="placeholder">Archived</em>', $violations->get(0)->getMessage());
  }

  /**
   * Test validation with an invalid state.
   */
  public function testInvalidState() {
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->save();
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->moderation_state->value = 'invalid_state';
    $violations = $node->validate();

    $this->assertCount(1, $violations);
    $this->assertEquals('State <em class="placeholder">invalid_state</em> does not exist on <em class="placeholder">Editorial</em> workflow', $violations->get(0)->getMessage());
  }

  /**
   * Test validation with content that has no initial state or an invalid state.
   */
  public function testInvalidStateWithoutExisting() {
    // Create content without moderation enabled for the content type.
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->save();
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->save();

    // Enable moderation to test validation on existing content, with no
    // explicit state.
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addState('deleted_state', 'Deleted state');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    // Validate the invalid state.
    $node->moderation_state->value = 'invalid_state';
    $violations = $node->validate();
    $this->assertCount(1, $violations);

    // Assign the node to a state we're going to delete.
    $node->moderation_state->value = 'deleted_state';
    $node->save();

    // Delete the state so $node->original contains an invalid state when
    // validating.
    $workflow->getTypePlugin()->deleteState('deleted_state');
    $workflow->save();
    $node->moderation_state->value = 'draft';
    $violations = $node->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Test state transition validation with multiple languages.
   */
  public function testInvalidStateMultilingual() {
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->save();

    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $node = Node::create([
      'type' => 'example',
      'title' => 'English Published Node',
      'langcode' => 'en',
      'moderation_state' => 'published',
    ]);
    $node->save();

    $node_fr = $node->addTranslation('fr');
    $node_fr->setTitle('French Published Node');
    $node_fr->save();
    $this->assertEquals('published', $node_fr->moderation_state->value);

    // Create a pending revision of the original node.
    $node->moderation_state = 'draft';
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(FALSE);
    $node->save();

    // For the pending english revision, there should be a violation from draft
    // to archived.
    $node->moderation_state = 'archived';
    $violations = $node->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('Invalid state transition from <em class="placeholder">Draft</em> to <em class="placeholder">Archived</em>', $violations->get(0)->getMessage());

    // From the default french published revision, there should be none.
    $node_fr = Node::load($node->id())->getTranslation('fr');
    $this->assertEquals('published', $node_fr->moderation_state->value);
    $node_fr->moderation_state = 'archived';
    $violations = $node_fr->validate();
    $this->assertCount(0, $violations);

    // From the latest french revision, there should also be no violation.
    $node_fr = $node->getTranslation('fr');
    $this->assertEquals('published', $node_fr->moderation_state->value);
    $node_fr->moderation_state = 'archived';
    $violations = $node_fr->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests that content without prior moderation information can be moderated.
   */
  public function testExistingContentWithNoModeration() {
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
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

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
  public function testExistingMultilingualContentWithNoModeration() {
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
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    // Reload the French version of the node.
    $node = Node::load($nid);
    $node_fr = $node->getTranslation('fr');

    /** @var \Drupal\node\NodeInterface $node_fr */
    $node_fr->setTitle('Nouveau');
    $node_fr->save();
  }

}
