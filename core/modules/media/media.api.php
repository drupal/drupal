<?php

/**
 * @file
 * Hooks related to Media and its plugins.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the information provided in \Drupal\media\Annotation\MediaSource.
 *
 * @param array $sources
 *   The array of media source plugin definitions, keyed by plugin ID.
 */
function hook_media_source_info_alter(array &$sources) {
  $sources['youtube']['label'] = t('Youtube rocks!');
}

/**
 * @} End of "addtogroup hooks".
 */
