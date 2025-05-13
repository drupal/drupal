<?php

declare(strict_types=1);

namespace Drupal\Core\Mailer;

use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * An interface defining mailer transport service factory implementations.
 *
 * The transport service factory is responsible to create a transport instance
 * according to the site configuration. The default service factory looks up the
 * `mailer_dsn` key from the `system.mail` config and returns an appropriate
 * transport implementation.
 *
 * Contrib and custom code may choose to replace or decorate the transport
 * service factory in order to provide a mailer transport instance which
 * requires more complex setup.
 */
interface TransportServiceFactoryInterface {

  /**
   * Creates and returns a configured mailer transport class.
   */
  public function createTransport(): TransportInterface;

}
