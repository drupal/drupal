<?php

namespace Drupal\Core\Config\Testing;

use Drupal\Core\Config\Development\ConfigSchemaChecker as SchemaChecker;

/**
 * Listens to the config save event and validates schema.
 *
 * If tests have the $strictConfigSchema property set to TRUE this event
 * listener will be added to the container and throw exceptions if configuration
 * is invalid.
 *
 * @see \Drupal\KernelTests\KernelTestBase::register()
 * @see \Drupal\simpletest\WebTestBase::setUp()
 * @see \Drupal\simpletest\KernelTestBase::containerBuild()
 *
 * @deprecated in drupal:8.3.0 and is removed from drupal:9.0.0.
 *   Use Drupal\Core\Config\Development\ConfigSchemaChecker.
 */
class ConfigSchemaChecker extends SchemaChecker {
}
