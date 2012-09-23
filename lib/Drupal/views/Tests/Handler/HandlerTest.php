<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\HandlerTest.
 */

namespace Drupal\views\Tests\Handler;

use stdClass;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Plugin\views\HandlerBase;

/**
 * Tests abstract handlers of views.
 */
class HandlerTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui');

  public static function getInfo() {
    return array(
      'name' => 'Handlers tests',
      'description' => 'Tests abstract handler definitions.',
      'group' => 'Views',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  function testFilterInOperatorUi() {
    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration'));
    $this->drupalLogin($admin_user);
    menu_router_rebuild();

    $path = 'admin/structure/views/nojs/config-item/test_filter_in_operator_ui/default/filter/type';
    $this->drupalGet($path);
    $this->assertFieldByName('options[expose][reduce]', FALSE);

    $edit = array(
      'options[expose][reduce]' => TRUE,
    );
    $this->drupalPost($path, $edit, t('Apply'));
    $this->drupalGet($path);
    $this->assertFieldByName('options[expose][reduce]', TRUE);
  }

  /**
   * Tests the breakPhraseString() method.
   */
  function testBreakPhraseString() {
    $empty_stdclass = new StdClass();
    $empty_stdclass->operator = 'or';
    $empty_stdclass->value = array();

    // check defaults
    $null = NULL;
    $this->assertEqual($empty_stdclass, HandlerBase::breakPhraseString('', $null));

    $handler = views_get_handler('node', 'title', 'argument');
    $this->assertEqual($handler, HandlerBase::breakPhraseString('', $handler), 'The breakPhraseString() method works correctly.');

    // test ors
    $handler = HandlerBase::breakPhraseString('word1 word2+word');
    $this->assertEqualValue(array('word1', 'word2', 'word'), $handler);
    $this->assertEqual('or', $handler->operator);
    $handler = HandlerBase::breakPhraseString('word1+word2+word');
    $this->assertEqualValue(array('word1', 'word2', 'word'), $handler);
    $this->assertEqual('or', $handler->operator);
    $handler = HandlerBase::breakPhraseString('word1 word2 word');
    $this->assertEqualValue(array('word1', 'word2', 'word'), $handler);
    $this->assertEqual('or', $handler->operator);
    $handler = HandlerBase::breakPhraseString('word-1+word-2+word');
    $this->assertEqualValue(array('word-1', 'word-2', 'word'), $handler);
    $this->assertEqual('or', $handler->operator);
    $handler = HandlerBase::breakPhraseString('wõrd1+wõrd2+wõrd');
    $this->assertEqualValue(array('wõrd1', 'wõrd2', 'wõrd'), $handler);
    $this->assertEqual('or', $handler->operator);

    // test ands.
    $handler = HandlerBase::breakPhraseString('word1,word2,word');
    $this->assertEqualValue(array('word1', 'word2', 'word'), $handler);
    $this->assertEqual('and', $handler->operator);
    $handler = HandlerBase::breakPhraseString('word1 word2,word');
    $this->assertEqualValue(array('word1 word2', 'word'), $handler);
    $this->assertEqual('and', $handler->operator);
    $handler = HandlerBase::breakPhraseString('word1,word2 word');
    $this->assertEqualValue(array('word1', 'word2 word'), $handler);
    $this->assertEqual('and', $handler->operator);
    $handler = HandlerBase::breakPhraseString('word-1,word-2,word');
    $this->assertEqualValue(array('word-1', 'word-2', 'word'), $handler);
    $this->assertEqual('and', $handler->operator);
    $handler = HandlerBase::breakPhraseString('wõrd1,wõrd2,wõrd');
    $this->assertEqualValue(array('wõrd1', 'wõrd2', 'wõrd'), $handler);
    $this->assertEqual('and', $handler->operator);

    // test a single word
    $handler = HandlerBase::breakPhraseString('word');
    $this->assertEqualValue(array('word'), $handler);
    $this->assertEqual('and', $handler->operator);
  }

  /**
   * Tests views_break_phrase function.
   */
  function testBreakPhrase() {
    $empty_stdclass = new StdClass();
    $empty_stdclass->operator = 'or';
    $empty_stdclass->value = array();

    $null = NULL;
    // check defaults
    $this->assertEqual($empty_stdclass, HandlerBase::breakPhrase('', $null));

    $handler = views_get_handler('node', 'title', 'argument');
    $this->assertEqual($handler, HandlerBase::breakPhrase('', $handler), 'The breakPhrase() method works correctly.');

    // Generate three random numbers which can be used below;
    $n1 = rand(0, 100);
    $n2 = rand(0, 100);
    $n3 = rand(0, 100);
    // test ors
    $this->assertEqualValue(array($n1, $n2, $n3), HandlerBase::breakPhrase("$n1 $n2+$n3", $handler));
    $this->assertEqual('or', $handler->operator);
    $this->assertEqualValue(array($n1, $n2, $n3), HandlerBase::breakPhrase("$n1+$n2+$n3", $handler));
    $this->assertEqual('or', $handler->operator);
    $this->assertEqualValue(array($n1, $n2, $n3), HandlerBase::breakPhrase("$n1 $n2 $n3", $handler));
    $this->assertEqual('or', $handler->operator);
    $this->assertEqualValue(array($n1, $n2, $n3), HandlerBase::breakPhrase("$n1 $n2++$n3", $handler));
    $this->assertEqual('or', $handler->operator);

    // test ands.
    $this->assertEqualValue(array($n1, $n2, $n3), HandlerBase::breakPhrase("$n1,$n2,$n3", $handler));
    $this->assertEqual('and', $handler->operator);
    $this->assertEqualValue(array($n1, $n2, $n3), HandlerBase::breakPhrase("$n1,,$n2,$n3", $handler));
    $this->assertEqual('and', $handler->operator);
  }

  /**
   * Check to see if two values are equal.
   *
   * @param $first
   *   The first value to check.
   * @param views_handler $handler
   * @param string $message
   *   The message to display along with the assertion.
   * @param string $group
   *   The type of assertion - examples are "Browser", "PHP".
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertEqualValue($first, $handler, $message = '', $group = 'Other') {
    return $this->assert($first == $handler->value, $message ? $message : t('First value is equal to second value'), $group);
  }

}
