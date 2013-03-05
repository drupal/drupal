<?php

/**
 * @file
 * Contains \Drupal\locale\LocaleBundle.
 */

namespace Drupal\locale;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Registers locale module's services to the container.
 */
class LocaleBundle extends Bundle {

  /**
   * Implements \Symfony\Component\HttpKernel\Bundle\BundleInterface::build().
   */
  public function build(ContainerBuilder $container) {
    $container->register('locale_config_subscriber', 'Drupal\locale\LocaleConfigSubscriber')
      ->addArgument(new Reference('language_manager'))
      ->addArgument(new Reference('config.context'))
      ->addTag('event_subscriber');
  }

}
