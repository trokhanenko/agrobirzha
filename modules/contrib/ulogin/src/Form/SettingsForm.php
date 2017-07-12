<?php

namespace Drupal\ulogin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\Tableselect;
use Drupal\Core\Url;
use Drupal\ulogin\UloginHelper;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ulogin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ulogin.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ulogin.settings')->getRawData();
    $form = array();

    $form['vtabs'] = array(
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-fset-display',
      '#attached' => array(
        'library' => ['ulogin/admin'],
      ),
    );

    $form['fset_display'] = array(
      '#type' => 'details',
      '#title' => t('Widget settings'),
      '#group' => 'vtabs'

    );
    $form['fset_display']['ulogin_widget_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Widget title'),
      '#default_value' => isset($config['widget_title']) ? $config['widget_title'] : '',
    );
    $form['fset_display']['ulogin_widget_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Widget ID'),
      '#description' => t('Enter uLogin ID of the widget if you have configured one on the uLogin site.'),
      '#default_value' => isset($config['widget_id']) ? $config['widget_id'] : '',
    );
    $form['fset_display']['ulogin_display'] = array(
      '#type' => 'radios',
      '#title' => t('Widget type'),
      '#description' => t('Select uLogin widget type.'),
      '#options' => array(
        'small' => t('Small icons'),
        'panel' => t('Big icons'),
        'window' => t('Popup window'),
        'buttons' => t('Custom icons'),
      ),
      '#default_value' => isset($config['display']) ? $config['display'] : 'panel',
    );
    $form['fset_display']['ulogin_icons_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Custom icons path'),
      '#description' => t('Custom icons path relative to Drupal root directory. See @link for details.',
        array(
          '@link' => Link::fromTextAndUrl('custom buttons page', Url::fromUri('http://ulogin.ru/custom_buttons.html', array('attributes' => array('target' => '_blank'))))
            ->toString()
        )),
      '#default_value' => isset($config['icons_path']) ? $config['icons_path'] : '',
      '#states' => array(
        'visible' => array(
          ':input[name="ulogin_display"]' => array('value' => 'buttons'),
        ),
      ),
    );

    $form['fset_display']['ulogin_widget_weight'] = array(
      '#type' => 'weight',
      '#title' => t('Widget weight'),
      '#description' => t('Determines the order of the elements on the form - heavier elements get positioned later.'),
      '#default_value' => isset($config['widget_weight']) ? $config['widget_weight'] : -100,
      '#delta' => 100,
    );

    $form['fset_providers'] = array(
      '#type' => 'details',
      '#title' => t('Authentication providers'),
      '#group' => 'vtabs'
    );

    $header = array(
      'name' => t('Name'),
    );
    $options = array();
    $providers = UloginHelper::providers_list();
    $enabled_providers = array(
      'vkontakte',
      'odnoklassniki',
      'mailru',
      'facebook',
      'twitter',
      'google',
      'yandex',
      'livejournal',
      'openid'
    );
    $enabled_providers = array_filter(isset($config['providers_enabled']) ? $config['providers_enabled'] : array_combine($enabled_providers, $enabled_providers));
    $main_providers = array('vkontakte', 'odnoklassniki', 'mailru', 'facebook');
    $main_providers = array_filter(isset($config['providers_main']) ? $config['providers_main'] : array_combine($main_providers, $main_providers));
    $main_providers = array_intersect_assoc($main_providers, $enabled_providers);
    $form['fset_providers']['ulogin_providers'] = array();
    foreach (array_keys($main_providers + $enabled_providers + $providers) as $provider_id) {
      $options[$provider_id] = array(
        'name' => $providers[$provider_id],
        '#attributes' => array('class' => array('draggable')),
      );
      $form['fset_providers']['ulogin_providers']['ulogin_provider_' . $provider_id . '_main'] = array(
        '#tree' => FALSE,
        '#type' => 'checkbox',
        '#default_value' => in_array($provider_id, $main_providers) && in_array($provider_id, $enabled_providers),
        '#states' => array(
          'disabled' => array(
            ':input[name="ulogin_providers[' . $provider_id . ']"]' => array('checked' => FALSE),
          ),
        ),
      );
    }

    $form['fset_providers']['ulogin_providers'] += array(
      '#type' => 'tableselect',
      '#title' => t('Providers'),
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $enabled_providers,
      '#pre_render' => [
        ['Drupal\ulogin\Form\SettingsForm', 'preRenderProviders'],
      ],
    );

    $form['fset_fields'] = array(
      '#type' => 'details',
      '#title' => t('Fields to request'),
      '#group' => 'vtabs'
    );

    $header = array(
      'name' => t('Name'),
    );
    $options = array();
    $fields = UloginHelper::fields_list();
    $required_fields = array(
      'first_name',
      'last_name',
      'email',
      'nickname',
      'bdate',
      'sex',
      'photo',
      'photo_big',
      'country',
      'city'
    );
    $required_fields = array_filter(isset($config['fields_required']) ? $config['fields_required'] : array_combine($required_fields, $required_fields));
    $optional_fields = array_filter(isset($config['fields_optional']) ? $config['fields_optional'] : array_combine(array('phone'), array('phone')));
    $form['fset_fields']['ulogin_fields'] = array();
    foreach (array_keys($fields) as $field_id) {
      $options[$field_id] = array(
        'name' => $fields[$field_id],
        '#attributes' => array('class' => array('draggable')),
      );
      $form['fset_fields']['ulogin_fields']['ulogin_field_' . $field_id . '_required'] = array(
        '#tree' => FALSE,
        '#type' => 'checkbox',
        '#default_value' => in_array($field_id, $required_fields),
        '#states' => array(
          'disabled' => array(
            ':input[name="ulogin_fields[' . $field_id . ']"]' => array('checked' => FALSE),
          ),
        ),
      );
    }
    $form['fset_fields']['ulogin_fields'] += array(
      '#type' => 'tableselect',
      '#title' => t('Fields'),
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $required_fields + $optional_fields,
      '#pre_render' => [
        ['Drupal\ulogin\Form\SettingsForm', 'preRenderFields'],
      ],
    );

    $form['fset_account'] = array(
      '#type' => 'details',
      '#title' => t('Account settings'),
      '#group' => 'vtabs'
    );
    $form['fset_account']['ulogin_disable_username_change'] = array(
      '#type' => 'checkbox',
      '#title' => t('Disable username change'),
      '#description' => t('Remove username field from user account edit form for users created by uLogin.'
        . ' If this is unchecked then users should also have "Change own username" permission to actually be able to change the username.'),
      '#default_value' => isset($config['disable_username_change']) ? $config['disable_username_change'] : 1,
    );
    $form['fset_account']['ulogin_remove_password_fields'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remove password fields'),
      '#description' => t('Remove password fields from user account edit form for users created by uLogin.'),
      '#default_value' => isset($config['remove_password_fields']) ? $config['remove_password_fields'] : 1,
    );
    $form['fset_account']['ulogin_pictures'] = array(
      '#type' => 'checkbox',
      '#title' => t('Save uLogin provided picture as user picture'),
      '#description' => t('Save pictures provided by uLogin as user pictures. Check the "Enable user pictures" option at <a href="@link">Account settings</a> to make this option available.',
        array(
          '@link' => Url::fromUri('internal:/admin/config/people/accounts')
            ->toString()
        )),
      '#default_value' => isset($config['pictures']) ? $config['pictures'] : 1,
//      TODO TODO Need to port it for d8
//        '#disabled' => !variable_get('user_pictures', 0),
    );
    $form['fset_account']['ulogin_email_confirm'] = array(
      '#type' => 'checkbox',
      '#title' => t('Confirm emails'),
      '#description' => t('Confirm manually entered emails - if you require email address and authentication provider does not provide one. Install @link module to make this option available.',
        array(
          '@link' => Link::fromTextAndUrl(t('Email Change Confirmation'), Url::fromUri('http://drupal.org/project/email_confirm', array('attributes' => array('target' => '_blank'))))
            ->toString()
        )),
      '#default_value' => isset($config['email_confirm']) ? $config['email_confirm'] : 0,
      '#disabled' => !\Drupal::moduleHandler()->moduleExists('email_confirm'),
    );
    $form['fset_account']['ulogin_username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username pattern'),
      '#description' => t('Create username for new users using this pattern; counter will be added in case of username conflict.') . ' ' .
        t('Install @link module to get list of all available tokens.',
          array(
            '@link' => Link::fromTextAndUrl('Token', Url::fromUri('http://drupal.org/project/token', array('attributes' => array('target' => '_blank'))))
              ->toString()
          )) . ' ' .
        t('You should use only uLogin tokens here as the user is not created yet.'),
      '#default_value' => isset($config['username']) ? $config['username'] : '[user:ulogin:network]_[user:ulogin:uid]',
      '#required' => TRUE,
    );
    $form['fset_account']['ulogin_display_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Display name pattern'),
      '#description' => t('Leave empty to not alter display name. You can use any user tokens here.') . ' '
        . t('Install @link module to get list of all available tokens.',
          array(
            '@link' => Link::fromTextAndUrl('Token', Url::fromUri('http://drupal.org/project/token', array('attributes' => array('target' => '_blank'))))
              ->toString()
          )),
      '#default_value' => isset($config['display_name']) ? $config['display_name'] : '[user:ulogin:first_name] [user:ulogin:last_name]',
    );
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['fset_account']['fset_token'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => array('user'),
        '#global_types' => FALSE,
        '#click_insert' => TRUE,
        '#show_restricted' => FALSE,
        '#recursion_limit' => 3,
        '#text' => t('Browse available tokens'),
      );
    }

    $form['fset_account']['ulogin_override_realname'] = array(
      '#type' => 'checkbox',
      '#title' => t('Override Real name'),
      '#description' => t('Override <a href="@link1">Real name settings</a> using the above display name pattern for users created by uLogin. This option is available only if @link2 module is installed.',
        array(
          '@link1' => Url::fromUri('internal:/admin/config/people/realname')->toString(),
          '@link2' =>  Link::fromTextAndUrl(
            'Real name',
            Url::fromUri('http://drupal.org/project/realname', array('attributes' => array('target' => '_blank')))
          )->toString()
        )),
        '#default_value' => isset($config['override_realname']) ? $config['override_realname']:0,
        '#disabled' => !\Drupal::moduleHandler()->moduleExists('realname'),
    );

    $form['fset_other'] = array(
      '#type' => 'details',
      '#title' => t('Other settings'),
      '#group' => 'vtabs'
    );
    /*$form['fset_other']['ulogin_redirect'] = array(
      '#type' => 'checkbox',
      '#title' => t('Do not reload/redirect'),
      '#default_value' => variable_get('redirect', 0),
    );*/
    $form['fset_other']['ulogin_destination'] = array(
      '#type' => 'textfield',
      '#title' => t('Redirect after login'),
      '#default_value' => isset($config['destination']) ? $config['destination'] : '',
      '#description' => t('Drupal path to redirect to, like "node/1". Leave empty to return to the same page (set to [HTTP_REFERER] for widget in modal dialogs loaded by AJAX).'),
    );
    $form['fset_other']['ulogin_forms'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Drupal forms'),
      '#description' => t('Add default uLogin widget to these forms.'),
      '#options' => array(
        'user_login_form' => t('User login form'),
        'user_register_form' => t('User registration form'),
        'comment_comment_form' => t('Comment form'),
      ),
      '#default_value' => isset($config['forms']) ? $config['forms'] : array(
        'user_login_form'
      ),
    );
    $form['fset_other']['ulogin_duplicate_emails'] = array(
      '#type' => 'radios',
      '#title' => t('Duplicate emails'),
      '#description' => t('Select how to handle duplicate email addresses. This situation occurs when the same user is trying to authenticate using different authentication providers, but with the same email address.'),
      '#options' => array(
        0 => t('Allow duplicate email addresses'),
        1 => t("Don't allow duplicate email addresses, block registration and advise to log in using the existing account"),
      ),
      '#default_value' => isset($config['duplicate_emails']) ? $config['duplicate_emails'] : 1,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Pre-render callback for the providers tableselect.
   */
  public static function preRenderProviders($element) {
    $element['#header']['main'] = t('Main');
//       Add "main" column.
    foreach (array_keys($element['#options']) as $provider_id) {
      $key = 'ulogin_provider_' . $provider_id . '_main';
      $element['#options'][$provider_id]['main'] = \Drupal::service('renderer')
        ->render($element[$key]);
      unset($element[$key]);
    }
    $element = Tableselect::preRenderTableselect($element);
    $element['#pre_rendered'] = TRUE;
    // Assign id to the table.
    $table_id = 'ulogin-providers';
    $element['#attributes'] = array('id' => $table_id);
    drupal_attach_tabledrag($element, array(
      'table_id' => $table_id,
      'action' => 'order',
      'relationship' => 'sibling',
      'group' => 'ulogin-providers-weight',
      'hidden' => FALSE,
    ));
    return $element;
  }

  /**
   * Pre-render callback for the fields tableselect.
   */
  public static function preRenderFields($element) {
    $element['#header']['required'] = t('Required');
    foreach (array_keys($element['#options']) as $field_id) {
      $key = 'ulogin_field_' . $field_id . '_required';
      $element['#options'][$field_id]['required'] = array(
        'data' => \Drupal::service('renderer')->render($element[$key]),
      );
      unset($element[$key]);
    }
    $element = Tableselect::preRenderTableselect($element);
    $element['#pre_rendered'] = TRUE;
    return $element;
  }

  /**
   * Form validation handler for the ulogin admin settings form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $providers = UloginHelper::providers_list();
    $fields = UloginHelper::fields_list();

    // Process 'main' checkboxes and remove them.
    $providers_main_values = array();
    foreach (array_keys($providers) as $provider_id) {
      $providers_main_values[$provider_id] = $values['ulogin_provider_' . $provider_id . '_main'] ? $provider_id : '';
      unset($values['ulogin_provider_' . $provider_id . '_main']);
    }
    // Remove weights so they are not saved as variables.
    $providers_weights = array();
    foreach (array_keys($providers) as $provider_id) {
      $providers_weights[$provider_id] = $values['ulogin_provider_' . $provider_id . '_weight'];
      unset($values['ulogin_provider_' . $provider_id . '_weight']);
    }
    asort($providers_weights);

    $providers_enabled_values = $values['ulogin_providers'];
    unset($values['ulogin_providers']);

    $values['ulogin_providers_enabled'] = array();
    $values['ulogin_providers_main'] = array();
    foreach (array_keys($providers_weights) as $provider_id) {
      $values['ulogin_providers_enabled'][$provider_id] = $providers_enabled_values[$provider_id];
      $values['ulogin_providers_main'][$provider_id] = $providers_main_values[$provider_id];
    }

    // Process 'required' checkboxes and remove them.
    $fields_required_values = array();
    foreach (array_keys($fields) as $field_id) {
      $fields_required_values[$field_id] = $values['ulogin_field_' . $field_id . '_required'] ? $field_id : '';
      unset($values['ulogin_field_' . $field_id . '_required']);
    }

    $fields_enabled_values = $values['ulogin_fields'];
    unset($values['ulogin_fields']);

    $values['ulogin_fields_required'] = $fields_required_values;
    $values['ulogin_fields_optional'] = array_diff_assoc($fields_enabled_values, $fields_required_values);
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ulogin.settings');
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'ulogin_') !== FALSE) {
        $config->set(str_replace('ulogin_', '', $key), $value);
      }
    }
    $config->save();
    drupal_set_message(t('Configuration was saved.'));
  }

}
