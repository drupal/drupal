<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Base class for tests of Migrate source plugins.
 */
abstract class MigrateSourceTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate'];

  /**
   * The mocked migration.
   *
   * @var MigrationInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $migration;

  /**
   * The source plugin under test.
   *
   * @var \Drupal\migrate\Plugin\MigrateSourceInterface
   */
  protected $plugin;

  /**
   * The data provider.
   *
   * @see \Drupal\Tests\migrate\Kernel\MigrateSourceTestBase::testSource
   *
   * @return array
   *   Array of data sets to test, each of which is a numerically indexed array
   *   with the following elements:
   *   - An array of source data, which can be optionally processed and set up
   *     by subclasses.
   *   - An array of expected result rows.
   *   - (optional) The number of result rows the plugin under test is expected
   *     to return. If this is not a numeric value, the plugin will not be
   *     counted.
   *   - (optional) Array of configuration options for the plugin under test.
   */
  abstract public function providerSource();

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a mock migration. This will be injected into the source plugin
    // under test.
    $this->migration = $this->prophesize(MigrationInterface::class);

    $this->migration->id()->willReturn(
      $this->randomMachineName(16)
    );
    // Prophesize a useless ID map plugin and an empty set of destination IDs.
    // Calling code can override these prophecies later and set up different
    // behaviors.
    $this->migration->getIdMap()->willReturn(
      $this->prophesize(MigrateIdMapInterface::class)->reveal()
    );
    $this->migration->getDestinationIds()->willReturn([]);
  }

  /**
   * Determines the plugin to be tested by reading the class @covers annotation.
   *
   * @return string
   */
  protected function getPluginClass() {
    $annotations = $this->getAnnotations();

    if (isset($annotations['class']['covers'])) {
      return $annotations['class']['covers'][0];
    }
    else {
      $this->fail('No plugin class was specified');
    }
  }

  /**
   * Instantiates the source plugin under test.
   *
   * @param array $configuration
   *   The source plugin configuration.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface|object
   *   The fully configured source plugin.
   */
  protected function getPlugin(array $configuration) {
    // Only create the plugin once per test.
    if ($this->plugin) {
      return $this->plugin;
    }

    $class = ltrim($this->getPluginClass(), '\\');

    /** @var \Drupal\migrate\Plugin\MigratePluginManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.migrate.source');

    foreach ($plugin_manager->getDefinitions() as $id => $definition) {
      if (ltrim($definition['class'], '\\') == $class) {
        $this->plugin = $plugin_manager
          ->createInstance($id, $configuration, $this->migration->reveal());

        $this->migration
          ->getSourcePlugin()
          ->willReturn($this->plugin);

        return $this->plugin;
      }
    }
    $this->fail('No plugin found for class ' . $class);
  }

  /**
   * Tests the source plugin against a particular data set.
   *
   * @param array $source_data
   *   The source data that the source plugin will read.
   * @param array $expected_data
   *   The result rows the source plugin is expected to return.
   * @param mixed $expected_count
   *   (optional) How many rows the source plugin is expected to return.
   *   Defaults to count($expected_data). If set to a non-null, non-numeric
   *   value (like FALSE or 'nope'), the source plugin will not be counted.
   * @param array $configuration
   *   (optional) Configuration for the source plugin.
   * @param mixed $high_water
   *   (optional) The value of the high water field.
   *
   * @dataProvider providerSource
   */
  public function testSource(array $source_data, array $expected_data, $expected_count = NULL, array $configuration = [], $high_water = NULL) {
    $plugin = $this->getPlugin($configuration);

    // All source plugins must define IDs.
    $this->assertNotEmpty($plugin->getIds());

    // If there is a high water mark, set it in the high water storage.
    if (isset($high_water)) {
      $this->container
        ->get('keyvalue')
        ->get('migrate:high_water')
        ->set($this->migration->reveal()->id(), $high_water);
    }

    if (is_null($expected_count)) {
      $expected_count = count($expected_data);
    }
    // If an expected count was given, assert it only if the plugin is
    // countable.
    if (is_numeric($expected_count)) {
      $this->assertInstanceOf('\Countable', $plugin);
      $this->assertCount($expected_count, $plugin);
    }

    $i = 0;
    /** @var \Drupal\migrate\Row $row */
    foreach ($plugin as $row) {
      $this->assertInstanceOf(Row::class, $row);

      $expected = $expected_data[$i++];
      $actual = $row->getSource();

      foreach ($expected as $key => $value) {
        $this->assertArrayHasKey($key, $actual);

        if (is_array($value)) {
          ksort($value);
          ksort($actual[$key]);
          $this->assertEquals($value, $actual[$key]);
        }
        else {
          $this->assertSame((string) $value, (string) $actual[$key]);
        }
      }
    }
    // False positives occur if the foreach is not entered. So, confirm the
    // foreach loop was entered if the expected count is greater than 0.
    if ($expected_count > 0) {
      $this->assertGreaterThan(0, $i);
    }
  }

}
