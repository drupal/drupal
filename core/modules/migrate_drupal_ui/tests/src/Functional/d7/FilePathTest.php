<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional\d7;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Tests\ExtensionListTestTrait;
use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase;

// cspell:ignore terok

/**
 * Tests the Drupal 7 public and private file migrations.
 *
 * To test file migrations both the public and private test source files are
 * created in the temporary directory of the destination test site. Tests are
 * done with the source files at the top level temporary directory and sub paths
 * from that.
 *
 * @group migrate_drupal_ui
 */
class FilePathTest extends MigrateUpgradeTestBase {

  use ExtensionListTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fs;

  /**
   * The base path to the source files on the destination site.
   *
   * @var string[]
   */
  protected $localDirectory = [];

  /**
   * The file scheme variables in the source database.
   *
   * These are 'file_private_path', 'file_public_path', and
   * 'file_temporary_path',
   *
   * @var string[]
   */
  protected $sourceFileScheme = [];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'migrate',
    'migrate_drupal',
    'migrate_drupal_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fs = \Drupal::service('file_system');
    $this->loadFixture($this->getModulePath('migrate_drupal') . '/tests/fixtures/drupal7.php');
  }

  /**
   * Executes all steps of migrations upgrade.
   *
   * @param string $file_private_path
   *   The source database file_private_path value.
   * @param string $file_public_path
   *   The source database file_public_path value.
   * @param string $file_temporary_path
   *   The source database file_temporary_path value.
   * @param string $private
   *   The path to the source private files.
   * @param string $public
   *   The path to the source public files.
   * @param string $temporary
   *   The path to the source temporary files.
   *
   * @dataProvider providerTestFilePath
   */
  public function testFilePath(string $file_private_path, string $file_public_path, string $file_temporary_path, string $private, string $public, string $temporary) {
    $this->sourceFileScheme['private'] = $file_private_path;
    $this->sourceFileScheme['public'] = $file_public_path;
    $this->sourceFileScheme['temporary'] = $file_temporary_path;

    $this->localDirectory['private'] = $private;
    $this->localDirectory['public'] = $public;
    $this->localDirectory['temporary'] = $temporary;

    // Create the source files.
    $this->makeFiles();

    // Set the source db variables.
    $this->sourceDatabase->update('variable')
      ->fields(['value' => serialize($file_private_path)])
      ->condition('name', 'file_private_path')
      ->execute();
    $this->sourceDatabase->update('variable')
      ->fields(['value' => serialize($file_public_path)])
      ->condition('name', 'file_public_path')
      ->execute();
    $this->sourceDatabase->update('variable')
      ->fields(['value' => serialize($file_temporary_path)])
      ->condition('name', 'file_temporary_path')
      ->execute();

    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $driver = $connection_options['driver'];

    // Use the driver connection form to get the correct options out of the
    // database settings. This supports all of the databases we test against.
    $drivers = drupal_get_database_types();
    $form = $drivers[$driver]->getFormOptions($connection_options);
    $connection_options = array_intersect_key($connection_options, $form + $form['advanced_options']);
    // Remove isolation_level since that option is not configurable in the UI.
    unset($connection_options['isolation_level']);
    $edit = [
      $driver => $connection_options,
      'version' => '7',
    ];
    if (count($drivers) !== 1) {
      $edit['driver'] = $driver;
    }
    // Set the public and private base paths for the Credential Form.
    $edit['source_private_file_path'] = $this->fs->realpath($this->getSourcePath('private'));
    $edit['source_base_path'] = $this->fs->realpath($this->getSourcePath('public'));
    $edits = $this->translatePostValues($edit);

    // Start the upgrade.
    $this->drupalGet('/upgrade');
    $this->submitForm([], 'Continue');
    $this->submitForm($edits, 'Review upgrade');

    // The migrations are now in store - remove all but the file migrations.
    $store = \Drupal::service('tempstore.private')->get('migrate_drupal_ui');
    $migration_array = array_intersect_key(
      $store->get('migrations'),
      array_flip(['d7_file', 'd7_file_private'])
    );
    $store->set('migrations', $migration_array);

    // Perform the migrations.
    $this->submitForm([], 'Perform upgrade');
    $this->resetAll();
    $this->assertFileMigrations();
  }

  /**
   * Data provider of test dates for file path test.
   *
   * @return string[][]
   *   An array of test data.
   */
  public function providerTestFilePath() {
    return [
      'All source base paths are at temporary' => [
        'sites/default/private',
        'sites/default/files',
        '/tmp',
        '',
        '',
        '',
      ],
      'The private files are in a subdirectory' => [
        'sites/default/private',
        'sites/default/files',
        '/tmp',
        'abc',
        '',
        '',
      ],
      ' The public files are in a subdirectory' => [
        'sites/default/private',
        'sites/default/files',
        '/tmp',
        '',
        'def',
        '',
      ],
      'The private, public and temporary files are in separate subdirectories' => [
        'private',
        'files',
        '/tmp',
        'abc',
        'def',
        'xyz',
      ],
    ];
  }

  /**
   * Creates files for the test.
   *
   * The source files are written to a subdirectory of the temporary files
   * directory of the test sites. The subdirectory path always ends with the
   * path to the relevant scheme as set in the source variable table.
   *
   * For example:
   *   The source site files_managed table.
   *     uri: public://foo.txt
   *     filename: foo.txt
   *   The source site variable table.
   *     file_public_path: sites/default/files
   *   Local directory
   *     /bar
   *
   * The resulting directory is /bar/sites/default/files/foo.txt.
   */
  protected function makeFiles() {
    // Get file information from the source database.
    foreach ($this->getManagedFiles() as $file) {
      $this->assertSame(1, preg_match('/^(private|public|temporary):/', $file['uri'], $matches));
      $scheme = $matches[1];
      $path = $this->sourceFileScheme[$scheme] ?? '';
      $filepath = implode('/', [
        $this->getSourcePath($scheme),
        $path,
        $file['filename'],
      ]);
      // Create the file.
      $source_file = @fopen($filepath, 'w');
      if (!$source_file) {
        // If fopen didn't work, make sure there's a writable directory in
        // place.
        $dir = $this->fs->dirname($filepath);
        $this->fs->prepareDirectory($dir, FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
        // Let's try that fopen again.
        $source_file = @fopen($filepath, 'w');
      }
      fwrite($source_file, '42');
    }
  }

  /**
   * Gets the source base path for the Credential form.
   *
   * @param string $scheme
   *   The file scheme.
   */
  public function getSourcePath($scheme) {
    $base_path = $this->localDirectory[$scheme] ?: '';
    // Puts the source files in the site temp directory.
    return $this->tempFilesDirectory . '/' . $base_path;
  }

  /**
   * Gets the file data.
   *
   * @return string[][]
   *   Data from the source file_managed table.
   */
  public function getManagedFiles() {
    return [
      [
        'filename' => 'cube.jpeg',
        'uri' => 'public://cube.jpeg',
      ],
      [
        'filename' => 'ds9.txt',
        'uri' => 'public://ds9.txt',
      ],
      [
        'filename' => 'Babylon5.txt',
        'uri' => 'private://Babylon5.txt',
      ],
      [
        'filename' => 'TerokNor.txt',
        'uri' => 'temporary://TerokNor.txt',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return '';
  }

}
