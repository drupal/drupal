((Drupal, once) => {
  Drupal.behaviors.umamiHeader = {
    attach(context) {
      once(
        'umami-header',
        '[aria-controls="umami-header__dropdown"]',
        context,
      ).forEach((button) => {
        button.addEventListener('click', (e) => {
          button.setAttribute(
            'aria-expanded',
            e.currentTarget.getAttribute('aria-expanded') === 'false',
          );
        });
      });
    },
  };
})(Drupal, once);
