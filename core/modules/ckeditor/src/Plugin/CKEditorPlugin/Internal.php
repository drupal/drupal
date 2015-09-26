<?php

/**
 * @file
 * Contains \Drupal\ckeditor\Plugin\CKEditorPlugin\Internal.
 */

namespace Drupal\ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Plugin\FilterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "internal" plugin (i.e. core plugins part of our CKEditor build).
 *
 * @CKEditorPlugin(
 *   id = "internal",
 *   label = @Translation("CKEditor core")
 * )
 */
class Internal extends CKEditorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a \Drupal\ckeditor\Plugin\CKEditorPlugin\Internal object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CacheBackendInterface $cache_backend) {
    $this->cache = $cache_backend;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.default')
    );
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::isInternal().
   */
  public function isInternal() {
    return TRUE;
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  public function getFile() {
    // This plugin is already part of Drupal core's CKEditor build.
    return FALSE;
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getConfig().
   */
  public function getConfig(Editor $editor) {
    // Reasonable defaults that provide expected basic behavior.
    $config = array(
      'customConfig' => '', // Don't load CKEditor's config.js file.
      'pasteFromWordPromptCleanup' => TRUE,
      'resize_dir' => 'vertical',
      'justifyClasses' => array('text-align-left', 'text-align-center', 'text-align-right', 'text-align-justify'),
      'entities' => FALSE,
    );

    // Add the allowedContent setting, which ensures CKEditor only allows tags
    // and attributes that are allowed by the text format for this text editor.
    list($config['allowedContent'], $config['disallowedContent']) = $this->generateACFSettings($editor);

    // Add the format_tags setting, if its button is enabled.
    $toolbar_rows = array();
    $settings = $editor->getSettings();
    foreach ($settings['toolbar']['rows'] as $row_number => $row) {
      $toolbar_rows[] = array_reduce($settings['toolbar']['rows'][$row_number], function (&$result, $button_group) {
        return array_merge($result, $button_group['items']);
      }, array());
    }
    $toolbar_buttons = array_unique(NestedArray::mergeDeepArray($toolbar_rows));
    if (in_array('Format', $toolbar_buttons)) {
      $config['format_tags'] = $this->generateFormatTagsSetting($editor);
    }

    return $config;
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginButtonsInterface::getButtons().
   */
  public function getButtons() {
    $button = function($name, $direction = 'ltr') {
      // In the markup below, we mostly use the name (which may include spaces),
      // but in one spot we use it as a CSS class, so strip spaces.
      // Note: this uses str_replace() instead of Html::cleanCssIdentifier()
      // because we must provide these class names exactly how CKEditor expects
      // them in its library, which cleanCssIdentifier() does not do.
      $class_name = str_replace(' ', '', $name);
      return [
        '#type' => 'inline_template',
        '#template' => '<a href="#" class="cke-icon-only cke_{{ direction }}" role="button" title="{{ name }}" aria-label="{{ name }}"><span class="cke_button_icon cke_button__{{ classname }}_icon">{{ name }}</span></a>',
        '#context' => [
          'direction' => $direction,
          'name' => $name,
          'classname' => $class_name,
        ],
      ];
    };

    return array(
      // "basicstyles" plugin.
      'Bold' => array(
        'label' => t('Bold'),
        'image_alternative' => $button('bold'),
        'image_alternative_rtl' => $button('bold', 'rtl'),
      ),
      'Italic' => array(
        'label' => t('Italic'),
        'image_alternative' => $button('italic'),
        'image_alternative_rtl' => $button('italic', 'rtl'),
      ),
      'Underline' => array(
        'label' => t('Underline'),
        'image_alternative' => $button('underline'),
        'image_alternative_rtl' => $button('underline', 'rtl'),
      ),
      'Strike' => array(
        'label' => t('Strike-through'),
        'image_alternative' => $button('strike'),
        'image_alternative_rtl' => $button('strike', 'rtl'),
      ),
      'Superscript' => array(
        'label' => t('Superscript'),
        'image_alternative' => $button('super script'),
        'image_alternative_rtl' => $button('super script', 'rtl'),
      ),
      'Subscript' => array(
        'label' => t('Subscript'),
        'image_alternative' => $button('sub script'),
        'image_alternative_rtl' => $button('sub script', 'rtl'),
      ),
      // "removeformat" plugin.
      'RemoveFormat' => array(
        'label' => t('Remove format'),
        'image_alternative' => $button('remove format'),
        'image_alternative_rtl' => $button('remove format', 'rtl'),
      ),
      // "justify" plugin.
      'JustifyLeft' => array(
        'label' => t('Align left'),
        'image_alternative' => $button('justify left'),
        'image_alternative_rtl' => $button('justify left', 'rtl'),
      ),
      'JustifyCenter' => array(
        'label' => t('Align center'),
        'image_alternative' => $button('justify center'),
        'image_alternative_rtl' => $button('justify center', 'rtl'),
      ),
      'JustifyRight' => array(
        'label' => t('Align right'),
        'image_alternative' => $button('justify right'),
        'image_alternative_rtl' => $button('justify right', 'rtl'),
      ),
      'JustifyBlock' => array(
        'label' => t('Justify'),
        'image_alternative' => $button('justify block'),
        'image_alternative_rtl' => $button('justify block', 'rtl'),
      ),
      // "list" plugin.
      'BulletedList' => array(
        'label' => t('Bullet list'),
        'image_alternative' => $button('bulleted list'),
        'image_alternative_rtl' => $button('bulleted list', 'rtl'),
      ),
      'NumberedList' => array(
        'label' => t('Numbered list'),
        'image_alternative' => $button('numbered list'),
        'image_alternative_rtl' => $button('numbered list', 'rtl'),
      ),
      // "indent" plugin.
      'Outdent' => array(
        'label' => t('Outdent'),
        'image_alternative' => $button('outdent'),
        'image_alternative_rtl' => $button('outdent', 'rtl'),
      ),
      'Indent' => array(
        'label' => t('Indent'),
        'image_alternative' => $button('indent'),
        'image_alternative_rtl' => $button('indent', 'rtl'),
      ),
      // "undo" plugin.
      'Undo' => array(
        'label' => t('Undo'),
        'image_alternative' => $button('undo'),
        'image_alternative_rtl' => $button('undo', 'rtl'),
      ),
      'Redo' => array(
        'label' => t('Redo'),
        'image_alternative' => $button('redo'),
        'image_alternative_rtl' => $button('redo', 'rtl'),
      ),
      // "blockquote" plugin.
      'Blockquote' => array(
        'label' => t('Blockquote'),
        'image_alternative' => $button('blockquote'),
        'image_alternative_rtl' => $button('blockquote', 'rtl'),
      ),
      // "horizontalrule" plugin
      'HorizontalRule' => array(
        'label' => t('Horizontal rule'),
        'image_alternative' => $button('horizontal rule'),
        'image_alternative_rtl' => $button('horizontal rule', 'rtl'),
      ),
      // "clipboard" plugin.
      'Cut' => array(
        'label' => t('Cut'),
        'image_alternative' => $button('cut'),
        'image_alternative_rtl' => $button('cut', 'rtl'),
      ),
      'Copy' => array(
        'label' => t('Copy'),
        'image_alternative' => $button('copy'),
        'image_alternative_rtl' => $button('copy', 'rtl'),
      ),
      'Paste' => array(
        'label' => t('Paste'),
        'image_alternative' => $button('paste'),
        'image_alternative_rtl' => $button('paste', 'rtl'),
      ),
      // "pastetext" plugin.
      'PasteText' => array(
        'label' => t('Paste Text'),
        'image_alternative' => $button('paste text'),
        'image_alternative_rtl' => $button('paste text', 'rtl'),
      ),
      // "pastefromword" plugin.
      'PasteFromWord' => array(
        'label' => t('Paste from Word'),
        'image_alternative' => $button('paste from word'),
        'image_alternative_rtl' => $button('paste from word', 'rtl'),
      ),
      // "specialchar" plugin.
      'SpecialChar' => array(
        'label' => t('Character map'),
        'image_alternative' => $button('special char'),
        'image_alternative_rtl' => $button('special char', 'rtl'),
      ),
      'Format' => array(
        'label' => t('HTML block format'),
        'image_alternative' => [
          '#type' => 'inline_template',
          '#template' => '<a href="#" role="button" aria-label="{{ format_text }}"><span class="ckeditor-button-dropdown">{{ format_text }}<span class="ckeditor-button-arrow"></span></span></a>',
          '#context' => [
            'format_text' => t('Format'),
          ],
        ],
      ),
      // "table" plugin.
      'Table' => array(
        'label' => t('Table'),
        'image_alternative' => $button('table'),
        'image_alternative_rtl' => $button('table', 'rtl'),
      ),
      // "showblocks" plugin.
      'ShowBlocks' => array(
        'label' => t('Show blocks'),
        'image_alternative' => $button('show blocks'),
        'image_alternative_rtl' => $button('show blocks', 'rtl'),
      ),
      // "sourcearea" plugin.
      'Source' => array(
        'label' => t('Source code'),
        'image_alternative' => $button('source'),
        'image_alternative_rtl' => $button('source', 'rtl'),
      ),
      // "maximize" plugin.
      'Maximize' => array(
        'label' => t('Maximize'),
        'image_alternative' => $button('maximize'),
        'image_alternative_rtl' => $button('maximize', 'rtl'),
      ),
      // No plugin, separator "button" for toolbar builder UI use only.
      '-' => array(
        'label' => t('Separator'),
        'image_alternative' => [
          '#type' => 'inline_template',
          '#template' => '<a href="#" role="button" aria-label="{{ button_separator_text }}" class="ckeditor-separator"></a>',
          '#context' => [
            'button_separator_text' => t('Button separator'),
          ],
        ],
        'attributes' => array(
          'class' => array('ckeditor-button-separator'),
          'data-drupal-ckeditor-type' => 'separator',
        ),
        'multiple' => TRUE,
      ),
    );
  }

  /**
   * Builds the "format_tags" configuration part of the CKEditor JS settings.
   *
   * @see getConfig()
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   *
   * @return array
   *   An array containing the "format_tags" configuration.
   */
  protected function generateFormatTagsSetting(Editor $editor) {
    // When no text format is associated yet, assume no tag is allowed.
    // @see \Drupal\Editor\EditorInterface::hasAssociatedFilterFormat()
    if (!$editor->hasAssociatedFilterFormat()) {
      return array();
    }

    $format = $editor->getFilterFormat();
    $cid = 'ckeditor_internal_format_tags:' . $format->id();

    if ($cached = $this->cache->get($cid)) {
      $format_tags = $cached->data;
    }
    else {
      // The <p> tag is always allowed — HTML without <p> tags is nonsensical.
      $format_tags = ['p'];

      // Given the list of possible format tags, automatically determine whether
      // the current text format allows this tag, and thus whether it should show
      // up in the "Format" dropdown.
      $possible_format_tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre'];
      foreach ($possible_format_tags as $tag) {
        $input = '<' . $tag . '>TEST</' . $tag . '>';
        $output = trim(check_markup($input, $editor->id()));
        if ($input == $output) {
          $format_tags[] = $tag;
        }
      }
      $format_tags = implode(';', $format_tags);

      // Cache the "format_tags" configuration. This cache item is infinitely
      // valid; it only changes whenever the text format is changed, hence it's
      // tagged with the text format's cache tag.
      $this->cache->set($cid, $format_tags, Cache::PERMANENT, $format->getCacheTags());
    }

    return $format_tags;
  }

  /**
   * Builds the ACF part of the CKEditor JS settings.
   *
   * This ensures that CKEditor obeys the HTML restrictions defined by Drupal's
   * filter system, by enabling CKEditor's Advanced Content Filter (ACF)
   * functionality: http://ckeditor.com/blog/CKEditor-4.1-RC-Released.
   *
   * @see getConfig()
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   *
   * @return array
   *   An array with two values:
   *   - the first value is the "allowedContent" setting: a well-formatted array
   *     or TRUE. The latter indicates that anything is allowed.
   *   - the second value is the "disallowedContent" setting: a well-formatted
   *     array or FALSE. The latter indicates that nothing is disallowed.
   */
  protected function generateACFSettings(Editor $editor) {
    // When no text format is associated yet, assume nothing is disallowed, so
    // set allowedContent to true.
    if (!$editor->hasAssociatedFilterFormat()) {
      return TRUE;
    }

    $format = $editor->getFilterFormat();
    $filter_types = $format->getFilterTypes();

    // When nothing is disallowed, set allowedContent to true.
    if (!in_array(FilterInterface::TYPE_HTML_RESTRICTOR, $filter_types)) {
      return array(TRUE, FALSE);
    }
    // Generate setting that accurately reflects allowed tags and attributes.
    else {
      $get_attribute_values = function($attribute_values, $allowed_values) {
        $values = array_keys(array_filter($attribute_values, function($value) use ($allowed_values) {
          if ($allowed_values) {
            return $value !== FALSE;
          }
          else {
            return $value === FALSE;
          }
        }));
        if (count($values)) {
          return implode(',', $values);
        }
        else {
          return NULL;
        }
      };

      $html_restrictions = $format->getHtmlRestrictions();
      // When all HTML is allowed, also set allowedContent to true and
      // disallowedContent to false.
      if ($html_restrictions === FALSE) {
        return array(TRUE, FALSE);
      }
      $allowed = array();
      $disallowed = array();
      if (isset($html_restrictions['forbidden_tags'])) {
        foreach ($html_restrictions['forbidden_tags'] as $tag) {
          $disallowed[$tag] = TRUE;
        }
      }
      foreach ($html_restrictions['allowed'] as $tag => $attributes) {
        // Tell CKEditor the tag is allowed, but no attributes.
        if ($attributes === FALSE) {
          $allowed[$tag] = array(
            'attributes' => FALSE,
            'styles' => FALSE,
            'classes' => FALSE,
          );
        }
        // Tell CKEditor the tag is allowed, as well as any attribute on it. The
        // "style" and "class" attributes are handled separately by CKEditor:
        // they are disallowed even if you specify it in the list of allowed
        // attributes, unless you state specific values for them that are
        // allowed. Or, in this case: any value for them is allowed.
        elseif ($attributes === TRUE) {
          $allowed[$tag] = array(
            'attributes' => TRUE,
            'styles' => TRUE,
            'classes' => TRUE,
          );
          // We've just marked that any value for the "style" and "class"
          // attributes is allowed. However, that may not be the case: the "*"
          // tag may still apply restrictions.
          // Since CKEditor's ACF follows the following principle:
          //     Once validated, an element or its property cannot be
          //     invalidated by another rule.
          // That means that the most permissive setting wins. Which means that
          // it will still be allowed by CKEditor to e.g. define any style, no
          // matter what the "*" tag's restrictions may be. If there's a setting
          // for either the "style" or "class" attribute, it cannot possibly be
          // more permissive than what was set above. Hence: inherit from the
          // "*" tag where possible.
          if (isset($html_restrictions['allowed']['*'])) {
            $wildcard = $html_restrictions['allowed']['*'];
            if (isset($wildcard['style'])) {
              if (!is_array($wildcard['style'])) {
                $allowed[$tag]['styles'] = $wildcard['style'];
              }
              else {
                $allowed_styles = $get_attribute_values($wildcard['style'], TRUE);
                if (isset($allowed_styles)) {
                  $allowed[$tag]['styles'] = $allowed_styles;
                }
                else {
                  unset($allowed[$tag]['styles']);
                }
              }
            }
            if (isset($wildcard['class'])) {
              if (!is_array($wildcard['class'])) {
                $allowed[$tag]['classes'] = $wildcard['class'];
              }
              else {
                $allowed_classes = $get_attribute_values($wildcard['class'], TRUE);
                if (isset($allowed_classes)) {
                  $allowed[$tag]['classes'] = $allowed_classes;
                }
                else {
                  unset($allowed[$tag]['classes']);
                }
              }
            }
          }
        }
        // Tell CKEditor the tag is allowed, along with some tags.
        elseif (is_array($attributes)) {
          // Configure allowed attributes, allowed "style" attribute values and
          // allowed "class" attribute values.
          // CKEditor only allows specific values for the "class" and "style"
          // attributes; so ignore restrictions on other attributes, which
          // Drupal filters may provide.
          // NOTE: A Drupal contrib module can subclass this class, override the
          // getConfig() method, and override the JavaScript at
          // Drupal.editors.ckeditor to somehow make validation of values for
          // attributes other than "class" and "style" work.
          $allowed_attributes = array_filter($attributes, function($value) {
            return $value !== FALSE;
          });
          if (count($allowed_attributes)) {
            $allowed[$tag]['attributes'] = implode(',', array_keys($allowed_attributes));
          }
          if (isset($allowed_attributes['style']) && is_array($allowed_attributes['style'])) {
            $allowed_styles = $get_attribute_values($allowed_attributes['style'], TRUE);
            if (isset($allowed_styles)) {
              $allowed[$tag]['styles'] = $allowed_styles;
            }
          }
          if (isset($allowed_attributes['class']) && is_array($allowed_attributes['class'])) {
            $allowed_classes = $get_attribute_values($allowed_attributes['class'], TRUE);
            if (isset($allowed_classes)) {
              $allowed[$tag]['classes'] = $allowed_classes;
            }
          }

          // Handle disallowed attributes analogously. However, to handle *dis-
          // allowed* attribute values, we must look at *allowed* attributes'
          // disallowed attribute values! After all, a disallowed attribute
          // implies that all of its possible attribute values are disallowed,
          // thus we must look at the disallowed attribute values on allowed
          // attributes.
          $disallowed_attributes = array_filter($attributes, function($value) {
            return $value === FALSE;
          });
          if (count($disallowed_attributes)) {
            // No need to blacklist the 'class' or 'style' attributes; CKEditor
            // handles them separately (if no specific class or style attribute
            // values are allowed, then those attributes are disallowed).
            if (isset($disallowed_attributes['class'])) {
              unset($disallowed_attributes['class']);
            }
            if (isset($disallowed_attributes['style'])) {
              unset($disallowed_attributes['style']);
            }
            $disallowed[$tag]['attributes'] = implode(',', array_keys($disallowed_attributes));
          }
          if (isset($allowed_attributes['style']) && is_array($allowed_attributes['style'])) {
            $disallowed_styles = $get_attribute_values($allowed_attributes['style'], FALSE);
            if (isset($disallowed_styles)) {
              $disallowed[$tag]['styles'] = $disallowed_styles;
            }
          }
          if (isset($allowed_attributes['class']) && is_array($allowed_attributes['class'])) {
            $disallowed_classes = $get_attribute_values($allowed_attributes['class'], FALSE);
            if (isset($disallowed_classes)) {
              $disallowed[$tag]['classes'] = $disallowed_classes;
            }
          }
        }
      }

      return array($allowed, $disallowed);
    }
  }

}
