<?php

/**
 * @file
 * Definition of Drupal\file\FileBundle.
 */

namespace Drupal\file;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FileBundle extends Bundle {
  public function build(ContainerBuilder $container) {
    $container->register('file.usage', 'Drupal\file\FileUsage\DatabaseFileUsageBackend')
      ->addArgument(new Reference('database'));
  }
}
