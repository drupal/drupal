/**
 * @file
 * This file is used to add any javascript that is needed for the main menu.
 */

 (function () {
   const toggler = document.querySelector('[data-drupal-selector="menu-main-toggle"]');
   const menu = document.querySelector('[data-drupal-selector="menu-main"]');

   function toggleMenu() {
     toggler.classList.toggle('menu-main-toggle--active');
     menu.classList.toggle('menu-main--active');
     return false;
   }

   toggler.addEventListener('click', toggleMenu);
 }());
