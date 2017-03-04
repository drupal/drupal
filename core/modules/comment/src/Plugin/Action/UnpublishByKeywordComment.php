<?php

namespace Drupal\comment\Plugin\Action;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unpublishes a comment containing certain keywords.
 *
 * @Action(
 *   id = "comment_unpublish_by_keyword_action",
 *   label = @Translation("Unpublish comment containing keyword(s)"),
 *   type = "comment"
 * )
 */
class UnpublishByKeywordComment extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The comment entity builder handler.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a UnpublishByKeywordComment object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $comment_view_builder
   *   The comment entity builder handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityViewBuilderInterface $comment_view_builder, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->viewBuilder = $comment_view_builder;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getViewBuilder('comment'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($comment = NULL) {
    $build = $this->viewBuilder->view($comment);
    $text = $this->renderer->renderPlain($build);
    foreach ($this->configuration['keywords'] as $keyword) {
      if (strpos($text, $keyword) !== FALSE) {
        $comment->setPublished(FALSE);
        $comment->save();
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'keywords' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['keywords'] = [
      '#title' => $this->t('Keywords'),
      '#type' => 'textarea',
      '#description' => $this->t('The comment will be unpublished if it contains any of the phrases above. Use a case-sensitive, comma-separated list of phrases. Example: funny, bungee jumping, "Company, Inc."'),
      '#default_value' => Tags::implode($this->configuration['keywords']),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['keywords'] = Tags::explode($form_state->getValue('keywords'));
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\comment\CommentInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
