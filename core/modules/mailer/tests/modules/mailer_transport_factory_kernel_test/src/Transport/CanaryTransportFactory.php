<?php

declare(strict_types=1);

namespace Drupal\mailer_transport_factory_kernel_test\Transport;

use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * A transport factory only used to test the transport factory adapter.
 */
class CanaryTransportFactory extends AbstractTransportFactory implements TransportFactoryInterface {

  protected function getSupportedSchemes(): array {
    return ['drupal.test-canary'];
  }

  /**
   * {@inheritdoc}
   */
  public function create(Dsn $dsn): TransportInterface {
    if ($dsn->getScheme() === 'drupal.test-canary') {
      return new CanaryTransport($this->dispatcher, $this->logger);
    }

    throw new UnsupportedSchemeException($dsn, 'test_canary', $this->getSupportedSchemes());
  }

}
