<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\migrate\Exception\RequirementsException;

/**
 * Tests check requirements for comment entity translation source plugin.
 *
 * @group comment
 */
class CommentEntityTranslationCheckRequirementsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'comment',
    'language',
  ];

  /**
   * Tests exception thrown when the given module is not enabled in the source.
   *
   * @dataProvider providerTestCheckRequirements
   */
  public function testCheckRequirements($module) {
    // Disable the module in the source site.
    $this->sourceDatabase->update('system')
      ->condition('name', $module)
      ->fields([
        'status' => '0',
      ])
      ->execute();
    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage("The module $module is not enabled in the source site");
    $this->getMigration('d7_comment_entity_translation')
      ->getSourcePlugin()
      ->checkRequirements();
  }

  /**
   * Provides data for testCheckRequirements.
   *
   * @return string[][]
   */
  public function providerTestCheckRequirements() {
    return [
      ['comment'],
      ['node'],
    ];
  }

}
