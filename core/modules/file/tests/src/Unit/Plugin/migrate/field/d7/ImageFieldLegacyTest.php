<?php

namespace Drupal\Tests\file\Unit\Plugin\migrate\field\d7;

/**
 * @group legacy
 * @group file
 */
class ImageFieldLegacyTest extends ImageFieldTest {

  /**
   * @expectedDeprecation Deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use defineValueProcessPipeline() instead. See https://www.drupal.org/node/2944598.
   */
  public function testDefineValueProcessPipeline($method = 'processFieldValues') {
    parent::testDefineValueProcessPipeline($method);
  }

}
