<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\WizardManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

class WizardManager extends PluginManagerBase {

  /**
   * Constructs a WizardManager object.
   */
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('views', 'wizard');
    $this->discovery = new AlterDecorator($this->discovery, 'views_plugins_wizard');
    $this->discovery = new ProcessDecorator($this->discovery, array($this, 'processDefinition'));
    $this->discovery = new DerivativeDiscoveryDecorator($this->discovery);
    $this->discovery = new CacheDecorator($this->discovery, 'views:wizard', 'views_info');
    $this->factory = new DefaultFactory($this);
    $this->defaults = array(
      'module' => 'views',
    );
  }

}
