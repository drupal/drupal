<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\config_test\Entity\ConfigTest;
use Drupal\Core\Url;

/**
 * JSON:API integration test for the "ConfigTest" config entity type.
 *
 * @group jsonapi
 */
class ConfigTestTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['config_test', 'config_test_rest'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'config_test';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'config_test--config_test';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\config_test\ConfigTestInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['view config_test']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    switch ($method) {
      case 'GET':
        return "The 'view config_test' permission is required.";

      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $config_test = ConfigTest::create([
      'id' => 'llama',
      'label' => 'Llama',
    ]);
    $config_test->save();

    return $config_test;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/config_test/config_test/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'config_test--config_test',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'weight' => 0,
          'langcode' => 'en',
          'status' => TRUE,
          'dependencies' => [],
          'label' => 'Llama',
          'style' => NULL,
          'size' => NULL,
          'size_value' => NULL,
          'protected_property' => NULL,
          'drupal_internal__id' => 'llama',
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
