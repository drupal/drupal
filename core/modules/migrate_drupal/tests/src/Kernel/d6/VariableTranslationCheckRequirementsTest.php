<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d6;

use Drupal\migrate\Exception\RequirementsException;

/**
 * Tests check requirements for variable translation source plugin.
 *
 * @group migrate_drupal
 */
class VariableTranslationCheckRequirementsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['config_translation'];

  /**
   * {@inheritdoc}
   */
  public function setup() {
    parent::setUp();
    $this->sourceDatabase->schema()->dropTable('i18n_variable');
  }

  /**
   * Tests exception in thrown when the i18n_variable table does not exist.
   */
  public function testCheckRequirements() {
    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage("Source database table 'i18n_variable' does not exist");
    $this->getMigration('d6_system_maintenance_translation')
      ->getSourcePlugin()
      ->checkRequirements();
  }

}
