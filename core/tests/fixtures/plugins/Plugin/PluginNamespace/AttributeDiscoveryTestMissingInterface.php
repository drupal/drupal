<?php

declare(strict_types=1);

namespace com\example\PluginNamespace;

use Drupal\a_module_that_does_not_exist\Plugin\CustomInterface;

/**
 * Provides a custom test plugin that implements a missing interface.
 */
#[CustomPlugin(
  id: "discovery_test_missing_interface",
  title: "Discovery test plugin missing interface"
)]
class AttributeDiscoveryTestMissingInterface implements CustomInterface {}
