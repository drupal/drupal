<?php

namespace Drupal\block_content;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the custom block edit forms.
 */
class BlockContentForm extends ContentEntityForm {

  /**
   * The block content entity.
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $block = $this->entity;

    $form = parent::form($form, $form_state);

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit custom block %label', ['%label' => $block->label()]);
    }
    // Override the default CSS class name, since the user-defined custom block
    // type name in 'TYPE-block-form' potentially clashes with third-party class
    // names.
    $form['#attributes']['class'][0] = 'block-' . Html::getClass($block->bundle()) . '-form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $block = $this->entity;

    $insert = $block->isNew();
    $block->save();
    $context = ['@type' => $block->bundle(), '%info' => $block->label()];
    $logger = $this->logger('block_content');
    $block_type = $this->getBundleEntity();
    $t_args = ['@type' => $block_type->label(), '%info' => $block->label()];

    if ($insert) {
      $logger->notice('@type: added %info.', $context);
      drupal_set_message($this->t('@type %info has been created.', $t_args));
    }
    else {
      $logger->notice('@type: updated %info.', $context);
      drupal_set_message($this->t('@type %info has been updated.', $t_args));
    }

    if ($block->id()) {
      $form_state->setValue('id', $block->id());
      $form_state->set('id', $block->id());
      if ($insert) {
        if (!$theme = $block->getTheme()) {
          $theme = $this->config('system.theme')->get('default');
        }
        $form_state->setRedirect(
          'block.admin_add',
          [
            'plugin_id' => 'block_content:' . $block->uuid(),
            'theme' => $theme,
          ]
        );
      }
      else {
        $form_state->setRedirectUrl($block->urlInfo('collection'));
      }
    }
    else {
      // In the unlikely case something went wrong on save, the block will be
      // rebuilt and block form redisplayed.
      drupal_set_message($this->t('The block could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

}
