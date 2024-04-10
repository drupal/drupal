<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

/**
 * Base class for driver specific database tests.
 */
abstract class DriverSpecificDatabaseTestBase extends DriverSpecificKernelTestBase {

  use DatabaseTestSchemaDataTrait;
  use DatabaseTestSchemaInstallTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['database_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSampleSchema();
    $this->addSampleData();
  }

}
