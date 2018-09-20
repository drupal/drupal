<?php

namespace Drupal\Core\ProxyBuilder;

use Drupal\Component\ProxyBuilder\ProxyBuilder as BaseProxyBuilder;

/**
 * Extend the component proxy builder by using the DependencySerializationTrait.
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
