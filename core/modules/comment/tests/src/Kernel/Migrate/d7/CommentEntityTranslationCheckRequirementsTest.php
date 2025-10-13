<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel\Migrate\d7;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests check requirements for comment entity translation source plugin.
 */
#[Group('comment')]
#[RunTestsInSeparateProcesses]
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
   */
  #[DataProvider('providerTestCheckRequirements')]
  public function testCheckRequirements($module): void {
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
   *   An array of module machine names.
   */
  public static function providerTestCheckRequirements() {
    return [
      ['comment'],
      ['node'],
    ];
  }

}
