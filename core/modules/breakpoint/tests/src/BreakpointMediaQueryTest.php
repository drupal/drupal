<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointMediaQueryTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\Tests\UnitTestCase;
use Drupal\breakpoint\Entity\Breakpoint;
use Drupal\breakpoint\InvalidBreakpointMediaQueryException;

/**
 * Tests validation of media queries.
 *
 * @group breakpoint
 */
class BreakpointMediaQueryTest extends UnitTestCase {

  /**
   * Test valid media queries.
   */
  public function testValidMediaQueries() {
    $media_queries = array(
      // Bartik breakpoints.
      '(min-width: 0px)',
      'all and (min-width: 560px) and (max-width:850px)',
      'all and (min-width: 851px)',
      // Seven breakpoints.
      '(min-width: 0em)',
      'screen and (min-width: 40em)',
      // Stark breakpoints.
      '(min-width: 0px)',
      'all and (min-width: 480px) and (max-width: 959px)',
      'all and (min-width: 960px)',
      // Other media queries.
      '(orientation)',
      'all and (orientation)',
      'not all and (orientation)',
      'only all and (orientation)',
      'screen and (width)',
      'screen and (width: 0)',
      'screen and (width: 0px)',
      'screen and (width: 0em)',
      'screen and (min-width: -0)',
      'screen and (max-width: 0)',
      'screen and (max-width: 0.3)',
      'screen and (min-width)',
      // Multiline and comments.
      'screen and /* this is a comment */ (min-width)',
      "screen\nand /* this is a comment */ (min-width)",
      "screen\n\nand /* this is\n a comment */ (min-width)",
      // Unrecognized features are allowed.
      'screen and (-webkit-min-device-pixel-ratio: 7)',
      'screen and (min-orientation: landscape)',
      'screen and (max-orientation: landscape)',
    );

    foreach ($media_queries as $media_query) {
      $this->assertTrue(Breakpoint::isValidMediaQuery($media_query), $media_query . ' is valid.');
    }
  }

  /**
   * Test invalid media queries.
   */
  public function testInvalidMediaQueries() {
    $media_queries = array(
      '',
      'not (orientation)',
      'only (orientation)',
      'all and not all',
      'screen and (width: 0xx)',
      'screen and (width: -8xx)',
      'screen and (width: -xx)',
      'screen and (width: xx)',
      'screen and (width: px)',
      'screen and (width: -8px)',
      'screen and (width: -0.8px)',
      'screen and (height: 0xx)',
      'screen and (height: -8xx)',
      'screen and (height: -xx)',
      'screen and (height: xx)',
      'screen and (height: px)',
      'screen and (height: -8px)',
      'screen and (height: -0.8px)',
      'screen and (device-width: 0xx)',
      'screen and (device-width: -8xx)',
      'screen and (device-width: -xx)',
      'screen and (device-width: xx)',
      'screen and (device-width: px)',
      'screen and (device-width: -8px)',
      'screen and (device-width: -0.8px)',
      'screen and (device-height: 0xx)',
      'screen and (device-height: -8xx)',
      'screen and (device-height: -xx)',
      'screen and (device-height: xx)',
      'screen and (device-height: px)',
      'screen and (device-height: -8px)',
      'screen and (device-height: -0.8px)',
      'screen and (min-orientation)',
      'screen and (max-orientation)',
      'screen and (orientation: bogus)',
      '(orientation: bogus)',
      'screen and (ori"entation: bogus)',
    );

    foreach ($media_queries as $media_query) {
      try {
        $this->assertFalse(Breakpoint::isValidMediaQuery($media_query), $media_query . ' is not valid.');
      }
      catch (InvalidBreakpointMediaQueryException $e) {
        $this->assertTrue(TRUE, sprintf('%s is not valid.', $media_query));
      }
    }
  }
}
