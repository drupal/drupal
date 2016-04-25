<?php

namespace Drupal\system\Tests\Update;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that update hooks are properly run.
 *
 * @group Update
 */
class UpdateSchemaTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['update_test_schema'];

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The update URL.
   *
   * @var string
   */
  protected $updateUrl;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    require_once \Drupal::root() . '/core/includes/update.inc';
    $this->user = $this->drupalCreateUser(['administer software updates', 'access site in maintenance mode']);
    $this->updateUrl = Url::fromRoute('system.db_update');
  }

  /**
   * Tests that update hooks are properly run.
   */
  public function testUpdateHooks() {
    // Verify that the 8000 schema is in place.
    $this->assertEqual(drupal_get_installed_schema_version('update_test_schema'), 8000);
    $this->assertFalse(db_index_exists('update_test_schema_table', 'test'), 'Version 8000 of the update_test_schema module is installed.');

    // Increment the schema version.
    \Drupal::state()->set('update_test_schema_version', 8001);

    $this->drupalLogin($this->user);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->clickLink(t('Continue'));
    $this->assertRaw('Schema version 8001.');
    // Run the update hooks.
    $this->clickLink(t('Apply pending updates'));

    // Ensure schema has changed.
    $this->assertEqual(drupal_get_installed_schema_version('update_test_schema', TRUE), 8001);
    // Ensure the index was added for column a.
    $this->assertTrue(db_index_exists('update_test_schema_table', 'test'), 'Version 8001 of the update_test_schema module is installed.');
  }

}
