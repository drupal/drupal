<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the upgrade path for making vocabularies' description NULL.
 *
 * @group taxonomy
 * @see taxonomy_post_update_set_vocabulary_description_to_null()
 */
class NullDescriptionTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/remove-description-from-tags-vocabulary.php',
    ];
  }

  /**
   * Tests the upgrade path for updating empty description to NULL.
   */
  public function testRunUpdates(): void {
    $vocabulary = Vocabulary::load('tags');
    $this->assertInstanceOf(Vocabulary::class, $vocabulary);

    $this->assertSame("\n", $vocabulary->get('description'));
    $this->runUpdates();

    $vocabulary = Vocabulary::load('tags');
    $this->assertInstanceOf(Vocabulary::class, $vocabulary);

    $this->assertNull($vocabulary->get('description'));
    $this->assertSame('', $vocabulary->getDescription());
  }

}
