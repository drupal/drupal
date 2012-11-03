<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\TwigReferenceObjectTest.
 */

namespace Drupal\system\Tests\Theme;

/**
 * TwigReferenceObjectTest class.
 */
class TwigReferenceObjectTest {
  public function __construct($nid, $title) {
    $this->nid = $nid;
    $this->title = $title;
  }
}
