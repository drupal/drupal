<?php

namespace Drupal\system\Tests\Update;

/**
 * Tests system_update_8200().
 *
 * @see system_update_8200()
 *
 * @group Update
 */
class ConfigOverridesUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.filled.standard.php.gz',
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.config-override-fix.php',
    ];
  }

  /**
   * Tests that configuration has been updated.
   */
  public function testUpdatedSite() {
    $key_to_be_removed = 'display.default.display_options.fields.nid';
    /** @var \Drupal\Core\Config\Config $config_override */
    $language_config_override = \Drupal::service('language.config_factory_override');
    $config_override = $language_config_override->getOverride('es', 'views.view.content');
    $this->assertEqual('Spanish ID', $config_override->get($key_to_be_removed)['label'], 'The spanish override for the missing field exists before updating.');
    // Since the above view will be fixed by other updates that fix views
    // configuration for example,
    // views_post_update_update_cacheability_metadata(), also test configuration
    // that has yet to be modified in an update path.
    $config_override = $language_config_override->getOverride('es', 'system.cron');
    $this->assertEqual('Should be cleaned by system_update_8200', $config_override->get('bogus_key'), 'The spanish override in system.cron exists before updating.');

    $this->runUpdates();

    /** @var \Drupal\Core\Config\Config $config_override */
    $config_override = \Drupal::service('language.config_factory_override')->getOverride('es', 'views.view.content');
    $this->assertNull($config_override->get($key_to_be_removed), 'The spanish override for the missing field has been removed.');
    $config_override = $language_config_override->getOverride('es', 'system.cron');
    $this->assertTrue($config_override->isNew(), 'After updating the system.cron spanish override does not exist.');
    $this->assertTrue(empty($config_override->get()), 'After updating the system.cron spanish override has no data.');

    // Test that the spanish overrides still work.
    $this->drupalLogin($this->createUser(['access content overview']));
    $this->drupalGet('admin/content', ['language' => \Drupal::languageManager()->getLanguage('es')]);
    $this->assertText('Spanish Title');
    $this->assertText('Spanish Author');
  }

}
