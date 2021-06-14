<?php

namespace Drupal\Tests\editor\Functional\Rest;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

/**
 * ResourceTestBase for Editor entity.
 */
abstract class EditorResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ckeditor', 'editor'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'editor';

  /**
   * The Editor entity.
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
  protected function getExpectedNormalizedEntity() {
    return [
      'dependencies' => [
        'config' => [
          'filter.format.llama',
        ],
        'module' => [
          'ckeditor',
        ],
      ],
      'editor' => 'ckeditor',
      'format' => 'llama',
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
    // @see ::createEntity()
    return ['user.permissions'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return "The 'administer filters' permission is required.";
  }

}
