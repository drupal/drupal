<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * @coversDefaultClass \Drupal\content_moderation\Plugin\Validation\Constraint\ModerationStateConstraintValidator
 * @group content_moderation
 */
class EntityStateChangeValidationTest extends KernelTestBase {

  use ContentModerationTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'content_moderation',
    'user',
    'system',
    'language',
    'content_translation',
    'workflows',
  ];

  /**
   * An admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');

    $this->adminUser = $this->createUser(array_keys($this->container->get('user.permissions')->getPermissions()));
  }

  /**
   * Tests valid transitions.
   *
   * @covers ::validate
   */
  public function testValidTransition(): void {

    $node_type = NodeType::create([
      'type' => 'example',
      'name' => 'Example',
    ]);
    $node_type->save();
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->moderation_state->value = 'draft';
    $node->save();

    $this->setCurrentUser($this->createUser(['use editorial transition publish']));
    $node->moderation_state->value = 'published';
    $this->assertCount(0, $node->validate());
    $node->save();

    $this->assertEquals('published', $node->moderation_state->value);
  }

  /**
   * Tests invalid transitions.
   *
   * @covers ::validate
   */
  public function testInvalidTransition(): void {
    $this->setCurrentUser($this->adminUser);

    $node_type = NodeType::create([
      'type' => 'example',
      'name' => 'Example',
    ]);
    $node_type->save();
    $workflow = $this->createEditorialWorkflow();
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

    $this->assertEquals('Invalid state transition from Draft to Archived', $violations->get(0)->getMessage());
  }

  /**
   * Tests validation with an invalid state.
   */
  public function testInvalidState(): void {
    $node_type = NodeType::create([
      'type' => 'example',
      'name' => 'Example',
    ]);
    $node_type->save();
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->moderation_state->value = 'invalid_state';
    $violations = $node->validate();

    $this->assertCount(1, $violations);
    $this->assertEquals('State invalid_state does not exist on Editorial workflow', $violations->get(0)->getMessage());
  }

  /**
   * Tests validation with no initial state or an invalid state.
   */
  public function testInvalidStateWithoutExisting(): void {
    // Create content without moderation enabled for the content type.
    $node_type = NodeType::create([
      'type' => 'example',
      'name' => 'Example',
    ]);
    $node_type->save();
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->save();

    // Enable moderation to test validation on existing content, with no
    // explicit state.
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addState('deleted_state', 'Deleted state');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $this->setCurrentUser($this->createUser(['use editorial transition create_new_draft']));
    // Validate the invalid state.
    $node = Node::load($node->id());
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

    // When there is an invalid state, the content will revert to "draft". This
    // will allow a draft to draft transition.
    $node->moderation_state->value = 'draft';
    $violations = $node->validate();
    $this->assertCount(0, $violations);
    // This will disallow a draft to archived transition.
    $node->moderation_state->value = 'archived';
    $violations = $node->validate();
    $this->assertCount(1, $violations);
  }

  /**
   * Tests state transition validation with multiple languages.
   */
  public function testInvalidStateMultilingual(): void {

    ConfigurableLanguage::createFromLangcode('fr')->save();
    $node_type = NodeType::create([
      'type' => 'example',
      'name' => 'Example',
    ]);
    $node_type->save();

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $this->setCurrentUser($this->createUser(['use editorial transition archive']));
    $node = Node::create([
      'type' => 'example',
      'title' => 'English Published Node',
      'langcode' => 'en',
      'moderation_state' => 'published',
    ]);
    $node->save();

    $node_fr = $node->addTranslation('fr', $node->toArray());
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
    $this->assertEquals('Invalid state transition from Draft to Archived', $violations->get(0)->getMessage());

    // From the default french published revision, there should be none.
    $node_fr = Node::load($node->id())->getTranslation('fr');
    $this->assertEquals('published', $node_fr->moderation_state->value);
    $node_fr->moderation_state = 'archived';
    $violations = $node_fr->validate();
    $this->assertCount(0, $violations);

    // From the latest french revision, there should also be no violation.
    $node_fr = Node::load($node->id())->getTranslation('fr');
    $this->assertEquals('published', $node_fr->moderation_state->value);
    $node_fr->moderation_state = 'archived';
    $violations = $node_fr->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests that content without prior moderation information can be moderated.
   */
  public function testExistingContentWithNoModeration(): void {

    $node_type = NodeType::create([
      'type' => 'example',
      'name' => 'Example',
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
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $this->setCurrentUser($this->createUser(['use editorial transition publish']));
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
  public function testExistingMultilingualContentWithNoModeration(): void {
    $this->setCurrentUser($this->adminUser);

    // Enable French.
    ConfigurableLanguage::createFromLangcode('fr')->save();

    $node_type = NodeType::create([
      'type' => 'example',
      'name' => 'Example',
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
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    // Reload the French version of the node.
    $node = Node::load($nid);
    $node_fr = $node->getTranslation('fr');

    /** @var \Drupal\node\NodeInterface $node_fr */
    $node_fr->setTitle('Nouveau');
    $node_fr->save();
  }

  /**
   * @dataProvider transitionAccessValidationTestCases
   */
  public function testTransitionAccessValidation($permissions, $target_state, $messages): void {
    $node_type = NodeType::create([
      'type' => 'example',
      'name' => 'Example',
    ]);
    $node_type->save();
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addState('foo', 'Foo');
    $workflow->getTypePlugin()->addTransition('draft_to_foo', 'Draft to foo', ['draft'], 'foo');
    $workflow->getTypePlugin()->addTransition('foo_to_foo', 'Foo to foo', ['foo'], 'foo');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $this->setCurrentUser($this->createUser($permissions));

    $node = Node::create([
      'type' => 'example',
      'title' => 'Test content',
      'moderation_state' => $target_state,
    ]);
    $this->assertTrue($node->isNew());
    $violations = $node->validate();
    $this->assertSameSize($messages, $violations);
    foreach ($messages as $i => $message) {
      $this->assertEquals($message, $violations->get($i)->getMessage());
    }
  }

  /**
   * Test cases for ::testTransitionAccessValidation.
   */
  public static function transitionAccessValidationTestCases() {
    return [
      'Invalid transition, no permissions validated' => [
        [],
        'archived',
        ['Invalid state transition from Draft to Archived'],
      ],
      'Valid transition, missing permission' => [
        [],
        'published',
        ['You do not have access to transition from Draft to Published'],
      ],
      'Valid transition, granted published permission' => [
        ['use editorial transition publish'],
        'published',
        [],
      ],
      'Valid transition, granted draft permission' => [
        ['use editorial transition create_new_draft'],
        'draft',
        [],
      ],
      'Valid transition, incorrect permission granted' => [
        ['use editorial transition create_new_draft'],
        'published',
        ['You do not have access to transition from Draft to Published'],
      ],
      // Test with an additional state and set of transitions, since the
      // "published" transition can start from either "draft" or "published", it
      // does not capture bugs that fail to correctly distinguish the initial
      // workflow state from the set state of a new entity.
      'Valid transition, granted foo permission' => [
        ['use editorial transition draft_to_foo'],
        'foo',
        [],
      ],
      'Valid transition, incorrect  foo permission granted' => [
        ['use editorial transition foo_to_foo'],
        'foo',
        ['You do not have access to transition from Draft to Foo'],
      ],
    ];
  }

}
