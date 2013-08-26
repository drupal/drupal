<?php

/**
 * @file
 * Contains \Drupal\breakpoint\Entity\BreakpointInterface.
 */

namespace Drupal\breakpoint;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a breakpoint entity.
 */
interface BreakpointInterface extends ConfigEntityInterface {

  /**
   * Checks if the breakpoint is valid.
   *
   * @throws \Drupal\breakpoint\InvalidBreakpointSourceTypeException
   * @throws \Drupal\breakpoint\InvalidBreakpointSourceException
   * @throws \Drupal\breakpoint\InvalidBreakpointNameException
   * @throws \Drupal\breakpoint\InvalidBreakpointMediaQueryException
   *
   * @see isValidMediaQuery()
   */
  public function isValid();

  /**
   * Checks if a mediaQuery is valid.
   *
   * @throws \Drupal\breakpoint\InvalidBreakpointMediaQueryException
   *
   * @return bool
   *   Returns TRUE if the media query is valid.
   *
   * @see http://www.w3.org/TR/css3-mediaqueries/
   * @see http://www.w3.org/Style/CSS/Test/MediaQueries/20120229/reports/implement-report.html
   * @see https://github.com/adobe/webkit/blob/master/Source/WebCore/css/
   */
  public static function isValidMediaQuery($media_query);

}
