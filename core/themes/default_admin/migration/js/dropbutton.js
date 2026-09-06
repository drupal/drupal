((Drupal, once) => {
  Drupal.behaviors.ginDropbutton = {
    attach: function (context) {
      once('ginDropbutton', '.dropbutton-multiple:has(.dropbutton--gin)', context).forEach(el => {
        el.querySelector('.dropbutton__toggle').addEventListener('click', () => {
          this.updatePosition(el);

          window.addEventListener('scroll', () => Drupal.debounce(this.updatePositionIfOpen(el), 100));
        window.addEventListener('resize', () => Drupal.debounce(this.updatePositionIfOpen(el), 100));
        });
      });
    },

    updatePosition: function (el) {
      const preferredDir = document.documentElement.dir ?? 'ltr';
      const secondaryAction = el.querySelector('.secondary-action');
      const dropMenu = el.querySelector('.dropbutton__items');
      const toggleHeight = el.offsetHeight;
      const dropMenuWidth = dropMenu.offsetWidth;
      const dropMenuHeight = dropMenu.offsetHeight;
      const boundingRect = secondaryAction.getBoundingClientRect();
      const spaceBelow = window.innerHeight - boundingRect.bottom;
      const spaceLeft = boundingRect.left;
      const spaceRight = window.innerWidth - boundingRect.right;

      dropMenu.style.position = 'fixed';

      // Calculate the menu position based on available space and the preferred
      // reading direction.
      const leftAlignStyles = {
        left: `${boundingRect.left}px`,
        right: 'auto'
      };
      const rightAlignStyles = {
        left: 'auto',
        right: `${window.innerWidth - boundingRect.right}px`
      };

      if ('ltr' === preferredDir) {
        if (spaceRight >= dropMenuWidth) {
          Object.assign(dropMenu.style, leftAlignStyles);
        } else {
          Object.assign(dropMenu.style, rightAlignStyles);
        }
      } else {
        if (spaceLeft >= dropMenuWidth) {
          Object.assign(dropMenu.style, rightAlignStyles);
        } else {
          Object.assign(dropMenu.style, leftAlignStyles);
        }
      }

      if (spaceBelow >= dropMenuHeight) {
        dropMenu.style.top = `${boundingRect.bottom}px`;
      } else {
        dropMenu.style.top = `${boundingRect.top - toggleHeight - dropMenuHeight}px`
      }

    },

    updatePositionIfOpen: function (el) {
      if(el.classList.contains('open')) {
        this.updatePosition(el);
      }
    },

  };

})(Drupal, once);
