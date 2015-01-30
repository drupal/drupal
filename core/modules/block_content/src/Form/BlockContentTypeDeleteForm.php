<?php

/**
 * @file
 * Contains \Drupal\block_content\Form\BlockContentTypeDeleteForm.
 */

namespace Drupal\block_content\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for deleting a custom block type entity.
 */
class BlockContentTypeDeleteForm extends EntityDeleteForm {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  public $queryFactory;

  /**
   * Constructs a query factory object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query object.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $blocks = $this->queryFactory->get('block_content')->condition('type', $this->entity->id())->execute();
    if (!empty($blocks)) {
      $caption = '<p>' . $this->formatPlural(count($blocks), '%label is used by 1 custom block on your site. You can not remove this block type until you have removed all of the %label blocks.', '%label is used by @count custom blocks on your site. You may not remove %label until you have removed all of the %label custom blocks.', array('%label' => $this->entity->label())) . '</p>';
      $form['description'] = array('#markup' => $caption);
      return $form;
    }
    else {
      return parent::buildForm($form, $form_state);
    }
  }

}
