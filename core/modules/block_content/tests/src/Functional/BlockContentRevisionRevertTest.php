<?php

declare(strict_types = 1);

namespace Drupal\Tests\block_content\Functional;

/**
 * Block content revision form test.
 *
 * @group block_content
 * @coversDefaultClass \Drupal\Core\Entity\Form\RevisionRevertForm
 */
class BlockContentRevisionRevertTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'access block library',
    'view any basic block content history',
    'revert any basic block content revisions',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests revision revert.
   */
  public function testRevertForm(): void {
    $entity = $this->createBlockContent(save: FALSE)
      ->setRevisionCreationTime((new \DateTimeImmutable('11 January 2009 4pm'))->getTimestamp())
      ->setRevisionTranslationAffected(TRUE);
    $entity->setNewRevision();
    $entity->save();
    $revisionId = $entity->getRevisionId();

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
    $revision = \Drupal::entityTypeManager()->getStorage('block_content')
      ->loadRevision($revisionId);
    $this->drupalGet($revision->toUrl('revision-revert-form'));
    $this->assertSession()->pageTextContains('Are you sure you want to revert to the revision from Sun, 01/11/2009 - 16:00?');
    $this->assertSession()->buttonExists('Revert');
    $this->assertSession()->linkExists('Cancel');

    $countRevisions = static function (): int {
      return (int) \Drupal::entityTypeManager()->getStorage('block_content')
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
    $this->assertSession()->addressEquals(sprintf('admin/content/block/%s/revisions', $entity->id()));
    $this->assertSession()->pageTextContains(sprintf('basic %s has been reverted to the revision from Sun, 01/11/2009 - 16:00.', $entity->label()));
    // Three rows, from the top: the newly reverted revision, the revision from
    // 5pm, and the revision from 4pm.
    $this->assertSession()->elementsCount('css', 'table tbody tr', 3);
  }

}
