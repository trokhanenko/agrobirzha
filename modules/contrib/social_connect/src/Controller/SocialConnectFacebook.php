<?php

namespace Drupal\social_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_connect\ProcessUser;
use Drupal\Core\Entity\EntityFieldManagerInterface;

class SocialConnectFacebook extends ControllerBase {

  /**
   * A config object for the social_connect configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A config object for the social_connect global configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $globalConfig;

  /**
   * A config object for the social_connect connection configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $connectionConfig;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * @var \Drupal\social_connect\ProcessUser
   */
  protected $processUser;

  /**
   * Constructs a \Drupal\social_connect\SocialConnectFacebook object.
   * 
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager service.
   * @param \Drupal\social_connect\ProcessUser $process_user
   *   The process user service.
   */
  public function __construct(ConfigFactoryInterface $config, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager, ProcessUser $process_user) {
    $this->config = $config->get('social_connect.settings')->get('configurations');
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
    $this->processUser = $process_user;
    $this->globalConfig = $this->config['global'];
    $this->connectionConfig = $this->config['connections']['facebook'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $config = $container->get('config.factory');
    $entity_type_manager = $container->get('entity_type.manager');
    $entity_field_manager = $container->get('entity_field.manager');
    $process_user = $container->get('social_connect.manager');
    return new static($config, $entity_type_manager, $entity_field_manager, $process_user);
  }

  /**
   * 
   * @param Request $request
   * @return JsonResponse
   */
  public function handle(Request $request) {
    // Try to get current config.
    if (empty($this->config)) {
      $response = [
        'message' => $this->t('Config is not set.'),
      ];
      return new JsonResponse($response, 500);
    }
    // validate connection.
    $source = $request->get('source');
    if (!in_array($source, ['facebook', 'google'])) {
      $response = [
        'message' => $this->t('Field type can either be facebook/google.'),
      ];
      return new JsonResponse($response, 500);
    }

    // check if access token exists.
    $access_token = $request->get('access_token');
    if (empty($access_token)) {
      $response = [
        'message' => $this->t('Field access_token is required.'),
      ];
      return new JsonResponse($response, 500);
    }

    $user_data = (object) [];
    switch ($source) {
      case "facebook":
        $query = [
          'access_token' => $access_token,
          'fields' => implode(',', [
            'id',
            'email',
            'languages',
            'first_name',
            'middle_name',
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
            'about',
            'verified',
          ])
        ];

        $graph_url = Url::fromUri('https://graph.facebook.com/me', ['query' => $query])->toString();

        $user_info = [];
        try {
          $client = new Client();
          $graph_response = $client->get($graph_url);
          $graph_result = $graph_response->getBody()->getContents();
          $user_info = Json::decode($graph_result);
        }
        catch (RequestException $ex) {
          \Drupal::logger('social_connect')->error('Faild to get user info from facebook.');
        }

        if (isset($user_info['error'])) {
          $response = [
            'message' => $this->t('Access token provided is invalid or something went wrong while fetching data from facebook.'),
          ];
          return new JsonResponse($response, 500);
        }

        // Clear userinfo from not valid keys.
        foreach ($user_info as $key => $value) {
          if (empty($value)) {
            unset($user_info[$key]);
          }
        }

        if (!isset($user_info['email'])) {
          $response = [
            'message' => $this->t('Can\'t login with @source account (no email presented in response.)', ['@source' => $source]),
          ];
        }

        if (isset($user_info['id']) && !empty($user_info['id'])) {
          $id = $user_info['id'];
          $user_info['profilepicture'] = 'http://graph.facebook.com/' . $id . '/picture?type=' . $this->connectionConfig['picture_size'];
        }

        $username = $user_info['email'];

        // Before creating new user we need to check if username already exists.
        $account = user_load_by_mail($username);
        if (!$account) {
          $account = $this->processUser->createUser($username, $user_info['email']);
          if (!$account) {
            $response = [
              'message' => $this->t('Error saving user account.'),
            ];
            return new JsonResponse($response, 403);
          }
        }

        $mapping = $this->connectionConfig['field_maps'];
        $edit = [];
        foreach ($mapping as $map) {
          if ($map['override'] || $account->$map['profile_field']->isEmpty()) {
            $field = $map['profile_field'];
            if ($field !== 'user_picture') {
              $value = $user_info[$map['source_field']];
              $edit[$field] = $value;
            }
          }
        }

        if (isset($user_info['profilepicture'])) {
          if ($this->globalConfig['picture_override'] || !isset($account->user_picture) || $account->user_picture->isEmpty()) {
            $profile_fields = $this->fieldManager->getFieldDefinitions('user', 'user');
            $picture_field = $profile_fields['user_picture'];
            $uri_scheme = $picture_field->getSetting('uri_scheme');
            $file_directory = $picture_field->getSetting('file_directory');
            $uri = $uri_scheme . '://' . $file_directory;
            $destination_dir = \Drupal::token()->replace($uri);
            file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
            $picture = file_get_contents($user_info['profilepicture']);
            if ($file = file_save_data($picture, $destination_dir . '/' . $user_info['id'] . '-' . REQUEST_TIME . '.jpeg', FILE_EXISTS_REPLACE)) {
              $fid = $file->id();
              $edit['user_picture'] = ['target_id' => $fid];
            }
          }
        }
        if (isset($user_info['timezone'])) {
          $offset = $user_info['timezone'] * 3600;
          // Gets daylight savings.
          $dst = date("I");
          $timezone = timezone_name_from_abbr("", $offset, $dst);
          $edit['timezone'] = $timezone;
        }

        if (!empty($edit)) {
          $update = $this->processUser->updateUser($account, $edit);
          if ($update === FALSE) {
            $response = [
              'message' => $this->t('Error saving user account.'),
            ];
            return new JsonResponse($response, 403);
          }
        }

        $this->processUser->externalAuthLoginRegister($source, $account);

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

}
