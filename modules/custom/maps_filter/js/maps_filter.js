(function ($) {

  /**
   * Set active class on Views AJAX filter
   * on selected category
   */
  Drupal.behaviors.exposedfilter_buttons = {
    attach: function(context, settings) {
      $('.filter-tab a').on('click', function(e) {
        e.preventDefault();

        // Get ID of clicked item
        var id = $(e.target).attr('id');

        // Set the new value in the SELECT element
        var filter = $('#views-exposed-form-karta-block-1 select[name="field_regions_target_id"]');
        filter.val(id);

        // Unset and then set the active class
        $('.filter-tab a').removeClass('active');
        $(e.target).addClass('active');

        // Do it! Trigger the select box
        //filter.trigger('change');
        $('#views-exposed-form-karta-block-1 select[name="field_regions_target_id"]').trigger('change');
        $('#views-exposed-form-karta-block-1 button.form-submit').trigger('click');

      });
    }
  };


  jQuery(document).ajaxComplete(function(event, xhr, settings) {

    var filter_id = $('#views-exposed-form-karta-block-1 select[name="field_regions_target_id"]').find(":selected").val();
    $('.filter-tab a').removeClass('active');
    $('.filter-tab').find('#' + filter_id).addClass('active');

  });

  Drupal.behaviors.read_more_buttons = {
    attach: function(context, settings) {
      var read_more = "<span class='read-more'>Еще...</span>";
      $('.pop-list').once().append(read_more);
      $('.pop-list li').slice( 8, 28 ).css("display", "none");
      $('.read-more').once().click(function(){
        $('.pop-list li').slice( 8, 28 ).css("display", "inline-block");
        $('.read-more').remove();
      });
    }
  };


})(jQuery);
