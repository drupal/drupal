<?php

namespace Drupal\layout_builder;

@trigger_error(__NAMESPACE__ . '\QuickEditIntegration is deprecated in drupal:9.4.2 and is removed from drupal:10.0.0. Instead, use \Drupal\quickedit\LayoutBuilderIntegration. See https://www.drupal.org/node/3265518', E_USER_DEPRECATED);

use Drupal\quickedit\LayoutBuilderIntegration;

/**
 * Helper methods for Quick Edit module integration.
 *
 * @internal
 *   This is an internal utility class wrapping hook implementations.
 */
class QuickEditIntegration extends LayoutBuilderIntegration {}
