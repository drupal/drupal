<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\JoinManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

class JoinManager extends PluginManagerBase {

  /**
   * Constructs a JoinManager object.
   */
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('views', 'join');
    $this->discovery = new AlterDecorator($this->discovery, 'views_plugins_join');
    $this->discovery = new ProcessDecorator($this->discovery, array($this, 'processDefinition'));
    $this->discovery = new CacheDecorator($this->discovery, 'views:join', 'views_info');

    $this->factory = new DefaultFactory($this);
    $this->defaults = array(
      'module' => 'views',
    );
  }

}
