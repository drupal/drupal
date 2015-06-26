<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleTranslatedSchemaDefinitionTest.
 */

namespace Drupal\locale\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Adds and configures languages to check field schema definition.
 *
 * @group locale
 */
class LocaleTranslatedSchemaDefinitionTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'locale', 'node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->config('system.site')->set('default_langcode', 'fr')->save();
    // Make sure new entity type definitions are processed.
    \Drupal::service('entity.definition_update_manager')->applyUpdates();
    // Clear all caches so that the base field definition, its cache in the
    // entity manager, the t() cache, etc. are all cleared.
    drupal_flush_all_caches();
  }

  /**
   * Tests that translated field descriptions do not affect the update system.
   */
  function testTranslatedSchemaDefinition() {
    /** @var \Drupal\locale\StringDatabaseStorage $stringStorage */
    $stringStorage = \Drupal::service('locale.storage');

    $source = $stringStorage->createString(array(
      'source' => 'The node ID.',
    ))->save();

    $stringStorage->createTranslation(array(
      'lid' => $source->lid,
      'language' => 'fr',
      'translation' => 'Translated node ID',
    ))->save();

    // Ensure that the field is translated when access through the API.
    $this->assertEqual('Translated node ID', \Drupal::entityManager()->getBaseFieldDefinitions('node')['nid']->getDescription());

    // Assert there are no updates.
    $this->assertFalse(\Drupal::service('entity.definition_update_manager')->needsUpdates());
  }

  /**
   * Tests that translations do not affect the update system.
   */
  function testTranslatedUpdate() {
    // Visit the update page to collect any strings that may be translatable.
    $user = $this->drupalCreateUser(array('administer software updates'));
    $this->drupalLogin($user);
    $update_url = $GLOBALS['base_url'] . '/update.php';
    $this->drupalGet($update_url, array('external' => TRUE));

    /** @var \Drupal\locale\StringDatabaseStorage $stringStorage */
    $stringStorage = \Drupal::service('locale.storage');
    $sources = $stringStorage->getStrings();

    // Translate all source strings found.
    foreach ($sources as $source) {
      $stringStorage->createTranslation(array(
        'lid' => $source->lid,
        'language' => 'fr',
        'translation' => $this->randomMachineName(100),
      ))->save();
    }

    // Ensure that there are no updates just due to translations. Check for
    // markup and a link instead of specific text because text may be
    // translated.
    $this->drupalGet($update_url . '/selection', array('external' => TRUE));
    $this->assertRaw('messages--status', 'No pending updates.');
    $this->assertNoLinkByHref('fr/update.php/run', 'No link to run updates.');
  }
}
