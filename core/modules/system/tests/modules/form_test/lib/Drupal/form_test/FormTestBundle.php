<?php

/**
 * @file
 * Contains \Drupal\form_test\FormTestndle.
 */

namespace Drupal\form_test;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Form test dependency injection container.
 */
class FormTestBundle extends Bundle {

  /**
   * Overrides \Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    $container->register('form_test.form.serviceForm', 'Drupal\form_test\FormTestServiceObject');
  }

}
