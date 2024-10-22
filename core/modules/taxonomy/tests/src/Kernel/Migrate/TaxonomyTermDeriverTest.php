<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Migrate;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

// cspell:ignore vocabfixed vocablocalized vocabtranslate

/**
 * Tests d7 taxonomy term deriver.
 *
 * @group migrate_drupal_7
 */
class TaxonomyTermDeriverTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'text'];

  /**
   * Tests fields exist in  process pipeline for term migrations.
   */
  public function testBuilder(): void {
    // Test a field on the vocabfixed term.
    $process = $this->getMigration('d7_taxonomy_term:vocabfixed')->getProcess();
    $this->assertSame('field_training', $process['field_training'][0]['source']);

    // Test a field on the vocablocalized term.
    $process = $this->getMigration('d7_taxonomy_term:vocablocalized')->getProcess();
    $this->assertSame('field_sector', $process['field_sector'][0]['source']);

    // Test a field on the vocabtranslate term.
    $process = $this->getMigration('d7_taxonomy_term:vocabtranslate')->getProcess();
    $this->assertSame('field_chancellor', $process['field_chancellor'][0]['source']);

    // Test a field on the test_vocabulary term.
    $process = $this->getMigration('d7_taxonomy_term:test_vocabulary')->getProcess();
    $this->assertSame('field_integer', $process['field_integer'][0]['source']);
    $this->assertSame('field_term_reference', $process['field_term_reference'][0]['source']);
  }

}
