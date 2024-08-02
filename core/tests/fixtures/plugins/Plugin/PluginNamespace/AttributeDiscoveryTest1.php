<?php

declare(strict_types=1);

namespace com\example\PluginNamespace;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Provides a custom test plugin.
 */
#[Plugin(
  id: "discovery_test_1",
)]
#[CustomPlugin(
  id: "discovery_test_1",
  title: "Discovery test plugin"
)]
class AttributeDiscoveryTest1 {}
