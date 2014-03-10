<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Entity\FieldEntityTest.
 */

namespace Drupal\views\Tests\Entity;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests the field plugin base integration with the entity system.
 */
class FieldEntityTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_field_get_entity');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'comment');

  public static function getInfo() {
    return array(
      'name' => 'Field: Entity Api Integration',
      'description' => 'Tests the field plugin base integration with the entity system.',
      'group' => 'Views module integration',
    );
  }

  /**
   * Tests the getEntity method.
   */
  public function testGetEntity() {
    // The view is a view of comments, their nodes and their authors, so there
    // are three layers of entities.

    $account = entity_create('user', array('name' => $this->randomName(), 'bundle' => 'user'));
    $account->save();
    $this->container->get('comment.manager')->addDefaultField('node', 'page');
    // Force a flush of the in-memory storage.
    $this->container->get('views.views_data')->clear();

    $node = entity_create('node', array('uid' => $account->id(), 'type' => 'page'));
    $node->save();
    $comment = entity_create('comment', array(
      'uid' => $account->id(),
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment'
    ));
    $comment->save();

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
