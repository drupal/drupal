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
    $this->drupalGet('user');
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->assertEquals('//html', $page->getXpath());
    $this->assertNotEmpty($driver->getContent());
    /** @var \Behat\Mink\Element\NodeElement $body_element */
    $body_element = $driver->find('//html/body')[0];
    self::assertNotEmpty($body_element);
    $this->assertEquals('body', $body_element->getTagName());
    $assert_session->elementExists('xpath', '/body');
    $assert_session->elementNotExists('xpath', '/aksdjfajsdf');
    $assert_session->elementExists('css', 'body');
  }


}
