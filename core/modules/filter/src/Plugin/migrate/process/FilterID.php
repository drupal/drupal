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
      if (in_array(static::getSourceFilterType($value), [FilterInterface::TYPE_TRANSFORM_REVERSIBLE, FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE], TRUE)) {
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
    switch ($filter_id) {
      // Drupal 7 core filters.
      // - https://git.drupalcode.org/project/drupal/blob/7.69/modules/filter/filter.module#L1229
      // - https://git.drupalcode.org/project/drupal/blob/7.69/modules/php/php.module#L139
      case 'filter_html':
        return FilterInterface::TYPE_HTML_RESTRICTOR;

      case 'filter_url':
        return FilterInterface::TYPE_MARKUP_LANGUAGE;

      case 'filter_autop':
        return FilterInterface::TYPE_MARKUP_LANGUAGE;

      case 'filter_htmlcorrector':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      case 'filter_html_escape':
        return FilterInterface::TYPE_HTML_RESTRICTOR;

      case 'php_code':
        return FilterInterface::TYPE_MARKUP_LANGUAGE;

      // Drupal 7 contrib filters.
      // https://www.drupal.org/project/abbrfilter
      case 'abbrfilter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/ace_editor
      case 'ace_editor':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/adsense
      case 'adsense':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/api
      case 'api_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/api_tokens
      case 'api_tokens':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/autofloat
      case 'filter_autofloat':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/bbcode
      case 'bbcode':
        return FilterInterface::TYPE_MARKUP_LANGUAGE;

      // https://www.drupal.org/project/biblio
      case 'biblio_filter_reference':
      case 'biblio_filter_inline_reference':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/caption
      case 'caption':
        return FilterInterface::TYPE_TRANSFORM_REVERSIBLE;

      // https://www.drupal.org/project/caption_filter
      case 'caption_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/cincopa
      case 'filter_cincopa':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/ckeditor_blocks
      case 'ckeditor_blocks':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/ckeditor_filter
      case 'ckeditor_filter':
        return FilterInterface::TYPE_HTML_RESTRICTOR;

      // https://www.drupal.org/project/ckeditor_link
      case 'ckeditor_link_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/ckeditor_swf
      case 'ckeditor_swf_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/codefilter
      case 'codefilter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/collapse_text
      case 'collapse_text_filter':
        return FilterInterface::TYPE_TRANSFORM_REVERSIBLE;

      // https://www.drupal.org/project/columns_filter
      case 'columns_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/commonmark
      case 'commonmark':
        return FilterInterface::TYPE_MARKUP_LANGUAGE;

      // https://www.drupal.org/project/commons_hashtags
      case 'filter_hashtags':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/deepzoom
      case 'deepzoom':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/editor
      case 'editor_align':
      case 'editor_caption':
        return FilterInterface::TYPE_TRANSFORM_REVERSIBLE;

      // https://www.drupal.org/project/elf
      case 'elf':
        return FilterInterface::TYPE_TRANSFORM_REVERSIBLE;

      // https://www.drupal.org/project/emogrifier
      case 'filter_emogrifier':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/emptyparagraphkiller
      case 'emptyparagraphkiller':
        return FilterInterface::TYPE_HTML_RESTRICTOR;

      // https://www.drupal.org/project/entity_embed
      case 'entity_embed':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      case 'filter_align':
        return FilterInterface::TYPE_TRANSFORM_REVERSIBLE;

      // https://www.drupal.org/project/ext_link_page
      case 'ext_link_page':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/filter_html_image_secure
      case 'filter_html_image_secure':
        return FilterInterface::TYPE_HTML_RESTRICTOR;

      // https://www.drupal.org/project/filter_transliteration
      case 'filter_transliteration':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/flickr
      case 'flickr_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/float_filter
      case 'float_filter':
        return FilterInterface::TYPE_TRANSFORM_REVERSIBLE;

      // https://www.drupal.org/project/footnotes
      case 'filter_footnotes':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/forena
      case 'forena_report':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/g2
      case 'filter_g2':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/geo_filter
      case 'geo_filter_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/google_analytics_counter
      case 'filter_google_analytics_counter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/google_analytics_referrer
      case 'filter_google_analytics_referrer':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/gotwo
      case 'gotwo_link':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/h5p
      case 'h5p_content':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/highlightjs
      case 'highlight_js':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/htmLawed
      case 'htmLawed':
        return FilterInterface::TYPE_HTML_RESTRICTOR;

      // https://www.drupal.org/project/htmlpurifier
      case 'htmlpurifier_basic':
      case 'htmlpurifier_advanced':
        return FilterInterface::TYPE_HTML_RESTRICTOR;

      // https://www.drupal.org/project/htmltidy
      case 'htmltidy':
        return FilterInterface::TYPE_HTML_RESTRICTOR;

      // https://www.drupal.org/project/icon
      case 'icon_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/iframe_filter
      case 'iframe':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/image_resize_filter
      case 'image_resize_filter':
        return FilterInterface::TYPE_TRANSFORM_REVERSIBLE;

      // https://www.drupal.org/project/insert_view
      case 'insert_view':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/intlinks
      case 'intlinks title':
      case 'intlinks hide bad':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/jquery_ui_filter
      case 'accordion':
      case 'dialog':
      case 'tabs':
        return FilterInterface::TYPE_MARKUP_LANGUAGE;

      // https://www.drupal.org/project/language_sections
      case 'language_sections':
        return FilterInterface::TYPE_MARKUP_LANGUAGE;

      // https://www.drupal.org/project/lazy
      case 'lazy_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/lazyloader_filter
      case 'lazyloader_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/link_node
      case 'filter_link_node':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/linktitle
      case 'linktitle':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/markdown
      case 'filter_markdown':
        return FilterInterface::TYPE_MARKUP_LANGUAGE;

      // https://www.drupal.org/project/media_wysiwyg
      case 'media_filter':
      case 'media_filter_paragraph_fix':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/mentions
      case 'filter_mentions':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/menu_filter
      case 'menu_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/mobile_codes
      case 'mobile_codes':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/multicolumn
      case 'multicolumn':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/multilink
      case 'multilink_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/mytube
      case 'mytube':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/node_embed
      case 'node_embed':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/node_field_embed
      case 'node_field_embed':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/noindex_external_links
      case 'external_links':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/noreferrer
      case 'noreferrer':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/oembed
      case 'oembed':
      case 'oembed_legacy':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/office_html
      case 'office_html_strip':
        return FilterInterface::TYPE_HTML_RESTRICTOR;

      case 'office_html_convert':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/openlayers_filters
      case 'openlayers':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/opengraph_filter
      case 'opengraph_filter':
        return FilterInterface::TYPE_TRANSFORM_REVERSIBLE;

      // https://www.drupal.org/project/pathologic
      case 'pathologic':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/popup
      case 'popup_tags':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/prettify
      case 'prettify':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/rel_to_abs
      case 'rel_to_abs':
        return FilterInterface::TYPE_TRANSFORM_REVERSIBLE;

      // https://www.drupal.org/project/rollover_filter
      case 'rollover_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/sanitizable
      case 'sanitizable':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/smart_paging
      case 'smart_paging_filter':
      case 'smart_paging_filter_autop':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/spamspan
      case 'spamspan':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/scald
      case 'mee_scald_widgets':
        return FilterInterface::TYPE_TRANSFORM_REVERSIBLE;

      // https://www.drupal.org/project/script_filter
      case 'script_filter':
        return FilterInterface::TYPE_TRANSFORM_REVERSIBLE;

      // https://www.drupal.org/project/shortcode
      case 'shortcode':
        return FilterInterface::TYPE_MARKUP_LANGUAGE;

      case 'shortcode_text_corrector':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/smiley
      case 'smiley':
        return FilterInterface::TYPE_TRANSFORM_REVERSIBLE;

      // https://www.drupal.org/project/svg_embed
      case 'filter_svg_embed':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/spoiler
      case 'spoiler':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/tableofcontents
      case 'filter_toc':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/tables
      case 'filter_tables':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/target_filter_url
      case 'target_filter_url':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/textile
      case 'textile':
        return FilterInterface::TYPE_MARKUP_LANGUAGE;

      // https://www.drupal.org/project/theme_filter
      case 'theme_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/token_filter
      case 'filter_tokens':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/transliteration
      case 'transliteration':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/typogrify
      case 'typogrify':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/uuid_link
      case 'uuid_link_filter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/wysiwyg
      case 'wysiwyg':
      case 'wysiwyg_template_cleanup':
        return FilterInterface::TYPE_HTML_RESTRICTOR;

      // https://www.drupal.org/project/word_link
      case 'word_link':
        return FilterInterface::TYPE_TRANSFORM_REVERSIBLE;

      // https://www.drupal.org/project/wordfilter
      case 'wordfilter':
        return FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE;

      // https://www.drupal.org/project/xbbcode
      case 'xbbcode':
        return FilterInterface::TYPE_MARKUP_LANGUAGE;
    }

    return NULL;
  }

}
