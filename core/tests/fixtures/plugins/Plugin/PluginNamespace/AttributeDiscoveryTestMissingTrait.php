<?php

declare(strict_types=1);

namespace com\example\PluginNamespace;

use Drupal\a_module_that_does_not_exist\Plugin\CustomTrait;

/**
 * Provides a custom test plugin that uses a missing trait.
 */
#[CustomPlugin(
  id: "discovery_test_missing_trait",
  title: "Discovery test plugin missing trait"
)]
class AttributeDiscoveryTestMissingTrait {
  use CustomTrait;

}
