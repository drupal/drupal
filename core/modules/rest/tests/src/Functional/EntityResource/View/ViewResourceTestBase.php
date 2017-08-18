<?php

namespace Drupal\Tests\rest\Functional\EntityResource\View;

use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\views\Entity\View;

abstract class ViewResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['views'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'view';

  /**
   * @var \Drupal\views\ViewEntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer views']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $view = View::create([
      'id' => 'test_rest',
      'label' => 'Test REST',
    ]);
    $view->save();
    return $view;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'base_field' => 'nid',
      'base_table' => 'node',
      'core' => '8.x',
      'dependencies' => [],
      'description' => '',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_title' => 'Master',
          'position' => 0,
          'display_options' => [
            'display_extenders' => [],
          ],
          'cache_metadata' => [
            'max-age' => -1,
            'contexts' => [
              'languages:language_interface',
              'url.query_args',
            ],
            'tags' => [],
          ],
        ],
      ],
      'id' => 'test_rest',
      'label' => 'Test REST',
      'langcode' => 'en',
      'module' => 'views',
      'status' => TRUE,
      'tag' => '',
      'uuid' => $this->entity->uuid(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    return [
      'user.permissions',
    ];
  }

}
