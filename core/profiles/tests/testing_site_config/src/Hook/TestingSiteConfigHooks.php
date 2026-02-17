<?php

declare(strict_types=1);

namespace Drupal\testing_site_config\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Simulates a hook class with a dependency chain requiring the kernel.
 *
 * During an interactive site installation, if 'Check for updates automatically'
 * is checked, the update module will be installed in the submit handler of the
 * site configure form. After that, the user 1 entity is loaded to be populated
 * with values from the form. This leads to hook implementations (the user_load
 * hook in this instance) to be called. However, since the container was rebuilt
 * when the update module was installed, all the service properties in
 * SiteConfigureForm, such as entityTypeManager, need to re-populated from the
 * newly-built container, otherwise they will reference outdated objects and
 * cause exceptions.
 *
 * @see \Drupal\Core\Installer\Form\SiteConfigureForm::submitForm()
 */
class TestingSiteConfigHooks {

  public function __construct(
    #[Autowire(service: 'kernel')]
    protected $kernel,
  ) {}

  #[Hook('user_load')]
  public function userLoad(): void {
    assert(isset($this->kernel));
  }

}
