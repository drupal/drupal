<?php

/**
 * @file
 * Definition of Drupal\layout\Plugin\Type\LayoutManager.
 */

namespace Drupal\layout\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Component\Plugin\Factory\ReflectionFactory;

/**
 * Layout plugin manager.
 */
class LayoutManager extends PluginManagerBase {

  protected $defaults = array(
    'class' => 'Drupal\layout\Plugin\layout\layout\StaticLayout',
  );

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase::__construct().
   */
  public function __construct() {
    // Create layout plugin derivatives from declaratively defined layouts.
    $this->discovery = new DerivativeDiscoveryDecorator(new AnnotatedClassDiscovery('layout', 'layout'));
    $this->factory = new ReflectionFactory($this);
  }
}
