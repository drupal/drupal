<?php

namespace Drupal\Tests\text\Unit\Plugin\migrate\field\d6;

/**
 * @coversDefaultClass \Drupal\text\Plugin\migrate\field\d6\TextField
 * @group text
 * @group legacy
 */
class TextFieldLegacyTest extends TextFieldTest {

  /**
   * @covers ::processFieldValues
   * @expectedDeprecation Deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use defineValueProcessPipeline() instead. See https://www.drupal.org/node/2944598.
   */
  public function testProcessFilteredTextFieldValues($method = 'processFieldValues') {
    parent::testProcessFilteredTextFieldValues($method);
  }

  /**
   * @covers ::processFieldValues
   * @expectedDeprecation Deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use defineValueProcessPipeline() instead. See https://www.drupal.org/node/2944598.
   */
  public function testProcessBooleanTextImplicitValues($method = 'processFieldValues') {
    parent::testProcessBooleanTextImplicitValues($method);
  }

  /**
   * @covers ::processFieldValues
   * @expectedDeprecation Deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use defineValueProcessPipeline() instead. See https://www.drupal.org/node/2944598.
   */
  public function testProcessBooleanTextExplicitValues($method = 'processFieldValues') {
    parent::testProcessBooleanTextExplicitValues($method);
  }

}
