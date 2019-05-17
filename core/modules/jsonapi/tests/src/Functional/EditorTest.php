<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;

/**
 * JSON:API integration test for the "Editor" config entity type.
 *
 * @group jsonapi
 */
class EditorTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['filter', 'editor', 'ckeditor'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'editor';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'editor--editor';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\editor\EditorInterface
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
    // Create a "Llama" filter format.
    $llama_format = FilterFormat::create([
      'name' => 'Llama',
      'format' => 'llama',
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

    $llama_format->save();

    // Create a "Camelids" editor.
    $camelids = Editor::create([
      'format' => 'llama',
      'editor' => 'ckeditor',
    ]);
    $camelids
      ->setImageUploadSettings([
        'status' => FALSE,
        'scheme' => 'public',
        'directory' => 'inline-images',
        'max_size' => '',
        'max_dimensions' => [
          'width' => '',
          'height' => '',
        ],
      ])
      ->save();

    return $camelids;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/editor/editor/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'editor--editor',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'dependencies' => [
            'config' => [
              'filter.format.llama',
            ],
            'module' => [
              'ckeditor',
            ],
          ],
          'editor' => 'ckeditor',
          'image_upload' => [
            'status' => FALSE,
            'scheme' => 'public',
            'directory' => 'inline-images',
            'max_size' => '',
            'max_dimensions' => [
              'width' => NULL,
              'height' => NULL,
            ],
          ],
          'langcode' => 'en',
          'settings' => [
            'toolbar' => [
              'rows' => [
                [
                  [
                    'name' => 'Formatting',
                    'items' => [
                      'Bold',
                      'Italic',
                    ],
                  ],
                  [
                    'name' => 'Links',
                    'items' => [
                      'DrupalLink',
                      'DrupalUnlink',
                    ],
                  ],
                  [
                    'name' => 'Lists',
                    'items' => [
                      'BulletedList',
                      'NumberedList',
                    ],
                  ],
                  [
                    'name' => 'Media',
                    'items' => [
                      'Blockquote',
                      'DrupalImage',
                    ],
                  ],
                  [
                    'name' => 'Tools',
                    'items' => [
                      'Source',
                    ],
                  ],
                ],
              ],
            ],
            'plugins' => [
              'language' => [
                'language_list' => 'un',
              ],
            ],
          ],
          'status' => TRUE,
          'drupal_internal__format' => 'llama',
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

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return "The 'administer filters' permission is required.";
  }

  /**
   * {@inheritdoc}
   */
  protected function createAnotherEntity($key) {
    FilterFormat::create([
      'name' => 'Pachyderm',
      'format' => 'pachyderm',
      'langcode' => 'fr',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <a> <b> <lo>',
          ],
        ],
      ],
    ])->save();

    $entity = Editor::create([
      'format' => 'pachyderm',
      'editor' => 'ckeditor',
    ]);

    $entity->setImageUploadSettings([
      'status' => FALSE,
      'scheme' => 'public',
      'directory' => 'inline-images',
      'max_size' => '',
      'max_dimensions' => [
        'width' => '',
        'height' => '',
      ],
    ])->save();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected static function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Also reset the 'filter_format' entity access control handler because
    // editor access also depends on access to the configured filter format.
    \Drupal::entityTypeManager()->getAccessControlHandler('filter_format')->resetCache();
    return parent::entityAccess($entity, $operation, $account);
  }

}
