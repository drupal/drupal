<?php

namespace Drupal\Tests\field\Functional\EntityReference;

use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the translation of entity reference field display on nodes.
 *
 * @group entity_reference
 */
class EntityReferenceFieldTranslatedReferenceViewTest extends BrowserTestBase {

  /**
   * Flag indicating whether the field is translatable.
   *
   * @var bool
   */
  protected $translatable = TRUE;

  /**
   * The langcode of the source language.
   *
   * @var string
   */
  protected $baseLangcode = 'en';

  /**
   * Target langcode for translation.
   *
   * @var string
   */
  protected $translateToLangcode = 'hu';

  /**
   * The test entity type name.
   *
   * @var string
   */
  protected $testEntityTypeName = 'node';

  /**
   * Entity type which have the entity reference field.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $referrerType;

  /**
   * Entity type which can be referenced.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $referencedType;

  /**
   * The referrer entity.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $referrerEntity;

  /**
   * The entity to refer.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $referencedEntityWithoutTranslation;

  /**
   * The entity to refer.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $referencedEntityWithTranslation;

  /**
   * The machine name of the entity reference field.
   *
   * @var string
   */
  protected $referenceFieldName = 'test_reference_field';

  /**
   * The label of the untranslated referenced entity, used in assertions.
   *
   * @var string
   */
  protected $labelOfNotTranslatedReference;

  /**
   * The original label of the referenced entity, used in assertions.
   *
   * @var string
   */
  protected $originalLabel;

  /**
   * The translated label of the referenced entity, used in assertions.
   *
   * @var string
   */
  protected $translatedLabel;

  /**
   * An user with permission to edit the referrer entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'language',
    'content_translation',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp() {
    parent::setUp();

    $this->labelOfNotTranslatedReference = $this->randomMachineName();
    $this->originalLabel = $this->randomMachineName();
    $this->translatedLabel = $this->randomMachineName();

    $this->setUpLanguages();

    // We setup languages, so we need to ensure that the language manager
    // and language path processor is updated.
    $this->rebuildContainer();

    $this->setUpContentTypes();
    $this->enableTranslation();
    $this->setUpEntityReferenceField();
    $this->createContent();

    $this->webUser = $this->drupalCreateUser([
      'edit any ' . $this->referrerType->id() . ' content',
    ]);
  }

  /**
   * Tests if the entity is displayed in an entity reference field.
   */
  public function testEntityReferenceDisplay() {
    // Create a translated referrer entity.
    $this->referrerEntity = $this->createReferrerEntity();
    $this->assertEntityReferenceDisplay();
    $this->assertEntityReferenceFormDisplay();

    // Disable translation for referrer content type.
    $this->drupalLogin($this->rootUser);
    $this->drupalPostForm('admin/config/regional/content-language', ['settings[node][referrer][translatable]' => FALSE], t('Save configuration'));
    $this->drupalLogout();

    // Create a referrer entity without translation.
    $this->referrerEntity = $this->createReferrerEntity(FALSE);
    $this->assertEntityReferenceDisplay();
    $this->assertEntityReferenceFormDisplay();
  }

  /**
   * Assert entity reference display.
   */
  protected function assertEntityReferenceDisplay() {
    $url = $this->referrerEntity->toUrl();
    $translation_url = $this->referrerEntity->toUrl('canonical', ['language' => ConfigurableLanguage::load($this->translateToLangcode)]);

    $this->drupalGet($url);
    $this->assertText($this->labelOfNotTranslatedReference, 'The label of not translated reference is displayed.');
    $this->assertText($this->originalLabel, 'The default label of translated reference is displayed.');
    $this->assertNoText($this->translatedLabel, 'The translated label of translated reference is not displayed.');
    $this->drupalGet($translation_url);
    $this->assertText($this->labelOfNotTranslatedReference, 'The label of not translated reference is displayed.');
    $this->assertNoText($this->originalLabel, 'The default label of translated reference is not displayed.');
    $this->assertText($this->translatedLabel, 'The translated label of translated reference is displayed.');
  }

  /**
   * Assert entity reference form display.
   */
  protected function assertEntityReferenceFormDisplay() {
    $this->drupalLogin($this->webUser);
    $url = $this->referrerEntity->toUrl('edit-form');
    $translation_url = $this->referrerEntity->toUrl('edit-form', ['language' => ConfigurableLanguage::load($this->translateToLangcode)]);

    $this->drupalGet($url);
    $this->assertSession()->fieldValueEquals('test_reference_field[0][target_id]', $this->originalLabel . ' (1)');
    $this->assertSession()->fieldValueEquals('test_reference_field[1][target_id]', $this->labelOfNotTranslatedReference . ' (2)');
    $this->drupalGet($translation_url);
    $this->assertSession()->fieldValueEquals('test_reference_field[0][target_id]', $this->translatedLabel . ' (1)');
    $this->assertSession()->fieldValueEquals('test_reference_field[1][target_id]', $this->labelOfNotTranslatedReference . ' (2)');
    $this->drupalLogout();
  }

  /**
   * Adds additional languages.
   */
  protected function setUpLanguages() {
    ConfigurableLanguage::createFromLangcode($this->translateToLangcode)->save();
  }

  /**
   * Creates a test subject contents, with translation.
   */
  protected function createContent() {
    $this->referencedEntityWithTranslation = $this->createReferencedEntityWithTranslation();
    $this->referencedEntityWithoutTranslation = $this->createNotTranslatedReferencedEntity();
  }

  /**
   * Enables translations where it needed.
   */
  protected function enableTranslation() {
    // Enable translation for the entity types and ensure the change is picked
    // up.
    \Drupal::service('content_translation.manager')->setEnabled($this->testEntityTypeName, $this->referrerType->id(), TRUE);
    \Drupal::service('content_translation.manager')->setEnabled($this->testEntityTypeName, $this->referencedType->id(), TRUE);

    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Adds term reference field for the article content type.
   */
  protected function setUpEntityReferenceField() {
    FieldStorageConfig::create([
      'field_name' => $this->referenceFieldName,
      'entity_type' => $this->testEntityTypeName,
      'type' => 'entity_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'translatable' => $this->translatable,
      'settings' => [
        'allowed_values' => [
          [
            'target_type' => $this->testEntityTypeName,
          ],
        ],
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => $this->referenceFieldName,
      'bundle' => $this->referrerType->id(),
      'entity_type' => $this->testEntityTypeName,
    ])
      ->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay($this->testEntityTypeName, $this->referrerType->id())
      ->setComponent($this->referenceFieldName, [
        'type' => 'entity_reference_autocomplete',
      ])
      ->save();
    $display_repository->getViewDisplay($this->testEntityTypeName, $this->referrerType->id())
      ->setComponent($this->referenceFieldName, [
        'type' => 'entity_reference_label',
      ])
      ->save();
  }

  /**
   * Create content types.
   */
  protected function setUpContentTypes() {
    $this->referrerType = $this->drupalCreateContentType([
        'type' => 'referrer',
        'name' => 'Referrer',
      ]);
    $this->referencedType = $this->drupalCreateContentType([
        'type' => 'referenced_page',
        'name' => 'Referenced Page',
      ]);
  }

  /**
   * Create a referenced entity with a translation.
   */
  protected function createReferencedEntityWithTranslation() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::entityTypeManager()->getStorage($this->testEntityTypeName)->create([
      'title' => $this->originalLabel,
      'type' => $this->referencedType->id(),
      'description' => [
        'value' => $this->randomMachineName(),
        'format' => 'basic_html',
      ],
      'langcode' => $this->baseLangcode,
    ]);
    $node->save();
    $node->addTranslation($this->translateToLangcode, [
      'title' => $this->translatedLabel,
    ]);
    $node->save();

    return $node;
  }

  /**
   * Create the referenced entity.
   */
  protected function createNotTranslatedReferencedEntity() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::entityTypeManager()->getStorage($this->testEntityTypeName)->create([
      'title' => $this->labelOfNotTranslatedReference,
      'type' => $this->referencedType->id(),
      'description' => [
        'value' => $this->randomMachineName(),
        'format' => 'basic_html',
      ],
      'langcode' => $this->baseLangcode,
    ]);
    $node->save();

    return $node;
  }

  /**
   * Create the referrer entity.
   */
  protected function createReferrerEntity($translatable = TRUE) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::entityTypeManager()->getStorage($this->testEntityTypeName)->create([
      'title' => $this->randomMachineName(),
      'type' => $this->referrerType->id(),
      'description' => [
        'value' => $this->randomMachineName(),
        'format' => 'basic_html',
      ],
      $this->referenceFieldName => [
        ['target_id' => $this->referencedEntityWithTranslation->id()],
        ['target_id' => $this->referencedEntityWithoutTranslation->id()],
      ],
      'langcode' => $this->baseLangcode,
    ]);
    if ($translatable) {
      $node->addTranslation($this->translateToLangcode, $node->toArray());
    }
    $node->save();

    return $node;
  }

}
