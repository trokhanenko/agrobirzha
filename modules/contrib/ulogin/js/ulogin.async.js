/*global Drupal: false, jQuery: false */
/*jslint devel: true, browser: true, maxerr: 50, indent: 2 */
(function ($, Drupal, settings) {
    "use strict";

    Drupal.ulogin = Drupal.ulogin || {};
    Drupal.ulogin.initWidgets = function (context, settings) {
        $.each(Drupal.settings.ulogin, function (index, value) {
            $('#' + value + ':not(.ulogin-processed)', context).addClass('ulogin-processed').each(function () {
                uLogin.customInit(value);
            });
        });
    };

    Drupal.behaviors.ulogin_async = {};
    Drupal.behaviors.ulogin_async.attach = function (context, settings) {
        if (typeof uLogin != 'undefined') {
            Drupal.ulogin.initWidgets(context, settings);
        }
        else {
            $.ajax({
                url: '//ulogin.ru/js/ulogin.js',
                dataType: 'script',
                cache: true, // Otherwise will get fresh copy on every page load, this is why not $.getScript().
                success: function (data, textStatus, jqXHR) {
                    Drupal.ulogin.initWidgets(context, settings);
                }
            });
        }
    };

})(jQuery, Drupal, drupalSettings);
