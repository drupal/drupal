<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests that new translations do not delete existing ones.
 *
 * @group content_translation
 */
class ContentTranslationNewTranslationWithExistingRevisionsTest extends ContentTranslationPendingRevisionTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'content_translation',
    'content_translation_test',
    'language',
    'node',
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
    $this->doSetup();
    $this->enableContentModeration();
  }

  /**
   * Tests a translation with a draft is not deleted.
   */
  public function testDraftTranslationIsNotDeleted() {
    $this->drupalLogin($this->translator);

    // Create a test node.
    $values = [
      'title' => "Test EN",
      'moderation_state' => 'published',
    ];
    $id = $this->createEntity($values, 'en');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->storage->load($id);

    // Add a published translation.
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
    $edit = [
      'title[0][value]' => "Test IT",
      'moderation_state[0][state]' => 'published',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $it_revision = $this->loadRevisionTranslation($entity, 'it');

    // Add a draft translation.
    $this->drupalGet($this->getEditUrl($it_revision));
    $edit = [
      'title[0][value]' => "Test IT 2",
      'moderation_state[0][state]' => 'draft',
    ];
    $this->submitForm($edit, 'Save (this translation)');

    // Add a new draft translation.
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
    $edit = [
      'title[0][value]' => "Test FR",
      'moderation_state[0][state]' => 'published',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    // Check the first translation still exists.
    $entity = $this->storage->loadUnchanged($id);
    $this->assertTrue($entity->hasTranslation('it'));
  }

  /**
   * Test translation delete hooks are not invoked.
   */
  public function testCreatingNewDraftDoesNotInvokeDeleteHook() {
    $this->drupalLogin($this->translator);

    // Create a test node.
    $values = [
      'title' => "Test EN",
      'moderation_state' => 'published',
    ];
    $id = $this->createEntity($values, 'en');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->storage->load($id);

    // Add a published translation.
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
    $edit = [
      'title[0][value]' => "Test IT",
      'moderation_state[0][state]' => 'published',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $it_revision = $this->loadRevisionTranslation($entity, 'it');

    // Add a draft translation.
    $this->drupalGet($this->getEditUrl($it_revision));
    $edit = [
      'title[0][value]' => "Test IT 2",
      'moderation_state[0][state]' => 'draft',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    // Add a new draft translation.
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
    $edit = [
      'title[0][value]' => "Test FR",
      'moderation_state[0][state]' => 'draft',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    // If the translation delete hook was incorrectly invoked, the state
    // variable would be set.
    $this->assertNull($this->container->get('state')->get('content_translation_test.translation_deleted'));
  }

}
