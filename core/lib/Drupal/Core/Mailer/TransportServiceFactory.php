<?php

declare(strict_types=1);

namespace Drupal\Core\Mailer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * The default mailer transport service factory.
 */
class TransportServiceFactory implements TransportServiceFactoryInterface {

  use TransportServiceFactoryTrait;

  /**
   * Constructs a new transport service factory.
   *
   * @param Iterable<TransportFactoryInterface> $factories
   *   A list of transport factories.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(
    #[AutowireIterator(tag: 'mailer.transport_factory')]
    iterable $factories,
    protected ConfigFactoryInterface $configFactory,
  ) {
    $this->factories = $factories;
  }

  /**
   * {@inheritdoc}
   */
  public function createTransport(): TransportInterface {
    $dsn = $this->configFactory->get('system.mail')->get('mailer_dsn');
    $dsnObject = new Dsn(...$dsn);
    return $this->fromDsnObject($dsnObject);
  }

}
