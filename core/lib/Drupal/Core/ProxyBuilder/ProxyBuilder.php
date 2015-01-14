<?php

/**
 * @file
 * Contains \Drupal\Core\ProxyBuilder\ProxyBuilder.
 */

namespace Drupal\Core\ProxyBuilder;

use Drupal\Component\ProxyBuilder\ProxyBuilder as BaseProxyBuilder;

/**
 * Extend the component proxy builder by using the DependencySerialziationTrait.
 */
class ProxyBuilder extends BaseProxyBuilder {

  /**
   * {@inheritdoc{
   */
  protected function buildUseStatements() {
    $output = parent::buildUseStatements();

    $output .= 'use \Drupal\Core\DependencyInjection\DependencySerializationTrait;' . "\n\n";

    return $output;
  }

}
