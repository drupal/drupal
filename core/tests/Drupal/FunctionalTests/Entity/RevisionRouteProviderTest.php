<?php

namespace Drupal\FunctionalTests\Entity;

use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests revision route provider.
 *
 * @group Entity
 * @coversDefaultClass \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider
 */
class RevisionRouteProviderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests title is from revision in context.
   */
  public function testRevisionTitle(): void {
    $entity = EntityTestRev::create();
    $entity
      ->setName('first revision, view revision')
      ->setNewRevision();
    $entity->save();
    $revisionId = $entity->getRevisionId();

    // A default revision is created to ensure it is not pulled from the
    // non-revision entity parameter.
    $entity
      ->setName('second revision, view revision')
      ->setNewRevision();
    $entity->isDefaultRevision(TRUE);
    $entity->save();

    // Reload the object.
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('entity_test_rev');
    $revision = $storage->loadRevision($revisionId);
    $this->drupalGet($revision->toUrl('revision'));
    $this->assertSession()->responseContains('first revision');
    $this->assertSession()->responseNotContains('second revision');
  }

}
