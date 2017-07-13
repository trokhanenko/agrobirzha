(function ($, Drupal, drupalSettings) {

  'use strict';
  Drupal.behaviors.social_connect = {
    attach: function (context, settings) {
      // social-connect div
      var $socialDiv = $('.social-connect');
      // to ensure call only once
      if (!$socialDiv.hasClass('initialized')) {
        // load js file
        $.getScript('https://apis.google.com/js/platform.js', function (data, textStatus, jqxhr) {
          // this is your callback.
        });
        // FB LOGIN
        var socialSettings = settings.social_connect;
        FB.init({
          appId: parseInt(socialSettings.facebook.app_id),
          version: 'v2.8',
          status: true,
          cookie: true,
          xfbml: true
        });
        $('.social-connect .facebook').click(function () {
          FB.login(function (response) {
            facebookLoginCallback(response);
          }, {scope: 'public_profile'});
        });
        var facebookLoginCallback = function (response) {
          if (response.authResponse) {
            var accessData = response.authResponse;
            // Logged into your app and Facebook.
            var data = {
              source: 'facebook',
              access_token: accessData.accessToken
            };
            debug(data.source, 'Welcome!  Fetching your information... ');
            fetchUserData(data);
          } else {
            debug(data.source, 'User cancelled login or did not fully authorize.');
          }
        };

        // facebook logout
        var facebookLogout = function () {
          FB.logout(function (response) {
            debug('facebook', 'logout success.');
          });
        };
        var fetchUserData = function (data) {
          $.ajax({
            type: "POST",
            url: "/social-connect/facebook/handle",
            data: data,
            dataType: "json",
            cache: false,
            success: function (result) {
              debug(data.source, 'login success.');
              console.log(result);
              $('.social-connect .messages').html(result.message);
              //facebookLogout();
              //location.reload();
            },
            error: function (error) {
              debug(data.source, 'login submit error.');
              console.log(error);
              $('.social-connect .messages').html(error.responseJSON.message);
//              facebookLogout();
            }
          });
        };
        // FB LOGIN END

        // G+ LOGIN
        var googleUser = {};
        var startApp = function () {
          gapi.load('auth2', function () {
            auth2 = gapi.auth2.init({
              client_id: socialSettings.gplus.client_id,
              cookiepolicy: 'single_host_origin',
            });
            attachSignin(document.getElementById('gplus-connect'));
          });
        };

        var attachSignin = function (element) {
          auth2.attachClickHandler(element, {}, function (googleUser) {
            console.log(googleUser);
//            var profile = googleUser.getBasicProfile();

          }, function (error) {
            // error 
          });
        }
        startApp();
        // G+ LOGIN END

        // This function is used to console message(s) for debuging
        var debug = function (source, message) {
          if (socialSettings.debug) {
            console.log('Social connet: ' + source + ': ' + message);
          }
        };
        // Adding class to social-connect div
        $socialDiv.addClass('initialized');
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
// Client ID: 474186406648-fjs2beug4dm00tmb8pmdiodsc5kl0pl6.apps.googleusercontent.com
// Client Secret: WcxX3yCo3SI7E9xQTkEpNSLa