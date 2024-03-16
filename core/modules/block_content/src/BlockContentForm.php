<?php

namespace Drupal\block_content;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the content block edit forms.
 *
 * @internal
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
      $form['#title'] = $this->t('Edit content block %label', ['%label' => $block->label()]);
    }
    // Override the default CSS class name, since the user-defined content block
    // type name in 'TYPE-block-form' potentially clashes with third-party class
    // names.
    $form['#attributes']['class'][0] = 'block-' . Html::getClass($block->bundle()) . '-form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $element = parent::actions($form, $form_state);

    if ($this->getRequest()->query->has('theme')) {
      $element['submit']['#value'] = $this->t('Save and configure');
    }

    if ($this->currentUser()->hasPermission('administer blocks') && !$this->getRequest()->query->has('theme') && $this->entity->isNew()) {
      $element['configure_block'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save and configure'),
        '#weight' => 20,
        '#submit' => array_merge($element['submit']['#submit'], ['::configureBlock']),
      ];
    }

    return $element;
  }

  /**
   * Form submission handler for the 'configureBlock' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function configureBlock(array $form, FormStateInterface $form_state): void {
    $block = $this->entity;
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
    $form_state->setIgnoreDestination();
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
      $logger->info('@type: added %info.', $context);
      $this->messenger()->addStatus($this->t('@type %info has been created.', $t_args));
    }
    else {
      $logger->info('@type: updated %info.', $context);
      $this->messenger()->addStatus($this->t('@type %info has been updated.', $t_args));
    }

    if ($block->id()) {
      $form_state->setValue('id', $block->id());
      $form_state->set('id', $block->id());
      $theme = $block->getTheme();
      if ($insert && $theme) {
        $form_state->setRedirect(
          'block.admin_add',
          [
            'plugin_id' => 'block_content:' . $block->uuid(),
            'theme' => $theme,
            'region' => $this->getRequest()->query->getString('region'),
          ]
        );
      }
      else {
        $form_state->setRedirectUrl($block->toUrl('collection'));
      }
    }
    else {
      // In the unlikely case something went wrong on save, the block will be
      // rebuilt and block form redisplayed.
      $this->messenger()->addError($this->t('The block could not be saved.'));
      $form_state->setRebuild();
    }
  }

}
