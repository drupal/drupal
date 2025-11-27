<?php

declare(strict_types=1);

namespace Drupal\mailer_capture;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\mailer_capture\Transport\CaptureTransport;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Enforce mailer transport which captures sent messages in a key value store.
 *
 * Enforces CaptureTransport as the mailer transport service implementation,
 * sidestepping mailer transport factory. As a result, the contents of the
 * system.mail mailer_dsn is irrelevant for transport service construction.
 */
class MailerCaptureServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    $definition = new Definition(CaptureTransport::class, [
      new Reference(EventDispatcherInterface::class),
    ]);
    $definition->addMethodCall('setKeyValueFactory', [
      new Reference(KeyValueFactoryInterface::class),
    ]);
    $container->setDefinition(TransportInterface::class, $definition->setPublic(TRUE));
  }

}
