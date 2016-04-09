<?php

namespace Drupal\Tests\hal\Kernel;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\comment\Entity\Comment;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests that nodes and terms are correctly normalized and denormalized.
 *
 * @group hal
 */
class EntityNormalizeTest extends NormalizerTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'taxonomy', 'comment');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    \Drupal::service('router.builder')->rebuild();
    $this->installSchema('system', array('sequences'));
    $this->installSchema('comment', array('comment_entity_statistics'));
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['node', 'comment']);
  }

  /**
   * Tests the normalization of nodes.
   */
  public function testNode() {
    $node_type = NodeType::create(['type' => 'example_type']);
    $node_type->save();

    $user = User::create(['name' => $this->randomMachineName()]);
    $user->save();

    // Add comment type.
    $this->container->get('entity.manager')->getStorage('comment_type')->create(array(
      'id' => 'comment',
      'label' => 'comment',
      'target_entity_type_id' => 'node',
    ))->save();

    $this->addDefaultCommentField('node', 'example_type');

    $node = Node::create([
      'title' => $this->randomMachineName(),
      'uid' => $user->id(),
      'type' => $node_type->id(),
      'status' => NODE_PUBLISHED,
      'promote' => 1,
      'sticky' => 0,
      'body' => [
        'value' => $this->randomMachineName(),
        'format' => $this->randomMachineName()
      ],
      'revision_log' => $this->randomString(),
    ]);
    $node->save();

    $original_values = $node->toArray();

    $normalized = $this->serializer->normalize($node, $this->format);

    /** @var \Drupal\node\NodeInterface $denormalized_node */
    $denormalized_node = $this->serializer->denormalize($normalized, 'Drupal\node\Entity\Node', $this->format);

    $this->assertEqual($original_values, $denormalized_node->toArray(), 'Node values are restored after normalizing and denormalizing.');
  }

  /**
   * Tests the normalization of terms.
   */
  public function testTerm() {
    $vocabulary = Vocabulary::create(['vid' => 'example_vocabulary']);
    $vocabulary->save();

    $account = User::create(['name' => $this->randomMachineName()]);
    $account->save();

    // @todo Until https://www.drupal.org/node/2327935 is fixed, if no parent is
    // set, the test fails because target_id => 0 is reserialized to NULL.
    $term_parent = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary->id(),
    ]);
    $term_parent->save();
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary->id(),
      'description' => array(
        'value' => $this->randomMachineName(),
        'format' => $this->randomMachineName(),
      ),
      'parent' => $term_parent->id(),
    ]);
    $term->save();

    $original_values = $term->toArray();

    $normalized = $this->serializer->normalize($term, $this->format, ['account' => $account]);

    /** @var \Drupal\taxonomy\TermInterface $denormalized_term */
    $denormalized_term = $this->serializer->denormalize($normalized, 'Drupal\taxonomy\Entity\Term', $this->format, ['account' => $account]);

    $this->assertEqual($original_values, $denormalized_term->toArray(), 'Term values are restored after normalizing and denormalizing.');
  }

  /**
   * Tests the normalization of comments.
   */
  public function testComment() {
    $node_type = NodeType::create(['type' => 'example_type']);
    $node_type->save();

    $account = User::create(['name' => $this->randomMachineName()]);
    $account->save();

    // Add comment type.
    $this->container->get('entity.manager')->getStorage('comment_type')->create(array(
      'id' => 'comment',
      'label' => 'comment',
      'target_entity_type_id' => 'node',
    ))->save();

    $this->addDefaultCommentField('node', 'example_type');

    $node = Node::create([
      'title' => $this->randomMachineName(),
      'uid' => $account->id(),
      'type' => $node_type->id(),
      'status' => NODE_PUBLISHED,
      'promote' => 1,
      'sticky' => 0,
      'body' => [[
        'value' => $this->randomMachineName(),
        'format' => $this->randomMachineName()
      ]],
    ]);
    $node->save();

    $parent_comment = Comment::create(array(
      'uid' => $account->id(),
      'subject' => $this->randomMachineName(),
      'comment_body' => [
        'value' => $this->randomMachineName(),
        'format' => NULL,
      ],
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
    ));
    $parent_comment->save();

    $comment = Comment::create(array(
      'uid' => $account->id(),
      'subject' => $this->randomMachineName(),
      'comment_body' => [
        'value' => $this->randomMachineName(),
        'format' => NULL,
      ],
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'pid' => $parent_comment->id(),
      'mail' => 'dries@drupal.org',
      'homepage' => 'http://buytaert.net',
    ));
    $comment->save();

    $original_values = $comment->toArray();
    // Hostname will always be denied view access.
    // No value will exist for name as this is only for anonymous users.
    unset($original_values['hostname'], $original_values['name']);

    $normalized = $this->serializer->normalize($comment, $this->format, ['account' => $account]);

    // Assert that the hostname field does not appear at all in the normalized
    // data.
    $this->assertFalse(array_key_exists('hostname', $normalized), 'Hostname was not found in normalized comment data.');

    /** @var \Drupal\comment\CommentInterface $denormalized_comment */
    $denormalized_comment = $this->serializer->denormalize($normalized, 'Drupal\comment\Entity\Comment', $this->format, ['account' => $account]);

    // Before comparing, unset values that are expected to differ.
    $denormalized_comment_values = $denormalized_comment->toArray();
    unset($denormalized_comment_values['hostname'], $denormalized_comment_values['name']);
    $this->assertEqual($original_values, $denormalized_comment_values, 'The expected comment values are restored after normalizing and denormalizing.');
  }

}
