<?php

namespace Drupal\Tests\link\Unit\Plugin\migrate\field\d6;

/**
 * @group legacy
 * @group link
 */
class LinkFieldLegacyTest extends LinkFieldTest {

  /**
   * @expectedDeprecation Deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use defineValueProcessPipeline() instead. See https://www.drupal.org/node/2944598.
   */
  public function testDefineValueProcessPipeline($method = 'processFieldValues') {
    parent::testDefineValueProcessPipeline($method);
  }

}
