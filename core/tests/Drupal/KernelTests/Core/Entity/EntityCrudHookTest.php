<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Database\Database;
use Drupal\Core\Language\LanguageInterface;
use Drupal\block\Entity\Block;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;
use Drupal\file\Entity\File;

/**
 * Tests the invocation of hooks when creating, inserting, loading, updating or
 * deleting an entity.
 *
 * Tested hooks are:
 * - hook_entity_insert() and hook_ENTITY_TYPE_insert()
 * - hook_entity_preload()
 * - hook_entity_load() and hook_ENTITY_TYPE_load()
 * - hook_entity_update() and hook_ENTITY_TYPE_update()
 * - hook_entity_predelete() and hook_ENTITY_TYPE_predelete()
 * - hook_entity_delete() and hook_ENTITY_TYPE_delete()
 *
 * These hooks are each tested for several entity types.
 *
 * @group Entity
 */
class EntityCrudHookTest extends EntityKernelTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'block_test',
    'entity_crud_hook_test',
    'file',
    'taxonomy',
    'node',
    'comment',
  ];

  protected $ids = [];

  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('user', ['users_data']);
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['node', 'comment']);
  }

  /**
   * Checks the order of CRUD hook execution messages.
   *
   * Module entity_crud_hook_test implements all core entity CRUD hooks and
   * stores a message for each in $GLOBALS['entity_crud_hook_test'].
   *
   * @param $messages
   *   An array of plain-text messages in the order they should appear.
   */
  protected function assertHookMessageOrder($messages) {
    $positions = [];
    foreach ($messages as $message) {
      // Verify that each message is found and record its position.
      $position = array_search($message, $GLOBALS['entity_crud_hook_test']);
      $this->assertNotFalse($position, $message);
      $positions[] = $position;
    }

    // Sort the positions and ensure they remain in the same order.
    $sorted = $positions;
    sort($sorted);
    $this->assertTrue($sorted == $positions, 'The hook messages appear in the correct order.');
  }

  /**
   * Tests hook invocations for CRUD operations on blocks.
   */
  public function testBlockHooks() {
    $entity = Block::create([
      'id' => 'stark_test_html',
      'plugin' => 'test_html',
      'theme' => 'stark',
    ]);

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_block_create called',
      'entity_crud_hook_test_entity_create called for type block',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $entity->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_block_presave called',
      'entity_crud_hook_test_entity_presave called for type block',
      'entity_crud_hook_test_block_insert called',
      'entity_crud_hook_test_entity_insert called for type block',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $entity = Block::load($entity->id());

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_entity_load called for type block',
      'entity_crud_hook_test_block_load called',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $entity->label = 'New label';
    $entity->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_block_presave called',
      'entity_crud_hook_test_entity_presave called for type block',
      'entity_crud_hook_test_block_update called',
      'entity_crud_hook_test_entity_update called for type block',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $entity->delete();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_block_predelete called',
      'entity_crud_hook_test_entity_predelete called for type block',
      'entity_crud_hook_test_block_delete called',
      'entity_crud_hook_test_entity_delete called for type block',
    ]);
  }

  /**
   * Tests hook invocations for CRUD operations on comments.
   */
  public function testCommentHooks() {
    $account = $this->createUser();
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();
    $this->addDefaultCommentField('node', 'article', 'comment', CommentItemInterface::OPEN);

    $node = Node::create([
      'uid' => $account->id(),
      'type' => 'article',
      'title' => 'Test node',
      'status' => 1,
      'promote' => 0,
      'sticky' => 0,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'created' => REQUEST_TIME,
      'changed' => REQUEST_TIME,
    ]);
    $node->save();
    $nid = $node->id();
    $GLOBALS['entity_crud_hook_test'] = [];

    $comment = Comment::create([
      'cid' => NULL,
      'pid' => 0,
      'entity_id' => $nid,
      'entity_type' => 'node',
      'field_name' => 'comment',
      'uid' => $account->id(),
      'subject' => 'Test comment',
      'created' => REQUEST_TIME,
      'changed' => REQUEST_TIME,
      'status' => 1,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_comment_create called',
      'entity_crud_hook_test_entity_create called for type comment',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $comment->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_comment_presave called',
      'entity_crud_hook_test_entity_presave called for type comment',
      'entity_crud_hook_test_comment_insert called',
      'entity_crud_hook_test_entity_insert called for type comment',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $comment = Comment::load($comment->id());

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_entity_load called for type comment',
      'entity_crud_hook_test_comment_load called',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $comment->setSubject('New subject');
    $comment->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_comment_presave called',
      'entity_crud_hook_test_entity_presave called for type comment',
      'entity_crud_hook_test_comment_update called',
      'entity_crud_hook_test_entity_update called for type comment',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $comment->delete();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_comment_predelete called',
      'entity_crud_hook_test_entity_predelete called for type comment',
      'entity_crud_hook_test_comment_delete called',
      'entity_crud_hook_test_entity_delete called for type comment',
    ]);
  }

  /**
   * Tests hook invocations for CRUD operations on files.
   */
  public function testFileHooks() {
    $this->installEntitySchema('file');

    $url = 'public://entity_crud_hook_test.file';
    file_put_contents($url, 'Test test test');
    $file = File::create([
      'fid' => NULL,
      'uid' => 1,
      'filename' => 'entity_crud_hook_test.file',
      'uri' => $url,
      'filemime' => 'text/plain',
      'filesize' => filesize($url),
      'status' => 1,
      'created' => REQUEST_TIME,
      'changed' => REQUEST_TIME,
    ]);

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_file_create called',
      'entity_crud_hook_test_entity_create called for type file',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $file->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_file_presave called',
      'entity_crud_hook_test_entity_presave called for type file',
      'entity_crud_hook_test_file_insert called',
      'entity_crud_hook_test_entity_insert called for type file',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $file = File::load($file->id());

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_entity_load called for type file',
      'entity_crud_hook_test_file_load called',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $file->setFilename('new.entity_crud_hook_test.file');
    $file->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_file_presave called',
      'entity_crud_hook_test_entity_presave called for type file',
      'entity_crud_hook_test_file_update called',
      'entity_crud_hook_test_entity_update called for type file',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $file->delete();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_file_predelete called',
      'entity_crud_hook_test_entity_predelete called for type file',
      'entity_crud_hook_test_file_delete called',
      'entity_crud_hook_test_entity_delete called for type file',
    ]);
  }

  /**
   * Tests hook invocations for CRUD operations on nodes.
   */
  public function testNodeHooks() {
    $account = $this->createUser();

    $node = Node::create([
      'uid' => $account->id(),
      'type' => 'article',
      'title' => 'Test node',
      'status' => 1,
      'promote' => 0,
      'sticky' => 0,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'created' => REQUEST_TIME,
      'changed' => REQUEST_TIME,
    ]);

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_node_create called',
      'entity_crud_hook_test_entity_create called for type node',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $node->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_node_presave called',
      'entity_crud_hook_test_entity_presave called for type node',
      'entity_crud_hook_test_node_insert called',
      'entity_crud_hook_test_entity_insert called for type node',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $node = Node::load($node->id());

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_entity_preload called for type node',
      'entity_crud_hook_test_entity_load called for type node',
      'entity_crud_hook_test_node_load called',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $node->title = 'New title';
    $node->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_node_presave called',
      'entity_crud_hook_test_entity_presave called for type node',
      'entity_crud_hook_test_node_update called',
      'entity_crud_hook_test_entity_update called for type node',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $node->delete();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_node_predelete called',
      'entity_crud_hook_test_entity_predelete called for type node',
      'entity_crud_hook_test_node_delete called',
      'entity_crud_hook_test_entity_delete called for type node',
    ]);
  }

  /**
   * Tests hook invocations for CRUD operations on taxonomy terms.
   */
  public function testTaxonomyTermHooks() {
    $this->installEntitySchema('taxonomy_term');

    $vocabulary = Vocabulary::create([
      'name' => 'Test vocabulary',
      'vid' => 'test',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'description' => NULL,
      'module' => 'entity_crud_hook_test',
    ]);
    $vocabulary->save();
    $GLOBALS['entity_crud_hook_test'] = [];

    $term = Term::create([
      'vid' => $vocabulary->id(),
      'name' => 'Test term',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'description' => NULL,
      'format' => 1,
    ]);

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_taxonomy_term_create called',
      'entity_crud_hook_test_entity_create called for type taxonomy_term',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $term->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_taxonomy_term_presave called',
      'entity_crud_hook_test_entity_presave called for type taxonomy_term',
      'entity_crud_hook_test_taxonomy_term_insert called',
      'entity_crud_hook_test_entity_insert called for type taxonomy_term',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $term = Term::load($term->id());

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_entity_load called for type taxonomy_term',
      'entity_crud_hook_test_taxonomy_term_load called',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $term->setName('New name');
    $term->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_taxonomy_term_presave called',
      'entity_crud_hook_test_entity_presave called for type taxonomy_term',
      'entity_crud_hook_test_taxonomy_term_update called',
      'entity_crud_hook_test_entity_update called for type taxonomy_term',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $term->delete();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_taxonomy_term_predelete called',
      'entity_crud_hook_test_entity_predelete called for type taxonomy_term',
      'entity_crud_hook_test_taxonomy_term_delete called',
      'entity_crud_hook_test_entity_delete called for type taxonomy_term',
    ]);
  }

  /**
   * Tests hook invocations for CRUD operations on taxonomy vocabularies.
   */
  public function testTaxonomyVocabularyHooks() {
    $this->installEntitySchema('taxonomy_term');

    $vocabulary = Vocabulary::create([
      'name' => 'Test vocabulary',
      'vid' => 'test',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'description' => NULL,
      'module' => 'entity_crud_hook_test',
    ]);

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_taxonomy_vocabulary_create called',
      'entity_crud_hook_test_entity_create called for type taxonomy_vocabulary',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $vocabulary->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_taxonomy_vocabulary_presave called',
      'entity_crud_hook_test_entity_presave called for type taxonomy_vocabulary',
      'entity_crud_hook_test_taxonomy_vocabulary_insert called',
      'entity_crud_hook_test_entity_insert called for type taxonomy_vocabulary',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $vocabulary = Vocabulary::load($vocabulary->id());

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_entity_load called for type taxonomy_vocabulary',
      'entity_crud_hook_test_taxonomy_vocabulary_load called',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $vocabulary->set('name', 'New name');
    $vocabulary->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_taxonomy_vocabulary_presave called',
      'entity_crud_hook_test_entity_presave called for type taxonomy_vocabulary',
      'entity_crud_hook_test_taxonomy_vocabulary_update called',
      'entity_crud_hook_test_entity_update called for type taxonomy_vocabulary',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $vocabulary->delete();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_taxonomy_vocabulary_predelete called',
      'entity_crud_hook_test_entity_predelete called for type taxonomy_vocabulary',
      'entity_crud_hook_test_taxonomy_vocabulary_delete called',
      'entity_crud_hook_test_entity_delete called for type taxonomy_vocabulary',
    ]);
  }

  /**
   * Tests hook invocations for CRUD operations on users.
   */
  public function testUserHooks() {
    $account = User::create([
      'name' => 'Test user',
      'mail' => 'test@example.com',
      'created' => REQUEST_TIME,
      'status' => 1,
      'language' => 'en',
    ]);

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_user_create called',
      'entity_crud_hook_test_entity_create called for type user',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $account->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_user_presave called',
      'entity_crud_hook_test_entity_presave called for type user',
      'entity_crud_hook_test_user_insert called',
      'entity_crud_hook_test_entity_insert called for type user',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    User::load($account->id());

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_entity_load called for type user',
      'entity_crud_hook_test_user_load called',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $account->name = 'New name';
    $account->save();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_user_presave called',
      'entity_crud_hook_test_entity_presave called for type user',
      'entity_crud_hook_test_user_update called',
      'entity_crud_hook_test_entity_update called for type user',
    ]);

    $GLOBALS['entity_crud_hook_test'] = [];
    $account->delete();

    $this->assertHookMessageOrder([
      'entity_crud_hook_test_user_predelete called',
      'entity_crud_hook_test_entity_predelete called for type user',
      'entity_crud_hook_test_user_delete called',
      'entity_crud_hook_test_entity_delete called for type user',
    ]);
  }

  /**
   * Tests rollback from failed entity save.
   */
  public function testEntityRollback() {
    // Create a block.
    try {
      EntityTest::create(['name' => 'fail_insert'])->save();
      $this->fail('Expected exception has not been thrown.');
    }
    catch (\Exception $e) {
      $this->pass('Expected exception has been thrown.');
    }

    if (Database::getConnection()->supportsTransactions()) {
      // Check that the block does not exist in the database.
      $ids = \Drupal::entityQuery('entity_test')->condition('name', 'fail_insert')->execute();
      $this->assertTrue(empty($ids), 'Transactions supported, and entity not found in database.');
    }
    else {
      // Check that the block exists in the database.
      $ids = \Drupal::entityQuery('entity_test')->condition('name', 'fail_insert')->execute();
      $this->assertFalse(empty($ids), 'Transactions not supported, and entity found in database.');
    }
  }

}
