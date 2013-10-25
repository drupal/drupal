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
 * Class that parses Drupal module's, theme's and profile's .info.yml files.
 */
class InfoParser implements InfoParserInterface {

  /**
   * Array of all info keyed by filename.
   *
   * @var array
   */
  protected $parsedInfos = array();

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
    if (!isset($this->parsedInfos[$filename])) {
      if (!file_exists($filename)) {
        $this->parsedInfos[$filename] = array();
      }
      else {
        try {
          $this->parsedInfos[$filename] = $this->getParser()->parse(file_get_contents($filename));
        }
        catch (ParseException $e) {
          $message = String::format("Unable to parse !file. Parser error !error.", array('!file' => $filename, '!error' => $e->getMessage()));
          throw new InfoParserException($message, $filename);
        }
        $missing_keys = array_diff($this->getRequiredKeys(), array_keys($this->parsedInfos[$filename]));
        if (!empty($missing_keys)) {
          $message = format_plural(count($missing_keys), 'Missing required key (!missing_keys) in !file.', 'Missing required keys (!missing_keys) in !file.', array('!missing_keys' => implode(', ', $missing_keys), '!file' => $filename));
          throw new InfoParserException($message, $filename);
        }
        if (isset($this->parsedInfos[$filename]['version']) && $this->parsedInfos[$filename]['version'] === 'VERSION') {
          $this->parsedInfos[$filename]['version'] = \Drupal::VERSION;
        }
      }
    }
    return $this->parsedInfos[$filename];
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
    return array('name', 'type');
  }

}
