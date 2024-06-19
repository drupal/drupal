<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests the new_revision setting of taxonomy vocabularies.
 *
 * @group taxonomy
 */
class TaxonomyRevisionTest extends BrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests default revision settings on vocabularies.
   */
  public function testVocabularyTermRevision(): void {
    $assert = $this->assertSession();
    $vocabulary1 = $this->createVocabulary(['new_revision' => TRUE]);
    $vocabulary2 = $this->createVocabulary(['new_revision' => FALSE]);
    $user = $this->createUser([
      'administer taxonomy',
    ]);
    $term1 = $this->createTerm($vocabulary1);
    $term2 = $this->createTerm($vocabulary2);

    // Create some revisions so revision checkbox is visible.
    $term1 = $this->createTaxonomyTermRevision($term1);
    $term2 = $this->createTaxonomyTermRevision($term2);
    $this->drupalLogin($user);
    $this->drupalGet($term1->toUrl('edit-form'));
    $assert->statusCodeEquals(200);
    $assert->checkboxChecked('Create new revision');
    $this->drupalGet($term2->toUrl('edit-form'));
    $assert->statusCodeEquals(200);
    $assert->checkboxNotChecked('Create new revision');

  }

}
