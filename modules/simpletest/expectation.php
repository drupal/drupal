<?php
// $Id$

/**
 *    Assertion that can display failure information.
 *    Also includes various helper methods.
 *    @package SimpleTest
 *    @subpackage UnitTester
 *    @abstract
 */
class SimpleExpectation {
  var $_dumper = false;
  var $_message;

  /**
   *    Creates a dumper for displaying values and sets
   *    the test message.
   *    @param string $message    Customised message on failure.
   */
  function SimpleExpectation($message = '%s') {
    $this->_message = $message;
  }

  /**
   *    Tests the expectation. True if correct.
   *    @param mixed $compare        Comparison value.
   *    @return boolean              True if correct.
   *    @access public
   *    @abstract
   */
  function test($compare) {}

  /**
   *    Returns a human readable test message.
   *    @param mixed $compare      Comparison value.
   *    @return string             Description of success
   *                               or failure.
   *    @access public
   *    @abstract
   */
  function testMessage($compare) {}

  /**
   *    Overlays the generated message onto the stored user
   *    message. An additional message can be interjected.
   *    @param mixed $compare        Comparison value.
   *    @param SimpleDumper $dumper  For formatting the results.
   *    @return string               Description of success
   *                                 or failure.
   *    @access public
   */
  function overlayMessage($compare, $dumper) {
    $this->_dumper = $dumper;
    return sprintf($this->_message, $this->testMessage($compare));
  }

  /**
   *    Accessor for the dumper.
   *    @return SimpleDumper    Current value dumper.
   *    @access protected
   */
  function &_getDumper() {
    if (!$this->_dumper) {
      $dumper = &new SimpleDumper();
      return $dumper;
    }
    return $this->_dumper;
  }

  /**
   *    Test to see if a value is an expectation object.
   *    A useful utility method.
   *    @param mixed $expectation    Hopefully an Epectation
   *                                 class.
   *    @return boolean              True if descended from
   *                                 this class.
   *    @access public
   *    @static
   */
  function isExpectation($expectation) {
    return is_object($expectation) && is_a($expectation, 'SimpleExpectation');
  }
}

/**
 *    A wildcard expectation always matches.
 *    @package SimpleTest
 *    @subpackage MockObjects
 */
class AnythingExpectation extends SimpleExpectation {

  /**
   *    Tests the expectation. Always true.
   *    @param mixed $compare  Ignored.
   *    @return boolean        True.
   *    @access public
   */
  function test($compare) {
    return true;
  }

  /**
   *    Returns a human readable test message.
   *    @param mixed $compare      Comparison value.
   *    @return string             Description of success
   *                               or failure.
   *    @access public
   */
  function testMessage($compare) {
    $dumper = &$this->_getDumper();
    return 'Anything always matches [' . $dumper->describeValue($compare) . ']';
  }
}

/**
 *    An expectation that passes on boolean true.
 *    @package SimpleTest
 *    @subpackage MockObjects
 */
class TrueExpectation extends SimpleExpectation {

  /**
   *    Tests the expectation.
   *    @param mixed $compare  Should be true.
   *    @return boolean        True on match.
   *    @access public
   */
  function test($compare) {
    return (boolean)$compare;
  }

  /**
   *    Returns a human readable test message.
   *    @param mixed $compare      Comparison value.
   *    @return string             Description of success
   *                               or failure.
   *    @access public
   */
  function testMessage($compare) {
    $dumper = &$this->_getDumper();
    return 'Expected true, got [' . $dumper->describeValue($compare) . ']';
  }
}

