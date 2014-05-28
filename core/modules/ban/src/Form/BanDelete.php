<?php

/**
 * @file
 * Contains \Drupal\ban\Form\BanDelete.
 */

namespace Drupal\ban\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\ban\BanIpManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form to unban IP addresses.
 */
class BanDelete extends ConfirmFormBase {

  /**
   * The banned IP address.
   *
   * @var string
   */
  protected $banIp;

  /**
   * Constructs a new BanDelete object.
   *
   * @param \Drupal\ban\BanIpManagerInterface $ip_manager
   *   The IP manager.
   */
  public function __construct(BanIpManagerInterface $ip_manager) {
    $this->ipManager = $ip_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ban.ip_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ban_ip_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to unblock %ip?', array('%ip' => $this->banIp));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('ban.admin_page');
  }

  /**
   * {@inheritdoc}
   *
   * @param string $ban_id
   *   The IP address record ID to unban.
   */
  public function buildForm(array $form, array &$form_state, $ban_id = '') {
    if (!$this->banIp = $this->ipManager->findById($ban_id)) {
      throw new NotFoundHttpException();
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->ipManager->unbanIp($this->banIp);
    watchdog('user', 'Deleted %ip', array('%ip' => $this->banIp));
    drupal_set_message($this->t('The IP address %ip was deleted.', array('%ip' => $this->banIp)));
    $form_state['redirect_route'] = $this->getCancelRoute();
  }

}
