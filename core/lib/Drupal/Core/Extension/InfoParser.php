<?php

namespace Drupal\Core\Extension;

/**
 * Parses extension .info.yml files.
 */
class InfoParser extends InfoParserDynamic {

  /**
   * Array of all info keyed by filename.
   *
   * @var array
   */
  protected static $parsedInfos = [];

  /**
   * {@inheritdoc}
   */
  public function parse($filename) {
    if (!isset(static::$parsedInfos[$filename])) {
      static::$parsedInfos[$filename] = parent::parse($filename);
    }
    return static::$parsedInfos[$filename];
  }

}
