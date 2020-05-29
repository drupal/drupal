<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;

/**
 * JSON:API integration test for the "FilterFormat" config entity type.
 *
 * @group jsonapi
 */
class FilterFormatTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['filter'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'filter_format';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'filter_format--filter_format';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer filters']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $pablo_format = FilterFormat::create([
      'name' => 'Pablo Picasso',
      'format' => 'pablo',
      'langcode' => 'es',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <a> <b> <lo>',
          ],
        ],
      ],
    ]);
    $pablo_format->save();
    return $pablo_format;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/filter_format/filter_format/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'filter_format--filter_format',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'dependencies' => [],
          'filters' => [
            'filter_html' => [
              'id' => 'filter_html',
              'provider' => 'filter',
              'status' => TRUE,
              'weight' => -10,
              'settings' => [
                'allowed_html' => '<p> <a> <b> <lo>',
                'filter_html_help' => TRUE,
                'filter_html_nofollow' => FALSE,
              ],
            ],
          ],
          'langcode' => 'es',
          'name' => 'Pablo Picasso',
          'status' => TRUE,
          'weight' => 0,
          'drupal_internal__format' => 'pablo',
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
