<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests manifest file.
 *
 * @group Theme
 */
class ManifestTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['manifest_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests manifest file.
   */
  public function testManifest() {
    $this->drupalGet('<front>');

    // Check that the link to the manifest file is in the response.
    $this->assertSession()->responseContains('system/manifest');

    $content = $this->drupalGet('system/manifest');
    $manifest_content = json_decode($content, TRUE);
    $this->assertNotEmpty($manifest_content);
    $this->assertArrayHasKey('short_name', $manifest_content);
    $this->assertSame('Drupal', $manifest_content['short_name']);

    $system_config = $this->container->get('config.factory')->getEditable('system.site');
    $system_config->set('manifest.short_name', 'Llama');
    $system_config->save();

    $content = $this->drupalGet('system/manifest');
    $manifest_content = json_decode($content, TRUE);
    $this->assertNotEmpty($manifest_content);
    $this->assertArrayHasKey('short_name', $manifest_content);
    $this->assertSame('Llama', $manifest_content['short_name']);

    // Check that the alter hook hasn't fired.
    $this->assertArrayNotHasKey('altered', $manifest_content);
  }

  /**
   * Tests the alter hook for the manifest data.
   */
  public function testManifestAlter() {
    \Drupal::state()->set('manifest_generation_test_link_alter', TRUE);

    $content = $this->drupalGet('system/manifest');
    $manifest_content = json_decode($content, TRUE);

    $this->assertArrayHasKey('altered', $manifest_content);
    $this->assertSame('Owl', $manifest_content['altered']);
  }

}
