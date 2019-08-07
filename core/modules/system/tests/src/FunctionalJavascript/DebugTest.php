<?php

namespace Drupal\Tests\system\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Class DebugTest
 *
 * @group debug
 */
class DebugTest extends WebDriverTestBase {

  public function testXPath() {
    $driver = $this->getSession()->getDriver();
    $this->assertEquals('Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver', get_class($driver));
    $this->drupalGet('debug.html');
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->assertEquals('//html', $page->getXpath());
    $this->assertNotEmpty($driver->getContent());
    //file_put_contents('/Users/ted.bowman/Sites/www/ted.html', $driver->getContent());
    /** @var \Behat\Mink\Element\NodeElement $body_element */
    $body_element = $driver->find('//*[@id="findme"]')[0];
    self::assertNotEmpty($body_element);
    $this->assertEquals('h1', $body_element->getTagName());
    $assert_session->elementExists('xpath', '/body');
    $assert_session->elementNotExists('xpath', '/aksdjfajsdf');
    $assert_session->elementExists('css', 'body');
  }


}
