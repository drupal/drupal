<?php

namespace Drupal\KernelTests\Core\Common;

use Drupal\KernelTests\KernelTestBase;

/**
 * @covers ::drupal_set_message
 * @group Common
 * @group legacy
 */
class DrupalSetMessageTest extends KernelTestBase {

  /**
   * The basic functionality of drupal_set_message().
   *
   * @expectedDeprecation drupal_set_message() is deprecated in Drupal 8.5.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Messenger\MessengerInterface::addMessage() instead. See https://www.drupal.org/node/2774931
   * @expectedDeprecation drupal_get_message() is deprecated in Drupal 8.5.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Messenger\MessengerInterface::all() or \Drupal\Core\Messenger\MessengerInterface::messagesByType() instead. See https://www.drupal.org/node/2774931
   */
  public function testDrupalSetMessage() {
    drupal_set_message(t('A message: @foo', ['@foo' => 'bar']));
    $messages = drupal_get_messages();
    $this->assertInstanceOf('Drupal\Core\Render\Markup', $messages['status'][0]);
    $this->assertEquals('A message: bar', (string) $messages['status'][0]);
  }

}
