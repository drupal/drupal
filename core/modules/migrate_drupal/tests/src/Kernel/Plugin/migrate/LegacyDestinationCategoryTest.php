<?php

namespace Drupal\Tests\migrate_drupal\Kernel\Plugin\migrate;

/**
 * Extends DestinationCategoryTest to test with deprecated modules.
 *
 * @see \Drupal\Tests\DeprecatedModulesTestTrait::removeDeprecatedModules()
 *
 * @group migrate_drupal
 * @group legacy
 */
class LegacyDestinationCategoryTest extends DestinationCategoryTest {

  /**
   * {@inheritdoc}
   */
  protected $excludeDeprecated = FALSE;

}
