<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests that the node_access system stores the proper fallback marker.
 *
 * @group node
 */
class NodeAccessLanguageFallbackTest extends NodeTestBase {

  /**
   * Enable language and a non-language-aware node access module.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'node_access_test',
    'content_translation',
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

    // After enabling a node access module, the {node_access} table has to be
    // rebuilt.
    node_access_rebuild();

    // Add Hungarian, Catalan, and Afrikaans.
    ConfigurableLanguage::createFromLangcode('hu')->save();
    ConfigurableLanguage::createFromLangcode('ca')->save();
    ConfigurableLanguage::createFromLangcode('af')->save();

    // Enable content translation for the current entity type.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'page', TRUE);
  }

  /**
   * Tests node access fallback handling with multiple node languages.
   */
  public function testNodeAccessLanguageFallback(): void {
    // The node_access_test module allows nodes to be marked private. We need to
    // ensure that system honors the fallback system of node access properly.
    // Note that node_access_test_language is language-sensitive and does not
    // apply to the fallback test.

    // Create one node in Hungarian and marked as private.
    $node = $this->drupalCreateNode([
      'body' => [[]],
      'langcode' => 'hu',
      'private' => [['value' => 1]],
      'status' => 1,
    ]);

    // There should be one entry in node_access, with fallback set to hu.
    $this->checkRecords(1, 'hu');

    // Create a translation user.
    $admin = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
      'translate any entity',
      'administer content translation',
    ]);
    $this->drupalLogin($admin);
    $this->drupalGet('node/' . $node->id() . '/translations');
    $this->assertSession()->statusCodeEquals(200);

    // Create a Catalan translation through the UI.
    $url_options = ['language' => \Drupal::languageManager()->getLanguage('ca')];
    $this->drupalGet('node/' . $node->id() . '/translations/add/hu/ca', $url_options);
    $this->assertSession()->statusCodeEquals(200);
    // Save the form.
    $this->getSession()->getPage()->pressButton('Save (this translation)');
    $this->assertSession()->statusCodeEquals(200);

    // Check the node access table.
    $this->checkRecords(2, 'hu');

    // Programmatically create a translation. This process lets us check that
    // both forms and code behave in the same way.
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    // Reload the node.
    $node = $storage->load(1);
    // Create an Afrikaans translation.
    $translation = $node->addTranslation('af');
    $translation->title->value = $this->randomString();
    $translation->status = 1;
    $node->save();

    // Check the node access table.
    $this->checkRecords(3, 'hu');

    // For completeness, edit the Catalan version again.
    $this->drupalGet('node/' . $node->id() . '/edit', $url_options);
    $this->assertSession()->statusCodeEquals(200);
    // Save the form.
    $this->getSession()->getPage()->pressButton('Save (this translation)');
    $this->assertSession()->statusCodeEquals(200);
    // Check the node access table.
    $this->checkRecords(3, 'hu');
  }

  /**
   * Queries the node_access table and checks for proper storage.
   *
   * @param int $count
   *   The number of rows expected by the query (equal to the translation
   *   count).
   * @param string $langcode
   *   The expected language code set as the fallback property.
   */
  public function checkRecords($count, $langcode = 'hu'): void {
    $select = \Drupal::database()
      ->select('node_access', 'na')
      ->fields('na', ['nid', 'fallback', 'langcode', 'grant_view'])
      ->condition('na.realm', 'node_access_test', '=')
      ->condition('na.gid', 8888, '=');
    $records = $select->execute()->fetchAll();
    // Check that the expected record count is returned.
    $this->assertCount($count, $records);
    // The fallback value is 'hu' and should be set to 1. For other languages,
    // it should be set to 0. Casting to boolean lets us run that comparison.
    foreach ($records as $record) {
      $this->assertEquals((bool) $record->fallback, $record->langcode === $langcode);
    }
  }

}
