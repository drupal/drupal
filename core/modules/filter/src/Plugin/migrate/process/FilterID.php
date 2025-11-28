<?php

namespace Drupal\filter\Plugin\migrate\process;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\StaticMap;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

// cspell:ignore abbrfilter adsense autofloat biblio cincopa codefilter
// cspell:ignore commonmark deepzoom emogrifier emptyparagraphkiller forena
// cspell:ignore gotwo htmlpurifier htmltidy intlinks lazyloader linktitle
// cspell:ignore multicolumn multilink mytube openlayers opengraph sanitizable
// cspell:ignore shortcode spamspan typogrify wordfilter xbbcode

/**
 * Determines the filter ID.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *    replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess('filter_id')]
class FilterID extends StaticMap implements ContainerFactoryPluginInterface {

  /**
   * The filter plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\Drupal\Component\Plugin\FallbackPluginManagerInterface
   */
  protected $filterManager;

  /**
   * FilterID constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $filter_manager
   *   The filter plugin manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translator
   *   (optional) The string translation service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PluginManagerInterface $filter_manager, ?TranslationInterface $translator = NULL) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->filterManager = $filter_manager;
    $this->stringTranslation = $translator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.filter'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $plugin_id = parent::transform($value, $migrate_executable, $row, $destination_property);

    // If the static map is bypassed on failure, the returned plugin ID will be
    // an array if $value was. Plugin IDs cannot be arrays, so flatten it before
    // passing it into the filter manager.
    if (is_array($plugin_id)) {
      $plugin_id = implode(':', $plugin_id);
    }

    if ($this->filterManager->hasDefinition($plugin_id)) {
      return $plugin_id;
    }
    else {
      if (in_array(static::getSourceFilterType($value), [
        FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
        FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      ], TRUE)) {
        $message = sprintf('Filter %s could not be mapped to an existing filter plugin; omitted since it is a transformation-only filter. Install and configure a successor after the migration.', $plugin_id);
        $migrate_executable->saveMessage($message, MigrationInterface::MESSAGE_INFORMATIONAL);
        $this->stopPipeline();
        return NULL;
      }
      $fallback = $this->filterManager->getFallbackPluginId($plugin_id);

      // @see \Drupal\filter\Plugin\migrate\process\FilterSettings::transform()
      $message = sprintf('Filter %s could not be mapped to an existing filter plugin; defaulting to %s and dropping all settings. Either redo the migration with the module installed that provides an equivalent filter, or modify the text format after the migration to remove this filter if it is no longer necessary.', $plugin_id, $fallback);
      $migrate_executable->saveMessage($message, MigrationInterface::MESSAGE_WARNING);

      return $fallback;
    }
  }

  /**
   * Gets the Drupal 8 filter type for a Drupal 7 filter.
   *
   * @param string $filter_id
   *   A Drupal 7 filter ID.
   *
   * @return int
   *   One of:
   *   - FilterInterface::TYPE_MARKUP_LANGUAGE
   *   - FilterInterface::TYPE_HTML_RESTRICTOR
   *   - FilterInterface::TYPE_TRANSFORM_REVERSIBLE
   *   - FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
   *
   * @see \Drupal\filter\Plugin\FilterInterface::getType()
   */
  protected static function getSourceFilterType($filter_id) {
    // Drupal 7 core filters.
    // - https://git.drupalcode.org/project/drupal/blob/7.69/modules/filter/filter.module#L1229
    // - https://git.drupalcode.org/project/drupal/blob/7.69/modules/php/php.module#L139
    return match ($filter_id) {
      'filter_html' => FilterInterface::TYPE_HTML_RESTRICTOR,
      'filter_url' => FilterInterface::TYPE_MARKUP_LANGUAGE,
      'filter_autop' => FilterInterface::TYPE_MARKUP_LANGUAGE,
      'filter_htmlcorrector' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      'filter_html_escape' => FilterInterface::TYPE_HTML_RESTRICTOR,
      'php_code' => FilterInterface::TYPE_MARKUP_LANGUAGE,
      // Drupal 7 contrib filters.
      // https://www.drupal.org/project/abbrfilter
      'abbrfilter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/ace_editor
      'ace_editor' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/adsense
      'adsense' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/api
      'api_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/api_tokens
      'api_tokens' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/autofloat
      'filter_autofloat' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/bbcode
      'bbcode' => FilterInterface::TYPE_MARKUP_LANGUAGE,
      // https://www.drupal.org/project/biblio
      'biblio_filter_reference', 'biblio_filter_inline_reference' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/caption
      'caption' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      // https://www.drupal.org/project/caption_filter
      'caption_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/cincopa
      'filter_cincopa' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/ckeditor_blocks
      'ckeditor_blocks' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/ckeditor_filter
      'ckeditor_filter' => FilterInterface::TYPE_HTML_RESTRICTOR,
      // https://www.drupal.org/project/ckeditor_link
      'ckeditor_link_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/ckeditor_swf
      'ckeditor_swf_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/codefilter
      'codefilter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/collapse_text
      'collapse_text_filter' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      // https://www.drupal.org/project/columns_filter
      'columns_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/commonmark
      'commonmark' => FilterInterface::TYPE_MARKUP_LANGUAGE,
      // https://www.drupal.org/project/commons_hashtags
      'filter_hashtags' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/deepzoom
      'deepzoom' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/editor
      'editor_align', 'editor_caption' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      // https://www.drupal.org/project/elf
      'elf' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      // https://www.drupal.org/project/emogrifier
      'filter_emogrifier' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/emptyparagraphkiller
      'emptyparagraphkiller' => FilterInterface::TYPE_HTML_RESTRICTOR,
      // https://www.drupal.org/project/entity_embed
      'entity_embed' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      'filter_align' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      // https://www.drupal.org/project/ext_link_page
      'ext_link_page' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/filter_html_image_secure
      'filter_html_image_secure' => FilterInterface::TYPE_HTML_RESTRICTOR,
      // https://www.drupal.org/project/filter_transliteration
      'filter_transliteration' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/flickr
      'flickr_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/float_filter
      'float_filter' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      // https://www.drupal.org/project/footnotes
      'filter_footnotes' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/forena
      'forena_report' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/g2
      'filter_g2' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/geo_filter
      'geo_filter_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/google_analytics_counter
      'filter_google_analytics_counter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/google_analytics_referrer
      'filter_google_analytics_referrer' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/gotwo
      'gotwo_link' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/h5p
      'h5p_content' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/highlightjs
      'highlight_js' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/htmLawed
      'htmLawed' => FilterInterface::TYPE_HTML_RESTRICTOR,
      // https://www.drupal.org/project/htmlpurifier
      'htmlpurifier_basic', 'htmlpurifier_advanced' => FilterInterface::TYPE_HTML_RESTRICTOR,
      // https://www.drupal.org/project/htmltidy
      'htmltidy' => FilterInterface::TYPE_HTML_RESTRICTOR,
      // https://www.drupal.org/project/icon
      'icon_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/iframe_filter
      'iframe' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/image_resize_filter
      'image_resize_filter' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      // https://www.drupal.org/project/insert_view
      'insert_view' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/intlinks
      'intlinks title', 'intlinks hide bad' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/jquery_ui_filter
      'accordion', 'dialog', 'tabs' => FilterInterface::TYPE_MARKUP_LANGUAGE,
      // https://www.drupal.org/project/language_sections
      'language_sections' => FilterInterface::TYPE_MARKUP_LANGUAGE,
      // https://www.drupal.org/project/lazy
      'lazy_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/lazyloader_filter
      'lazyloader_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/link_node
      'filter_link_node' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/linktitle
      'linktitle' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/markdown
      'filter_markdown' => FilterInterface::TYPE_MARKUP_LANGUAGE,
      // https://www.drupal.org/project/media_wysiwyg
      'media_filter', 'media_filter_paragraph_fix' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/mentions
      'filter_mentions' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/menu_filter
      'menu_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/mobile_codes
      'mobile_codes' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/multicolumn
      'multicolumn' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/multilink
      'multilink_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/mytube
      'mytube' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/node_embed
      'node_embed' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/node_field_embed
      'node_field_embed' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/noindex_external_links
      'external_links' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/noreferrer
      'noreferrer' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/oembed
      'oembed', 'oembed_legacy' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/office_html
      'office_html_strip' => FilterInterface::TYPE_HTML_RESTRICTOR,
      'office_html_convert' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/openlayers_filters
      'openlayers' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/opengraph_filter
      'opengraph_filter' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      // https://www.drupal.org/project/pathologic
      'pathologic' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/popup
      'popup_tags' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/prettify
      'prettify' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/rel_to_abs
      'rel_to_abs' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      // https://www.drupal.org/project/rollover_filter
      'rollover_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/sanitizable
      'sanitizable' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/smart_paging
      'smart_paging_filter', 'smart_paging_filter_autop' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/spamspan
      'spamspan' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/scald
      'mee_scald_widgets' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      // https://www.drupal.org/project/script_filter
      'script_filter' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      // https://www.drupal.org/project/shortcode
      'shortcode' => FilterInterface::TYPE_MARKUP_LANGUAGE,
      'shortcode_text_corrector' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/smiley
      'smiley' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      // https://www.drupal.org/project/svg_embed
      'filter_svg_embed' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/spoiler
      'spoiler' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/tableofcontents
      'filter_toc' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/tables
      'filter_tables' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/target_filter_url
      'target_filter_url' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/textile
      'textile' => FilterInterface::TYPE_MARKUP_LANGUAGE,
      // https://www.drupal.org/project/theme_filter
      'theme_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/token_filter
      'filter_tokens' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/transliteration
      'transliteration' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/typogrify
      'typogrify' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/uuid_link
      'uuid_link_filter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/wysiwyg
      'wysiwyg', 'wysiwyg_template_cleanup' => FilterInterface::TYPE_HTML_RESTRICTOR,
      // https://www.drupal.org/project/word_link
      'word_link' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      // https://www.drupal.org/project/wordfilter
      'wordfilter' => FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
      // https://www.drupal.org/project/xbbcode
      'xbbcode' => FilterInterface::TYPE_MARKUP_LANGUAGE,
      default => NULL,
    };
  }

}
