(function ($, Drupal, window, document) {
  Drupal.behaviors.myModuleBehavior = {
    attach: function (context, settings) {
      // Behavior вызывается несколько раз на странице, не забывайте использовать функцию .once().
      $('.view-companies .view-content').once().slick({
        slidesToShow: 3,
        slidesToScroll: 1,
      });

      // if (window.matchMedia('screen and (max-width: 767px)').matches) {
      //   $('#block-produkcia .menu').once().addClass('collapse');
      // }
      // $("#block-produkcia-menu").once().click(function() {
      //   if (window.matchMedia('screen and (max-width: 767px)').matches) {
      //     $('#block-produkcia .menu').collapse('toggle');
      //   }
      // });
    }
  };
})(jQuery, Drupal, this, this.document);
