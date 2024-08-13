<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Taxonomy term revision form test.
 *
 * @group taxonomy
 * @coversDefaultClass \Drupal\Core\Entity\Form\RevisionRevertForm
 */
class TaxonomyRevisionRevertTest extends BrowserTestBase {

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
    'revert all taxonomy revisions',
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
   * Tests revision revert.
   */
  public function testRevertForm(): void {
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

    // Cannot revert latest revision.
    $this->drupalGet($entity->toUrl('revision-revert-form'));
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
    $this->drupalGet($revision->toUrl('revision-revert-form'));
    $this->assertSession()->pageTextContains('Are you sure you want to revert to the revision from Sun, 11 Jan 2009 - 16:00?');
    $this->assertSession()->buttonExists('Revert');
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
    $this->submitForm([], 'Revert');
    $this->assertEquals($count + 1, $countRevisions());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals(sprintf('taxonomy/term/%s/revisions', $entity->id()));
    $this->assertSession()->pageTextContains(sprintf('Test %s has been reverted to the revision from Sun, 11 Jan 2009 - 16:00.', $termName));
    $this->assertSession()->elementsCount('css', 'table tbody tr', 3);
  }

}
