<?php

declare(strict_types=1);

namespace com\example\PluginNamespace;

use Drupal\a_module_that_does_not_exist\Plugin\Custom;

/**
 * Provides a custom test plugin that extends from a missing dependency.
 */
#[CustomPlugin(
  id: "discovery_test_2",
  title: "Discovery test plugin 2"
)]
class AttributeDiscoveryTest2 extends Custom {}
