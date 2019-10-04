<?php

namespace Drupal\Tests\jsonapi\Kernel\ResourceType;

use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;

/**
 * @coversDefaultClass \Drupal\jsonapi\ResourceType\ResourceType
 * @group jsonapi
 *
 * @internal
 */
class ResourceTypeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'serialization',
    'jsonapi',
    'user',
    'text',
    'node',
  ];

  /**
   * Tests construction of a ResourceType using a deprecated $fields argument.
   *
   * @group legacy
   * @expectedDeprecation Passing an array with strings or booleans as a field mapping to Drupal\jsonapi\ResourceType\ResourceType::__construct() is deprecated in Drupal 8.8.0 and will not be allowed in Drupal 9.0.0. See \Drupal\jsonapi\ResourceTypeRepository::getFields(). See https://www.drupal.org/node/3084746.
   * @covers ::__construct
   * @covers ::updateDeprecatedFieldMapping
   */
  public function testUpdateDeprecatedFieldMapping() {
    $deprecated_field_mapping = [
      'uid' => 'author',
      'body' => FALSE,
    ];
    $resource_type = new ResourceType('node', 'article', Node::class, FALSE, TRUE, TRUE, FALSE, $deprecated_field_mapping);
    $this->assertSame('author', $resource_type->getFieldByInternalName('uid')->getPublicName());
    $this->assertFalse($resource_type->getFieldByInternalName('body')->isFieldEnabled());
  }

}
