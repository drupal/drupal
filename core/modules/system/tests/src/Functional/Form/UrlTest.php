<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the form API URL element.
 *
 * @group Form
 */
class UrlTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['form_test'];

  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that #type 'url' fields are properly validated and trimmed.
   */
  public function testFormUrl(): void {
    $edit = [];
    $edit['url'] = 'http://';
    $edit['url_required'] = ' ';
    $this->drupalGet('form-test/url');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains("The URL http:// is not valid.");
    $this->assertSession()->pageTextContains("Required URL field is required.");

    $edit = [];
    $edit['url'] = "\n";
    $edit['url_required'] = 'http://example.com/   ';
    $this->drupalGet('form-test/url');
    $this->submitForm($edit, 'Submit');
    $values = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertSame('', $values['url']);
    $this->assertEquals('http://example.com/', $values['url_required']);

    $edit = [];
    $edit['url'] = 'http://foo.bar.example.com/';
    $edit['url_required'] = 'https://www.drupal.org/node/1174630?page=0&foo=bar#new';
    $this->drupalGet('form-test/url');
    $this->submitForm($edit, 'Submit');
    $values = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertEquals($edit['url'], $values['url']);
    $this->assertEquals($edit['url_required'], $values['url_required']);
  }

}
