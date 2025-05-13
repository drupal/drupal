<?php

declare(strict_types=1);

namespace Drupal\mailer_transport_factory_kernel_test\Transport;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * A transport only used to test the transport factory adapter.
 */
class CanaryTransport extends AbstractTransport implements TransportInterface {

  protected function doSend(SentMessage $message): void {
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return 'drupal.test-canary://default';
  }

}
