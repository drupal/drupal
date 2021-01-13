<?php

namespace Drupal\help_topics_twig_tester;

<<<<<<< 071b42e1ea21830eee33997671b2850ce844f52e
=======
use Drupal\help_topics_twig_tester\HelpTestTwigNodeVisitor;
use Twig\TwigFilter;
>>>>>>> Non-working initial patch from the issue
use Twig\Extension\AbstractExtension;

/**
 * Defines and registers Drupal Twig extensions for testing help topics.
 */
class HelpTestTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors() {
    return [
      new HelpTestTwigNodeVisitor(),
    ];
  }

}
