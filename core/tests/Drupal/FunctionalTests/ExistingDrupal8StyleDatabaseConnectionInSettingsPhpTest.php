<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests;

use Drupal\Core\Database\Connection;
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

    // Add a replica connection to the database settings.
    $contents .= "\$databases['default']['replica'][] = array (\n";
    $contents .= "  'database' => 'db',\n";
    $contents .= "  'username' => 'db',\n";
    $contents .= "  'password' => 'db',\n";
    $contents .= "  'prefix' => 'test22806835',\n";
    $contents .= "  'host' => 'db',\n";
    $contents .= "  'port' => 3306,\n";
    $contents .= "  $namespace_replace\n";
    $contents .= "  'driver' => 'mysql',\n";
    $contents .= ");\n";

    file_put_contents($filename, $contents);
  }

  /**
   * Confirms that the site works with Drupal 8 style database connection array.
   */
  public function testExistingDrupal8StyleDatabaseConnectionInSettingsPhp(): void {
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

  /**
   * Confirms that the replica database connection works.
   */
  public function testReplicaDrupal8StyleDatabaseConnectionInSettingsPhp(): void {
    $this->drupalLogin($this->drupalCreateUser());

    $replica = Database::getConnection('replica', 'default');
    $this->assertInstanceOf(Connection::class, $replica);
  }

}
