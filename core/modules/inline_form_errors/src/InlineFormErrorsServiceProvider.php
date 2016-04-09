<?php

namespace Drupal\inline_form_errors;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the form_error_handler service to enable inline form errors.
 */
class InlineFormErrorsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('form_error_handler')
      ->setClass(FormErrorHandler::class)
      ->setArguments([new Reference('string_translation'), new Reference('link_generator'), new Reference('renderer')]);
  }

}
