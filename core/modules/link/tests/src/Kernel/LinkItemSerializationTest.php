<?php

namespace Drupal\Tests\link\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\link\LinkItemInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests link field serialization.
 *
 * @group link
 */
class LinkItemSerializationTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['link', 'serialization'];

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->serializer = \Drupal::service('serializer');

    // Create a generic, external, and internal link fields for validation.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'type' => 'link',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
      'settings' => ['link_type' => LinkItemInterface::LINK_GENERIC],
    ])->save();
  }

  /**
   * Tests the serialization.
   */
  public function testLinkSerialization() {
    // Create entity.
    $entity = EntityTest::create();
    $url = 'https://www.drupal.org?test_param=test_value';
    $parsed_url = UrlHelper::parse($url);
    $title = $this->randomMachineName();
    $class = $this->randomMachineName();
    $entity->field_test->uri = $parsed_url['path'];
    $entity->field_test->title = $title;
    $entity->field_test->first()
      ->get('options')
      ->set('query', $parsed_url['query']);
    $entity->field_test->first()
      ->get('options')
      ->set('attributes', ['class' => $class]);
    $entity->save();
    $serialized = $this->serializer->serialize($entity, 'json');
    $deserialized = $this->serializer->deserialize($serialized, EntityTest::class, 'json');
    $options_expected = [
      'query' => $parsed_url['query'],
      'attributes' => ['class' => $class],
    ];
    $this->assertSame($options_expected, $deserialized->field_test->options);
  }

  /**
   * Tests the deserialization.
   */
  public function testLinkDeserialization() {
    // Create entity.
    $entity = EntityTest::create();
    $url = 'https://www.drupal.org?test_param=test_value';
    $parsed_url = UrlHelper::parse($url);
    $title = $this->randomMachineName();
    $entity->field_test->uri = $parsed_url['path'];
    $entity->field_test->title = $title;
    $entity->field_test->first()
      ->get('options')
      ->set('query', $parsed_url['query']);
    $json = json_decode($this->serializer->serialize($entity, 'json'), TRUE);
    $json['field_test'][0]['options'] = 'string data';
    $serialized = json_encode($json, TRUE);
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The generic FieldItemNormalizer cannot denormalize string values for "options" properties of the "field_test" field (field item class: Drupal\link\Plugin\Field\FieldType\LinkItem).');
    $this->serializer->deserialize($serialized, EntityTest::class, 'json');
  }

}
