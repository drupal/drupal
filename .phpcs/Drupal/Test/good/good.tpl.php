<?php

/**
 * @file
 * Valid example template file.
 *
 * Alternative control structure style is allowed as well as curly brackets.
 */
?>
<div>
<?php if (TRUE): ?>
  <a href="<?php print $front_page; ?>" title="<?php print t('Home'); ?>" id="logo">
    <img src="<?php print $logo; ?>" alt="<?php print t('Home'); ?>" />
  </a>
<?php else: ?>
  <i>some text</i>
<?php endif; ?>
</div>
<div>
<?php if (TRUE) { ?>
  <a href="<?php print $front_page; ?>" title="<?php print t('Home'); ?>" id="logo">
    <img src="<?php print $logo; ?>" alt="<?php print t('Home'); ?>" />
  </a>
<?php } else { ?>
  <i>some text</i>
<?php } ?>
</div>
<br />
<?php print $foo; ?>
  <?php print l($app['icon'], $app['site_url'], array(
          'html' => TRUE,
          'attributes' => array('target' => '_blank'),
        )
  ) ?>
