<?php

namespace Drupal\settings_tray\Block;

use Drupal\block\BlockForm;
use Drupal\block\BlockInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Url;

/**
 * Provides form for block instance forms when used in the off-canvas dialog.
 *
 * This form removes advanced sections of regular block form such as the
 * visibility settings, machine ID and region.
 *
 * @internal
 */
class BlockEntitySettingTrayForm extends BlockForm {

  use AjaxFormHelperTrait;

  /**
   * Provides a title callback to get the block's admin label.
   *
   * @param \Drupal\block\BlockInterface $block
   *   The block entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title.
   */
  public function title(BlockInterface $block) {
    // @todo Wrap "Configure " in <span class="visually-hidden"></span> once
    //   https://www.drupal.org/node/2359901 is fixed.
    return $this->t('Configure @block', ['@block' => $block->getPlugin()->getPluginDefinition()['admin_label']]);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Create link to full block form.
    $query = [];
    if ($destination = $this->getRequest()->query->get('destination')) {
      $query['destination'] = $destination;
    }
    $form['advanced_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Advanced block options'),
      '#url' => $this->entity->toUrl('edit-form', ['query' => $query]),
      '#weight' => 1000,
    ];

    // Remove the ID and region elements.
    unset($form['id'], $form['region'], $form['settings']['admin_label']);

    if (isset($form['settings']['label_display']) && isset($form['settings']['label'])) {
      // Only show the label input if the label will be shown on the page.
      $form['settings']['label_display']['#weight'] = -100;
      $form['settings']['label']['#states']['visible'] = [
        ':input[name="settings[label_display]"]' => ['checked' => TRUE],
      ];

      // Relabel to "Block title" because on the front-end this may be confused
      // with page title.
      $form['settings']['label']['#title'] = $this->t("Block title");
      $form['settings']['label_display']['#title'] = $this->t("Display block title");
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save @block', ['@block' => $this->entity->getPlugin()->getPluginDefinition()['admin_label']]);
    $actions['delete']['#access'] = FALSE;
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildVisibilityInterface(array $form, FormStateInterface $form_state) {
    // Do not display the visibility.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateVisibility(array $form, FormStateInterface $form_state) {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  protected function submitVisibility(array $form, FormStateInterface $form_state) {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginForm(BlockPluginInterface $block) {
    if ($block instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($block, 'settings_tray', 'configure');
    }
    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::ajaxSubmit',
    ];
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // static::ajaxSubmit() requires data-drupal-selector to be the same between
    // the various Ajax requests. A bug in \Drupal\Core\Form\FormBuilder
    // prevents that from happening unless $form['#id'] is also the same.
    // Normally, #id is set to a unique HTML ID via Html::getUniqueId(), but
    // here we bypass that in order to work around the data-drupal-selector bug.
    // This is okay so long as we assume that this form only ever occurs once on
    // a page.
    // @todo Remove this workaround once https://www.drupal.org/node/2897377 is
    //   fixed.
    $form['#id'] = Html::getId($form_state->getBuildInfo()['form_id']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    if ($redirect_url = $this->getRedirectUrl()) {
      $command = new RedirectCommand($redirect_url->setAbsolute()->toString());
    }
    else {
      // Settings Tray always provides a destination.
      throw new \Exception("No destination provided by Settings Tray form");
    }
    $response = new AjaxResponse();
    return $response->addCommand($command);
  }

  /**
   * Gets the form's redirect URL from 'destination' provide in the request.
   *
   * @return \Drupal\Core\Url|null
   *   The redirect URL or NULL if dialog should just be closed.
   */
  protected function getRedirectUrl() {
    // \Drupal\Core\Routing\RedirectDestination::get() cannot be used directly
    // because it will use <current> if 'destination' is not in the query
    // string.
    if ($this->getRequest()->query->has('destination') && $destination = $this->getRedirectDestination()->get()) {
      return Url::fromUserInput('/' . $destination);
    }
  }

}
