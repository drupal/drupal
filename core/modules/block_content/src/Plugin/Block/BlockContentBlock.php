<?php

/**
 * @file
 * Contains \Drupal\block_content\Plugin\Block\BlockContentBlock.
 */

namespace Drupal\block_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic custom block type.
 *
 * @Block(
 *  id = "block_content",
 *  admin_label = @Translation("Custom block"),
 *  category = @Translation("Custom"),
 *  deriver = "Drupal\block_content\Plugin\Derivative\BlockContent"
 * )
 */
class BlockContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Plugin Block Manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface.
   */
  protected $blockManager;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The Drupal account to use for checking for access to block.
   *
   * @var \Drupal\Core\Session\AccountInterface.
   */
  protected $account;

  /**
   * Constructs a new BlockContentBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Block\BlockManagerInterface
   *   The Plugin Block Manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which view access should be checked.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockManagerInterface $block_manager, EntityManagerInterface $entity_manager, AccountInterface $account, UrlGeneratorInterface $url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->blockManager = $block_manager;
    $this->entityManager = $entity_manager;
    $this->account = $account;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.block'),
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'status' => TRUE,
      'info' => '',
      'view_mode' => 'full',
      // Modify the default max age for custom block blocks: modifications made
      // to them will automatically invalidate corresponding cache tags, thus
      // allowing us to cache custom block blocks forever.
      'cache' => array(
        'max_age' => \Drupal\Core\Cache\Cache::PERMANENT,
      ),
    );
  }

  /**
   * Overrides \Drupal\Core\Block\BlockBase::blockForm().
   *
   * Adds body and description fields to the block configuration form.
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $options = $this->entityManager->getViewModeOptions('block_content');
    $form['view_mode'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('View mode'),
      '#description' => t('Output the block in this view mode.'),
      '#default_value' => $this->configuration['view_mode'],
      '#access' => (count($options) > 1),
    );
    $form['title']['#description'] = t('The title of the block as shown to the user.');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Invalidate the block cache to update custom block-based derivatives.
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
    $this->blockManager->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $uuid = $this->getDerivativeId();
    if ($block = $this->entityManager->loadEntityByUuid('block_content', $uuid)) {
      return $this->entityManager->getViewBuilder($block->getEntityTypeId())->view($block, $this->configuration['view_mode']);
    }
    else {
      return array(
        '#markup' => t('Block with uuid %uuid does not exist. <a href="!url">Add custom block</a>.', array(
          '%uuid' => $uuid,
          '!url' => $this->urlGenerator->generate('block_content.add_page')
        )),
        '#access' => $this->account->hasPermission('administer blocks')
      );
    }
  }
}
