<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional;

/**
 * Block content revision delete form test.
 *
 * @group block_content
 * @coversDefaultClass \Drupal\Core\Entity\Form\RevisionDeleteForm
 */
class BlockContentRevisionDeleteTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'view any basic block content history',
    'delete any basic block content revisions',
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
   * Tests revision delete.
   */
  public function testDeleteForm(): void {
    $entity = $this->createBlockContent(save: FALSE)
      ->setRevisionCreationTime((new \DateTimeImmutable('11 January 2009 4pm'))->getTimestamp())
      ->setRevisionTranslationAffected(TRUE);
    $entity->setNewRevision();
    $entity->save();
    $revisionId = $entity->getRevisionId();

    // Cannot delete latest revision.
    $this->drupalGet($entity->toUrl('revision-delete-form'));
    $this->assertSession()->statusCodeEquals(403);

    // Create a new non default revision.
    $entity
      ->setRevisionCreationTime((new \DateTimeImmutable('11 January 2009 5pm'))->getTimestamp())
      ->setRevisionTranslationAffected(TRUE)
      ->setNewRevision();
    $entity->isDefaultRevision(FALSE);
    $entity->save();
    $nonDefaultRevisionId = $entity->getRevisionId();

    // Reload the default entity.
    $revision = \Drupal::entityTypeManager()->getStorage('block_content')
      ->loadRevision($revisionId);
    // Cannot delete default revision.
    $this->drupalGet($revision->toUrl('revision-delete-form'));
    $this->assertSession()->statusCodeEquals(403);
    $this->assertFalse($revision->access('delete revision', $this->adminUser, FALSE));

    // Reload the non default entity.
    $revision2 = \Drupal::entityTypeManager()->getStorage('block_content')
      ->loadRevision($nonDefaultRevisionId);
    $this->drupalGet($revision2->toUrl('revision-delete-form'));
    $this->assertSession()->pageTextContains('Are you sure you want to delete the revision from Sun, 11 Jan 2009 - 17:00?');
    $this->assertSession()->buttonExists('Delete');
    $this->assertSession()->linkExists('Cancel');
    $this->assertTrue($revision2->access('delete revision', $this->adminUser, FALSE));

    $countRevisions = static function (): int {
      return (int) \Drupal::entityTypeManager()->getStorage('block_content')
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
    $this->assertSession()->addressEquals(sprintf('admin/content/block/%s/revisions', $entity->id()));
    $this->assertSession()->pageTextContains(sprintf('Revision from Sun, 11 Jan 2009 - 17:00 of basic %s has been deleted.', $entity->label()));
    $this->assertSession()->elementsCount('css', 'table tbody tr', 1);
  }

}
