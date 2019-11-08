<?php

namespace Drupal\Tests\field\Kernel;

/**
 * Extends FieldTypePluginManagerTest to test with deprecated modules.
 *
 * @see \Drupal\Tests\DeprecatedModulesTestTrait::removeDeprecatedModules()
 *
 * @group field
 * @group legacy
 */
class LegacyFieldTypePluginManagerTest extends FieldTypePluginManagerTest {

  /**
   * {@inheritdoc}
   */
  protected $excludeDeprecated = FALSE;

}
