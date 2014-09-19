<?php

/**
 * @file
 * Contains Drupal\responsive_image\ResponsiveImageForm.
 */

namespace Drupal\responsive_image;

use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the responsive image edit/add forms.
 */
class ResponsiveImageMappingForm extends EntityForm {

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
   * Constructs the responsive image mapping form.
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
      $form['#title'] = $this->t('<em>Duplicate responsive image mapping</em> @label', array('@label' => $this->entity->label()));
      $this->entity = $this->entity->createDuplicate();
    }
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit responsive image mapping</em> @label', array('@label' => $this->entity->label()));
    }

    /** @var \Drupal\responsive_image\ResponsiveImageMappingInterface $responsive_image_mapping */
    $responsive_image_mapping = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $responsive_image_mapping->label(),
      '#description' => $this->t("Example: 'Hero image' or 'Author image'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $responsive_image_mapping->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\responsive_image\Entity\ResponsiveImageMapping::load',
        'source' => array('label'),
      ),
      '#disabled' => (bool) $responsive_image_mapping->id() && $this->operation != 'duplicate',
    );

    if ((bool) $responsive_image_mapping->id() && $this->operation != 'duplicate') {
      $description = $this->t('Select a breakpoint group from the installed themes.') . ' ' . $this->t("Warning: if you change the breakpoint group you lose all your selected mappings.");
    }
    else {
      $description = $this->t('Select a breakpoint group from the installed themes.');
    }
    $form['breakpointGroup'] = array(
      '#type' => 'select',
      '#title' => $this->t('Breakpoint group'),
      '#default_value' => $responsive_image_mapping->getBreakpointGroup(),
      '#options' => $this->breakpointManager->getGroups(),
      '#required' => TRUE,
      '#description' => $description,
    );

    $image_styles = image_style_options(TRUE);
    $image_styles[RESPONSIVE_IMAGE_EMPTY_IMAGE] = $this->t('- empty image -');
    $breakpoints = $this->breakpointManager->getBreakpointsByGroup($responsive_image_mapping->getBreakpointGroup());
    foreach ($breakpoints as $breakpoint_id => $breakpoint) {
      foreach ($breakpoint->getMultipliers() as $multiplier) {
        $label = $multiplier . ' ' . $breakpoint->getLabel() . ' [' . $breakpoint->getMediaQuery() . ']';
        $form['keyed_mappings'][$breakpoint_id][$multiplier] = array(
          '#type' => 'select',
          '#title' => $label,
          '#options' => $image_styles,
          '#default_value' => $responsive_image_mapping->getImageStyle($breakpoint_id, $multiplier),
          '#description' => $this->t('Select an image style for this breakpoint.'),
        );
      }
    }

    $form['#tree'] = TRUE;

    return parent::form($form, $form_state, $responsive_image_mapping);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    // Only validate on edit.
    if ($form_state->hasValue('keyed_mappings')) {
      // Check if another breakpoint group is selected.
      if ($form_state->getValue('breakpointGroup') != $form_state->getCompleteForm()['breakpointGroup']['#default_value']) {
        // Remove the mappings since the breakpoint ID has changed.
        $form_state->unsetValue('keyed_mappings');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\responsive_image\ResponsiveImageMappingInterface $responsive_image_mapping */
    $responsive_image_mapping = $this->entity;
    // Remove all the existing mappings and replace with submitted values.
    $responsive_image_mapping->removeMappings();
    if ($form_state->hasValue('keyed_mappings')) {
      foreach ($form_state->getValue('keyed_mappings') as $breakpoint_id => $multipliers) {
        foreach ($multipliers as $multiplier => $image_style) {
          $responsive_image_mapping->addMapping($breakpoint_id, $multiplier, $image_style);
        }
      }
    }
    $responsive_image_mapping->save();

    $this->logger('responsive_image')->notice('Responsive image mapping @label saved.', array('@label' => $responsive_image_mapping->label()));
    drupal_set_message($this->t('Responsive image mapping %label saved.', array('%label' => $responsive_image_mapping->label())));

    // Redirect to edit form after creating a new mapping or after selecting
    // another breakpoint group.
    if (!$responsive_image_mapping->hasMappings()) {
      $form_state->setRedirect(
        'entity.responsive_image_mapping.edit_form',
        array('responsive_image_mapping' => $responsive_image_mapping->id())
      );
    }
    else {
      $form_state->setRedirect('responsive_image.mapping_page');
    }
  }

}
