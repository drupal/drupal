<?php

declare(strict_types=1);

namespace Drupal\Tests\default_admin\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests migration of inherited browser storage keys.
 */
#[Group('default_admin')]
#[RunTestsInSeparateProcesses]
final class LegacyStorageTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'default_admin';

  /**
   * Tests that legacy values migrate without replacing newer values.
   */
  public function testLegacyStorageMigration(): void {
    $this->drupalGet('<front>');
    $this->getSession()->executeScript(<<<'JS'
      localStorage.setItem('GinDarkMode', 'true');
      localStorage.setItem('Drupal.gin.dark_mode', 'true');
      localStorage.setItem('GinSidebarOpen', 'true');
      localStorage.setItem('Drupal.gin.toolbarExpanded', 'false');
      localStorage.setItem('Drupal.gin.sidebarExpanded.mobile', 'true');
      localStorage.setItem('Drupal.gin.sidebarExpanded.desktop', 'false');
      localStorage.setItem('Drupal.gin.sidebarWidth', '320px');
      localStorage.setItem('Drupal.defaultAdmin.sidebarWidth', '400px');
      JS);

    $this->getSession()->reload();

    $expected = [
      'GinDarkMode' => NULL,
      'Drupal.gin.dark_mode' => NULL,
      'GinSidebarOpen' => NULL,
      'Drupal.gin.toolbarExpanded' => NULL,
      'Drupal.gin.sidebarExpanded.mobile' => NULL,
      'Drupal.gin.sidebarExpanded.desktop' => NULL,
      'Drupal.gin.sidebarWidth' => NULL,
      'Drupal.navigation.sidebarExpanded' => 'true',
      'Drupal.defaultAdmin.sidebarExpanded.mobile' => 'true',
      'Drupal.defaultAdmin.sidebarExpanded.desktop' => 'false',
      'Drupal.defaultAdmin.sidebarWidth' => '400px',
    ];
    foreach ($expected as $key => $value) {
      $encoded_key = json_encode($key, JSON_THROW_ON_ERROR);
      $encoded_value = json_encode($value, JSON_THROW_ON_ERROR);
      $condition = "localStorage.getItem($encoded_key) === $encoded_value";
      $this->assertJsCondition($condition);
    }
  }

}
