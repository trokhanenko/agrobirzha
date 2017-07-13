<?php

namespace Drupal\social_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;
use Drupal\Component\FileSystem\FileSystem;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\social_connect\ProcessUser;

class SocialConnectGplus extends ControllerBase {

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;
  protected $entityTypeManager;

  /**
   * @var \Drupal\social_connect\ProcessUser
   */
  protected $processUser;

  /**
   * 
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, ProcessUser $process_user) {
    $this->config = $config_factory->get('social_connect.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->processUser = $process_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $config_manager = $container->get('config.manager');
    $entity_type_manager = $container->get('entity_type.manager');
    $process_user = $container->get('social_connect.manager');
    return new static($config_manager, $entity_type_manager, $process_user);
  }

  /**
   * 
   * @param Request $request
   * @return JsonResponse
   */
  public function handle(Request $request) {
// Try to get current config.
    $configs = \Drupal::configFactory()->get('social_connect.settings')->get('configurations');

//    $settingsGlobal = $configs['global'];
    $settingsFB = $configs['connections']['facebook'];
    if (!$settingsFB) {
      $response = [
        'message' => $this->t('Config is not set.'),
      ];
      return new JsonResponse($response, 400);
    }
// validate connection.
    $source = $request->get('source');
    if (!in_array($source, ['facebook', 'google'])) {
      $response = [
        'message' => $this->t('Field type can either be facebook/google.'),
      ];
      return new JsonResponse($response, 400);
    }

// check if access token exists.
    $access_token = $request->get('access_token');
    if (empty($access_token)) {
      $response = [
        'message' => $this->t('Field access_token is required.'),
      ];
      return new JsonResponse($response, 400);
    }

    $user_data = (object) [];
    switch ($source) {
      case "facebook":
        $graph_fields = implode(',', [
          'id',
          'email',
          'languages',
          'first_name',
          'last_name',
          'name',
          'link',
          'locale',
          'birthday',
          'hometown',
          'location',
          'work',
          'political',
          'favorite_athletes',
          'favorite_teams',
          'quotes',
          'religion',
          'sports',
          'website',
          'timezone',
        ]);

        $graph_url = "https://graph.facebook.com/me?access_token=" . $access_token . '&fields=' . $graph_fields;
        $client = new Client();

        $graph_response = $client->get($graph_url, ['http_errors' => false]);
        $graph_result = $graph_response->getBody()->getContents();
        $user_info = Json::decode($graph_result);
        if (isset($user_info['error'])) {
          $response = [
            'message' => $this->t('Access token provided is invalid or something went wrong while fetching data from facebook.'),
          ];
          return new JsonResponse($response, 400);
        }

// Clear userinfo from not valid keys.
        foreach ($user_info as $key => $value) {
          if (empty($value)) {
            unset($user_info[$key]);
          }
        }

        if (!isset($user_info['email'])) {
          $response = [
            'message' => $this->t('Can\'t login with @source account (no email presented in response)', ['@source' => $source]),
          ];
        }

        $username = $user_info['email'];

// Before creating new user we need to check if username already exists.
        $account = user_load_by_mail($username);
        if (!$account) {
          $account = $this->socialConnectCreateUser($username, $user_info['email']);
        }
        $user_info['source'] = $source;

        $this->socialConnectUpdateUser($account, $user_info);
// Log user in.
        $form_state['uid'] = $account->uid;
        user_login_submit(array(), $form_state);

        $response = [
          'message' => $this->t('Logged in.'),
        ];
        return new JsonResponse($response);
        break;
      case "google":
        $url = "https://www.googleapis.com/oauth2/v3/userinfo?access_token=" . $access_token;
        $client = new Client();
        $res = $client->get($url, ['http_errors' => false])->getBody()->getContents();
        $social_info = (object) Json::decode($res);
        if (isset($social_info->error)) {
          $jsonResponse = [
            'status' => [
              'code' => 0,
              'message' => 'Access token provided is invalid.',
            ],
            'data' => NULL
          ];
          return new JsonResponse($jsonResponse, 200);
        }
        break;
    }
  }

  /**
   * Helper function.
   * Check is username exists.
   */
  function _social_connect_check_user($username) {
    if (!empty($username) && $uid = db_select('users')->fields('users', ['uid'])->condition('name', db_like($username), 'LIKE')->range(0, 1)->execute()->fetchField()) {
      return $uid;
    }
    return FALSE;
  }

  /**
   * Helper function.
   * Create new user.
   */
  function socialConnectCreateUser($name, $email) {
    $new_user = [
      'name' => $name,
      'pass' => user_password(),
      'init' => $name,
      'mail' => $email,
      'status' => 1,
      'access' => REQUEST_TIME,
    ];
    $account = User::create($new_user)->save();
    // Email notification.
    if (!isset($account->status)) {
      return FALSE;
    }

    if ($account->status) {
      _user_mail_notify('register_no_approval_required', $account);
    }
    else {
      _user_mail_notify('register_pending_approval', $account);
    }

    return $account;
  }

  /**
   * Helper function.
   * Update new user.
   */
  function socialConnectUpdateUser($account, $user_info) {
    $source = $user_info['source'];
    $configs = \Drupal::configFactory()->get('social_connect.settings')->get('configurations');
    $global_settings = $configs['global'];
    $source_settings = $configs['connections'][$source];
    $mapping = $source_settings['field_maps'];


    foreach ($mapping as $map) {
      if ($map['override'] || empty($account->$map['profile_field'])) {
        $account->set($map['profile_field'], $user_info[$map['source_field']]);
      }
    }

//    if (isset($user_info['profilepicture']) && $config[$source]['profile_picture']) {
    if ($settingsGlobal['picture_override'] || !isset($account->picture) || empty($account->picture)) {

//      if ($picture = file_get_contents('http://graph.facebook.com/' . $user_info['id'] . '/picture?type=large')) {
      $picture = file_get_contents('http://graph.facebook.com/' . $user_info['id'] . '/picture?type=large');

      $profile_fields = \Drupal::entityManager()->getFieldDefinitions('user', 'user');
      $picture_field = $profile_fields['user_picture'];
      $uri_scheme = $picture_field->getSetting('uri_scheme');
      $file_directory = $picture_field->getSetting('file_directory');
      $uri = $uri_scheme . '://' . $file_directory;
      $destination_dir = \Drupal::token()->replace($uri);

      file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
      $file = file_save_data($picture, $destination_dir . '/' . $user_info['id'] . '.jpeg', FILE_EXISTS_REPLACE);
      $fid = $file->id();
      $account->set('user_picture', ['target_id' => $fid]);
//      }
    }
//    }

    if (isset($user_info['timezone'])) {
      $offset = $user_info['timezone'] * 3600;
// Gets daylight savings.
      $dst = date("I");

      $timezone = timezone_name_from_abbr("", $offset, $dst);
      $account->set('timezone', $timezone);
    }

// If module "domain" enabled we will add current domain to current user.
//    if (module_exists('domain')) {
//      $current_domain = domain_get_domain();
//      if (isset($current_domain['domain_id'])) {
//        $domain_id = ($current_domain['domain_id'] == 0) ? "-1" : $current_domain['domain_id'];
//        if (!isset($account->domain_user[$domain_id])) {
//          $edit['domain_user'][$domain_id] = $domain_id;
//        }
//      }
//    }

    $account->save();

    return $account;
  }

}
