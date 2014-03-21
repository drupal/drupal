<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Entity\ViewEntityDependenciesTest.
 */

namespace Drupal\views\Tests\Entity;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests \Drupal\views\Entity\View::calculateDependencies().
 */
class ViewEntityDependenciesTest extends ViewTestBase {

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
      'name' => 'View dependencies test',
      'description' => 'Tests the calculation of dependencies for views.',
      'group' => 'Views module integration',
    );
  }

  /**
   * Tests the calculateDependencies method.
   */
  public function testCalculateDependencies() {
    // The view is a view of comments, their nodes and their authors, so there
    // are three layers of entities.
    $account = entity_create('user', array('name' => $this->randomName(), 'bundle' => 'user'));
    $account->save();
    $this->drupalCreateContentType(array('type' => 'page'));
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

    $expected_dependencies = array(
      'module' => array(
        'comment',
        'node',
        'user',
      )
    );
    $dependencies = $view->calculateDependencies();
    $this->assertEqual($expected_dependencies, $dependencies);
  }

}
