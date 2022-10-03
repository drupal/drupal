<?php

namespace Drupal\Tests\migrate_drupal_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests migrate upgrade credential form with settings in settings.php.
 *
 * @group migrate_drupal_ui
 */
class SettingsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'migrate_drupal',
    'migrate_drupal_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Log in as user 1. Migrations in the UI can only be performed as user 1.
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Test the Credential form with defaults in settings.php.
   *
   * @param string|null $source_connection
   *   The value for the source_connection select field.
   * @param string $version
   *   The legacy Drupal version.
   * @param string[] $manual
   *   User entered form values.
   * @param string[] $databases
   *   Databases data or the settings array.
   * @param string $expected_source_connection
   *   The expected source database connection key.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   *
   * @dataProvider providerTestCredentialForm
   */
  public function testCredentialForm($source_connection, $version, array $manual, array $databases, $expected_source_connection) {
    // Write settings.
    $migrate_file_public_path = '/var/www/drupal7/sites/default/files';
    $migrate_file_private_path = '/var/www/drupal7/sites/default/files/private';
    $settings['settings']['migrate_source_version'] = (object) [
      'value' => $version,
      'required' => TRUE,
    ];
    $settings['settings']['migrate_source_connection'] = (object) [
      'value' => $source_connection,
      'required' => TRUE,
    ];
    $settings['settings']['migrate_file_public_path'] = (object) [
      'value' => $migrate_file_public_path,
      'required' => TRUE,
    ];
    $settings['settings']['migrate_file_private_path'] = (object) [
      'value' => $migrate_file_private_path,
      'required' => TRUE,
    ];
    foreach ($databases as $key => $value) {
      $settings['databases'][$key]['default'] = (object) [
        'value' => $value['default'],
        'required' => TRUE,
      ];
    }
    $this->writeSettings($settings);

    $edits = [];
    // Enter the values manually if provided.
    if (!empty($manual)) {
      $edit = [];
      $driver = 'mysql';
      $edit[$driver]['host'] = $manual['host'];
      $edit[$driver]['database'] = $manual['database'];
      $edit[$driver]['username'] = $manual['username'];
      $edit[$driver]['password'] = $manual['password'];
      $edits = $this->translatePostValues($edit);
    }

    // Start the upgrade process.
    $this->drupalGet('/upgrade');
    $this->submitForm([], 'Continue');
    $session = $this->assertSession();
    // The source connection field is only displayed when there are connections
    // other than default.
    if (empty($databases)) {
      $session->fieldNotExists('source_connection');
    }
    else {
      $session->fieldExists('source_connection');
    }

    // Submit the Credential form.
    $this->submitForm($edits, 'Review upgrade');

    // Confirm that the form actually submitted. IF it submitted, we should see
    // error messages about reading files. If there is no error message, that
    // indicates that the form did not submit.
    $session->responseContains('Failed to read from Document root');

    // Assert the form values.
    $session->fieldValueEquals('version', $version);

    // Check the manually entered credentials or simply the database key.
    if (empty($manual)) {
      $session->fieldValueEquals('source_connection', $expected_source_connection);
    }
    else {
      $session->fieldValueEquals('mysql[host]', $manual['host']);
      $session->fieldValueEquals('mysql[database]', $manual['database']);
      $session->fieldValueEquals('mysql[username]', $manual['username']);
    }

    // Confirm the file paths are correct.
    $session->fieldValueEquals('d6_source_base_path', $migrate_file_public_path);
    $session->fieldValueEquals('source_base_path', $migrate_file_public_path);
    $session->fieldValueEquals('source_private_file_path', $migrate_file_private_path);
  }

  /**
   * Data provider for testCredentialForm.
   */
  public function providerTestCredentialForm() {
    return [
      'no values in settings.php' => [
        'source_connection' => "",
        'version' => '7',
        'manual' => [
          'host' => '172.18.0.2',
          'database' => 'drupal7',
          'username' => 'kate',
          'password' => 'pwd',
        ],
        'databases' => [],
        'expected_source_connection' => '',
      ],
      'single database in settings, migrate' => [
        'source_connection' => 'migrate',
        'version' => '7',
        'manual' => [],
        'databases' => [
          'migrate' => [
            'default' => [
              'database' => 'drupal7',
              'username' => 'user',
              'password' => 'pwd',
              'prefix' => 'test',
              'host' => '172.18.0.3',
              'port' => '3307',
              'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
              'driver' => 'mysql',
            ],
          ],
        ],
        'expected_source_connection' => 'migrate',
      ],
      'migrate_source_connection not set' => [
        'source_connection' => '',
        'version' => '7',
        'manual' => [],
        'databases' => [
          'migrate' => [
            'default' => [
              'database' => 'drupal7',
              'username' => 'user',
              'password' => 'pwd',
              'prefix' => 'test',
              'host' => '172.18.0.3',
              'port' => '3307',
              'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
              'driver' => 'mysql',
            ],
          ],
        ],
        'expected_source_connection' => 'migrate',
      ],
      'single database in settings, legacy' => [
        'source_connection' => 'legacy',
        'version' => '6',
        'manual' => [],
        'databases' => [
          'legacy' => [
            'default' => [
              'database' => 'drupal6',
              'username' => 'user',
              'password' => 'pwd',
              'prefix' => 'test',
              'host' => '172.18.0.6',
              'port' => '3307',
              'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
              'driver' => 'mysql',
            ],
          ],
        ],
        'expected_source_connection' => 'legacy',
      ],
      'two databases in settings' => [
        'source_connection' => 'source2',
        'version' => '7',
        'manual' => [],
        'databases' => [
          'migrate' => [
            'default' => [
              'database' => 'drupal7',
              'username' => 'user',
              'password' => 'pwd',
              'prefix' => 'test',
              'host' => '172.18.0.3',
              'port' => '3307',
              'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
              'driver' => 'mysql',
            ],
          ],
          'legacy' => [
            'default' => [
              'database' => 'site',
              'username' => 'user',
              'password' => 'pwd',
              'prefix' => 'test',
              'host' => '172.18.0.2',
              'port' => '3307',
              'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
              'driver' => 'mysql',
            ],
          ],
        ],
        'expected_source_connection' => 'migrate',
      ],
      'database in settings, but use manual' => [
        'source_connection' => '',
        'version' => '7',
        'manual' => [
          'host' => '172.18.0.2',
          'database' => 'drupal7',
          'username' => 'kate',
          'password' => 'pwd',
        ],
        'databases' => [
          'legacy' => [
            'default' => [
              'database' => 'site',
              'username' => 'user',
              'password' => 'pwd',
              'prefix' => 'test',
              'host' => '172.18.0.2',
              'port' => '3307',
              'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
              'driver' => 'mysql',
            ],
          ],
        ],
        'expected_source_connection' => '',
      ],
    ];
  }

}
