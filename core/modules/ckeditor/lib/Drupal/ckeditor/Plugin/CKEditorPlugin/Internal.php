<?php

/**
 * @file
 * Contains \Drupal\ckeditor\Plugin\CKEditorPlugin\Internal.
 */

namespace Drupal\ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\ckeditor\Annotation\CKEditorPlugin;
use Drupal\Core\Annotation\Translation;
use Drupal\editor\Plugin\Core\Entity\Editor;

/**
 * Defines the "internal" plugin (i.e. core plugins part of our CKEditor build).
 *
 * @CKEditorPlugin(
 *   id = "internal",
 *   label = @Translation("CKEditor core")
 * )
 */
class Internal extends CKEditorPluginBase {

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
      'removeDialogTabs' => 'image:Link;image:advanced;link:advanced',
      'resize_dir' => 'vertical',
      'keystrokes' =>  array(
        // 0x11000 is CKEDITOR.CTRL, see http://docs.ckeditor.com/#!/api/CKEDITOR-property-CTRL.
        array(0x110000 + 75, 'link'),
        array(0x110000 + 76, NULL),
      ),
    );

    // Next, add the format_tags setting, if its button is enabled.
    $toolbar_buttons = array_unique(NestedArray::mergeDeepArray($editor->settings['toolbar']['buttons']));
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
      return '<a href="#" class="cke-icon-only cke_' . $direction . '" role="button" title="' . $name . '" aria-label="' . $name . '"><span class="cke_button_icon cke_button__' . str_replace(' ', '', $name) . '_icon">' . $name . '</span></a>';
    };

    return array(
      // "basicstyles" plugin.
      'Bold' => array(
        'label' => t('Bold'),
        'image_alternative' => $button('bold'),
      ),
      'Italic' => array(
        'label' => t('Italic'),
        'image_alternative' => $button('italic'),
      ),
      'Strike' => array(
        'label' => t('Strike-through'),
        'image_alternative' => $button('strike'),
      ),
      'Superscript' => array(
        'label' => t('Superscript'),
        'image_alternative' => $button('super script'),
      ),
      'Subscript' => array(
        'label' => t('Subscript'),
        'image_alternative' => $button('sub script'),
      ),
      // "removeformat" plugin.
      'RemoveFormat' => array(
        'label' => t('Remove format'),
        'image_alternative' => $button('remove format'),
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
      // "link" plugin.
      'Link' => array(
        'label' => t('Link'),
        'image_alternative' => $button('link'),
      ),
      'Unlink' => array(
        'label' => t('Unlink'),
        'image_alternative' => $button('unlink'),
      ),
      'Anchor' => array(
        'label' => t('Anchor'),
        'image_alternative' => $button('anchor'),
        'image_alternative_rtl' => $button('anchor', 'rtl'),
      ),
      // "blockquote" plugin.
      'Blockquote' => array(
        'label' => t('Blockquote'),
        'image_alternative' => $button('blockquote'),
      ),
      // "horizontalrule" plugin
      'HorizontalRule' => array(
        'label' => t('Horizontal rule'),
        'image_alternative' => $button('horizontal rule'),
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
      ),
      'Format' => array(
        'label' => t('HTML block format'),
        'image_alternative' => '<a href="#" role="button" aria-label="' . t('Format') . '"><span class="ckeditor-button-dropdown">' . t('Format') . '<span class="ckeditor-button-arrow"></span></span></a>',
      ),
      // "image" plugin.
      'Image' => array(
        'label' => t('Image'),
        'image_alternative' => $button('image'),
      ),
      // "table" plugin.
      'Table' => array(
        'label' => t('Table'),
        'image_alternative' => $button('table'),
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
      ),
      // "maximize" plugin.
      'Maximize' => array(
        'label' => t('Maximize'),
        'image_alternative' => $button('maximize'),
      ),
      // No plugin, separator "buttons" for toolbar builder UI use only.
      '|' => array(
        'label' => t('Group separator'),
        'image_alternative' => '<a href="#" role="button" aria-label="' . t('Button group separator') . '" class="ckeditor-group-separator"></a>',
        'attributes' => array('class' => array('ckeditor-group-button-separator')),
        'multiple' => TRUE,
      ),
      '-' => array(
        'label' => t('Separator'),
        'image_alternative' => '<a href="#" role="button" aria-label="' . t('Button separator') . '" class="ckeditor-separator"></a>',
        'attributes' => array('class' => array('ckeditor-button-separator')),
        'multiple' => TRUE,
      ),
    );
  }

  /**
   * Builds the "format_tags" configuration part of the CKEditor JS settings.
   *
   * @see getConfig()
   *
   * @param \Drupal\editor\Plugin\Core\Entity\Editor $editor
   *   A configured text editor object.
   * @return array
   *   An array containing the "format_tags" configuration.
   */
  protected function generateFormatTagsSetting(Editor $editor) {
    // The <p> tag is always allowed — HTML without <p> tags is nonsensical.
    $format_tags = array('p');

    // Given the list of possible format tags, automatically determine whether
    // the current text format allows this tag, and thus whether it should show
    // up in the "Format" dropdown.
    $possible_format_tags = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre');
    foreach ($possible_format_tags as $tag) {
      $input = '<' . $tag . '>TEST</' . $tag . '>';
      $output = trim(check_markup($input, $editor->format));
      if ($input == $output) {
        $format_tags[] = $tag;
      }
    }

    return implode(';', $format_tags);
  }

}
