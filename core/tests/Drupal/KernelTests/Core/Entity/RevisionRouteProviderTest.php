<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests revision route provider.
 *
 * @coversDefaultClass \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider
 * @group Entity
 */
class RevisionRouteProviderTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('user');
    $this->setUpCurrentUser(['uid' => 1]);
  }

  /**
   * Tests revision access for revision overview.
   *
   * Tests routes which do not need a specific revision parameter.
   */
  public function testOperationAccessOverview(): void {
    $entity = EntityTestRev::create()
      ->setName('first revision');
    $entity->save();
    $this->assertFalse($entity->toUrl('version-history')->access());

    $entity
      ->setName('view all revisions')
      ->setNewRevision();
    $entity->save();
    $this->assertTrue($entity->toUrl('version-history')->access());
  }

  /**
   * Tests revision access is granted by entity operations.
   *
   * Ensures entity is sourced from revision parameter, not entity parameter or
   * default revision.
   * E.g 'entity_test_rev_revision'
   * in '/{entity_test_rev}/revision/{entity_test_rev_revision}/view'.
   *
   * @param string $linkTemplate
   *   The link template to test.
   * @param string $entityLabel
   *   Access is granted via specially named entity label passed to
   *   EntityTestAccessControlHandler.
   *
   * @dataProvider providerOperationAccessRevisionRoutes
   */
  public function testOperationAccessRevisionRoutes(string $linkTemplate, string $entityLabel): void {
    $entityStorage = \Drupal::entityTypeManager()->getStorage('entity_test_rev');

    $entity = EntityTestRev::create()
      ->setName('first revision');
    $entity->save();
    $noAccessRevisionId = $entity->getRevisionId();

    $entity
      ->setName($entityLabel)
      ->setNewRevision();
    $entity->save();
    $hasAccessRevisionId = $entity->getRevisionId();

    $this->assertNotEquals($noAccessRevisionId, $hasAccessRevisionId);

    // Create an additional default revision to ensure access isn't being pulled
    // from default revision.
    $entity
      ->setName('default')
      ->setNewRevision();
    $entity->isDefaultRevision(TRUE);
    $entity->save();

    // Reload entity so default revision flags are accurate.
    $originalRevision = $entityStorage->loadRevision($noAccessRevisionId);
    $viewableRevision = $entityStorage->loadRevision($hasAccessRevisionId);

    $this->assertFalse($originalRevision->toUrl($linkTemplate)->access());
    $this->assertTrue($viewableRevision->toUrl($linkTemplate)->access());
  }

  /**
   * Data provider for testOperationAccessRevisionRoutes.
   *
   * @return array
   *   Data for testing.
   */
  public function providerOperationAccessRevisionRoutes(): array {
    $data = [];

    $data['view revision'] = [
      'revision',
      'view revision',
    ];

    $data['revert revision'] = [
      'revision-revert-form',
      'revert',
    ];

    $data['delete revision'] = [
      'revision-delete-form',
      'delete revision',
    ];

    return $data;
  }

}
