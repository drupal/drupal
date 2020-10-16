<?php

namespace Drupal\Core\Block;

use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Render\PreviewFallbackInterface;

/**
 * Defines a base block implementation that most blocks plugins will extend.
 *
 * This abstract class provides the generic block configuration form, default
 * block settings, and handling for general user-defined block visibility
 * settings.
 *
 * @ingroup block_api
 */
abstract class BlockBase extends PluginBase implements BlockPluginInterface, PluginWithFormsInterface, PreviewFallbackInterface, ContextAwarePluginInterface {

  use BlockPluginTrait;
  use ContextAwarePluginTrait;
  use ContextAwarePluginAssignmentTrait;

}
