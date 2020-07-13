<?php

namespace Drupal\Tests\views\Functional\Entity;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use Drupal\comment\Entity\Comment;

/**
 * Tests the field plugin base integration with the entity system.
 *
 * @group views
 */
class FieldEntityTest extends ViewTestBase {

  use CommentTestTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_field_get_entity'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'comment'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(FALSE);

    $this->drupalCreateContentType(['type' => 'page']);
    $this->addDefaultCommentField('node', 'page');

    ViewTestData::createTestViews(static::class, ['views_test_config']);
  }

  /**
   * Tests the getEntity method.
   */
  public function testGetEntity() {
    // The view is a view of comments, their nodes and their authors, so there
    // are three layers of entities.

    $account = User::create(['name' => $this->randomMachineName(), 'bundle' => 'user']);
    $account->save();

    $node = Node::create([
      'uid' => $account->id(),
      'type' => 'page',
      'title' => $this->randomString(),
    ]);
    $node->save();
    $comment = Comment::create([
      'uid' => $account->id(),
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
    ]);
    $comment->save();

    $user = $this->drupalCreateUser(['access comments']);
    $this->drupalLogin($user);

    $view = Views::getView('test_field_get_entity');
    $this->executeView($view);
    $row = $view->result[0];

    // Tests entities on the base level.
    $entity = $view->field['cid']->getEntity($row);
    $this->assertEqual($entity->id(), $comment->id(), 'Make sure the right comment entity got loaded.');
    // Tests entities as relationship on first level.
    $entity = $view->field['nid']->getEntity($row);
    $this->assertEqual($entity->id(), $node->id(), 'Make sure the right node entity got loaded.');
    // Tests entities as relationships on second level.
    $entity = $view->field['uid']->getEntity($row);
    $this->assertEqual($entity->id(), $account->id(), 'Make sure the right user entity got loaded.');
  }

}
