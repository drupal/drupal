<?php

namespace Drupal\Tests\datetime\Unit\Plugin\migrate\field\d6;

/**
 * @group migrate
 * @group legacy
 */
class DateFieldLegacyTest extends DateFieldTest {

  /**
   * @expectedDeprecation Deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use defineValueProcessPipeline() instead. See https://www.drupal.org/node/2944598.
   */
  public function testUnknownDateType($method = 'processFieldValues') {
    parent::testUnknownDateType($method);
  }

}
