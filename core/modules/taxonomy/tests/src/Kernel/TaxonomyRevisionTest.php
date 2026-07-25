<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the new_revision setting of taxonomy vocabularies.
 */
#[Group('taxonomy')]
#[RunTestsInSeparateProcesses]
class TaxonomyRevisionTest extends KernelTestBase {

  use TaxonomyTestTrait;
  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'taxonomy',
    'user',
    'text',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * Tests default revision settings on vocabularies.
   */
  public function testVocabularyTermRevision(): void {
    $assert = $this->assertSession();
    $vocabulary1 = $this->createVocabulary(['new_revision' => TRUE]);
    $vocabulary2 = $this->createVocabulary(['new_revision' => FALSE]);
    $user = $this->drupalCreateUser([
      'administer taxonomy',
    ]);
    $term1 = $this->createTerm($vocabulary1);
    $term2 = $this->createTerm($vocabulary2);

    // Create some revisions so revision checkbox is visible.
    $term1 = $this->createTaxonomyTermRevision($term1);
    $term2 = $this->createTaxonomyTermRevision($term2);
    $this->setCurrentUser($user);
    $this->drupalGet($term1->toUrl('edit-form'));
    $assert->statusCodeEquals(200);
    $assert->checkboxChecked('Create new revision');
    $this->drupalGet($term2->toUrl('edit-form'));
    $assert->statusCodeEquals(200);
    $assert->checkboxNotChecked('Create new revision');

  }

}
