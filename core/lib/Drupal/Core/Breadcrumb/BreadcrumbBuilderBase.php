<?php

/**
 * @file
 * Contains \Drupal\Core\Breadcrumb\BreadcrumbBuilderBase.
 */

namespace Drupal\Core\Breadcrumb;

use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a common base class for breadcrumb builders adding a link generator.
 *
 * @todo This class is now vestigial. Remove it and use the traits in
 *   breadcrumb builders directly.
 */
abstract class BreadcrumbBuilderBase implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;
  use LinkGeneratorTrait;
}
