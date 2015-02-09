<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\HandlerAllTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\HandlerBase;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Tests instances of all handlers.
 *
 * @group views
 */
class HandlerAllTest extends HandlerTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'aggregator',
    'book',
    'block',
    'comment',
    'contact',
    'field',
    'filter',
    'file',
    'forum',
    'history',
    'language',
    'locale',
    'node',
    'search',
    'statistics',
    'taxonomy',
    'user',
  );

  /**
   * Tests most of the handlers.
   */
  public function testHandlers() {
    $this->drupalCreateContentType(array('type' => 'article'));
    $this->addDefaultCommentField('node', 'article');

    $object_types = array_keys(ViewExecutable::getHandlerTypes());
    foreach ($this->container->get('views.views_data')->get() as $base_table => $info) {
      if (!isset($info['table']['base'])) {
        continue;
      }

      $view = entity_create('view', array('base_table' => $base_table));
      $view = $view->getExecutable();

      // @todo The groupwise relationship is currently broken.
      $exclude[] = 'taxonomy_term_data:tid_representative';
      $exclude[] = 'users:uid_representative';

      // Go through all fields and there through all handler types.
      foreach ($info as $field => $field_info) {
        // Table is a reserved key for the metainformation.
        if ($field != 'table' && !in_array("$base_table:$field", $exclude)) {
          $item = array(
            'table' => $base_table,
            'field' => $field,
          );
          foreach ($object_types as $type) {
            if (isset($field_info[$type]['id'])) {
              $options = array();
              if ($type == 'filter') {
                $handler = $this->container->get("plugin.manager.views.$type")->getHandler($item);
                if ($handler instanceof InOperator) {
                  $options['value'] = array(1);
                }
              }
              $view->addHandler('default', $type, $base_table, $field, $options);
            }
          }
        }
      }

      // Go through each step invidiually to see whether some parts are failing.
      $view->build();
      $view->preExecute();
      $view->execute();
      $view->render();

      // Make sure all handlers extend the HandlerBase.
      foreach ($object_types as $type) {
        if (isset($view->{$type})) {
          foreach ($view->{$type} as $handler) {
            $this->assertTrue($handler instanceof HandlerBase, format_string(
              '@type handler of class %class is an instance of HandlerBase',
              array(
                '@type' => $type,
                '%class' => get_class($handler),
              )));
          }
        }
      }
    }
  }

}
