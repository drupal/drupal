<?php

namespace Drupal\Tests\views\Functional\Rest;

use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;
use Drupal\views\Entity\View;

abstract class ViewResourceTestBase extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views', 'views_ui'];

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
      'dependencies' => [],
      'description' => '',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_title' => 'Default',
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
    return [];
  }

}
