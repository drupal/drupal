<?php

namespace Drupal\layout_builder\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring a layout section.
 *
 * @internal
 *   Form classes are internal.
 */
class ConfigureSectionForm extends FormBase {

  use AjaxFormHelperTrait;
  use LayoutBuilderContextTrait;
  use LayoutBuilderHighlightTrait;
  use LayoutRebuildTrait;

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The plugin being configured.
   *
   * @var \Drupal\Core\Layout\LayoutInterface|\Drupal\Core\Plugin\PluginFormInterface
   */
  protected $layout;

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * The field delta.
   *
   * @var int
   */
  protected $delta;

  /**
   * Indicates whether the section is being added or updated.
   *
   * @var bool
   */
  protected $isUpdate;

  /**
   * Constructs a new ConfigureSectionForm.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, PluginFormFactoryInterface $plugin_form_manager) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->pluginFormFactory = $plugin_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('plugin_form.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_configure_section';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $plugin_id = NULL) {
    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->isUpdate = is_null($plugin_id);

    if ($this->isUpdate) {
      $section = $this->sectionStorage->getSection($this->delta);
      if ($label = $section->getLayoutSettings()['label']) {
        $form['#title'] = $this->t('Configure @section', ['@section' => $label]);
      }
    }
    else {
      $section = new Section($plugin_id);
    }
    // Passing available contexts to the layout plugin here could result in an
    // exception since the layout may not have a context mapping for a required
    // context slot on creation.
    $this->layout = $section->getLayout();

    $form_state->setTemporaryValue('gathered_contexts', $this->getAvailableContexts($this->sectionStorage));
    $form['#tree'] = TRUE;
    $form['layout_settings'] = [];
    $subform_state = SubformState::createForSubform($form['layout_settings'], $form, $form_state);
    $form['layout_settings'] = $this->getPluginForm($this->layout)->buildConfigurationForm($form['layout_settings'], $subform_state);

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->isUpdate ? $this->t('Update') : $this->t('Add section'),
      '#button_type' => 'primary',
    ];
    if ($this->isAjax()) {
      $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
      // @todo static::ajaxSubmit() requires data-drupal-selector to be the same
      //   between the various Ajax requests. A bug in
      //   \Drupal\Core\Form\FormBuilder prevents that from happening unless
      //   $form['#id'] is also the same. Normally, #id is set to a unique HTML
      //   ID via Html::getUniqueId(), but here we bypass that in order to work
      //   around the data-drupal-selector bug. This is okay so long as we
      //   assume that this form only ever occurs once on a page. Remove this
      //   workaround in https://www.drupal.org/node/2897377.
      $form['#id'] = Html::getId($form_state->getBuildInfo()['form_id']);
    }
    $target_highlight_id = $this->isUpdate ? $this->sectionUpdateHighlightId($delta) : $this->sectionAddHighlightId($delta);
    $form['#attributes']['data-layout-builder-target-highlight-id'] = $target_highlight_id;

    // Mark this as an administrative page for JavaScript ("Back to site" link).
    $form['#attached']['drupalSettings']['path']['currentPathIsAdmin'] = TRUE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $subform_state = SubformState::createForSubform($form['layout_settings'], $form, $form_state);
    $this->getPluginForm($this->layout)->validateConfigurationForm($form['layout_settings'], $subform_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Call the plugin submit handler.
    $subform_state = SubformState::createForSubform($form['layout_settings'], $form, $form_state);
    $this->getPluginForm($this->layout)->submitConfigurationForm($form['layout_settings'], $subform_state);

    // If this layout is context-aware, set the context mapping.
    if ($this->layout instanceof ContextAwarePluginInterface) {
      $this->layout->setContextMapping($subform_state->getValue('context_mapping', []));
    }

    $plugin_id = $this->layout->getPluginId();
    $configuration = $this->layout->getConfiguration();

    if ($this->isUpdate) {
      $this->sectionStorage->getSection($this->delta)->setLayoutSettings($configuration);
    }
    else {
      $this->sectionStorage->insertSection($this->delta, new Section($plugin_id, $configuration));
    }

    $this->layoutTempstoreRepository->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    return $this->rebuildAndClose($this->sectionStorage);
  }

  /**
   * Retrieves the plugin form for a given layout.
   *
   * @param \Drupal\Core\Layout\LayoutInterface $layout
   *   The layout plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the layout.
   */
  protected function getPluginForm(LayoutInterface $layout) {
    if ($layout instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($layout, 'configure');
    }

    if ($layout instanceof PluginFormInterface) {
      return $layout;
    }

    throw new \InvalidArgumentException(sprintf('The "%s" layout does not provide a configuration form', $layout->getPluginId()));
  }

  /**
   * Retrieve the section storage property.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface
   *   The section storage for the current form.
   */
  public function getSectionStorage() {
    return $this->sectionStorage;
  }

}
