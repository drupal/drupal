<?php

namespace Drupal\layout_builder\Entity;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\layout_builder\LayoutBuilderEnabledInterface;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\LayoutBuilderOverridableInterface;

/**
 * Provides an interface for entity displays that have layout.
 */
interface LayoutEntityDisplayInterface extends EntityDisplayInterface, SectionListInterface, LayoutBuilderEnabledInterface, LayoutBuilderOverridableInterface {}
