(function ($) {
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
})(jQuery);
