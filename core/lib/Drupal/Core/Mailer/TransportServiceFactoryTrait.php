<?php

declare(strict_types=1);

namespace Drupal\Core\Mailer;

use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * A trait containing helper methods for transport service construction.
 */
trait TransportServiceFactoryTrait {

  /**
   * A list of transport factories.
   *
   * @var Iterable<TransportFactoryInterface>
   */
  protected iterable $factories;

  /**
   * Constructs a transport instance given a DSN object.
   *
   * @param \Symfony\Component\Mailer\Transport\Dsn $dsn
   *   The mailer DSN object.
   *
   * @throws \Symfony\Component\Mailer\Exception\IncompleteDsnException
   * @throws \Symfony\Component\Mailer\Exception\UnsupportedSchemeException
   */
  protected function fromDsnObject(Dsn $dsn): TransportInterface {
    foreach ($this->factories as $factory) {
      if ($factory->supports($dsn)) {
        return $factory->create($dsn);
      }
    }

    throw new UnsupportedSchemeException($dsn);
  }

}
