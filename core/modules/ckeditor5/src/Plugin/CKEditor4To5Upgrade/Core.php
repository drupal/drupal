<?php

declare(strict_types=1);

namespace Drupal\ckeditor5\Plugin\CKEditor4To5Upgrade;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\filter\FilterFormatInterface;

/**
 * Provides the CKEditor 4 to 5 upgrade for Drupal core's CKEditor plugins.
 *
 * @CKEditor4To5Upgrade(
 *   id = "core",
 *   cke4_buttons = {
 *     "DrupalImage",
 *     "DrupalLink",
 *     "DrupalUnlink",
 *     "Bold",
 *     "Italic",
 *     "Underline",
 *     "Superscript",
 *     "Subscript",
 *     "BulletedList",
 *     "NumberedList",
 *     "Outdent",
 *     "Indent",
 *     "Undo",
 *     "Redo",
 *     "Blockquote",
 *     "JustifyLeft",
 *     "JustifyCenter",
 *     "JustifyRight",
 *     "JustifyBlock",
 *     "HorizontalRule",
 *     "Format",
 *     "Table",
 *     "Source",
 *     "Strike",
 *     "Cut",
 *     "Copy",
 *     "Paste",
 *     "PasteText",
 *     "PasteFromWord",
 *     "ShowBlocks",
 *     "Maximize",
 *     "-",
 *     "RemoveFormat",
 *     "Styles",
 *     "SpecialChar",
 *     "Language",
 *     "DrupalMediaLibrary",
 *   },
 *   cke4_plugin_settings = {
 *     "stylescombo",
 *     "language",
 *   },
 *   cke5_plugin_elements_subset_configuration = {
 *    "ckeditor5_heading",
 *    "ckeditor5_alignment",
 *    "ckeditor5_list",
 *    "media_media",
 *   }
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class Core extends PluginBase implements CKEditor4To5UpgradePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(string $cke4_button, HTMLRestrictions $text_format_html_restrictions): ?array {
    static $alignment_mapped;
    switch ($cke4_button) {
      // @see \Drupal\ckeditor\Plugin\CKEditorPlugin\DrupalImage
      case 'DrupalImage':
        return ['uploadImage'];

      // @see \Drupal\ckeditor\Plugin\CKEditorPlugin\DrupalLink
      case 'DrupalLink':
        return ['link'];

      case 'DrupalUnlink':
        return NULL;

      // @see \Drupal\ckeditor\Plugin\CKEditorPlugin\Internal
      case 'Bold':
      case 'Italic':
      case 'Underline':
      case 'Superscript':
      case 'Subscript':
      case 'BulletedList':
      case 'NumberedList':
      case 'Outdent':
      case 'Indent':
      case 'Undo':
      case 'Redo':
        return [lcfirst($cke4_button)];

      case 'Blockquote':
        return ['blockQuote'];

      case 'JustifyLeft':
      case 'JustifyCenter':
      case 'JustifyRight':
      case 'JustifyBlock':
        if (!isset($alignment_mapped)) {
          $alignment_mapped = TRUE;
          return ['alignment'];
        }
        return NULL;

      case 'HorizontalRule':
        return ['horizontalLine'];

      case 'Format':
        if ($text_format_html_restrictions->isUnrestricted()) {
          // When no restrictions exist, all tags possibly supported by "Format"
          // in CKEditor 4 must be supported.
          return ['heading', 'codeBlock'];
        }
        $allowed_elements = $text_format_html_restrictions->getAllowedElements();
        // Check if <h*> is supported.
        // Merely checking the existence of the array key is sufficient; this
        // plugin does not set or need any additional attributes.
        // @see \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions()
        $intersect = array_intersect(['h2', 'h3', 'h4', 'h5', 'h6'], array_keys($allowed_elements));

        // Do not return the 'codeBlock' toolbar item, not even when `<pre>` is
        // allowed in the text format. This ensures that SmartDefaultSettings:
        // - first adds the `code` toolbar item (for inline `<code>`)
        // - then adds `codeBlock` toolbar item (for code blocks: `<pre><code>`)
        // @see https://www.drupal.org/project/drupal/issues/3263384#comment-14446315
        return count($intersect) > 0 ? ['heading'] : NULL;

      case 'Table':
        return ['insertTable'];

      case 'Source':
        return ['sourceEditing'];

      case 'Strike':
        return ['strikethrough'];

      case 'Cut':
      case 'Copy':
      case 'Paste':
      case 'PasteText':
      case 'PasteFromWord':
      case 'ShowBlocks':
      case 'Maximize':
      case '-':
        // @see https://www.drupal.org/project/ckeditor5/issues/3211049#comment-14167764
        return NULL;

      // @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\RemoveFormat
      case 'RemoveFormat':
        return ['removeFormat'];

      // @see \Drupal\ckeditor\Plugin\CKEditorPlugin\StylesCombo
      case 'Styles':
        // @todo Change in https://www.drupal.org/project/ckeditor5/issues/3222797
        return NULL;

      // @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\specialCharacters
      case 'SpecialChar':
        return ['specialCharacters'];

      // @see \Drupal\ckeditor\Plugin\CKEditorPlugin\Language
      case 'Language':
        return ['textPartLanguage'];

      // @see \Drupal\media_library\Plugin\CKEditorPlugin\DrupalMediaLibrary
      case 'DrupalMediaLibrary':
        return ['drupalMedia'];

      default:
        throw new \OutOfBoundsException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4SettingsToCKEditor5Configuration(string $cke4_plugin_id, array $cke4_plugin_settings): ?array {
    switch ($cke4_plugin_id) {
      // @see \Drupal\ckeditor\Plugin\CKEditorPlugin\StylesCombo
      case 'stylescombo':
        // @todo Change in https://www.drupal.org/project/ckeditor5/issues/3222797
        return NULL;

      // @see \Drupal\ckeditor\Plugin\CKEditorPlugin\Language
      case 'language':
        // Identical configuration.
        return ['ckeditor5_language' => $cke4_plugin_settings];

      default:
        throw new \OutOfBoundsException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function computeCKEditor5PluginSubsetConfiguration(string $cke5_plugin_id, FilterFormatInterface $text_format): ?array {
    switch ($cke5_plugin_id) {
      case 'ckeditor5_heading':
        $restrictions = $text_format->getHtmlRestrictions();
        if ($restrictions === FALSE) {
          // The default is to allow all headings, which makes sense when there
          // are no restrictions.
          // @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Heading::DEFAULT_CONFIGURATION
          return NULL;
        }
        // Otherwise, only enable headings that allowed by the restrictions.
        $configuration = [];
        foreach (range(1, 6) as $index) {
          // Merely checking the existence of the array key is sufficient; this
          // plugin does not set or need any additional attributes.
          // @see \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions()
          if (array_key_exists("h$index", $restrictions['allowed'])) {
            $configuration['enabled_headings'][] = "heading$index";
          }
        }
        return $configuration;

      case 'ckeditor5_alignment':
        $alignment_classes_to_types = [
          'text-align-left' => 'left',
          'text-align-right' => 'right',
          'text-align-center' => 'center',
          'text-align-justify' => 'justify',
        ];
        $restrictions = $text_format->getHtmlRestrictions();
        if ($restrictions === FALSE) {
          // The default is to allow all alignments. This makes sense when there
          // are no restrictions.
          // @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Alignment::DEFAULT_CONFIGURATION
          return NULL;
        }
        // Otherwise, enable alignment types based on the provided restrictions.
        // I.e. if a tag is found with a text-align-{alignment type} class,
        // activate that alignment type.
        $configuration = [];
        foreach ($restrictions['allowed'] as $tag) {
          $classes = isset($tag['class']) && is_array($tag['class']) ? $tag['class'] : [];
          foreach (array_keys($classes) as $class) {
            if (isset($alignment_classes_to_types[$class])) {
              $configuration['enabled_alignments'][] = $alignment_classes_to_types[$class];
            }
          }
        }
        if (isset($configuration['enabled_alignments'])) {
          $configuration['enabled_alignments'] = array_unique($configuration['enabled_alignments']);
        }
        return $configuration;

      case 'ckeditor5_list':
        $restrictions = $text_format->getHtmlRestrictions();
        if ($restrictions === FALSE) {
          // The default is to allow a reversed list and a start index, which makes sense when there
          // are no restrictions.
          // @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\ListPlugin::default_configuration()
          return NULL;
        }
        $configuration = [];
        $configuration['reversed'] = !empty($restrictions['allowed']['ol']['reversed']);
        $configuration['startIndex'] = !empty($restrictions['allowed']['ol']['start']);
        return $configuration;

      case 'media_media':
        $restrictions = $text_format->getHtmlRestrictions();
        if ($restrictions === FALSE) {
          // The default is to not allow the user to override the default view mode.
          // @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Media::defaultConfiguration()
          return NULL;
        }
        $configuration = [];
        // Check if data-view-mode is allowed.
        $configuration['allow_view_mode_override'] = !empty($restrictions['allowed']['drupal-media']['data-view-mode']);
        return $configuration;

      default:
        throw new \OutOfBoundsException();
    }
  }

}
