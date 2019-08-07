<?php

namespace Drupal\Tests\system\FunctionalJavascript;

use Behat\Mink\Driver\Selenium2Driver;
use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Class DebugTest
 *
 * @group debug
 */
class DebugTest extends WebDriverTestBase {

  /**
   * Passes for ted and Peter?
   */
  public function testXPath() {
    $driver = $this->getSession()->getDriver();
    $this->assertEquals('Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver', get_class($driver));
    $this->drupalGet('debug.html');
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->assertEquals('//html', $page->getXpath());
    $this->assertNotEmpty($driver->getContent());

    $this->drupalGet('user');
    $page = $this->getSession()->getPage();
    $this->assertEquals('//html', $page->getXpath());
    $this->assertNotEmpty($driver->getContent());

  }


  /**
   * works for ted, not Peter
   */
  public function testDrupalGetDrupalHtml() {
    $this->drupalGet('user');
    $driver = $this->getSession()->getDriver();

    $doc = new \DOMDocument();
    $content = $driver->getContent();
    // We got the actual page from Drupal.
    $this->assertTrue(strstr($content, 'Username') !== FALSE);
    $doc->loadXML($content);

    // This doesn't work for Peter?
    $xpath = new \DOMXPath($doc);
    /** @var \DOMNodeList $elements */
    $elements = $xpath->query("//html/body");
    $this->assertNotEmpty($elements);
    foreach ($elements as $element) {
      $this->assertEquals('body', $element->nodeName);
    }
  }

  /**
   * Works for both Ted and peter.
   */
  public function testDrupalGetValidHtml() {
    $this->drupalGet('validxml.xml');
    $driver = $this->getSession()->getDriver();
    $doc = new \DOMDocument();
    $content = $driver->getContent();
    $doc->loadXML($content);

    $xpath = new \DOMXPath($doc);

    // example 1: for everything with an id
    $elements = $xpath->query("//book");
    $this->assertNotEmpty($elements);
  }

}
