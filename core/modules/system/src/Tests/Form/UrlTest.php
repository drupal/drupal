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
  public static $modules = array('form_test');

  protected $profile = 'testing';

  /**
   * Tests that #type 'url' fields are properly validated and trimmed.
   */
  function testFormUrl() {
    $edit = array();
    $edit['url'] = 'http://';
    $edit['url_required'] = ' ';
    $this->drupalPostForm('form-test/url', $edit, 'Submit');
    $this->assertRaw(t('The URL %url is not valid.', array('%url' => 'http://')));
    $this->assertRaw(t('@name field is required.', array('@name' => 'Required URL')));

    $edit = array();
    $edit['url'] = "\n";
    $edit['url_required'] = 'http://example.com/   ';
    $values = Json::decode($this->drupalPostForm('form-test/url', $edit, 'Submit'));
    $this->assertIdentical($values['url'], '');
    $this->assertEqual($values['url_required'], 'http://example.com/');

    $edit = array();
    $edit['url'] = 'http://foo.bar.example.com/';
    $edit['url_required'] = 'https://www.drupal.org/node/1174630?page=0&foo=bar#new';
    $values = Json::decode($this->drupalPostForm('form-test/url', $edit, 'Submit'));
    $this->assertEqual($values['url'], $edit['url']);
    $this->assertEqual($values['url_required'], $edit['url_required']);
  }

}
