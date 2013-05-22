<?php

/**
 * @file
 * Contains \Drupal\filter\Annotation\Filter.
 */

namespace Drupal\filter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an filter annotation object.
 *
 * @Annotation
 */
class Filter extends Plugin {

  public $title;
  public $description = '';
  public $weight = 0;
  public $status = FALSE;
  public $cache = TRUE;
  public $settings = array();

}
