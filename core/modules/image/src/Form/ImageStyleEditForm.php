<?php

/**
 * @file
 * Contains \Drupal\image\Form\ImageStyleEditForm.
 */

namespace Drupal\image\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\image\ConfigurableImageEffectInterface;
use Drupal\image\ImageEffectManager;
use Drupal\Component\Utility\String;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for image style edit form.
 */
class ImageStyleEditForm extends ImageStyleFormBase {

  /**
   * The image effect manager service.
   *
   * @var \Drupal\image\ImageEffectManager
   */
  protected $imageEffectManager;

  /**
   * Constructs an ImageStyleEditForm object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The storage.
   * @param \Drupal\image\ImageEffectManager $image_effect_manager
   *   The image effect manager service.
   */
  public function __construct(EntityStorageInterface $image_style_storage, ImageEffectManager $image_effect_manager) {
    parent::__construct($image_style_storage);
    $this->imageEffectManager = $image_effect_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('plugin.manager.image.effect')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $form['#title'] = $this->t('Edit style %name', array('%name' => $this->entity->label()));
    $form['#tree'] = TRUE;
    $form['#attached']['css'][drupal_get_path('module', 'image') . '/css/image.admin.css'] = array();

    // Show the thumbnail preview.
    $preview_arguments = array('#theme' => 'image_style_preview', '#style' => $this->entity);
    $form['preview'] = array(
      '#type' => 'item',
      '#title' => $this->t('Preview'),
      '#markup' => drupal_render($preview_arguments),
      // Render preview above parent elements.
      '#weight' => -5,
    );

    // Build the list of existing image effects for this image style.
    $form['effects'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Effect'),
        $this->t('Weight'),
        $this->t('Operations'),
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'image-effect-order-weight',
        ),
      ),
      '#attributes' => array(
        'id' => 'image-style-effects',
      ),
      '#empty' => t('There are currently no effects in this style. Add one by selecting an option below.'),
      // Render effects below parent elements.
      '#weight' => 5,
    );
    foreach ($this->entity->getEffects() as $effect) {
      $key = $effect->getUuid();
      $form['effects'][$key]['#attributes']['class'][] = 'draggable';
      $form['effects'][$key]['#weight'] = isset($user_input['effects']) ? $user_input['effects'][$key]['weight'] : NULL;
      $form['effects'][$key]['effect'] = array(
        '#tree' => FALSE,
        'data' => array(
          'label' => array(
            '#markup' => String::checkPlain($effect->label()),
          ),
        ),
      );

      $summary = $effect->getSummary();

      if (!empty($summary)) {
        $summary['#prefix'] = ' ';
        $form['effects'][$key]['effect']['data']['summary'] = $summary;
      }

      $form['effects'][$key]['weight'] = array(
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', array('@title' => $effect->label())),
        '#title_display' => 'invisible',
        '#default_value' => $effect->getWeight(),
        '#attributes' => array(
          'class' => array('image-effect-order-weight'),
        ),
      );

      $links = array();
      $is_configurable = $effect instanceof ConfigurableImageEffectInterface;
      if ($is_configurable) {
        $links['edit'] = array(
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('image.effect_edit_form', [
            'image_style' => $this->entity->id(),
            'image_effect' => $key,
          ]),
        );
      }
      $links['delete'] = array(
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('image.effect_delete', [
          'image_style' => $this->entity->id(),
          'image_effect' => $key,
        ]),
      );
      $form['effects'][$key]['operations'] = array(
        '#type' => 'operations',
        '#links' => $links,
      );
    }

    // Build the new image effect addition form and add it to the effect list.
    $new_effect_options = array();
    $effects = $this->imageEffectManager->getDefinitions();
    uasort($effects, function ($a, $b) {
      return strcasecmp($a['id'], $b['id']);
    });
    foreach ($effects as $effect => $definition) {
      $new_effect_options[$effect] = $definition['label'];
    }
    $form['effects']['new'] = array(
      '#tree' => FALSE,
      '#weight' => isset($user_input['weight']) ? $user_input['weight'] : NULL,
      '#attributes' => array('class' => array('draggable')),
    );
    $form['effects']['new']['effect'] = array(
      'data' => array(
        'new' => array(
          '#type' => 'select',
          '#title' => $this->t('Effect'),
          '#title_display' => 'invisible',
          '#options' => $new_effect_options,
          '#empty_option' => $this->t('Select a new effect'),
        ),
        array(
          'add' => array(
            '#type' => 'submit',
            '#value' => $this->t('Add'),
            '#validate' => array('::effectValidate'),
            '#submit' => array('::submitForm', '::effectSave'),
          ),
        ),
      ),
      '#prefix' => '<div class="image-style-new">',
      '#suffix' => '</div>',
    );

    $form['effects']['new']['weight'] = array(
      '#type' => 'weight',
      '#title' => $this->t('Weight for new effect'),
      '#title_display' => 'invisible',
      '#default_value' => count($this->entity->getEffects()) + 1,
      '#attributes' => array('class' => array('image-effect-order-weight')),
    );
    $form['effects']['new']['operations'] = array(
      'data' => array(),
    );

    return parent::form($form, $form_state);
  }

  /**
   * Validate handler for image effect.
   */
  public function effectValidate($form, FormStateInterface $form_state) {
    if (!$form_state->getValue('new')) {
      $form_state->setErrorByName('new', $this->t('Select an effect to add.'));
    }
  }

  /**
   * Submit handler for image effect.
   */
  public function effectSave($form, FormStateInterface $form_state) {
    $this->save($form, $form_state);

    // Check if this field has any configuration options.
    $effect = $this->imageEffectManager->getDefinition($form_state->getValue('new'));

    // Load the configuration form for this option.
    if (is_subclass_of($effect['class'], '\Drupal\image\ConfigurableImageEffectInterface')) {
      $form_state->setRedirect(
        'image.effect_add_form',
        array(
          'image_style' => $this->entity->id(),
          'image_effect' => $form_state->getValue('new'),
        ),
        array('query' => array('weight' => $form_state->getValue('weight')))
      );
    }
    // If there's no form, immediately add the image effect.
    else {
      $effect = array(
        'id' => $effect['id'],
        'data' => array(),
        'weight' => $form_state->getValue('weight'),
      );
      $effect_id = $this->entity->addImageEffect($effect);
      $this->entity->save();
      if (!empty($effect_id)) {
        drupal_set_message($this->t('The image effect was successfully applied.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Update image effect weights.
    if (!$form_state->isValueEmpty('effects')) {
      $this->updateEffectWeights($form_state->getValue('effects'));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    drupal_set_message($this->t('Changes to the style have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Update style');

    return $actions;
  }

  /**
   * Updates image effect weights.
   *
   * @param array $effects
   *   Associative array with effects having effect uuid as keys and and array
   *   with effect data as values.
   */
  protected function updateEffectWeights(array $effects) {
    foreach ($effects as $uuid => $effect_data) {
      if ($this->entity->getEffects()->has($uuid)) {
        $this->entity->getEffect($uuid)->setWeight($effect_data['weight']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // Do not copy effects here, see self::updateEffectWeights().
      if ($key != 'effects') {
        $entity->set($key, $value);
      }
    }
  }

}
