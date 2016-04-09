<?php

namespace Drupal\Tests\path\Unit\Migrate\d6;

use Drupal\Tests\path\Unit\Migrate\UrlAliasTestBase;

/**
 * Tests the d6_url_alias source plugin.
 *
 * @group path
 */
class UrlAliasTest extends UrlAliasTestBase {

  const PLUGIN_CLASS = 'Drupal\path\Plugin\migrate\source\d6\UrlAlias';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'highWaterProperty' => array('field' => 'test'),
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

}
