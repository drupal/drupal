<?php

namespace Drupal\demo_umami\Hook;

use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Hook implementations for demo_umami.
 */
class DemoUmamiHooks {

  use StringTranslationTrait;

  /**
   * DemoUmamiHooks constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   The admin context.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected AccountInterface $currentUser,
    protected AdminContext $adminContext,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): string {
    $output = '';
    switch ($route_name) {
      case 'help.page.demo_umami':
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('Umami is an example food magazine website that demonstrates some of the features of Drupal core. It is intended to be used as an example site, rather than as a foundation for building your own site. For more information, see the <a href=":demo_umami">online documentation for the Umami installation profile</a>.', [':demo_umami' => 'https://www.drupal.org/node/2941833']) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<h3>' . $this->t('Demonstrating Drupal core functionality') . '</h3>';
        $output .= '<p>' . $this->t('You can look around the site to get ideas for what kinds of features Drupal is capable of, and to see how an actual site can be built using Drupal core.') . '</p>';
        $output .= '<h3>' . $this->t('Sample content') . '</h3>';
        $output .= '<p>' . $this->t('The Umami profile is very handy if you are developing a feature and need some sample content.') . '</p>';
        $output .= '<h2>' . $this->t('What to do when you are ready to build your Drupal website') . '</h2>';
        $output .= '<p>' . $this->t("Once you've tried Drupal using Umami and want to build your own site, simply reinstall Drupal and select a different installation profile (such as Standard) from the install screen.") . '</p>';
    }

    return $output;
  }

  /**
   * Implements hook_form_FORM_ID_alter() for install_configure_form().
   *
   * Allows the profile to alter the site configuration form.
   */
  #[Hook('form_install_configure_form_alter')]
  public function formInstallConfigureFormAlter(&$form, FormStateInterface $form_state): void {
    $form['site_information']['site_name']['#default_value'] = 'Umami Food Magazine';
    $form['#submit'][] = [$this, 'installConfigureSubmit'];
  }

  /**
   * Submission handler to sync the contact.form.feedback recipient.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function installConfigureSubmit(array $form, FormStateInterface $form_state): void {
    $site_mail = $form_state->getValue('site_mail');
    ContactForm::load('feedback')->setRecipients([$site_mail])->trustData()->save();

    $password = $form_state->getValue('account')['pass'];
    $this->setUserPasswords($password);
  }

  /**
   * Implements hook_toolbar().
   */
  #[Hook('toolbar')]
  public function toolbar(): array {
    // Add a warning about using an experimental profile.
    // @todo This can be removed once a generic warning for experimental
    //   profiles has been introduced in
    //   https://www.drupal.org/project/drupal/issues/2934374
    $items['experimental-profile-warning'] = [
      '#weight' => 3400,
      '#cache' => [
        'contexts' => ['route'],
      ],
    ];

    // Show warning only on administration pages.
    if ($this->adminContext->isAdminRoute()) {
      $link_to_help_page = $this->moduleHandler->moduleExists('help') && $this->currentUser->hasPermission('access help pages');
      $items['experimental-profile-warning']['#type'] = 'toolbar_item';
      $items['experimental-profile-warning']['tab'] = [
        '#type' => 'inline_template',
        '#template' => '<a class="toolbar-warning" href="{{ more_info_link }}">This site is intended for demonstration purposes.</a>',
        '#context' => [
          // Link directly to the drupal.org documentation if the help pages
          // aren't available.
          'more_info_link' => $link_to_help_page ? Url::fromRoute('help.page', ['name' => 'demo_umami'])
            : 'https://www.drupal.org/node/2941833',
        ],
        '#attached' => [
          'library' => ['demo_umami/toolbar-warning'],
        ],
      ];
    }
    return $items;
  }

  /**
   * Sets the password of admin to be the password for all users.
   */
  public function setUserPasswords(#[\SensitiveParameter] $admin_password): void {
    $user_storage = $this->entityTypeManager->getStorage('user');
    // Collect the IDs of all users with roles editor or author.
    $ids = $user_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('roles', ['author', 'editor'], 'IN')
      ->execute();

    $users = $user_storage->loadMultiple($ids);

    foreach ($users as $user) {
      $user->setPassword($admin_password);
      $user->save();
    }
  }

  /**
   * Implements hook_navigation_content_top().
   */
  #[Hook('navigation_content_top')]
  public function navigationContentTop(): array {
    // Add a warning about using an experimental profile.
    // @todo This can be removed once a generic warning for experimental
    //   profiles has been introduced.
    //   https://www.drupal.org/project/drupal/issues/2934374
    $build = [
      'experimental-profile-warning' => [
        '#weight' => 3400,
        '#cache' => [
          'contexts' => [
            'route',
            'user.permissions',
          ],
        ],
      ],
    ];

    // Show warning only on administration pages.
    if (!$this->adminContext->isAdminRoute()) {
      return $build;
    }

    $link_to_help_page = $this->moduleHandler->moduleExists('help') && $this->currentUser->hasPermission('access help pages');
    $url = $link_to_help_page ? Url::fromRoute('help.page', ['name' => 'demo_umami'])->toString()
      : 'https://www.drupal.org/node/2941833';

    $build['experimental-profile-warning'] += [
      '#theme' => 'navigation__messages',
      '#message_list' => [
        [
          '#theme' => 'navigation__message',
          '#content' => [
            '#markup' => $this->t('This site is intended for demonstration purposes.'),
          ],
          '#url' => $url,
          '#type' => 'warning',
        ],
      ],
    ];

    return $build;
  }

}
