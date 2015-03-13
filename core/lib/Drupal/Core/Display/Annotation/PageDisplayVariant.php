<?php

/**
 * @file
 * Contains \Drupal\Core\Display\Annotation\PageDisplayVariant.
 */

namespace Drupal\Core\Display\Annotation;

/**
 * Defines a page display variant annotation object.
 *
 * Page display variants are a specific type of display variant, intended to
 * render entire pages. They must render the crucial parts of a page, which are:
 * - the title
 * - the main content
 * - any messages (#type => status_messages)
 *
 * @see \Drupal\Core\Display\VariantInterface
 * @see \Drupal\Core\Display\PageVariantInterface
 * @see \Drupal\Core\Display\VariantBase
 * @see \Drupal\Core\Display\VariantManager
 * @see plugin_api
 *
 * @Annotation
 */
class PageDisplayVariant extends DisplayVariant {

}
