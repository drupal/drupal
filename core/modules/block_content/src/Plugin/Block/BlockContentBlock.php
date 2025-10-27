<?php

namespace Drupal\block_content\Plugin\Block;

use Drupal\block_content\BlockContentUuidLookup;
use Drupal\block_content\Plugin\Derivative\BlockContent;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a generic block type.
 */
#[Block(
  id: "block_content",
  admin_label: new TranslatableMarkup("Content block"),
  category: new TranslatableMarkup("Content block"),
  deriver: BlockContent::class
)]
class BlockContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The block content entity.
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected $blockContent;

  /**
   * Constructs a new BlockContentBlock.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected BlockManagerInterface $blockManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountInterface $account,
    protected UrlGeneratorInterface $urlGenerator,
    protected BlockContentUuidLookup $uuidLookup,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    protected ?EntityRepositoryInterface $entityRepository = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!$this->entityRepository instanceof EntityRepositoryInterface) {
      @trigger_error('Calling ' . __CLASS__ . ' constructor without the $entityRepository argument is deprecated in drupal:11.3.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/project/drupal/issues/3175985', E_USER_DEPRECATED);
      $this->entityRepository = \Drupal::service(EntityRepositoryInterface::class);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_mode' => 'full',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $block = $this->getEntity();
    if (!$block) {
      return $form;
    }
    $options = $this->entityDisplayRepository->getViewModeOptionsByBundle('block_content', $block->bundle());

    $form['view_mode'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('View mode'),
      '#description' => $this->t('Output the block in this view mode.'),
      '#default_value' => $this->configuration['view_mode'],
      '#access' => (count($options) > 1),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Invalidate the block cache to update content block-based derivatives.
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
    $this->blockManager->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->getEntity()) {
      return $this->getEntity()->access('view', $account, TRUE);
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($block = $this->getEntity()) {
      return $this->entityTypeManager->getViewBuilder($block->getEntityTypeId())->view($block, $this->configuration['view_mode']);
    }
    else {
      return [
        '#markup' => $this->t('Block with uuid %uuid does not exist. <a href=":url">Add content block</a>.', [
          '%uuid' => $this->getDerivativeId(),
          ':url' => $this->urlGenerator->generate('block_content.add_page'),
        ]),
        '#access' => $this->account->hasPermission('administer blocks'),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPlaceholder(): bool {
    return TRUE;
  }

  /**
   * Loads the block content entity of the block.
   *
   * @return \Drupal\block_content\BlockContentInterface|null
   *   The block content entity.
   */
  protected function getEntity() {
    if (!isset($this->blockContent)) {
      $uuid = $this->getDerivativeId();
      if ($id = $this->uuidLookup->get($uuid)) {
        $this->blockContent = $this->entityTypeManager->getStorage('block_content')->load($id);
      }
    }
    /** @var \Drupal\block_content\BlockContentInterface|null */
    return $this->entityRepository->getTranslationFromContext($this->blockContent);
  }

}
