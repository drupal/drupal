<?php

declare(strict_types=1);

namespace Drupal\mailer_transport_factory_functional_test\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Returns responses for transport info routes.
 */
class TransportInfoController implements ContainerInjectionInterface {

  /**
   * Constructs a new transport info controller.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Symfony\Component\Mailer\Transport\TransportInterface $transport
   *   The mailer transport.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected TransportInterface $transport,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get(ConfigFactoryInterface::class),
      $container->get(TransportInterface::class)
    );
  }

  /**
   * Returns info about the configured mailer dsn and the resulting transport.
   */
  public function transportInfo(): Response {
    $mailerDsn = $this->configFactory->get('system.mail')->get('mailer_dsn');
    return new JsonResponse([
      'mailerDsn' => $mailerDsn,
      'mailerTransportClass' => $this->transport::class,
    ]);
  }

}
