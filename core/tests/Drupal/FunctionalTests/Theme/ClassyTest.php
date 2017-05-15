<?php

namespace Drupal\FunctionalTests\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the classy theme.
 *
 * @group classy
 */
class ClassyTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->assertTrue($this->container->get('theme_installer')->install(['classy']));
    $this->container->get('config.factory')
      ->getEditable('system.theme')
      ->set('default', 'classy')
      ->save();
  }

  /**
   * Tests that the Classy theme always adds its message CSS.
   *
   * @see classy.info.yml
   */
  public function testRegressionMissingMessagesCss() {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('classy/css/components/messages.css');
  }

}
