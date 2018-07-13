<?php

namespace Drupal\Tests\link\Kernel\Plugin\migrate\cckfield\d7;

/**
 * @group link
 * @group legacy
 */
class LinkCckDeprecationTest extends LinkCckTest {

  /**
   * @expectedDeprecation Deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use alterFieldInstanceMigration() instead. See https://www.drupal.org/node/2944598.
   */
  public function testAlterFieldInstanceMigration($method = 'processFieldInstance') {
    parent::testAlterFieldInstanceMigration($method);
  }

}
