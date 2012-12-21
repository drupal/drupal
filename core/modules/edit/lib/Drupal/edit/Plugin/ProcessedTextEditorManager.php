<?php

/**
 * @file
 * Definition of \Drupal\edit\Plugin\ProcessedTextEditorManager.
 */

namespace Drupal\edit\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

/**
 * ProcessedTextEditor manager.
 */
class ProcessedTextEditorManager extends PluginManagerBase {

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::__construct().
   */
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('edit', 'processed_text_editor');
    $this->discovery = new AlterDecorator($this->discovery, 'edit_wysiwyg');
    $this->discovery = new CacheDecorator($this->discovery, 'edit:wysiwyg');
    $this->factory = new DefaultFactory($this->discovery);
  }

}
