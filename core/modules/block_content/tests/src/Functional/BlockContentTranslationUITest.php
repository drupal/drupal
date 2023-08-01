<?php

namespace Drupal\Tests\block_content\Functional;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\Tests\content_translation\Functional\ContentTranslationUITestBase;

/**
 * Tests the block content translation UI.
 *
 * @group block_content
 */
class BlockContentTranslationUITest extends ContentTranslationUITestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'content_translation',
    'block',
    'field_ui',
    'block_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $defaultCacheContexts = [
    'languages:language_interface',
    'session',
    'theme',
    'url.path',
    'url.query_args',
    'user.permissions',
    'user.roles:authenticated',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityTypeId = 'block_content';
    $this->bundle = 'basic';
    $this->testLanguageSelector = FALSE;
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * {@inheritdoc}
   */
  protected function setupBundle() {
    // Create the basic bundle since it is provided by standard.
    $bundle = BlockContentType::create([
      'id' => $this->bundle,
      'label' => $this->bundle,
      'revision' => FALSE,
    ]);
    $bundle->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), [
      'translate any entity',
      'access administration pages',
      'administer blocks',
      'administer block_content fields',
      'access block library',
      'create basic block content',
      'edit any basic block content',
      'delete any basic block content',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getNewEntityValues($langcode) {
    return ['info' => $this->randomMachineName()] + parent::getNewEntityValues($langcode);
  }

  /**
   * Returns an edit array containing the values to be posted.
   */
  protected function getEditValues($values, $langcode, $new = FALSE) {
    $edit = parent::getEditValues($values, $langcode, $new);
    foreach ($edit as $property => $value) {
      if ($property == 'info') {
        $edit['info[0][value]'] = $value;
        unset($edit[$property]);
      }
    }
    return $edit;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestBasicTranslation() {
    parent::doTestBasicTranslation();

    // Ensure that a block translation can be created using the same description
    // as in the original language.
    $default_langcode = $this->langcodes[0];
    $values = $this->getNewEntityValues($default_langcode);
    $storage = \Drupal::entityTypeManager()->getStorage($this->entityTypeId);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->create(['type' => 'basic'] + $values);
    $entity->save();
    $entity->addTranslation('it', $values);

    try {
      $entity->save();
    }
    catch (\Exception $e) {
      $this->fail('Blocks can have translations with the same "info" value.');
    }

    // Check that the translate operation link is shown.
    $this->drupalGet('admin/content/block');
    $this->assertSession()->linkByHrefExists('admin/content/block/' . $entity->id() . '/translations');
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestTranslationEdit() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $languages = $this->container->get('language_manager')->getLanguages();

    foreach ($this->langcodes as $langcode) {
      // We only want to test the title for non-english translations.
      if ($langcode != 'en') {
        $options = ['language' => $languages[$langcode]];
        $url = $entity->toUrl('edit-form', $options);
        $this->drupalGet($url);
        $this->assertSession()->pageTextContains("Edit {$entity->bundle()} {$entity->getTranslation($langcode)->label()} [{$languages[$langcode]->getName()} translation]");
      }
    }
  }

}
