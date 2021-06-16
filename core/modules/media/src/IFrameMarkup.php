<?php

namespace Drupal\media;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\MarkupTrait;

/**
 * Defines an object that wraps oEmbed markup for use in an iFrame.
 *
 * This object is not constructed with a known safe string as the strings come
 * from an external site. It must not be used outside the Media module's oEmbed
 * iframe rendering.
 *
 * @internal
 *   This object is an internal part of the oEmbed system and should only be
 *   used in \Drupal\media\Controller\OEmbedIframeController.
 *
 * @see \Drupal\media\Controller\OEmbedIframeController
 */
class IFrameMarkup implements MarkupInterface {
  use MarkupTrait;

}
