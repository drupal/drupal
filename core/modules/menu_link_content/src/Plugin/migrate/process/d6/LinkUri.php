<?php

namespace Drupal\menu_link_content\Plugin\migrate\process\d6;

use \Drupal\menu_link_content\Plugin\migrate\process\LinkUri as RealLinkUri;

/**
 * Processes a link path into an 'internal:' or 'entity:' URI.
 *
 * @deprecated in Drupal 8.2.0, will be removed before Drupal 9.0.0. Use
 * \Drupal\menu_link_content\Plugin\migrate\process\LinkUri instead.
 *
 * @see https://www.drupal.org/node/2761389
 */
class LinkUri extends RealLinkUri {}
