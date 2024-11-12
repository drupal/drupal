<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Entity;

use Drupal\Component\Uuid\Uuid;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_translation\Traits\ContentTranslationTestTrait;

/**
 * Tests that an entity with a UUID as ID can be managed.
 *
 * @group Entity
 */
class EntityUuidIdTest extends BrowserTestBase {

  use ContentTranslationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'content_translation', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createLanguageFromLangcode('af');
    $this->enableContentTranslation('entity_test_uuid_id', 'entity_test_uuid_id');
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the user interface for the test entity.
   */
  public function testUi(): void {
    $this->drupalLogin($this->createUser([
      'administer entity_test content',
      'create content translations',
      'translate entity_test_uuid_id',
      'view test entity',
    ]));

    // Test adding an entity.
    $this->drupalGet('/entity_test_uuid_id/add');
    $this->submitForm([
      'Name' => 'Test entity with UUID ID',
    ], 'Save');
    $this->assertSession()->elementTextEquals('css', 'h1', 'Edit Test entity with UUID ID');
    $this->assertSession()->addressMatches('#^/entity_test_uuid_id/manage/' . Uuid::VALID_PATTERN . '/edit$#');

    // Test translating an entity.
    $this->clickLink('Translate');
    $this->clickLink('Add');
    $this->submitForm([
      'Name' => 'Afrikaans translation of test entity with UUID ID',
    ], 'Save');
    $this->assertSession()->elementTextEquals('css', 'h1', 'Afrikaans translation of test entity with UUID ID [Afrikaans translation]');
    $this->assertSession()->addressMatches('#^/af/entity_test_uuid_id/manage/' . Uuid::VALID_PATTERN . '/edit$#');
  }

}
