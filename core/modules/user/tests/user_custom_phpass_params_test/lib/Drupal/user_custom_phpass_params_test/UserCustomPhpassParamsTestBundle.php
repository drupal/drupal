<?php

/**
 * @file
 * Definition of Drupal\user_custom_phpass_params_test\UserCustomPhpassParamsTestBundle
 */

namespace Drupal\user_custom_phpass_params_test;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class UserCustomPhpassParamsTestBundle extends Bundle
{
  public function build(ContainerBuilder $container) {
    // Override the default password hashing service parameters
    $container->register('password', 'Drupal\Core\Password\PhpassHashedPassword')
      ->addArgument(19);
  }
}
