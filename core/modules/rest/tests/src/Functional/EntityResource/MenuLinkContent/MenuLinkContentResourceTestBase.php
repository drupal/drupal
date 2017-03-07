<?php

namespace Drupal\Tests\rest\Functional\EntityResource\MenuLinkContent;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

/**
 * ResourceTestBase for MenuLinkContent entity.
 */
abstract class MenuLinkContentResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_link_content'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'menu_link_content';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'changed',
  ];

  /**
   * The MenuLinkContent entity.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
      case 'POST':
      case 'PATCH':
      case 'DELETE':
        $this->grantPermissionsToTestedRole(['administer menu']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $menu_link = MenuLinkContent::create([
      'id' => 'llama',
      'title' => 'Llama Gabilondo',
      'description' => 'Llama Gabilondo',
      'link' => 'https://nl.wikipedia.org/wiki/Llama',
      'weight' => 0,
      'menu_name' => 'main',
    ]);
    $menu_link->save();

    return $menu_link;
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return [
      'title' => [
        [
          'value' => 'Dramallama',
        ],
      ],
      'link' => [
        [
          'uri' => 'http://www.urbandictionary.com/define.php?term=drama%20llama',
        ],
      ],
      'bundle' => [
        [
          'value' => 'menu_link_content',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'uuid' => [
        [
          'value' => $this->entity->uuid(),
        ],
      ],
      'id' => [
        [
          'value' => 1,
        ],
      ],
      'title' => [
        [
          'value' => 'Llama Gabilondo',
        ],
      ],
      'link' => [
        [
          'uri' => 'https://nl.wikipedia.org/wiki/Llama',
          'title' => NULL,
          'options' => [],
        ],
      ],
      'weight' => [
        [
          'value' => 0,
        ],
      ],
      'menu_name' => [
        [
          'value' => 'main',
        ],
      ],
      'langcode' => [
        [
          'value' => 'en',
        ],
      ],
      'bundle' => [
        [
          'value' => 'menu_link_content',
        ],
      ],
      'description' => [
        [
          'value' => 'Llama Gabilondo',
        ],
      ],
      'external' => [
        [
          'value' => FALSE,
        ],
      ],
      'rediscover' => [
        [
          'value' => FALSE,
        ],
      ],
      'expanded' => [
        [
          'value' => FALSE,
        ],
      ],
      'enabled' => [
        [
          'value' => TRUE,
        ],
      ],
      'changed' => [
        [
          'value' => $this->entity->getChangedTime(),
        ],
      ],
      'default_langcode' => [
        [
          'value' => TRUE,
        ],
      ],
      'parent' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    switch ($method) {
      case 'DELETE':
        return "You are not authorized to delete this menu_link_content entity.";
      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

}
