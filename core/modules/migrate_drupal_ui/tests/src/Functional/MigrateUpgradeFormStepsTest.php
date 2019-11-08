<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Drupal\Tests\migrate_drupal\Traits\CreateTestContentEntitiesTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\WebAssert;

/**
 * Tests the flow of the Migrate Drupal UI form.
 *
 * @group migrate_drupal_ui
 */
class MigrateUpgradeFormStepsTest extends BrowserTestBase {

  use MigrationConfigurationTrait;
  use CreateTestContentEntitiesTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Log in as user 1. Migrations in the UI can only be performed as user 1.
    $this->drupalLogin($this->rootUser);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return __DIR__ . '/files';
  }

  /**
   * Tests the flow of the Migrate Drupal UI form.
   *
   * The Migrate Drupal UI uses several forms to guide you through the upgrade
   * process. The forms displayed depend on if this is an incremental migration
   * or if there are potential ID conflicts. The forms are to be displayed in
   * this order; Overview or Incremental, if a migration has already been run
   * then Credential, Id conflict, if conflicts are detected, and lastly Review.
   */
  public function testMigrateUpgradeReviewPage() {
    /** @var \Drupal\Core\TempStore\PrivateTempStore  $store */
    $store = \Drupal::service('tempstore.private')->get('migrate_drupal_ui');
    $state = \Drupal::service('state');

    // Test that when data required by a form is missing that the correct first
    // form is displayed. The first form for an initial migration is the
    // Overview form and for an incremental migration it is the Incremental
    // form.
    $session = $this->assertSession();
    $expected['initial'] = 'Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal 8.';
    $expected['incremental'] = "An upgrade has already been performed on this site.";

    foreach (['/upgrade', '/upgrade/incremental'] as $expected) {
      if ($expected === '/upgrade/incremental') {
        // Set a performed time to signify an incremental migration. The time
        // value is a UNIX timestamp.
        $state->set('migrate_drupal_ui.performed', 1);
      }
      // Test that an invalid step to any form goes to the correct first form.
      $store->set('step', 'foo');
      $this->assertFirstForm($session, $expected);
      // Test that an undefined step to any form goes to the correct first form.
      $store->delete('step');
      $this->assertFirstForm($session, $expected);

      // For forms that require data from the private store, test that when that
      // data is missing the correct first page is displayed.
      // The Id conflict form requires the migrations array.
      $store->delete('migrations');
      $store->set('step', 'idconflict');
      $this->drupalGet('/upgrade/idconflict');
      $session->addressEquals($expected);

      // The Review form requires version, migrations and system_data. Test
      // three times with only one of the variables missing.
      $store->delete('version');
      $store->set('migrations', ['foo', 'bar']);
      $store->set('system_data', ['bar', 'foo']);
      $store->set('step', 'review');
      $this->drupalGet('/upgrade/review');
      $session->addressEquals($expected);

      $store->set('version', '6');
      $store->delete('migrations');
      $store->set('system_data', ['bar', 'foo']);
      $store->set('step', 'review');
      $this->drupalGet('/upgrade/review');
      $session->addressEquals($expected);

      $store->set('version', '6');
      $store->set('migrations', ['foo', 'bar']);
      $store->delete('system_data');
      $store->set('step', 'review');
      $this->drupalGet('/upgrade/review');
      $session->addressEquals($expected);
    }

    // Test that the credential form is displayed for incremental migrations.
    $store->set('step', 'overview');
    $this->drupalGet('/upgrade');
    $session->pageTextContains('An upgrade has already been performed on this site. To perform a new migration, create a clean and empty new install of Drupal 8. Rollbacks are not yet supported through the user interface.');
    $this->drupalPostForm(NULL, [], t('Import new configuration and content from old site'));
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
  }

  /**
   * Helper to test that a path goes to the Overview form.
   *
   * @param \Drupal\Tests\WebAssert $session
   *   The WebAssert object.
   * @param string $expected
   *   The expected response text.
   */
  protected function assertFirstForm(WebAssert $session, $expected) {
    $paths = [
      '',
      '/incremental',
      '/credentials',
      '/idconflict',
      '/review',
    ];
    foreach ($paths as $path) {
      $this->drupalGet('/upgrade' . $path);
      $session->addressEquals($expected);
    }
  }

}
