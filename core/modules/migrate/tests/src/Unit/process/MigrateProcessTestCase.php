<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Unit\MigrateTestCase;

/**
 * Base class for the Migrate module migrate process unit tests.
 */
abstract class MigrateProcessTestCase extends MigrateTestCase {

  /**
   * The migration process plugin.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $plugin;

  /**
   * A mock of a process row.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * The migration executable or a mock.
   *
   * @var \Drupal\migrate\MigrateExecutable
   */
  protected $migrateExecutable;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->row = $this->createStub(Row::class);
    $this->migrateExecutable = $this->createStub(MigrateExecutableInterface::class);

    parent::setUp();
  }

}
