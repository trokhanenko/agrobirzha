(function ($) {

  Drupal.behaviors.add_class_to_search_form = {
    attach: function(context, settings) {
      $('#edit-submit-customsearch').addClass("icon glyphicon glyphicon-search");
    }
  };

  Drupal.behaviors.mobile_menu = {
    attach: function(context, settings) {
      $('.sidebarBtn').click(function(){
        $('.sidebar').toggleClass('menu-active');
        $('.sidebarBtn').toggleClass('toggle');
      });
    }
  };

  Drupal.behaviors.switch_front_page = {
    attach: function(context, settings) {

      var table = $('.block-views-blockprice-partners-block-1');
      var grid = $('.block-views-blockprice-partners-block-2');

      $('.icon-grid-price').once().click(function(){
        grid.show();
        table.hide();
      });
      $('.icon-table-price').once().click(function(){
        table.show();
        grid.hide();
      });


      var table_posts = $('.block-views-blockposts-block-1');
      var grid_posts = $('.block-views-blockposts-block-2');

      $('.icon-grid-posts').once().click(function(){
        grid_posts.show();
        table_posts.hide();
      });
      $('.icon-table-posts').once().click(function(){
        table_posts.show();
        grid_posts.hide();
      });
    }
  };

  Drupal.behaviors.slider_front_page = {
    attach: function(context, settings) {
      $('.view-slide .view-content').once().slick({
        autoplay: true,
        autoplaySpeed: 2000,
        slidesToShow: 9,
        slidesToScroll: 2,
        prevArrow: '<img src="/themes/agrobirzha/images/left.png" class="slick-prev">',
        nextArrow: '<img src="/themes/agrobirzha/images/right.png" class="slick-next">',
        responsive: [
          {
            breakpoint: 768,
            settings: {
              arrows: false,
              slidesToShow: 5
            }
          },
        ]
      });
    }
  };
    /*Drupal.behaviors.slider_product_page = {
    attach: function(context, settings) {
      $('.tovar-images .field--name-field-izobrazenie').once().slick({
        autoplay: true,
        autoplaySpeed: 2000,
        arrows: false,
      });
    }
  };*/
})(jQuery);
