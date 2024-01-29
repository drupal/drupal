<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\comment\Entity\CommentType;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\HandlerBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\Entity\View;

/**
 * Tests instances of all handlers.
 *
 * @group views
 */
class HandlerAllTest extends ViewsKernelTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'comment',
    'contact',
    'dblog',
    'field',
    'filter',
    'file',
    'forum',
    'history',
    'image',
    'language',
    'locale',
    'media',
    'node',
    'search',
    'system',
    'options',
    'taxonomy',
    'text',
    'tracker',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests most of the handlers.
   */
  public function testHandlers(): void {
    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('forum', ['forum_index']);
    $this->installSchema('dblog', ['watchdog']);
    $this->installSchema('tracker', ['tracker_user']);
    // Create the comment body field storage.
    FieldStorageConfig::create([
      'type' => 'text_long',
      'entity_type' => 'comment',
      'field_name' => 'comment_body',
    ])->save();

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    CommentType::create(['id' => 'comment', 'label' => 'Default comment', 'target_entity_type_id' => 'node'])->save();
    $this->addDefaultCommentField('node', 'article');

    $object_types = array_keys(ViewExecutable::getHandlerTypes());
    foreach ($this->container->get('views.views_data')->getAll() as $base_table => $info) {
      if (!isset($info['table']['base'])) {
        continue;
      }

      $view_config = View::create(['base_table' => $base_table]);
      $view = $view_config->getExecutable();

      // @todo The groupwise relationship is currently broken.
      $exclude[] = 'taxonomy_term_field_data:tid_representative';
      $exclude[] = 'users_field_data:uid_representative';

      // Go through all fields and there through all handler types.
      foreach ($info as $field => $field_info) {
        // Table is a reserved key for the meta-information.
        if ($field != 'table' && !in_array("$base_table:$field", $exclude)) {
          $item = [
            'table' => $base_table,
            'field' => $field,
          ];
          foreach ($object_types as $type) {
            if (isset($field_info[$type]['id'])) {
              $options = [];
              if ($type == 'filter') {
                $handler = $this->container->get("plugin.manager.views.$type")->getHandler($item);
                // Set the value to use for the filter based on the filter type.
                if ($handler instanceof InOperator) {
                  $options['value'] = [1];
                }
                elseif ($handler instanceof NumericFilter) {
                  $options['value'] = ['value' => 1];
                }
                else {
                  $options['value'] = 1;
                }
              }
              $view->addHandler('default', $type, $base_table, $field, $options);
            }
          }
        }
      }

      // Go through each step individually to see whether some parts are
      // failing.
      $view->build();
      $view->preExecute();
      $view->execute();
      $view->render();

      // Make sure all handlers extend the HandlerBase.
      foreach ($object_types as $type) {
        if (isset($view->{$type})) {
          foreach ($view->{$type} as $handler) {
            $this->assertInstanceOf(HandlerBase::class, $handler);
          }
        }
      }
    }
  }

}
