<?php

/**
 * @file
 * Contains \Drupal\Tests\path\Unit\Migrate\d7\UrlAliasTest.
 */

namespace Drupal\Tests\path\Unit\Migrate\d7;

use Drupal\Tests\path\Unit\Migrate\UrlAliasTestBase;

/**
 * Tests the d7_url_alias source plugin.
 *
 * @group path
 */
class UrlAliasTest extends UrlAliasTestBase {

  const PLUGIN_CLASS = 'Drupal\path\Plugin\migrate\source\d7\UrlAlias';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd7_url_alias',
    ),
  );

  protected $expectedResults = array(
    array(
      'pid' => 1,
      'source' => 'node/1',
      'alias' => 'test-article',
      'language' => 'en',
    ),
    array(
      'pid' => 2,
      'source' => 'node/2',
      'alias' => 'another-alias',
      'language' => 'en',
    ),
  );

}
