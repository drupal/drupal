<?php

/**
 * @file
 * Definition of \Drupal\edit\Plugin\ProcessedTextPropertyBase.
 */

namespace Drupal\edit\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for processed text editor plugins.
 */
abstract class ProcessedTextEditorBase extends PluginBase implements ProcessedTextEditorInterface {

  /**
   * Implements \Drupal\edit\Plugin\ProcessedTextEditorInterface::addJsSettings().
   *
   * This base class provides an empty implementation for text editors that
   * do not need to add JavaScript settings besides those added by the library.
   */
  public function addJsSettings() {
  }

}
