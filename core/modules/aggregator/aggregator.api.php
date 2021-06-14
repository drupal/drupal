<?php

/**
 * @file
 * Aggregator API documentation.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations on the available fetchers.
 *
 * @param array[] $info
 *   Array of fetcher plugins
 */
function hook_aggregator_fetcher_info_alter(array &$info) {
  if (empty($info['foo_fetcher'])) {
    return;
  }

  $info['foo_fetcher']['class'] = Drupal\foo\Plugin\aggregator\fetcher\FooDefaultFetcher::class;
}

/**
 * Perform alterations on the available parsers.
 *
 * @param array[] $info
 *   Array of parser plugins
 */
function hook_aggregator_parser_info_alter(array &$info) {
  if (empty($info['foo_parser'])) {
    return;
  }

  $info['foo_parser']['class'] = Drupal\foo\Plugin\aggregator\parser\FooDefaultParser::class;
}

/**
 * Perform alterations on the available processors.
 *
 * @param array[] $info
 *   Array of processor plugins
 */
function hook_aggregator_processor_info_alter(array &$info) {
  if (empty($info['foo_processor'])) {
    return;
  }

  $info['foo_processor']['class'] = Drupal\foo\Plugin\aggregator\processor\FooDefaultProcessor::class;
}

/**
 * @} End of "addtogroup hooks".
 */
