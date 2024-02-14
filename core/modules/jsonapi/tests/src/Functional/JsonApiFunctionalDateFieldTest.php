<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;

/**
 * JSON:API integration test for the "Date" field.
 *
 * @group jsonapi
 */
class JsonApiFunctionalDateFieldTest extends JsonApiFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'basic_auth',
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FieldStorageConfig::create([
      'field_name' => 'field_datetime',
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => [
        'datetime_type' => 'datetime',
      ],
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_datetime',
      'label' => 'Date and time',
      'entity_type' => 'node',
      'bundle' => 'article',
      'required' => FALSE,
      'settings' => [],
      'description' => '',
    ])->save();
  }

  /**
   * Tests the GET method.
   */
  public function testRead() {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = $this->container->get('date.formatter');

    $timestamp_1 = 5000000;
    $timestamp_2 = 6000000;
    $timestamp_3 = 7000000;
    // Expected: node 1.
    $timestamp_smaller_than_value = $timestamp_2;
    // Expected: node 1 and node 2.
    $timestamp_smaller_than_or_equal_value = $timestamp_2;
    // Expected: node 3.
    $timestamp_greater_than_value = $timestamp_2;
    // Expected: node 2 and node 3.
    $timestamp_greater_than_or_equal_value = $timestamp_2;

    $node_1 = $this->createNode([
      'type' => 'article',
      'uuid' => 'es_test_1',
      'status' => NodeInterface::PUBLISHED,
      'field_datetime' => $date_formatter->format($timestamp_1, 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
    ]);
    $node_2 = $this->createNode([
      'type' => 'article',
      'uuid' => 'es_test_2',
      'status' => NodeInterface::PUBLISHED,
      'field_datetime' => $date_formatter->format($timestamp_2, 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
    ]);
    $node_3 = $this->createNode([
      'type' => 'article',
      'uuid' => 'es_test_3',
      'status' => NodeInterface::PUBLISHED,
      'field_datetime' => $date_formatter->format($timestamp_3, 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
    ]);

    // Checks whether the date is greater than the given timestamp.
    $filter = [
      'filter_datetime' => [
        'condition' => [
          'path' => 'field_datetime',
          'operator' => '>',
          'value' => date(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $timestamp_greater_than_value),
        ],
      ],
    ];
    $output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $output_uuids = array_map(function ($result) {
      return $result['id'];
    }, $output['data']);
    $this->assertCount(1, $output_uuids);
    $this->assertSame([
      $node_3->uuid(),
    ], $output_uuids);

    // Checks whether the date is greater than or equal to the given timestamp.
    $filter = [
      'filter_datetime' => [
        'condition' => [
          'path' => 'field_datetime',
          'operator' => '>=',
          'value' => date(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $timestamp_greater_than_or_equal_value),
        ],
      ],
    ];
    $output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $output_uuids = array_map(function ($result) {
      return $result['id'];
    }, $output['data']);
    $this->assertCount(2, $output_uuids);
    $this->assertSame([
      $node_2->uuid(),
      $node_3->uuid(),
    ], $output_uuids);

    // Checks whether the date is less than the given timestamp.
    $filter = [
      'filter_datetime' => [
        'condition' => [
          'path' => 'field_datetime',
          'operator' => '<',
          'value' => date(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $timestamp_smaller_than_value),
        ],
      ],
    ];
    $output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $output_uuids = array_map(function ($result) {
      return $result['id'];
    }, $output['data']);
    $this->assertCount(1, $output_uuids);
    $this->assertSame([
      $node_1->uuid(),
    ], $output_uuids);

    // Checks whether the date is less than or equal to the given timestamp.
    $filter = [
      'filter_datetime' => [
        'condition' => [
          'path' => 'field_datetime',
          'operator' => '<=',
          'value' => date(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $timestamp_smaller_than_or_equal_value),
        ],
      ],
    ];
    $output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $output_uuids = array_map(function ($result) {
      return $result['id'];
    }, $output['data']);
    $this->assertCount(2, $output_uuids);
    $this->assertSame([
      $node_1->uuid(),
      $node_2->uuid(),
    ], $output_uuids);
  }

}
