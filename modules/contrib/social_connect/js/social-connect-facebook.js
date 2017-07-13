(function ($, window, Drupal) {
  //'use strict';

  Drupal.behaviors.social_connect = {
    attach: function (context, settings) {
      //common loader
      $('.social-connect .item').click(function () {
        $(this).parents('.forms').siblings('.processing').removeClass('hide').addClass('show');
      });
      var socialSettings = settings.social_connect;
      // FB LOGIN
      FB.init({
        appId: socialSettings.facebook_app_id,
        version: 'v2.8',
        status: true,
        cookie: true,
        xfbml: true,
        oauth: true
      });
      $('.facebook-connect').click(function () {
        FB.getLoginStatus(function (response) {
          FB.login(function (response) {
            if (response.authResponse) {
              console.log('Welcome!  Fetching your information.... ');
              FB.api('/me?fields=email,name,first_name,last_name', function (data) {
                console.log('Good to see you, ' + data.name + '.');
                $.ajax({
                  type: "POST",
                  url: "/social-connect/facebook/handle",
                  data: data,
                  dataType: "json",
                  cache: false,
                  success: function (result) {
                    console.log('Facebook login success.');
                    FB.logout(function (response) {
                      console.log('Facebook logout success.');
                    });
                    location.reload();
                  },
                  error: function () {
                    console.log('Facebook login submit error.');
                  },
                });
              });
            } else {
              console.log('User cancelled login or did not fully authorize.');
              $('.forms').siblings('.processing').removeClass('show').addClass('hide');
              $(this).parents('.forms').siblings('.processing').removeClass('show').addClass('hide');
            }
          }, {
            scope: 'email'
          });
        });
      });
      // FB LOGIN END

    }
  };
})(jQuery, window, Drupal);

// Client ID: 474186406648-fjs2beug4dm00tmb8pmdiodsc5kl0pl6.apps.googleusercontent.com
// Client Secret: WcxX3yCo3SI7E9xQTkEpNSLa