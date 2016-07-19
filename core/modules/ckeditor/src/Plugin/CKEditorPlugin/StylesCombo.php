<?php

namespace Drupal\ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "stylescombo" plugin.
 *
 * @CKEditorPlugin(
 *   id = "stylescombo",
 *   label = @Translation("Styles dropdown")
 * )
 */
class StylesCombo extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    // This plugin is already part of Drupal core's CKEditor build.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $config = array();
    $settings = $editor->getSettings();
    if (!isset($settings['plugins']['stylescombo']['styles'])) {
      return $config;
    }
    $styles = $settings['plugins']['stylescombo']['styles'];
    $config['stylesSet'] = $this->generateStylesSetSetting($styles);
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'Styles' => array(
        'label' => t('Font style'),
        'image_alternative' => [
          '#type' => 'inline_template',
          '#template' => '<a href="#" role="button" aria-label="{{ styles_text }}"><span class="ckeditor-button-dropdown">{{ styles_text }}<span class="ckeditor-button-arrow"></span></span></a>',
          '#context' => [
            'styles_text' => t('Styles'),
          ],
        ],
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    // Defaults.
    $config = array('styles' => '');
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['stylescombo'])) {
      $config = $settings['plugins']['stylescombo'];
    }

    $form['styles'] = array(
      '#title' => t('Styles'),
      '#title_display' => 'invisible',
      '#type' => 'textarea',
      '#default_value' => $config['styles'],
      '#description' => t('A list of classes that will be provided in the "Styles" dropdown. Enter one or more classes on each line in the format: element.classA.classB|Label. Example: h1.title|Title. Advanced example: h1.fancy.title|Fancy title.<br />These styles should be available in your theme\'s CSS file.'),
      '#attached' => array(
        'library' => array('ckeditor/drupal.ckeditor.stylescombo.admin'),
      ),
      '#element_validate' => array(
        array($this, 'validateStylesValue'),
      ),
    );

    return $form;
  }

  /**
   * #element_validate handler for the "styles" element in settingsForm().
   */
  public function validateStylesValue(array $element, FormStateInterface $form_state) {
    $styles_setting = $this->generateStylesSetSetting($element['#value']);
    if ($styles_setting === FALSE) {
      $form_state->setError($element, t('The provided list of styles is syntactically incorrect.'));
    }
    else {
      $style_names = array_map(function ($style) { return $style['name']; }, $styles_setting);
      if (count($style_names) !== count(array_unique($style_names))) {
        $form_state->setError($element, t('Each style must have a unique label.'));
      }
    }
  }

  /**
   * Builds the "stylesSet" configuration part of the CKEditor JS settings.
   *
   * @see getConfig()
   *
   * @param string $styles
   *   The "styles" setting.
   * @return array|FALSE
   *   An array containing the "stylesSet" configuration, or FALSE when the
   *   syntax is invalid.
   */
  protected function generateStylesSetSetting($styles) {
    $styles_set = array();

    // Early-return when empty.
    $styles = trim($styles);
    if (empty($styles)) {
      return $styles_set;
    }

    $styles = str_replace(array("\r\n", "\r"), "\n", $styles);
    foreach (explode("\n", $styles) as $style) {
      $style = trim($style);

      // Ignore empty lines in between non-empty lines.
      if (empty($style)) {
        continue;
      }

      // Validate syntax: element[.class...]|label pattern expected.
      if (!preg_match('@^ *[a-zA-Z0-9]+ *(\\.[a-zA-Z0-9_-]+ *)*\\| *.+ *$@', $style)) {
        return FALSE;
      }

      // Parse.
      list($selector, $label) = explode('|', $style);
      $classes = explode('.', $selector);
      $element = array_shift($classes);

      // Build the data structure CKEditor's stylescombo plugin expects.
      // @see http://docs.cksource.com/CKEditor_3.x/Developers_Guide/Styles
      $configured_style = array(
        'name' => trim($label),
        'element' => trim($element),
      );
      if (!empty($classes)) {
        $configured_style['attributes'] = array(
          'class' => implode(' ', array_map('trim', $classes))
        );
      }
      $styles_set[] = $configured_style;
    }
    return $styles_set;
  }

}
