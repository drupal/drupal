<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the display of the description for hook_update().
 *
 * @group Update
 */
class UpdateDescriptionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests displayed description.
   */
  public function testDescription() {
    $user = $this->drupalCreateUser([
      'administer software updates',
      'access site in maintenance mode',
    ]);
    $this->drupalLogin($user);

    $connection = Database::getConnection();
    // Set the schema version.
    $connection->merge('key_value')
      ->condition('collection', 'system.schema')
      ->condition('name', 'update_test_description')
      ->fields([
        'collection' => 'system.schema',
        'name' => 'update_test_description',
        'value' => 'i:8000;',
      ])
      ->execute();
    // Update core.extension.
    $extensions = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute()
      ->fetchField();
    $extensions = unserialize($extensions);
    $extensions['module']['update_test_description'] = 8000;
    $connection->update('config')
      ->fields([
        'data' => serialize($extensions),
      ])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute();

    // Go to the update page.
    $update_url = Url::fromRoute('system.db_update');

    $this->drupalGet($update_url);
    $this->clickLink(t('Continue'));

    // Check that the description is displayed correctly.
    $this->assertSession()->responseContains('8001 - Update test of slash in description and/or.');
    $this->assertSession()->responseContains('8002 - Update test with multiline description, the quick brown fox jumped over the lazy dog.');
  }

}
