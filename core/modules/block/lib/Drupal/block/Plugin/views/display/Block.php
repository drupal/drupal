<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\display\Block.
 * Definition of Drupal\block\Plugin\views\display\Block.
 */

namespace Drupal\block\Plugin\views\display;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * The plugin that handles a block.
 *
 * @ingroup views_display_plugins
 *
 * @Plugin(
 *   id = "block",
 *   module = "block",
 *   title = @Translation("Block"),
 *   help = @Translation("Display the view as a block."),
 *   theme = "views_view",
 *   uses_hook_block = TRUE,
 *   contextual_links_locations = {"block"},
 *   admin = @Translation("Block")
 * )
 */
class Block extends DisplayPluginBase {

  /**
   * Whether the display allows attachments.
   *
   * @var bool
   */
  protected $usesAttachments = TRUE;

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['block_description'] = array('default' => '', 'translatable' => TRUE);
    $options['block_caching'] = array('default' => DRUPAL_NO_CACHE);

    return $options;
  }

  /**
   * The display block handler returns the structure necessary for a block.
   */
  public function execute() {
    // Prior to this being called, the $view should already be set to this
    // display, and arguments should be set on the view.
    $element = $this->view->render();
    if (!empty($this->view->result) || $this->getOption('empty') || !empty($this->view->style_plugin->definition['even empty'])) {
      return $element;
    }

    return array();
  }

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $categories['block'] = array(
      'title' => t('Block settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -10,
      ),
    );

    $block_description = strip_tags($this->getOption('block_description'));
    if (empty($block_description)) {
      $block_description = t('None');
    }

    $options['block_description'] = array(
      'category' => 'block',
      'title' => t('Block name'),
      'value' => views_ui_truncate($block_description, 24),
    );

    $types = $this->blockCachingModes();
    $options['block_caching'] = array(
      'category' => 'other',
      'title' => t('Block caching'),
      'value' => $types[$this->getCacheType()],
    );
  }

  /**
   * Provide a list of core's block caching modes.
   */
  protected function blockCachingModes() {
    return array(
      DRUPAL_NO_CACHE => t('Do not cache'),
      DRUPAL_CACHE_GLOBAL => t('Cache once for everything (global)'),
      DRUPAL_CACHE_PER_PAGE => t('Per page'),
      DRUPAL_CACHE_PER_ROLE => t('Per role'),
      DRUPAL_CACHE_PER_ROLE | DRUPAL_CACHE_PER_PAGE => t('Per role per page'),
      DRUPAL_CACHE_PER_USER => t('Per user'),
      DRUPAL_CACHE_PER_USER | DRUPAL_CACHE_PER_PAGE => t('Per user per page'),
    );
  }

  /**
   * Provide a single method to figure caching type, keeping a sensible default
   * for when it's unset.
   */
  public function getCacheType() {
    $cache_type = $this->getOption('block_caching');
    if (empty($cache_type)) {
      $cache_type = DRUPAL_NO_CACHE;
    }
    return $cache_type;
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state['section']) {
      case 'block_description':
        $form['#title'] .= t('Block admin description');
        $form['block_description'] = array(
          '#type' => 'textfield',
          '#description' => t('This will appear as the name of this block in administer >> structure >> blocks.'),
          '#default_value' => $this->getOption('block_description'),
        );
        break;
      case 'block_caching':
        $form['#title'] .= t('Block caching type');

        $form['block_caching'] = array(
          '#type' => 'radios',
          '#description' => t("This sets the default status for Drupal's built-in block caching method; this requires that caching be turned on in block administration, and be careful because you have little control over when this cache is flushed."),
          '#options' => $this->blockCachingModes(),
          '#default_value' => $this->getCacheType(),
        );
        break;
      case 'exposed_form_options':
        $this->view->initHandlers();
        if (!$this->usesExposed() && parent::usesExposed()) {
          $form['exposed_form_options']['warning'] = array(
            '#weight' => -10,
            '#markup' => '<div class="messages messages--warning">' . t('Exposed filters in block displays require "Use AJAX" to be set to work correctly.') . '</div>',
          );
        }
    }
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitOptionsForm(&$form, &$form_state) {
    parent::submitOptionsForm($form, $form_state);
    switch ($form_state['section']) {
      case 'block_description':
        $this->setOption('block_description', $form_state['values']['block_description']);
        break;
      case 'block_caching':
        $this->setOption('block_caching', $form_state['values']['block_caching']);
        break;
    }
  }

  /**
   * Block views use exposed widgets only if AJAX is set.
   */
  public function usesExposed() {
      if ($this->ajaxEnabled()) {
        return parent::usesExposed();
      }
      return FALSE;
    }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::remove().
   */
  public function remove() {
    parent::remove();

    $plugin_id = 'views_block:' . $this->view->storage->id() . '-' . $this->display['id'];
    foreach (entity_load_multiple_by_properties('block', array('plugin' => $plugin_id)) as $block) {
      $block->delete();
    }
  }

}
