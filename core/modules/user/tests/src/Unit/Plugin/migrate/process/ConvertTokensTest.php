<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Plugin\migrate\process\ConvertTokensTest.
 */

namespace Drupal\Tests\user\Unit\Plugin\migrate\process;

use Drupal\user\Plugin\migrate\process\ConvertTokens;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the ConvertTokens plugin.
 *
 * @group user
 */
class ConvertTokensTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->plugin = new ConvertTokens([], 'convert_tokens', []);
  }

  /**
   * Tests conversion of user tokens.
   */
  public function testConvertTokens() {
    $value = $this->plugin->transform('Account details for !username at !site', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals('Account details for [user:name] at [site:name]', $value);
  }

  /**
   * Tests conversion of user tokens with a NULL value.
   */
  public function testConvertTokensNull() {
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals('', $value);
  }

}
