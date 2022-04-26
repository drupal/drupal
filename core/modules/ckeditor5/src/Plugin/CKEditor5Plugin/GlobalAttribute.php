<?php

declare(strict_types=1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 Global Attribute for filter_html.
 *
 * Can be used for adding support for any "global attribute". For example:
 * `<* lang>` to allow the `lang` attribute on all supported tags.
 *
 * @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
 *
 * @internal
 *   Plugin classes are internal.
 */
class GlobalAttribute extends CKEditor5PluginDefault {

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    // This plugin is only loaded when filter_html is enabled.
    assert($editor->getFilterFormat()->filters()->has('filter_html'));
    $filter_html = $editor->getFilterFormat()->filters('filter_html');
    $restrictions = HTMLRestrictions::fromFilterPluginInstance($filter_html);

    // Determine which tags are allowed by filter_html, excluding the global
    // attribute `*` HTML tag, because that's what we're expanding this to right
    // now.
    $allowed_elements = $restrictions->getAllowedElements();
    unset($allowed_elements['*']);
    $allowed_tags = array_keys($allowed_elements);

    // Update the static plugin configuration: generate a `name` regular
    // expression to match any of the HTML tags supported by filter_html.
    // @see https://ckeditor.com/docs/ckeditor5/latest/features/general-html-support.html#configuration
    $dynamic_plugin_config = $static_plugin_config;
    $dynamic_plugin_config['htmlSupport']['allow'][0]['name']['regexp']['pattern'] = '/^(' . implode('|', $allowed_tags) . ')$/';
    return $dynamic_plugin_config;
  }

}
