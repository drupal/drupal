<?php

/**
 * @file
 * Contains \Drupal\Tests\path\Unit\Migrate\d6\UrlAliasTest.
 */

namespace Drupal\Tests\path\Unit\Migrate\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests the D6 url alias migrations.
 *
 * @group path
 */
class UrlAliasTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\path\Plugin\migrate\source\d6\UrlAlias';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'highWaterProperty' => array('field' => 'test'),
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_url_alias',
    ),
  );

  protected $expectedResults = array(
    array(
      'pid' => 1,
      'src' => 'node/1',
      'dst' => 'test-article',
      'language' => 'en',
    ),
    array(
      'pid' => 2,
      'src' => 'node/2',
      'dst' => 'another-alias',
      'language' => 'en',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $row) {
      $this->databaseContents['url_alias'][] = $row;
    }
    parent::setUp();
  }

}
