<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;

/**
 * Adds and configures languages to check field schema definition.
 *
 * @group locale
 */
class LocaleTranslatedSchemaDefinitionTest extends BrowserTestBase {

  use RequirementsPageTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language', 'locale', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->config('system.site')->set('default_langcode', 'fr')->save();

    // Clear all caches so that the base field definition, its cache in the
    // entity field manager, the t() cache, etc. are all cleared.
    drupal_flush_all_caches();
  }

  /**
   * Tests that translated field descriptions do not affect the update system.
   */
  public function testTranslatedSchemaDefinition() {
    /** @var \Drupal\locale\StringDatabaseStorage $stringStorage */
    $stringStorage = \Drupal::service('locale.storage');

    $source = $stringStorage->createString([
      'source' => 'Revision ID',
    ])->save();

    $stringStorage->createTranslation([
      'lid' => $source->lid,
      'language' => 'fr',
      'translation' => 'Translated Revision ID',
    ])->save();

    // Ensure that the field is translated when access through the API.
    $this->assertEqual('Translated Revision ID', \Drupal::service('entity_field.manager')->getBaseFieldDefinitions('node')['vid']->getLabel());

    // Assert there are no updates.
    $this->assertFalse(\Drupal::service('entity.definition_update_manager')->needsUpdates());
  }

  /**
   * Tests that translations do not affect the update system.
   */
  public function testTranslatedUpdate() {
    // Visit the update page to collect any strings that may be translatable.
    $user = $this->drupalCreateUser(['administer software updates']);
    $this->drupalLogin($user);
    $update_url = $GLOBALS['base_url'] . '/update.php';
    $this->drupalGet($update_url, ['external' => TRUE]);

    /** @var \Drupal\locale\StringDatabaseStorage $stringStorage */
    $stringStorage = \Drupal::service('locale.storage');
    $sources = $stringStorage->getStrings();

    // Translate all source strings found.
    foreach ($sources as $source) {
      $stringStorage->createTranslation([
        'lid' => $source->lid,
        'language' => 'fr',
        'translation' => $this->randomMachineName(100),
      ])->save();
    }

    // Ensure that there are no updates just due to translations. Check for
    // markup and a link instead of specific text because text may be
    // translated.
    $this->drupalGet($update_url . '/selection', ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->drupalGet($update_url . '/selection', ['external' => TRUE]);
    $this->assertRaw('messages--status');
    $this->assertSession()->linkByHrefNotExists('fr/update.php/run', 'No link to run updates.');
  }

}
