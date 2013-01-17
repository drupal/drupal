<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Entity\EntityCrudHookTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Database\Database;

/**
 * Tests invocation of hooks when performing an action.
 *
 * Tested hooks are:
 * - hook_entity_insert()
 * - hook_entity_load()
 * - hook_entity_update()
 * - hook_entity_predelete()
 * - hook_entity_delete()
 * As well as all type-specific hooks, like hook_node_insert(),
 * hook_comment_update(), etc.
 */
class EntityCrudHookTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_crud_hook_test', 'taxonomy', 'block_test', 'block', 'comment', 'file', 'entity_test');

  protected $ids = array();

  public static function getInfo() {
    return array(
      'name' => 'Entity CRUD hooks',
      'description' => 'Tests the invocation of hooks when inserting, loading, updating or deleting an entity.',
      'group' => 'Entity API',
    );
  }

  /**
   * Checks the order of CRUD hook execution messages.
   *
   * entity_crud_hook_test.module implements all core entity CRUD hooks and
   * stores a message for each in $_SESSION['entity_crud_hook_test'].
   *
   * @param $messages
   *   An array of plain-text messages in the order they should appear.
   */
  protected function assertHookMessageOrder($messages) {
    $positions = array();
    foreach ($messages as $message) {
      // Verify that each message is found and record its position.
      $position = array_search($message, $_SESSION['entity_crud_hook_test']);
      if ($this->assertTrue($position !== FALSE, $message)) {
        $positions[] = $position;
      }
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
    $entity = entity_create('block', array(
      'id' => 'stark.test_html_id',
      'plugin' => 'test_html_id',
    ));
    $_SESSION['entity_crud_hook_test'] = array();
    $entity->save();

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_block_presave called',
      'entity_crud_hook_test_entity_presave called for type block',
      'entity_crud_hook_test_block_insert called',
      'entity_crud_hook_test_entity_insert called for type block',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $entity = entity_load('block', $entity->id());

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_entity_load called for type block',
      'entity_crud_hook_test_block_load called',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $entity->label = 'New label';
    $entity->save();

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_block_presave called',
      'entity_crud_hook_test_entity_presave called for type block',
      'entity_crud_hook_test_block_update called',
      'entity_crud_hook_test_entity_update called for type block',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $entity->delete();

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_block_predelete called',
      'entity_crud_hook_test_entity_predelete called for type block',
      'entity_crud_hook_test_block_delete called',
      'entity_crud_hook_test_entity_delete called for type block',
    ));
  }

  /**
   * Tests hook invocations for CRUD operations on comments.
   */
  public function testCommentHooks() {
    $node = entity_create('node', array(
      'uid' => 1,
      'type' => 'article',
      'title' => 'Test node',
      'status' => 1,
      'comment' => 2,
      'promote' => 0,
      'sticky' => 0,
      'langcode' => LANGUAGE_NOT_SPECIFIED,
      'created' => REQUEST_TIME,
      'changed' => REQUEST_TIME,
    ));
    $node->save();
    $nid = $node->nid;

    $comment = entity_create('comment', array(
      'node_type' => 'node_type_' . $node->bundle(),
      'cid' => NULL,
      'pid' => 0,
      'nid' => $nid,
      'uid' => 1,
      'subject' => 'Test comment',
      'created' => REQUEST_TIME,
      'changed' => REQUEST_TIME,
      'status' => 1,
      'langcode' => LANGUAGE_NOT_SPECIFIED,
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    comment_save($comment);

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_comment_presave called',
      'entity_crud_hook_test_entity_presave called for type comment',
      'entity_crud_hook_test_comment_insert called',
      'entity_crud_hook_test_entity_insert called for type comment',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $comment = comment_load($comment->id());

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_entity_load called for type comment',
      'entity_crud_hook_test_comment_load called',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $comment->subject->value = 'New subject';
    comment_save($comment);

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_comment_presave called',
      'entity_crud_hook_test_entity_presave called for type comment',
      'entity_crud_hook_test_comment_update called',
      'entity_crud_hook_test_entity_update called for type comment',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $comment->delete();

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_comment_predelete called',
      'entity_crud_hook_test_entity_predelete called for type comment',
      'entity_crud_hook_test_comment_delete called',
      'entity_crud_hook_test_entity_delete called for type comment',
    ));
  }

  /**
   * Tests hook invocations for CRUD operations on files.
   */
  public function testFileHooks() {
    $url = 'public://entity_crud_hook_test.file';
    file_put_contents($url, 'Test test test');
    $file = entity_create('file', array(
      'fid' => NULL,
      'uid' => 1,
      'filename' => 'entity_crud_hook_test.file',
      'uri' => $url,
      'filemime' => 'text/plain',
      'filesize' => filesize($url),
      'status' => 1,
      'timestamp' => REQUEST_TIME,
    ));
    $_SESSION['entity_crud_hook_test'] = array();
    $file->save();

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_file_presave called',
      'entity_crud_hook_test_entity_presave called for type file',
      'entity_crud_hook_test_file_insert called',
      'entity_crud_hook_test_entity_insert called for type file',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $file = file_load($file->fid);

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_entity_load called for type file',
      'entity_crud_hook_test_file_load called',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $file->filename = 'new.entity_crud_hook_test.file';
    $file->save();

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_file_presave called',
      'entity_crud_hook_test_entity_presave called for type file',
      'entity_crud_hook_test_file_update called',
      'entity_crud_hook_test_entity_update called for type file',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $file->delete();

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_file_predelete called',
      'entity_crud_hook_test_entity_predelete called for type file',
      'entity_crud_hook_test_file_delete called',
      'entity_crud_hook_test_entity_delete called for type file',
    ));
  }

  /**
   * Tests hook invocations for CRUD operations on nodes.
   */
  public function testNodeHooks() {
    $node = entity_create('node', array(
      'uid' => 1,
      'type' => 'article',
      'title' => 'Test node',
      'status' => 1,
      'comment' => 2,
      'promote' => 0,
      'sticky' => 0,
      'langcode' => LANGUAGE_NOT_SPECIFIED,
      'created' => REQUEST_TIME,
      'changed' => REQUEST_TIME,
    ));
    $_SESSION['entity_crud_hook_test'] = array();
    $node->save();

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_node_presave called',
      'entity_crud_hook_test_entity_presave called for type node',
      'entity_crud_hook_test_node_insert called',
      'entity_crud_hook_test_entity_insert called for type node',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $node = node_load($node->nid);

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_entity_load called for type node',
      'entity_crud_hook_test_node_load called',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $node->title = 'New title';
    $node->save();

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_node_presave called',
      'entity_crud_hook_test_entity_presave called for type node',
      'entity_crud_hook_test_node_update called',
      'entity_crud_hook_test_entity_update called for type node',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    node_delete($node->nid);

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_node_predelete called',
      'entity_crud_hook_test_entity_predelete called for type node',
      'entity_crud_hook_test_node_delete called',
      'entity_crud_hook_test_entity_delete called for type node',
    ));
  }

  /**
   * Tests hook invocations for CRUD operations on taxonomy terms.
   */
  public function testTaxonomyTermHooks() {
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => 'Test vocabulary',
      'vid' => 'test',
      'langcode' => LANGUAGE_NOT_SPECIFIED,
      'description' => NULL,
      'module' => 'entity_crud_hook_test',
    ));
    taxonomy_vocabulary_save($vocabulary);

    $term = entity_create('taxonomy_term', array(
      'vid' => $vocabulary->id(),
      'name' => 'Test term',
      'langcode' => LANGUAGE_NOT_SPECIFIED,
      'description' => NULL,
      'format' => 1,
    ));
    $_SESSION['entity_crud_hook_test'] = array();
    taxonomy_term_save($term);

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_taxonomy_term_presave called',
      'entity_crud_hook_test_entity_presave called for type taxonomy_term',
      'entity_crud_hook_test_taxonomy_term_insert called',
      'entity_crud_hook_test_entity_insert called for type taxonomy_term',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $term = taxonomy_term_load($term->tid);

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_entity_load called for type taxonomy_term',
      'entity_crud_hook_test_taxonomy_term_load called',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $term->name = 'New name';
    taxonomy_term_save($term);

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_taxonomy_term_presave called',
      'entity_crud_hook_test_entity_presave called for type taxonomy_term',
      'entity_crud_hook_test_taxonomy_term_update called',
      'entity_crud_hook_test_entity_update called for type taxonomy_term',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    taxonomy_term_delete($term->tid);

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_taxonomy_term_predelete called',
      'entity_crud_hook_test_entity_predelete called for type taxonomy_term',
      'entity_crud_hook_test_taxonomy_term_delete called',
      'entity_crud_hook_test_entity_delete called for type taxonomy_term',
    ));
  }

  /**
   * Tests hook invocations for CRUD operations on taxonomy vocabularies.
   */
  public function testTaxonomyVocabularyHooks() {
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => 'Test vocabulary',
      'vid' => 'test',
      'langcode' => LANGUAGE_NOT_SPECIFIED,
      'description' => NULL,
      'module' => 'entity_crud_hook_test',
    ));
    $_SESSION['entity_crud_hook_test'] = array();
    taxonomy_vocabulary_save($vocabulary);

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_taxonomy_vocabulary_presave called',
      'entity_crud_hook_test_entity_presave called for type taxonomy_vocabulary',
      'entity_crud_hook_test_taxonomy_vocabulary_insert called',
      'entity_crud_hook_test_entity_insert called for type taxonomy_vocabulary',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $vocabulary = taxonomy_vocabulary_load($vocabulary->id());

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_entity_load called for type taxonomy_vocabulary',
      'entity_crud_hook_test_taxonomy_vocabulary_load called',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $vocabulary->name = 'New name';
    taxonomy_vocabulary_save($vocabulary);

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_taxonomy_vocabulary_presave called',
      'entity_crud_hook_test_entity_presave called for type taxonomy_vocabulary',
      'entity_crud_hook_test_taxonomy_vocabulary_update called',
      'entity_crud_hook_test_entity_update called for type taxonomy_vocabulary',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    taxonomy_vocabulary_delete($vocabulary->id());

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_taxonomy_vocabulary_predelete called',
      'entity_crud_hook_test_entity_predelete called for type taxonomy_vocabulary',
      'entity_crud_hook_test_taxonomy_vocabulary_delete called',
      'entity_crud_hook_test_entity_delete called for type taxonomy_vocabulary',
    ));
  }

  /**
   * Tests hook invocations for CRUD operations on users.
   */
  public function testUserHooks() {
    $account = entity_create('user', array(
      'name' => 'Test user',
      'mail' => 'test@example.com',
      'created' => REQUEST_TIME,
      'status' => 1,
      'language' => 'en',
    ));
    $_SESSION['entity_crud_hook_test'] = array();
    $account->save();

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_user_presave called',
      'entity_crud_hook_test_entity_presave called for type user',
      'entity_crud_hook_test_user_insert called',
      'entity_crud_hook_test_entity_insert called for type user',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    user_load($account->uid);

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_entity_load called for type user',
      'entity_crud_hook_test_user_load called',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    $account->name = 'New name';
    $account->save();

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_user_presave called',
      'entity_crud_hook_test_entity_presave called for type user',
      'entity_crud_hook_test_user_update called',
      'entity_crud_hook_test_entity_update called for type user',
    ));

    $_SESSION['entity_crud_hook_test'] = array();
    user_delete($account->uid);

    $this->assertHookMessageOrder(array(
      'entity_crud_hook_test_user_predelete called',
      'entity_crud_hook_test_entity_predelete called for type user',
      'entity_crud_hook_test_user_delete called',
      'entity_crud_hook_test_entity_delete called for type user',
    ));
  }

  /**
   * Tests rollback from failed insert in EntityNG.
   */
  function testEntityNGRollback() {
    // Create a block.
    try {
      $entity = entity_create('entity_test', array('name' => 'fail_insert'))->save();
      $this->fail('Expected exception has not been thrown.');
    }
    catch (\Exception $e) {
      $this->pass('Expected exception has been thrown.');
    }

    if (Database::getConnection()->supportsTransactions()) {
      // Check that the block does not exist in the database.
      $ids = entity_query('entity_test')->condition('name', 'fail_insert')->execute();
      $this->assertTrue(empty($ids), 'Transactions supported, and entity not found in database.');
    }
    else {
      // Check that the block exists in the database.
      $ids = entity_query('entity_test')->condition('name', 'fail_insert')->execute();
      $this->assertFalse(empty($ids), 'Transactions not supported, and entity found in database.');
    }
  }
}
