<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Kernel\Plugin\migrate\field\d7;

use Drupal\KernelTests\KernelTestBase;
use Drupal\link\LinkTitleVisibility;
use Drupal\link\Plugin\migrate\field\d7\LinkField;
use Drupal\migrate\Plugin\MigrationInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Prophecy\Argument;

/**
 * Tests Drupal\link\Plugin\migrate\field\d7\LinkField.
 */
#[CoversClass(LinkField::class)]
#[Group('link')]
#[RunTestsInSeparateProcesses]
class LinkFieldTest extends KernelTestBase {

  /**
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldInterface
   */
  protected $plugin;

  /**
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = new LinkField([], 'link', []);

    $migration = $this->prophesize(MigrationInterface::class);

    // The plugin's alterFieldInstanceMigration() method will call
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
   * Tests alter field instance migration.
   */
  public function testAlterFieldInstanceMigration($method = 'alterFieldInstanceMigration'): void {
    $this->plugin->$method($this->migration);

    $expected = [
      'plugin' => 'static_map',
      'source' => 'settings/title',
      'bypass' => TRUE,
      'map' => [
        'disabled' => LinkTitleVisibility::Disabled->value,
        'optional' => LinkTitleVisibility::Optional->value,
        'required' => LinkTitleVisibility::Required->value,
      ],
    ];
    $this->assertSame($expected, $this->migration->getProcess());
  }

}
