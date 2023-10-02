<?php

declare(strict_types = 1);

namespace Drupal\FunctionalTests\Entity;

use Drupal\entity_test_revlog\Entity\EntityTestMulWithRevisionLog;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests version history page with translations.
 *
 * @group Entity
 * @coversDefaultClass \Drupal\Core\Entity\Controller\VersionHistoryController
 */
final class RevisionVersionHistoryTranslatableTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test_revlog',
    'content_translation',
    'language',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Rebuild the container so that the new languages are picked up by services
    // that hold a list of languages.
    $this->rebuildContainer();
  }

  /**
   * Tests the version history page for translations.
   */
  public function testVersionHistoryTranslations(): void {
    $label = 'view all revisions,revert,delete revision';
    $entity = EntityTestMulWithRevisionLog::create([
      'name' => $label,
      'type' => 'entity_test_mul_revlog',
    ]);
    $entity->addTranslation('es', ['label' => 'version history test translations es']);
    $entity->save();

    $firstRevisionId = $entity->getRevisionId();

    $entity->setNewRevision();
    $entity->setName($label . ',2')
      ->save();

    $this->drupalGet($entity->toUrl('version-history'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementsCount('css', 'table tbody tr', 2);

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage($entity->getEntityTypeId());
    $firstRevision = $storage->loadRevision($firstRevisionId);

    $this->assertSession()->linkByHrefExists($firstRevision->toUrl('revision-revert-form')->toString());
    $this->assertSession()->linkByHrefExists($firstRevision->toUrl('revision-delete-form')->toString());
    $this->assertSession()->linkByHrefNotExists($firstRevision->getTranslation('es')->toUrl('revision-revert-form')->toString());
    $this->assertSession()->linkByHrefNotExists($firstRevision->getTranslation('es')->toUrl('revision-delete-form')->toString());

    $this->drupalGet($entity->getTranslation('es')->toUrl('version-history'));
    $this->assertSession()->linkByHrefNotExistsExact($firstRevision->toUrl('revision-revert-form')->toString());
    $this->assertSession()->linkByHrefNotExistsExact($firstRevision->toUrl('revision-delete-form')->toString());
    $this->assertSession()->linkByHrefExists($firstRevision->getTranslation('es')->toUrl('revision-revert-form')->toString());
    $this->assertSession()->linkByHrefExists($firstRevision->getTranslation('es')->toUrl('revision-delete-form')->toString());
  }

}
