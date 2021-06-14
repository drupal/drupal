<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;

/**
 * Tests the Log process plugin.
 *
 * @group migrate
 */
class LogTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'migrate'];

  /**
   * The Log process plugin.
   *
   * @var \Drupal\migrate\Plugin\migrate\process\Log
   */
  protected $logPlugin;

  /**
   * Migrate executable.
   *
   * @var \Drupal\Tests\migrate\Kernel\Plugin\TestMigrateExecutable
   */
  protected $executable;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $definition = [
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          ['id' => '1'],
        ],
        'ids' => [
          'id' => ['type' => 'integer'],
        ],
      ],
      'destination' => [
        'plugin' => 'null',
      ],
    ];

    /** @var \Drupal\migrate\Plugin\migration $migration */
    $migration = \Drupal::service('plugin.manager.migration')
      ->createStubMigration($definition);
    $this->executable = new TestMigrateExecutable($migration);

    // Plugin being tested.
    $this->logPlugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('log');
  }

  /**
   * Tests the Log plugin.
   */
  public function testLog() {
    $values = [
      'nid' => 2,
      'type' => 'page',
      'title' => 'page',
    ];

    $node = new Node($values, 'node', 'test');
    $node_array = <<< NODE
Array
(
    [nid] => Array
        (
        )

    [uuid] => Array
        (
        )

    [vid] => Array
        (
        )

    [langcode] => Array
        (
        )

    [type] => Array
        (
        )

    [revision_timestamp] => Array
        (
        )

    [revision_uid] => Array
        (
        )

    [revision_log] => Array
        (
        )

    [status] => Array
        (
        )

    [uid] => Array
        (
        )

    [title] => Array
        (
        )

    [created] => Array
        (
        )

    [changed] => Array
        (
        )

    [promote] => Array
        (
        )

    [sticky] => Array
        (
        )

    [default_langcode] => Array
        (
        )

    [revision_default] => Array
        (
        )

    [revision_translation_affected] => Array
        (
        )

)

NODE;

    $data = [
      'node' => [
        'value' => $node,
        'expected_message' => "'foo' value is Drupal\\node\Entity\Node:\n'$node_array'",
      ],
      'url' => [
        'value' => Url::fromUri('https://en.wikipedia.org/wiki/Drupal#Community'),
        'expected_message' => "'foo' value is Drupal\Core\Url:\n'https://en.wikipedia.org/wiki/Drupal#Community'",
      ],
    ];

    $i = 1;
    foreach ($data as $datum) {
      $this->executable->sourceIdValues = ['id' => $i++];

      // Test the input value is not altered.
      $new_value = $this->logPlugin->transform($datum['value'], $this->executable, new Row(), 'foo');
      $this->assertSame($datum['value'], $new_value);

      // Test the stored message.
      $message = $this->executable->getIdMap()
        ->getMessages($this->executable->sourceIdValues)
        ->fetchAllAssoc('message');
      $actual_message = key($message);
      $this->assertSame($datum['expected_message'], $actual_message);
    }
  }

}

/**
 * MigrateExecutable test class.
 */
class TestMigrateExecutable extends MigrateExecutable {

  /**
   * The configuration values of the source.
   *
   * @var array
   */
  public $sourceIdValues;

  /**
   * Get the ID map from the current migration.
   *
   * @return \Drupal\migrate\Plugin\MigrateIdMapInterface
   *   The ID map.
   */
  public function getIdMap() {
    return parent::getIdMap();
  }

}
