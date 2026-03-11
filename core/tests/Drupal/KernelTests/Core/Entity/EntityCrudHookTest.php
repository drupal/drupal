<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\block\Entity\Block;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests entity CRUD via hooks.
 *
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
 */
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
class EntityCrudHookTest extends EntityKernelTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_test',
    'file',
    'taxonomy',
    'node',
    'comment',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('user', ['users_data']);
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['node', 'comment']);
    $this->container->get('theme_installer')->install(['stark']);
  }

  /**
   * Checks the order of CRUD hook execution messages.
   *
   * Module entity_crud_hook_test implements all core entity CRUD hooks and
   * stores a message for each in $GLOBALS['entity_crud_hook_test'].
   *
   * @param string[] $messages
   *   An array of plain-text messages in the order they should appear.
   *
   * @internal
   */
  protected function assertHookMessageOrder(array $messages): void {
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
    $this->assertSame($positions, $sorted, 'The hook messages appear in the correct order.');
  }

  /**
   * Tests hook invocations for CRUD operations on blocks.
   */
  public function testBlockHooks(): void {
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
  public function testCommentHooks(): void {
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
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
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
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
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
  public function testFileHooks(): void {
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
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
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
  public function testNodeHooks(): void {
    $account = $this->createUser();

    $node = Node::create([
      'uid' => $account->id(),
      'type' => 'article',
      'title' => 'Test node',
      'status' => 1,
      'promote' => 0,
      'sticky' => 0,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
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
  public function testTaxonomyTermHooks(): void {
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
  public function testTaxonomyVocabularyHooks(): void {
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
  public function testUserHooks(): void {
    $account = User::create([
      'name' => 'Test user',
      'mail' => 'test@example.com',
      'created' => \Drupal::time()->getRequestTime(),
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
  public function testEntityRollback(): void {
    // Create a block.
    try {
      EntityTest::create(['name' => 'fail_insert'])->save();
      $this->fail('Expected exception has not been thrown.');
    }
    catch (\Exception) {
      // Expected exception; just continue testing.
    }

    // Check that the block does not exist in the database.
    $ids = \Drupal::entityQuery('entity_test')
      ->accessCheck(FALSE)
      ->condition('name', 'fail_insert')
      ->execute();
    $this->assertEmpty($ids);
  }

  /**
   * Implements hook_entity_create().
   */
  #[Hook('entity_create')]
  public function entityCreate(EntityInterface $entity): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_create called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for block entities.
   */
  #[Hook('block_create')]
  public function blockCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_create called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for comment entities.
   */
  #[Hook('comment_create')]
  public function commentCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_create called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for file entities.
   */
  #[Hook('file_create')]
  public function fileCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_create called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for node entities.
   */
  #[Hook('node_create')]
  public function nodeCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_create called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_create')]
  public function taxonomyTermCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_create called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_create')]
  public function taxonomyVocabularyCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_create called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for user entities.
   */
  #[Hook('user_create')]
  public function userCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_create called';
  }

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_presave called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for block entities.
   */
  #[Hook('block_presave')]
  public function blockPresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_presave called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for comment entities.
   */
  #[Hook('comment_presave')]
  public function commentPresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_presave called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for file entities.
   */
  #[Hook('file_presave')]
  public function filePresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_presave called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for node entities.
   */
  #[Hook('node_presave')]
  public function nodePresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_presave called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_presave')]
  public function taxonomyTermPresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_presave called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_presave')]
  public function taxonomyVocabularyPresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_presave called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for user entities.
   */
  #[Hook('user_presave')]
  public function userPresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_presave called';
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_insert called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for block entities.
   */
  #[Hook('block_insert')]
  public function blockInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_insert called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for comment entities.
   */
  #[Hook('comment_insert')]
  public function commentInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_insert called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for file entities.
   */
  #[Hook('file_insert')]
  public function fileInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_insert called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for node entities.
   */
  #[Hook('node_insert')]
  public function nodeInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_insert called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_insert')]
  public function taxonomyTermInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_insert called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_insert')]
  public function taxonomyVocabularyInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_insert called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for user entities.
   */
  #[Hook('user_insert')]
  public function userInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_insert called';
  }

  /**
   * Implements hook_entity_preload().
   */
  #[Hook('entity_preload')]
  public function entityPreload(array $entities, $type): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_preload called for type ' . $type;
  }

  /**
   * Implements hook_entity_load().
   */
  #[Hook('entity_load')]
  public function entityLoad(array $entities, $type): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_load called for type ' . $type;
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for block entities.
   */
  #[Hook('block_load')]
  public function blockLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_load called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for comment entities.
   */
  #[Hook('comment_load')]
  public function commentLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_load called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for file entities.
   */
  #[Hook('file_load')]
  public function fileLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_load called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for node entities.
   */
  #[Hook('node_load')]
  public function nodeLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_load called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_load')]
  public function taxonomyTermLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_load called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_load')]
  public function taxonomyVocabularyLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_load called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for user entities.
   */
  #[Hook('user_load')]
  public function userLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_load called';
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_update called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for block entities.
   */
  #[Hook('block_update')]
  public function blockUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_update called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for comment entities.
   */
  #[Hook('comment_update')]
  public function commentUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_update called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for file entities.
   */
  #[Hook('file_update')]
  public function fileUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_update called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for node entities.
   */
  #[Hook('node_update')]
  public function nodeUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_update called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_update')]
  public function taxonomyTermUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_update called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_update')]
  public function taxonomyVocabularyUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_update called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for user entities.
   */
  #[Hook('user_update')]
  public function userUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_update called';
  }

  /**
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_predelete called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for block entities.
   */
  #[Hook('block_predelete')]
  public function blockPredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_predelete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for comment entities.
   */
  #[Hook('comment_predelete')]
  public function commentPredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_predelete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for file entities.
   */
  #[Hook('file_predelete')]
  public function filePredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_predelete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for node entities.
   */
  #[Hook('node_predelete')]
  public function nodePredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_predelete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_predelete')]
  public function taxonomyTermPredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_predelete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_predelete')]
  public function taxonomyVocabularyPredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_predelete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for user entities.
   */
  #[Hook('user_predelete')]
  public function userPredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_predelete called';
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_delete called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for block entities.
   */
  #[Hook('block_delete')]
  public function blockDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_delete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for comment entities.
   */
  #[Hook('comment_delete')]
  public function commentDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_delete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for file entities.
   */
  #[Hook('file_delete')]
  public function fileDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_delete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for node entities.
   */
  #[Hook('node_delete')]
  public function nodeDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_delete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_delete')]
  public function taxonomyTermDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_delete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_delete')]
  public function taxonomyVocabularyDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_delete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for user entities.
   */
  #[Hook('user_delete')]
  public function userDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_delete called';
  }

}
