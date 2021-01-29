<?php

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
  public function testFormUrl() {
    $edit = [];
    $edit['url'] = 'http://';
    $edit['url_required'] = ' ';
    $this->drupalPostForm('form-test/url', $edit, 'Submit');
    $this->assertRaw(t('The URL %url is not valid.', ['%url' => 'http://']));
    $this->assertRaw(t('@name field is required.', ['@name' => 'Required URL']));

    $edit = [];
    $edit['url'] = "\n";
    $edit['url_required'] = 'http://example.com/   ';
    $this->drupalPostForm('form-test/url', $edit, 'Submit');
    $values = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertSame('', $values['url']);
    $this->assertEqual('http://example.com/', $values['url_required']);

    $edit = [];
    $edit['url'] = 'http://foo.bar.example.com/';
    $edit['url_required'] = 'https://www.drupal.org/node/1174630?page=0&foo=bar#new';
    $this->drupalPostForm('form-test/url', $edit, 'Submit');
    $values = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertEqual($edit['url'], $values['url']);
    $this->assertEqual($edit['url_required'], $values['url_required']);
  }

}
