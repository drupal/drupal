<?php

namespace Drupal\layout_builder\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base form for configuring a block.
 *
 * @internal
 *   Form classes are internal.
 */
abstract class ConfigureBlockFormBase extends FormBase implements BaseFormIdInterface {

  use AjaxFormHelperTrait;
  use ContextAwarePluginAssignmentTrait;
  use LayoutBuilderContextTrait;
  use LayoutRebuildTrait;

  /**
   * The plugin being configured.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface
   */
  protected $block;

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The UUID generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * The field delta.
   *
   * @var int
   */
  protected $delta;

  /**
   * The current region.
   *
   * @var string
   */
  protected $region;

  /**
   * The UUID of the component.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * Constructs a new block form.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The UUID generator.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, ContextRepositoryInterface $context_repository, BlockManagerInterface $block_manager, UuidInterface $uuid, PluginFormFactoryInterface $plugin_form_manager) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->contextRepository = $context_repository;
    $this->blockManager = $block_manager;
    $this->uuidGenerator = $uuid;
    $this->pluginFormFactory = $plugin_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('context.repository'),
      $container->get('plugin.manager.block'),
      $container->get('uuid'),
      $container->get('plugin_form.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'layout_builder_configure_block';
  }

  /**
   * Builds the form for the block.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage being configured.
   * @param int $delta
   *   The delta of the section.
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The section component containing the block.
   *
   * @return array
   *   The form array.
   */
  public function doBuildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, SectionComponent $component = NULL) {
    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->uuid = $component->getUuid();
    $this->block = $component->getPlugin();

    $form_state->setTemporaryValue('gathered_contexts', $this->getAvailableContexts($section_storage));

    // @todo Remove once https://www.drupal.org/node/2268787 is resolved.
    $form_state->set('block_theme', $this->config('system.theme')->get('default'));

    $form['#tree'] = TRUE;
    $form['settings'] = [];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->getPluginForm($this->block)->buildConfigurationForm($form['settings'], $subform_state);

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->submitLabel(),
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

    // Mark this as an administrative page for JavaScript ("Back to site" link).
    $form['#attached']['drupalSettings']['path']['currentPathIsAdmin'] = TRUE;
    return $form;
  }

  /**
   * Returns the label for the submit button.
   *
   * @return string
   *   Submit label.
   */
  abstract protected function submitLabel();

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->getPluginForm($this->block)->validateConfigurationForm($form['settings'], $subform_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Call the plugin submit handler.
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->getPluginForm($this->block)->submitConfigurationForm($form, $subform_state);

    // If this block is context-aware, set the context mapping.
    if ($this->block instanceof ContextAwarePluginInterface) {
      $this->block->setContextMapping($subform_state->getValue('context_mapping', []));
    }

    $configuration = $this->block->getConfiguration();

    $section = $this->sectionStorage->getSection($this->delta);
    $section->getComponent($this->uuid)->setConfiguration($configuration);

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
   * Retrieves the plugin form for a given block.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   The block plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the block.
   */
  protected function getPluginForm(BlockPluginInterface $block) {
    if ($block instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($block, 'configure');
    }
    return $block;
  }

  /**
   * Retrieves the section storage object.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface
   *   The section storage for the current form.
   */
  public function getSectionStorage() {
    return $this->sectionStorage;
  }

  /**
   * Retrieves the current layout section being edited by the form.
   *
   * @return \Drupal\layout_builder\Section
   *   The current layout section.
   */
  public function getCurrentSection() {
    return $this->sectionStorage->getSection($this->delta);
  }

  /**
   * Retrieves the current component being edited by the form.
   *
   * @return \Drupal\layout_builder\SectionComponent
   *   The current section component.
   */
  public function getCurrentComponent() {
    return $this->getCurrentSection()->getComponent($this->uuid);
  }

}
