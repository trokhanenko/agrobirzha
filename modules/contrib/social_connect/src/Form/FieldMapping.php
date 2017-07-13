<?php

namespace Drupal\social_connect\Form;

//use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Social connect configuration form.
 */
class FieldMapping extends ConfigFormBase {

  private $settingsFB;
  private $settingsGlobal;

  /**
   * Determines the ID of a form.
   */
  public function getFormId() {
    return 'social_connect_admin_settings_field_mapping';
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
    // API Connection.
    $configs = $this->config('social_connect.settings')->get('configurations');
    $this->settingsGlobal = $configs['global'];
    $this->settingsFB = $configs['connections']['facebook'];

    $mappings = $this->settingsFB['field_maps'];

    $profile_fields = \Drupal::entityManager()->getFieldDefinitions('user', 'user');
    $form['maps'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Profile field'),
        $this->t('Source field'),
        $this->t('Override profile value?'),
      ],
      '#empty' => $this->t('There are currently no field in user profile.'),
    ];
    $options = $this->sourceFields();
    foreach ($profile_fields as $field_name => $field) {
      $lable = $field->getLabel();
      if (!is_object($lable) && $field_name != 'user_picture') {
        $form['maps'][$field_name]['profile_field'] = [
          '#tree' => FALSE,
          'data' => [
            'label' => [
              '#plain_text' => $lable,
            ],
          ],
        ];

        $form['maps'][$field_name]['source_field'] = [
          '#type' => 'select',
          '#empty_option' => $this->t('Select'),
          '#empty_value' => NULL,
          '#options' => $options,
          '#default_value' => $this->getMapValue('source_field', $field_name),
        ];

        $form['maps'][$field_name]['override'] = array(
          '#type' => 'checkbox',
          '#default_value' => $this->getMapValue('override', $field_name),
        );
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save field mapping'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $mappings = [];
    foreach ($values['maps'] as $profile_field => $mapping) {
      $mappings[] = [
        'profile_field' => $profile_field,
        'source_field' => $mapping['source_field'],
        'override' => $mapping['override'],
      ];
    }

    $configs = $this->config('social_connect.settings')->get('configurations');
    $configs['connections']['facebook']['field_maps'] = $mappings;

    $this->config('social_connect.settings')
        ->set('configurations', $configs)
        ->save();

    parent::submitForm($form, $form_state);
  }

  private function getMapValue($key, $field) {
    $mappings = $this->settingsFB['field_maps'];
    foreach ($mappings as $mapping) {
      if ($mapping['profile_field'] == $field) {
        return $mapping[$key];
      }
    }
    return NULL;
  }

  function sourceFields($connection = 'facebook') {
    // facebook fields.
    $fields = [
      'facebook' => [
        ['id', "username", "User ID (used for login)"],
        ['languages', "languages", "Languages array"],
        ['first_name', "first_name", "Firstname"],
        ['last_name', "last_name", "Lastname"],
        ['email', "email", "Email"],
        ['name', "name", "Fullname"],
        ['http://graph.facebook.com/<id>/picture?type=small', "profilepicturesmall", "Profilepicture (small)"],
        ['http://graph.facebook.com/<id>/picture?type=normal', "profilepicturenormal", "Profilepicture (normal)"],
        ['http://graph.facebook.com/<id>/picture?type=large', "profilepicturelarge", "Profilepicture (large)"],
        ['link', "link", "Profile url"],
        ['locale', "locale", "Locale"],
        ['birthday', "birthday", "Birthdate"],
        ['bio', "bio", "Biography"],
        ['gender', "gender", "Gender (return values: 'M', 'F')"],
        ['hometown.name', "hometown", "Hometown"],
        ['location.name', "location", "Location"],
        ['work', "work", "Work array"],
        ['political', "political", "Political view"],
        ['religion', "religion", "Religion"],
        ['quotes', "quotes", "Favorite quotes"],
        ['website', "website", "Personal website"],
        ['sports', "sports", "Sports array"],
        ['favorite_athletes', "favorite_athletes", "Favorite athletes array"],
        ['favorite_teams', "favorite_teams", "Favorite teams array"],
        ['timezone', "timezone", "Timezone ID"],
      ],
    ];
    $options = [];
    foreach ($fields[$connection] as $field) {
      $options[$field[0]] = $field[2];
    }
    return $options;
  }

}
