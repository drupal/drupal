<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\views\Entity\View;

/**
 * JSON:API integration test for the "View" config entity type.
 *
 * @group jsonapi
 */
class ViewTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'view';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'view--view';

  /**
   * {@inheritdoc}
   *
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
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/view/view/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'view--view',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'base_field' => 'nid',
          'base_table' => 'node',
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
          'label' => 'Test REST',
          'langcode' => 'en',
          'module' => 'views',
          'status' => TRUE,
          'tag' => '',
          'drupal_internal__id' => 'test_rest',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

}
