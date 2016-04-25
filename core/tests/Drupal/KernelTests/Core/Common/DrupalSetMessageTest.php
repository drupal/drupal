<?php

namespace Drupal\KernelTests\Core\Common;

use Drupal\KernelTests\KernelTestBase;

/**
 * @covers ::drupal_set_message
 * @group PHPUnit
 */
class DrupalSetMessageTest extends KernelTestBase {

  /**
   * The basic functionality of drupal_set_message().
   */
  public function testDrupalSetMessage() {
    drupal_set_message(t('A message: @foo', ['@foo' => 'bar']));
    $messages = drupal_get_messages();
    $this->assertInstanceOf('Drupal\Core\Render\Markup', $messages['status'][0]);
    $this->assertEquals('A message: bar', (string) $messages['status'][0]);
  }

  protected function tearDown() {
    // Clear session to prevent global leakage.
    unset($_SESSION['messages']);
    parent::tearDown();
  }

}
