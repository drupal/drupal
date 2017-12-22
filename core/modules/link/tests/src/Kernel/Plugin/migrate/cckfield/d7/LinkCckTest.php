<?php

namespace Drupal\Tests\link\Kernel\Plugin\migrate\cckfield\d7;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\link\Plugin\migrate\cckfield\d7\LinkField;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\link\Plugin\migrate\cckfield\d7\LinkField
 * @group link
 * @group legacy
 */
class LinkCckTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface
   */
  protected $plugin;

  /**
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->plugin = new LinkField([], 'link', []);

    $migration = $this->prophesize(MigrationInterface::class);

    // The plugin's processFieldInstance() method will call
    // mergeProcessOfProperty() and return nothing. So, in order to examine the
    // process pipeline created by the plugin, we need to ensure that
    // getProcess() always returns the last input to mergeProcessOfProperty().
    $migration->mergeProcessOfProperty(Argument::type('string'), Argument::type('array'))
      ->will(function ($arguments) use ($migration) {
        $migration->getProcess()->willReturn($arguments[1]);
      });

    $this->migration = $migration->reveal();
  }

  /**
   * @covers ::processCckFieldValues
   * @expectedDeprecation CckFieldPluginBase is deprecated in Drupal 8.3.x and will be be removed before Drupal 9.0.x. Use \Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase instead.
   * @expectedDeprecation MigrateCckFieldInterface is deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.x. Use \Drupal\migrate_drupal\Annotation\MigrateField instead.
   */
  public function testProcessCckFieldValues() {
    $this->plugin->processFieldInstance($this->migration);

    $expected = [
      'plugin' => 'static_map',
      'source' => 'settings/title',
      'bypass' => TRUE,
      'map' => [
        'disabled' => DRUPAL_DISABLED,
        'optional' => DRUPAL_OPTIONAL,
        'required' => DRUPAL_REQUIRED,
      ],
    ];
    $this->assertSame($expected, $this->migration->getProcess());
  }

}
