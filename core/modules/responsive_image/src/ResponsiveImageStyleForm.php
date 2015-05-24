<?php

/**
 * @file
 * Contains Drupal\responsive_image\ResponsiveImageStyleForm.
 */

namespace Drupal\responsive_image;

use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the responsive image edit/add forms.
 */
class ResponsiveImageStyleForm extends EntityForm {

  /**
   * The breakpoint manager.
   *
   * @var \Drupal\breakpoint\BreakpointManagerInterface
   */
  protected $breakpointManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('breakpoint.manager')
    );
  }

  /**
   * Constructs the responsive image style form.
   *
   * @param \Drupal\breakpoint\BreakpointManagerInterface $breakpoint_manager
   *   The breakpoint manager.
   */
  public function __construct(BreakpointManagerInterface $breakpoint_manager) {
    $this->breakpointManager = $breakpoint_manager;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The array containing the complete form.
   */
  public function form(array $form, FormStateInterface $form_state) {
    if ($this->operation == 'duplicate') {
      $form['#title'] = $this->t('<em>Duplicate responsive image style</em> @label', array('@label' => $this->entity->label()));
      $this->entity = $this->entity->createDuplicate();
    }
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit responsive image style</em> @label', array('@label' => $this->entity->label()));
    }

    /** @var \Drupal\responsive_image\ResponsiveImageStyleInterface $responsive_image_style */
    $responsive_image_style = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $responsive_image_style->label(),
      '#description' => $this->t("Example: 'Hero image' or 'Author image'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $responsive_image_style->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\responsive_image\Entity\ResponsiveImageStyle::load',
        'source' => array('label'),
      ),
      '#disabled' => (bool) $responsive_image_style->id() && $this->operation != 'duplicate',
    );

    if ((bool) $responsive_image_style->id() && $this->operation != 'duplicate') {
      $description = $this->t('Select a breakpoint group from the installed themes.') . ' ' . $this->t("Warning: if you change the breakpoint group you lose all your selected image style mappings.");
    }
    else {
      $description = $this->t('Select a breakpoint group from the installed themes.');
    }
    $form['breakpoint_group'] = array(
      '#type' => 'select',
      '#title' => $this->t('Breakpoint group'),
      '#default_value' => $responsive_image_style->getBreakpointGroup(),
      '#options' => $this->breakpointManager->getGroups(),
      '#required' => TRUE,
      '#description' => $description,
    );

    $image_styles = image_style_options(TRUE);
    $image_styles[RESPONSIVE_IMAGE_EMPTY_IMAGE] = $this->t('- empty image -');

    $form['fallback_image_style'] = array(
      '#title' => $this->t('Fallback image style'),
      '#type' => 'select',
      '#default_value' => $responsive_image_style->getFallbackImageStyle(),
      '#options' => $image_styles,
      '#required' => TRUE,
    );

    $breakpoints = $this->breakpointManager->getBreakpointsByGroup($responsive_image_style->getBreakpointGroup());
    foreach ($breakpoints as $breakpoint_id => $breakpoint) {
      foreach ($breakpoint->getMultipliers() as $multiplier) {
        $label = $multiplier . ' ' . $breakpoint->getLabel() . ' [' . $breakpoint->getMediaQuery() . ']';
        $form['keyed_styles'][$breakpoint_id][$multiplier] = array(
          '#type' => 'details',
          '#title' => $label,
        );
        $image_style_mapping = $responsive_image_style->getImageStyleMapping($breakpoint_id, $multiplier);
        $form['keyed_styles'][$breakpoint_id][$multiplier]['image_mapping_type'] = array(
          '#title' => $this->t('Type'),
          '#type' => 'radios',
          '#options' => array(
            '_none' => $this->t('Do not use this breakpoint'),
            'image_style' => $this->t('Use image styles'),
            'sizes' => $this->t('Use the sizes attribute'),
          ),
          '#default_value' => isset($image_style_mapping['image_mapping_type']) ? $image_style_mapping['image_mapping_type'] : '_none',
        );
        $form['keyed_styles'][$breakpoint_id][$multiplier]['image_style'] = array(
          '#type' => 'select',
          '#title' => $this->t('Image style'),
          '#options' => $image_styles,
          '#default_value' => isset($image_style_mapping['image_mapping']) && is_string($image_style_mapping['image_mapping']) ? $image_style_mapping['image_mapping'] : '',
          '#description' => $this->t('Select an image style for this breakpoint.'),
          '#states' => array(
            'visible' => array(
              ':input[name="keyed_styles[' . $breakpoint_id . '][' . $multiplier . '][image_mapping_type]"]' => array('value' => 'image_style'),
            ),
          ),
        );
        $form['keyed_styles'][$breakpoint_id][$multiplier]['sizes'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Sizes'),
          '#default_value' => isset($image_style_mapping['image_mapping']['sizes']) ? $image_style_mapping['image_mapping']['sizes'] : '',
          '#description' => $this->t('Enter the value for the sizes attribute (e.g. "(min-width:700px) 700px, 100vw").'),
          '#states' => array(
            'visible' => array(
              ':input[name="keyed_styles[' . $breakpoint_id . '][' . $multiplier . '][image_mapping_type]"]' => array('value' => 'sizes'),
            ),
          ),
        );
        $form['keyed_styles'][$breakpoint_id][$multiplier]['sizes_image_styles'] = array(
          '#title' => $this->t('Image styles'),
          '#type' => 'checkboxes',
          '#options' => array_diff_key($image_styles, array('' => '')),
          '#default_value' => isset($image_style_mapping['image_mapping']['sizes_image_styles']) ? $image_style_mapping['image_mapping']['sizes_image_styles'] : array(),
          '#states' => array(
            'visible' => array(
              ':input[name="keyed_styles[' . $breakpoint_id . '][' . $multiplier . '][image_mapping_type]"]' => array('value' => 'sizes'),
            ),
          ),
        );
      }
    }

    $form['#tree'] = TRUE;

    return parent::form($form, $form_state, $responsive_image_style);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    // Only validate on edit.
    if ($form_state->hasValue('keyed_styles')) {
      // Check if another breakpoint group is selected.
      if ($form_state->getValue('breakpoint_group') != $form_state->getCompleteForm()['breakpoint_group']['#default_value']) {
        // Remove the image style mappings since the breakpoint ID has changed.
        $form_state->unsetValue('keyed_styles');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\responsive_image\ResponsiveImageStyleInterface $responsive_image_style */
    $responsive_image_style = $this->entity;
    // Remove all the existing mappings and replace with submitted values.
    $responsive_image_style->removeImageStyleMappings();
    if ($form_state->hasValue('keyed_styles')) {
      foreach ($form_state->getValue('keyed_styles') as $breakpoint_id => $multipliers) {
        foreach ($multipliers as $multiplier => $image_style_mapping) {
          if ($image_style_mapping['image_mapping_type'] === 'sizes') {
            $mapping = array(
              'image_mapping_type' => 'sizes',
              'image_mapping' => array(
                'sizes' => $image_style_mapping['sizes'],
                'sizes_image_styles' => array_keys(array_filter($image_style_mapping['sizes_image_styles'])),
              )
            );
            $responsive_image_style->addImageStyleMapping($breakpoint_id, $multiplier, $mapping);
          }
          elseif ($image_style_mapping['image_mapping_type'] === 'image_style') {
            $mapping = array(
              'image_mapping_type' => 'image_style',
              'image_mapping' => $image_style_mapping['image_style'],
            );
            $responsive_image_style->addImageStyleMapping($breakpoint_id, $multiplier, $mapping);
          }
        }
      }
    }
    $responsive_image_style->save();

    $this->logger('responsive_image')->notice('Responsive image style @label saved.', array('@label' => $responsive_image_style->label()));
    drupal_set_message($this->t('Responsive image style %label saved.', array('%label' => $responsive_image_style->label())));

    // Redirect to edit form after creating a new responsive image style or
    // after selecting another breakpoint group.
    if (!$responsive_image_style->hasImageStyleMappings()) {
      $form_state->setRedirect(
        'entity.responsive_image_style.edit_form',
        array('responsive_image_style' => $responsive_image_style->id())
      );
    }
    else {
      $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
    }
  }

}
