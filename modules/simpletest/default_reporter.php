<?php
// $Id: default_reporter.php,v 1.1 2008/04/20 18:34:43 dries Exp $

/**
 *    Parser for command line arguments. Extracts
 *    the a specific test to run and engages XML
 *    reporting when necessary.
 */
class SimpleCommandLineParser {
  protected $to_property = array('c' => 'case', 't' => 'test');
  protected $case = '';
  protected $test = '';
  protected $xml = false;

  function SimpleCommandLineParser($arguments) {
    if (!is_array($arguments)) {
      return;
    }
    foreach ($arguments as $i => $argument) {
      if (preg_match('/^--?(test|case|t|c)=(.+)$/', $argument, $matches)) {
        $this->{$this->_parseProperty($matches[1])} = $matches[2];
      }
      elseif (preg_match('/^--?(test|case|t|c)$/', $argument, $matches)) {
        if (isset($arguments[$i + 1])) {
          $this->{$this->_parseProperty($matches[1])} = $arguments[$i + 1];
        }
      }
      elseif (preg_match('/^--?(xml|x)$/', $argument)) {
        $this->xml = true;
      }
    }
  }
  function _parseProperty($property) {
    if (isset($this->to_property[$property])) {
      return $this->to_property[$property];
    }
    else {
      return $property;
    }
  }
  function getTest() {
    return $this->test;
  }

  function getTestCase() {
    return $this->case;
  }

  function isXml() {
    return $this->xml;
  }
}

/**
 *    The default reporter used by SimpleTest's autorun
 *    feature. The actual reporters used are dependency
 *    injected and can be overridden.
 */
class DefaultReporter extends SimpleReporterDecorator {
  /**
   *  Assembles the appopriate reporter for the environment.
   */
  function DefaultReporter() {
    if (SimpleReporter::inCli()) {
      global $argv;
      $parser = new SimpleCommandLineParser($argv);
      $interfaces = $parser->isXml() ? array('XmlReporter') : array('TextReporter');
      $reporter = &new SelectiveReporter(SimpleTest::preferred($interfaces), $parser->getTestCase(), $parser->getTest());
    }
    else {
      $reporter = &new SelectiveReporter(SimpleTest::preferred('HtmlReporter'), @$_GET['c'], @$_GET['t']);
    }
    $this->SimpleReporterDecorator($reporter);
  }
}
