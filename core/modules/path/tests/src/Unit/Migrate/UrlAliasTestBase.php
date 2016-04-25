<?php

namespace Drupal\Tests\path\Unit\Migrate;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Base class for url_alias source tests.
 */
abstract class UrlAliasTestBase extends MigrateSqlSourceTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['url_alias'] = $this->expectedResults;
    parent::setUp();
  }

}
