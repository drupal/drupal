<?php

declare(strict_types=1);

namespace Drupal\mailer_capture\Transport;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Defines a mail transport that captures sent messages in a key value store.
 *
 * This class is for running tests or for development.
 */
class CaptureTransport extends AbstractTransport implements TransportInterface {

  /**
   * Key value factory.
   */
  protected KeyValueFactoryInterface $keyValueFactory;

  /**
   * Set key value factory.
   */
  #[Required]
  public function setKeyValueFactory(KeyValueFactoryInterface $keyValueFactory): void {
    $this->keyValueFactory = $keyValueFactory;
  }

  /**
   * {@inheritdoc}
   */
  protected function doSend(SentMessage $message): void {
    $keyValueStore = $this->keyValueFactory->get('mailer_capture');
    $capturedMails = $keyValueStore->get('messages', []);
    $capturedMails[] = $message;
    $keyValueStore->set('messages', $capturedMails);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return 'drupal.test-capture';
  }

}
