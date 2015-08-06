<?php

/**
 * @file
 * Contains \Drupal\Tests\simpletest\Unit\AssertContentTraitTest.
 */

namespace Drupal\Tests\simpletest\Unit;

use Drupal\simpletest\AssertContentTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\simpletest\AssertContentTrait
 * @group simpletest
 */
class AssertContentTraitTest extends UnitTestCase {

  /**
   * @covers ::getTextContent
   */
  public function testGetTextContent() {
    $test = new TestClass();
    $raw_content = <<<EOT

<Head>
<style>
@import url("foo.css");
</style>
</head>
<body>
bar
</body>
EOT;
    $test->_setRawContent($raw_content);
    $this->assertNotContains('foo', $test->_getTextContent());
    $this->assertNotContains('<body>', $test->_getTextContent());
    $this->assertContains('bar', $test->_getTextContent());
  }

}

class TestClass {
  use AssertContentTrait;

  public function _setRawContent($content) {
    $this->setRawContent($content);
  }

  public function _getTextContent() {
    return $this->getTextContent();
  }

}
