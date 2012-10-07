<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\WizardManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

class WizardManager extends PluginManagerBase {

  /**
   * Constructs a WizardManager object.
   */
  public function __construct() {
    $this->discovery = new CacheDecorator(new AlterDecorator(new AnnotatedClassDiscovery('views', 'wizard'), 'views_plugins_wizard'), 'views:wizard', 'views_info');
    $this->factory = new DefaultFactory($this);
    $this->defaults = array(
      'module' => 'views',
    );
  }

}
