<?php

namespace Drupal\social_connect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Social connect configuration form.
 */
class AdminSettings extends ConfigFormBase {

  /**
   * @var Social global settings
   */
  private $globalSettings;

  /**
   * @var Social connect facebook settings
   */
  private $FbSettings;

  /**
   * @var Social connect google plus settings
   */
  private $gPlusSettings;

  /**
   * Determines the ID of a form.
   */
  public function getFormId() {
    return 'social_connect_admin_settings';
  }

  /**
   * Gets the configuration names that will be editable.
   */
  public function getEditableConfigNames() {
    return [
      'social_connect.settings',
    ];
  }

  /**
   * Form constructor.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $configs = $this->config('social_connect.settings')->get('configurations');
    $this->globalSettings = $configs['global'];
    $this->FbSettings = $configs['connections']['facebook'];
    $this->gPlusSettings = $configs['connections']['gplus'];

    // global settings
    $form['global_settings_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Global settings'),
      '#open' => FALSE,
    ];

    $form['global_settings_fieldset']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#description' => $this->t('If checked - the app will debug and print debug message in console (Advised to turn it off on production environment).'),
      '#default_value' => $this->globalSettings['debug'],
    ];

    // Login page settings.
    $form['global_settings_fieldset']['login_page_settings_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Login Page Settings'),
    ];

    $form['global_settings_fieldset']['login_page_settings_fieldset']['login_page_icons'] = [
      '#type' => 'select',
      '#title' => $this->t('Social connect icons'),
      '#description' => $this->t('Allows the users to login either with their social network account or with their already existing account.'),
      '#options' => [
        'above' => $this->t('Show the icon(s) above the existing login form (Default, recommended)'),
        'below' => $this->t('Show the icon(s) below the existing login form'),
        'disable' => $this->t('Do not show the icons on the login page'),
      ],
      '#default_value' => $this->globalSettings['login_page_icons'],
    ];

    $form['global_settings_fieldset']['login_page_settings_fieldset']['login_page_above_caption'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Above caption [Leave empty for none]'),
      '#default_value' => $this->globalSettings['login_page_above_caption'],
      '#description' => $this->t('This will be displayed above the social network icons. (You can put with HTML tags)'),
    ];

    $form['global_settings_fieldset']['login_page_settings_fieldset']['login_page_below_caption'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Below caption [Leave empty for none]'),
      '#default_value' => $this->globalSettings['login_page_below_caption'],
      '#description' => $this->t('This is the title displayed below the social network icons. (You can put with HTML tags)'),
    ];

    // Registration page settings.
    $form['global_settings_fieldset']['registration_page_settings_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Registration Page Settings'),
    ];

    $form['global_settings_fieldset']['registration_page_settings_fieldset']['registration_page_icons'] = [
      '#type' => 'select',
      '#title' => $this->t('Social Login Icons'),
      '#description' => $this->t('Allows the users to register by using either their social network account or by creating a new account.'),
      '#options' => [
        'above' => $this->t('Show the icons above the existing registration form (Default, recommended)'),
        'below' => $this->t('Show the icons below the existing registration form'),
        'disable' => $this->t('Do not show the icons on the registration page'),
      ],
      '#default_value' => $this->globalSettings['registration_page_icons'],
    ];

    $form['global_settings_fieldset']['registration_page_settings_fieldset']['registration_page_above_caption'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Above caption [Leave empty for none]'),
      '#default_value' => $this->globalSettings['registration_page_above_caption'],
      '#description' => $this->t('This is the title displayed above the social network icons. (You can put with HTML tags)'),
    ];

    $form['global_settings_fieldset']['registration_page_settings_fieldset']['registration_page_below_caption'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Below caption [Leave empty for none]'),
      '#default_value' => $this->globalSettings['registration_page_below_caption'],
      '#description' => $this->t('This is the title displayed below the social network icons. (You can put with HTML tags)'),
    ];

    $form['global_settings_fieldset']['picture_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override profile picture'),
      '#description' => $this->t('If checked - user profile picture will we overriden with Social profile picture.'),
      '#default_value' => $this->globalSettings['picture_override'],
    ];
    // Facebook settings
    $this->getFacebookSettings($form, $form_state);
    // Google plus settings
    $this->getGooglePlusSettings($form, $form_state);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configurations'),
    ];
    return $form;
  }

  /*
   * Facebook settings.
   */

  private function getFacebookSettings(array &$form, FormStateInterface $form_state) {
    $form['facebook_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Facebook connect'),
      '#default_value' => $this->FbSettings['enable'],
    ];

    $form['facebook_settings_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Facebook settings'),
      '#open' => FALSE,
      '#description' => $this->t('Your Facebook application settings.<br />You need to get application ID and secret from <a target="_blank" href="https://developers.facebook.com/">Facebook website</a>.'),
      '#states' => [
        'visible' => [
          'input[name="facebook_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['facebook_settings_fieldset']['facebook_app_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook application ID'),
      '#default_value' => $this->FbSettings['access']['app_id'],
      '#states' => [
        'required' => [
          'input[name="facebook_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['facebook_settings_fieldset']['facebook_app_key_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook application secret'),
      '#default_value' => $this->FbSettings['access']['secret'],
      '#states' => [
        'required' => [
          'input[name="facebook_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['facebook_settings_fieldset']['facebook_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom login button text'),
      '#default_value' => $this->FbSettings['button_text'],
    ];

    $form['facebook_settings_fieldset']['facebook_picture_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Picture size'),
      '#default_value' => $this->FbSettings['picture_size'],
      '#options' => [
        'small' => $this->t('Small'),
        'normal' => $this->t('Normal'),
        'album' => $this->t('Album'),
        'large' => $this->t('Large'),
        'square' => $this->t('Square'),
      ],
    ];
  }

  /*
   * Google plus settings.
   */

  private function getGooglePlusSettings(array &$form, FormStateInterface $form_state) {
    $form['gplus_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Google Plus connect'),
      '#default_value' => $this->gPlusSettings['enable'],
    ];

    $form['gplus_settings_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Google plus settings'),
      '#open' => FALSE,
      '#description' => $this->t('Your Goole plus application settings.<br />You need to get client ID and secret from <a target="_blank" href="https://console.developers.google.com/">Google website</a>.'),
      '#states' => [
        'visible' => [
          'input[name="gplus_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['gplus_settings_fieldset']['gplus_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google client ID'),
      '#default_value' => $this->gPlusSettings['access']['client_id'],
      '#states' => [
        'required' => [
          'input[name="gplus_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['gplus_settings_fieldset']['gplus_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Goole client secret'),
      '#default_value' => $this->gPlusSettings['access']['client_secret'],
      '#states' => [
        'required' => [
          'input[name="gplus_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['gplus_settings_fieldset']['gplus_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom login button text'),
      '#default_value' => $this->gPlusSettings['button_text'],
    ];

    $form['gplus_settings_fieldset']['gplus_picture_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Picture size'),
      '#default_value' => $this->gPlusSettings['picture_size'],
      '#options' => [
        'small' => $this->t('Small'),
        'normal' => $this->t('Normal'),
        'album' => $this->t('Album'),
        'large' => $this->t('Large'),
        'square' => $this->t('Square'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $configs = $this->settings;

    // global settings
    $configs['global'] = [
      'debug' => $values['debug'],
      'login_page_icons' => $values['login_page_icons'],
      'login_page_above_caption' => $values['login_page_above_caption'],
      'login_page_below_caption' => $values['login_page_below_caption'],
      'registration_page_icons' => $values['registration_page_icons'],
      'registration_page_above_caption' => $values['registration_page_above_caption'],
      'registration_page_below_caption' => $values['registration_page_below_caption'],
      'picture_override' => $values['picture_override'],
    ];

    // connection settings
    $configs['connections'] = [
      'facebook' => [
        'enable' => $values['facebook_enable'],
        'access' => [
          'app_id' => $values['facebook_app_key'],
          'secret' => $values['facebook_app_key_secret'],
        ],
        'button_text' => $values['facebook_button_text'],
        'picture_size' => $values['facebook_picture_size'],
      ],
      'gplus' => [
        'enable' => $values['gplus_enable'],
        'access' => [
          'client_id' => $values['gplus_client_id'],
          'client_secret' => $values['gplus_client_secret'],
        ],
        'button_text' => $values['gplus_button_text'],
        'picture_size' => $values['gplus_picture_size'],
      ],
    ];


    $this->config('social_connect.settings')
        ->set('configurations', $configs)
        ->save();

    parent::submitForm($form, $form_state);
  }

}
