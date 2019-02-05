<?php

namespace Drupal\FunctionalTests\Routing;

use Drupal\Tests\BrowserTestBase;

/**
 * @group routing
 */
class DefaultFormatTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'default_format_test'];

  public function testFoo() {
    $this->drupalGet('/default_format_test/human');
    $this->assertSame('format:html', $this->getSession()->getPage()->getContent());
    $this->assertSame('MISS', $this->drupalGetHeader('X-Drupal-Cache'));
    $this->drupalGet('/default_format_test/human');
    $this->assertSame('format:html', $this->getSession()->getPage()->getContent());
    $this->assertSame('HIT', $this->drupalGetHeader('X-Drupal-Cache'));

    $this->drupalGet('/default_format_test/machine');
    $this->assertSame('format:json', $this->getSession()->getPage()->getContent());
    $this->assertSame('MISS', $this->drupalGetHeader('X-Drupal-Cache'));
    $this->drupalGet('/default_format_test/machine');
    $this->assertSame('format:json', $this->getSession()->getPage()->getContent());
    $this->assertSame('HIT', $this->drupalGetHeader('X-Drupal-Cache'));
  }

  public function testMultipleRoutesWithSameSingleFormat() {
    $this->drupalGet('/default_format_test/machine');
    $this->assertSame('format:json', $this->getSession()->getPage()->getContent());
  }

}
