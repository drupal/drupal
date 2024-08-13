<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Taxonomy term revision delete form test.
 *
 * @group taxonomy
 * @coversDefaultClass \Drupal\Core\Entity\Form\RevisionDeleteForm
 */
class TaxonomyRevisionDeleteTest extends BrowserTestBase {

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
   * {@inheritdoc}
   */
  protected $permissions = [
    'view term revisions in test',
    'delete all taxonomy revisions',
  ];

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  private $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->vocabulary = $this->createVocabulary(['vid' => 'test', 'name' => 'Test']);
  }

  /**
   * Tests revision delete.
   */
  public function testDeleteForm(): void {
    $termName = $this->randomMachineName();
    $entity = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => $termName,
    ]);

    $entity->setRevisionCreationTime((new \DateTimeImmutable('11 January 2009 4pm'))->getTimestamp())
      ->setRevisionTranslationAffected(TRUE);
    $entity->setNewRevision();
    $entity->save();
    $revisionId = $entity->getRevisionId();

    $this->drupalLogin($this->drupalCreateUser($this->permissions));

    // Cannot delete latest revision.
    $this->drupalGet($entity->toUrl('revision-delete-form'));
    $this->assertSession()->statusCodeEquals(403);

    // Create a new latest revision.
    $entity
      ->setRevisionCreationTime((new \DateTimeImmutable('11 January 2009 5pm'))->getTimestamp())
      ->setRevisionTranslationAffected(TRUE)
      ->setNewRevision();
    $entity->save();

    // Reload the entity.
    $revision = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadRevision($revisionId);
    $this->drupalGet($revision->toUrl('revision-delete-form'));
    $this->assertSession()->pageTextContains('Are you sure you want to delete the revision from Sun, 11 Jan 2009 - 16:00?');
    $this->assertSession()->buttonExists('Delete');
    $this->assertSession()->linkExists('Cancel');

    $countRevisions = static function (): int {
      return (int) \Drupal::entityTypeManager()->getStorage('taxonomy_term')
        ->getQuery()
        ->accessCheck(FALSE)
        ->allRevisions()
        ->count()
        ->execute();
    };

    $count = $countRevisions();
    $this->submitForm([], 'Delete');
    $this->assertEquals($count - 1, $countRevisions());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals(sprintf('taxonomy/term/%s/revisions', $entity->id()));
    $this->assertSession()->pageTextContains(sprintf('Revision from Sun, 11 Jan 2009 - 16:00 of Test %s has been deleted.', $termName));
    $this->assertSession()->elementsCount('css', 'table tbody tr', 1);
  }

}
