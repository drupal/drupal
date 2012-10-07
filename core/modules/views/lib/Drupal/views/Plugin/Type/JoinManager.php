<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\JoinManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

class JoinManager extends PluginManagerBase {

  /**
   * Constructs a JoinManager object.
   */
  public function __construct() {
    $this->discovery = new CacheDecorator(new AlterDecorator(new AnnotatedClassDiscovery('views', 'join'), 'views_plugins_join'), 'views:join', 'views_info');
    $this->factory = new DefaultFactory($this);
    $this->defaults = array(
      'module' => 'views',
    );
  }

}
