<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\EventSubscriber;

use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the fast 404 functionality.
 *
 * @group EventSubscriber
 *
 * @see \Drupal\Core\EventSubscriber\Fast404ExceptionHtmlSubscriber
 */
class Fast404Test extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * Tests the fast 404 functionality.
   */
  public function testFast404(): void {
    $this->drupalGet('does-not-exist');
    $this->assertSession()->statusCodeEquals(404);
    // Regular 404s will contain CSS from the system module.
    $this->assertSession()->responseContains('modules/system/css/');
    $this->drupalGet('does-not-exist.txt');
    $this->assertSession()->statusCodeEquals(404);
    // Fast 404s do not have any CSS.
    $this->assertSession()->responseNotContains('modules/system/css/');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache', 'Miss');
    // Fast 404s can be cached.
    $this->drupalGet('does-not-exist.txt');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache', 'Hit');
    $this->assertSession()->pageTextNotContains('Oops I did it again!');

    // Changing configuration should invalidate the cache.
    $this->config('system.performance')->set('fast_404.html', '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>Oops I did it again!</h1><p>The requested URL "@path" was not found on this server.</p></body></html>')->save();
    $this->drupalGet('does-not-exist.txt');
    $this->assertSession()->responseNotContains('modules/system/css/');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache', 'Miss');
    $this->assertSession()->pageTextContains('Oops I did it again!');

    // Ensure disabling works.
    $this->config('system.performance')->set('fast_404.enabled', FALSE)->save();
    $this->drupalGet('does-not-exist.txt');
    $this->assertSession()->responseContains('modules/system/css/');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache', 'Miss');
    $this->assertSession()->pageTextNotContains('Oops I did it again!');

    // Ensure settings.php can override settings.
    $settings['config']['system.performance']['fast_404']['enabled'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    // Changing settings using an override means we need to rebuild everything.
    $this->rebuildAll();
    $this->drupalGet('does-not-exist.txt');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->responseNotContains('modules/system/css/');
    // Fast 404s returned via the exception subscriber still have the
    // X-Generator header.
    $this->assertSession()->responseHeaderContains('X-Generator', 'Drupal');
  }

  /**
   * Tests the fast 404 functionality.
   */
  public function testFast404PrivateFiles(): void {
    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    $file_url = 'system/files/test/private-file-test.txt';
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(404);

    // Create a private file for testing accessible by the admin user.
    \Drupal::service('file_system')->mkdir($this->privateFilesDirectory . '/test');
    $filepath = 'private://test/private-file-test.txt';
    $contents = "file_put_contents() doesn't seem to appreciate empty strings so let's put in some data.";
    file_put_contents($filepath, $contents);
    $file = File::create([
      'uri' => $filepath,
      'uid' => $admin->id(),
    ]);
    $file->save();

    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($contents);
  }

}
