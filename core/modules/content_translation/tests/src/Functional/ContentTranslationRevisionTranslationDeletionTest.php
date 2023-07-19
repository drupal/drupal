<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests that revision translation deletion is handled correctly.
 *
 * @group content_translation
 */
class ContentTranslationRevisionTranslationDeletionTest extends ContentTranslationPendingRevisionTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->enableContentModeration();
  }

  /**
   * Tests that translation overview handles pending revisions correctly.
   */
  public function testOverview() {
    $index = 1;
    $accounts = [
      $this->rootUser,
      $this->editor,
      $this->translator,
    ];
    foreach ($accounts as $account) {
      $this->currentAccount = $account;
      $this->doTestOverview($index++);
    }
  }

  /**
   * Performs a test run.
   *
   * @param int $index
   *   The test run index.
   */
  public function doTestOverview($index) {
    $this->drupalLogin($this->currentAccount);

    // Create a test node.
    $values = [
      'title' => "Test $index.1 EN",
      'moderation_state' => 'published',
    ];
    $id = $this->createEntity($values, 'en');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->storage->load($id);

    // Add a draft translation and check that it is available only in the latest
    // revision.
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
    $add_translation_href = $add_translation_url->toString();
    $this->drupalGet($add_translation_url);
    $edit = [
      'title[0][value]' => "Test $index.2 IT",
      'moderation_state[0][state]' => 'draft',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $entity = $this->storage->loadUnchanged($id);
    $this->assertFalse($entity->hasTranslation('it'));
    $it_revision = $this->loadRevisionTranslation($entity, 'it');
    $this->assertTrue($it_revision->hasTranslation('it'));

    // Check that translations cannot be deleted in drafts.
    $overview_url = $entity->toUrl('drupal:content-translation-overview');
    $this->drupalGet($overview_url);
    $it_delete_url = $this->getDeleteUrl($it_revision);
    $it_delete_href = $it_delete_url->toString();
    $this->assertSession()->linkByHrefNotExists($it_delete_href);
    $warning = 'The "Delete translation" action is only available for published translations.';
    $this->assertSession()->statusMessageContains($warning, 'warning');
    $this->drupalGet($this->getEditUrl($it_revision));
    $this->assertSession()->linkNotExistsExact('Delete translation');

    // Publish the translation and verify it can be deleted.
    $edit = [
      'title[0][value]' => "Test $index.3 IT",
      'moderation_state[0][state]' => 'published',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $entity = $this->storage->loadUnchanged($id);
    $this->assertTrue($entity->hasTranslation('it'));
    $it_revision = $this->loadRevisionTranslation($entity, 'it');
    $this->assertTrue($it_revision->hasTranslation('it'));
    $this->drupalGet($overview_url);
    $this->assertSession()->linkByHrefExists($it_delete_href);
    $this->assertSession()->statusMessageNotContains($warning);
    $this->drupalGet($this->getEditUrl($it_revision));
    $this->assertSession()->linkExistsExact('Delete translation');

    // Create an English draft and verify the published translation was
    // preserved.
    $this->drupalLogin($this->editor);
    $en_revision = $this->loadRevisionTranslation($entity, 'en');
    $this->drupalGet($this->getEditUrl($en_revision));
    $edit = [
      'title[0][value]' => "Test $index.4 EN",
      'moderation_state[0][state]' => 'draft',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $entity = $this->storage->loadUnchanged($id);
    $this->assertTrue($entity->hasTranslation('it'));
    $en_revision = $this->loadRevisionTranslation($entity, 'en');
    $this->assertTrue($en_revision->hasTranslation('it'));
    $this->drupalLogin($this->currentAccount);

    // Delete the translation and verify that it is actually gone and that it is
    // possible to create it again.
    $this->drupalGet($it_delete_url);
    $this->submitForm([], 'Delete Italian translation');
    $entity = $this->storage->loadUnchanged($id);
    $this->assertFalse($entity->hasTranslation('it'));
    $it_revision = $this->loadRevisionTranslation($entity, 'it');
    $this->assertTrue($it_revision->wasDefaultRevision());
    $this->assertTrue($it_revision->hasTranslation('it'));
    $this->assertLessThan($entity->getRevisionId(), $it_revision->getRevisionId());
    $this->drupalGet($overview_url);
    $this->assertSession()->linkByHrefNotExists($this->getEditUrl($it_revision)->toString());
    $this->assertSession()->linkByHrefExists($add_translation_href);

    // Publish the English draft and verify the translation is not accidentally
    // restored.
    $this->drupalLogin($this->editor);
    $en_revision = $this->loadRevisionTranslation($entity, 'en');
    $this->drupalGet($this->getEditUrl($en_revision));
    $edit = [
      'title[0][value]' => "Test $index.6 EN",
      'moderation_state[0][state]' => 'published',
    ];
    $this->submitForm($edit, 'Save');
    $entity = $this->storage->loadUnchanged($id);
    $this->assertFalse($entity->hasTranslation('it'));
    $this->drupalLogin($this->currentAccount);

    // Create a published translation again and verify it could be deleted.
    $this->drupalGet($add_translation_url);
    $edit = [
      'title[0][value]' => "Test $index.7 IT",
      'moderation_state[0][state]' => 'published',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $entity = $this->storage->loadUnchanged($id);
    $this->assertTrue($entity->hasTranslation('it'));
    $it_revision = $this->loadRevisionTranslation($entity, 'it');
    $this->assertTrue($it_revision->hasTranslation('it'));
    $this->drupalGet($overview_url);
    $this->assertSession()->linkByHrefExists($it_delete_href);

    // Create a translation draft again and verify it cannot be deleted.
    $this->drupalGet($this->getEditUrl($it_revision));
    $edit = [
      'title[0][value]' => "Test $index.8 IT",
      'moderation_state[0][state]' => 'draft',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $entity = $this->storage->loadUnchanged($id);
    $this->assertTrue($entity->hasTranslation('it'));
    $it_revision = $this->loadRevisionTranslation($entity, 'it');
    $this->assertTrue($it_revision->hasTranslation('it'));
    $this->drupalGet($overview_url);
    $this->assertSession()->linkByHrefNotExists($it_delete_href);

    // Delete the translation draft and verify the translation can be deleted
    // again, since the active revision is now a default revision.
    $this->drupalLogin($this->editor);
    $this->drupalGet($it_revision->toUrl('version-history'));
    $revision_deletion_url = Url::fromRoute('node.revision_delete_confirm',
      [
        'node' => $id,
        'node_revision' => $it_revision->getRevisionId(),
      ],
      [
        'language' => ConfigurableLanguage::load('it'),
        'absolute' => FALSE,
      ]
    );
    $revision_deletion_href = $revision_deletion_url->toString();
    $this->getSession()->getDriver()->click("//a[@href='$revision_deletion_href']");
    $this->submitForm([], 'Delete');
    $this->drupalLogin($this->currentAccount);
    $this->drupalGet($overview_url);
    $this->assertSession()->linkByHrefExists($it_delete_href);

    // Verify that now the translation can be deleted.
    $this->drupalGet($this->getEditUrl($it_revision)->setOption('query', ['destination', '/kittens']));
    $this->clickLink('Delete translation');
    $this->submitForm([], 'Delete Italian translation');
    $this->assertStringEndsWith('/kittens', $this->getSession()->getCurrentUrl());

    $entity = $this->storage->loadUnchanged($id);
    $this->assertFalse($entity->hasTranslation('it'));
    $it_revision = $this->loadRevisionTranslation($entity, 'it');
    $this->assertTrue($it_revision->wasDefaultRevision());
    $this->assertTrue($it_revision->hasTranslation('it'));
    $this->assertLessThan($entity->getRevisionId(), $it_revision->getRevisionId());
    $this->drupalGet($overview_url);
    $this->assertSession()->linkByHrefNotExists($this->getEditUrl($it_revision)->toString());
    $this->assertSession()->linkByHrefExists($add_translation_href);
  }

}
