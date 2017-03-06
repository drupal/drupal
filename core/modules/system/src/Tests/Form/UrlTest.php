<?php

namespace Drupal\system\Tests\Form;

use Drupal\Component\Serialization\Json;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the form API URL element.
 *
 * @group Form
 */
class UrlTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['form_test'];

  protected $profile = 'testing';

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
    $values = Json::decode($this->drupalPostForm('form-test/url', $edit, 'Submit'));
    $this->assertIdentical($values['url'], '');
    $this->assertEqual($values['url_required'], 'http://example.com/');

    $edit = [];
    $edit['url'] = 'http://foo.bar.example.com/';
    $edit['url_required'] = 'https://www.drupal.org/node/1174630?page=0&foo=bar#new';
    $values = Json::decode($this->drupalPostForm('form-test/url', $edit, 'Submit'));
    $this->assertEqual($values['url'], $edit['url']);
    $this->assertEqual($values['url_required'], $edit['url_required']);
  }

}
