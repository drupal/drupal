<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Entity;

use Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests revision route provider.
 */
#[CoversClass(RevisionHtmlRouteProvider::class)]
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
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
