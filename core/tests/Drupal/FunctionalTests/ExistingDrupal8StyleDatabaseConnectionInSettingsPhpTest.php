<?php

namespace Drupal\FunctionalTests;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;

/**
 * @group Database
 */
class ExistingDrupal8StyleDatabaseConnectionInSettingsPhpTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $driver = Database::getConnection()->driver();
    if (!in_array($driver, ['mysql', 'pgsql', 'sqlite'])) {
      $this->markTestSkipped("This test does not support the {$driver} database driver.");
    }

    $filename = $this->siteDirectory . '/settings.php';
    chmod($filename, 0777);
    $contents = file_get_contents($filename);

    $autoload = "'autoload' => 'core/modules/$driver/src/Driver/Database/$driver/',";
    $contents = str_replace($autoload, '', $contents);
    $namespace_search = "'namespace' => 'Drupal\\\\$driver\\\\Driver\\\\Database\\\\$driver',";
    $namespace_replace = "'namespace' => 'Drupal\\\\Core\\\\Database\\\\Driver\\\\$driver',";
    $contents = str_replace($namespace_search, $namespace_replace, $contents);
    file_put_contents($filename, $contents);
  }

  /**
   * Confirms that the site works with Drupal 8 style database connection array.
   */
  public function testExistingDrupal8StyleDatabaseConnectionInSettingsPhp() {
    $this->drupalLogin($this->drupalCreateUser());
    $this->assertSession()->addressEquals('user/2');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure that we are have tested with the Drupal 8 style database
    // connection array.
    $filename = $this->siteDirectory . '/settings.php';
    $contents = file_get_contents($filename);
    $driver = Database::getConnection()->driver();
    $this->assertStringContainsString("'namespace' => 'Drupal\\\\Core\\\\Database\\\\Driver\\\\$driver',", $contents);
    $this->assertStringContainsString("'driver' => '$driver',", $contents);
    $this->assertStringNotContainsString("'autoload' => 'core/modules/$driver/src/Driver/Database/$driver/", $contents);
  }

}
