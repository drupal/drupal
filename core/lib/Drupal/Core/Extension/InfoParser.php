<?php

/**
 * @file
 * Contains Drupal\Core\Extension\InfoParser.
 */

namespace Drupal\Core\Extension;

use Drupal\Component\Utility\String;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * Parses extension .info.yml files.
 */
class InfoParser implements InfoParserInterface {

  /**
   * Array of all info keyed by filename.
   *
   * @var array
   */
  protected static $parsedInfos = array();

  /**
   * Symfony YAML parser object.
   *
   * @var \Symfony\Component\Yaml\Parser
   */
  protected $parser;

  /**
   * {@inheritdoc}
   */
  public function parse($filename) {
    if (!isset(static::$parsedInfos[$filename])) {
      if (!file_exists($filename)) {
        static::$parsedInfos[$filename] = array();
      }
      else {
        try {
          static::$parsedInfos[$filename] = $this->getParser()->parse(file_get_contents($filename));
        }
        catch (ParseException $e) {
          $message = String::format("Unable to parse !file. Parser error !error.", array('!file' => $filename, '!error' => $e->getMessage()));
          throw new InfoParserException($message, $filename);
        }
        $missing_keys = array_diff($this->getRequiredKeys(), array_keys(static::$parsedInfos[$filename]));
        if (!empty($missing_keys)) {
          $message = format_plural(count($missing_keys), 'Missing required key (!missing_keys) in !file.', 'Missing required keys (!missing_keys) in !file.', array('!missing_keys' => implode(', ', $missing_keys), '!file' => $filename));
          throw new InfoParserException($message, $filename);
        }
        if (isset(static::$parsedInfos[$filename]['version']) && static::$parsedInfos[$filename]['version'] === 'VERSION') {
          static::$parsedInfos[$filename]['version'] = \Drupal::VERSION;
        }
      }
    }
    return static::$parsedInfos[$filename];
  }

  /**
   * Returns a parser for parsing .info.yml files.
   *
   * @return \Symfony\Component\Yaml\Parser
   *   Symfony YAML parser object.
   */
  protected function getParser() {
    if (!$this->parser) {
      $this->parser = new Parser();
    }
    return $this->parser;
  }

  /**
   * Returns an array of keys required to exist in .info.yml file.
   *
   * @return array
   *   An array of required keys.
   */
  protected function getRequiredKeys() {
    return array('type', 'core', 'name');
  }

}
