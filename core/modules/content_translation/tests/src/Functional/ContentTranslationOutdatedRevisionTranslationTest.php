<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the "Flag as outdated" functionality with revision translations.
 *
 * @group content_translation
 */
class ContentTranslationOutdatedRevisionTranslationTest extends ContentTranslationPendingRevisionTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->doSetup();
    $this->enableContentModeration();
  }

  /**
   * Tests that outdated revision translations work correctly.
   */
  public function testFlagAsOutdatedHidden(): void {
    // Create a test node.
    $values = [
      'title' => 'Test 1.1 EN',
      'moderation_state' => 'published',
    ];
    $id = $this->createEntity($values, 'en');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->storage->load($id);

    // Add a published Italian translation.
    $add_translation_url = Url::fromRoute("entity.{$this->entityTypeId}.content_translation_add",
      [
        $entity->getEntityTypeId() => $id,
        'source' => 'en',
        'target' => 'it',
      ],
      [
        'language' => ConfigurableLanguage::load('it'),
        'absolute' => FALSE,
      ]
    );
    $this->drupalGet($add_translation_url);
    $this->assertFlagWidget();
    $edit = [
      'title[0][value]' => 'Test 1.2 IT',
      'moderation_state[0][state]' => 'published',
    ];
    $this->submitForm($edit, 'Save (this translation)');

    // Add a published French translation.
    $add_translation_url = Url::fromRoute("entity.{$this->entityTypeId}.content_translation_add",
      [
        $entity->getEntityTypeId() => $id,
        'source' => 'en',
        'target' => 'fr',
      ],
      [
        'language' => ConfigurableLanguage::load('fr'),
        'absolute' => FALSE,
      ]
    );
    $this->drupalGet($add_translation_url);
    $this->assertFlagWidget();
    $edit = [
      'title[0][value]' => 'Test 1.3 FR',
      'moderation_state[0][state]' => 'published',
    ];
    $this->submitForm($edit, 'Save (this translation)');

    // Create an English draft.
    $entity = $this->storage->loadUnchanged($id);
    $en_edit_url = $this->getEditUrl($entity);
    $this->drupalGet($en_edit_url);
    $this->assertFlagWidget();
  }

  /**
   * Checks whether the flag widget is displayed.
   *
   * @internal
   */
  protected function assertFlagWidget(): void {
    $this->assertSession()->pageTextNotContains('Flag other translations as outdated');
    $this->assertSession()->pageTextContains('Translations cannot be flagged as outdated when content is moderated.');
  }

}
