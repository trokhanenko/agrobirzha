<?php

namespace Drupal\social_connect;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;
use Drupal\externalauth\Authmap;
use Drupal\externalauth\ExternalAuth;

//use Drupal\Core\Language\Language;

class ProcessUser {

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
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;
  protected $externalAuth;
  protected $authmap;

  /**
   * Constructs a \Drupal\social_connect\ProcessUser object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager service.
   */
  public function __construct(ConfigFactoryInterface $config, EntityTypeManagerInterface $entity_type_manager, Authmap $authmap, ExternalAuth $external_auth) {
    $this->config = $config->get('social_connect.settings')->get('configurations');
    $this->entityManager = $entity_type_manager;
    $this->externalAuth = $external_auth;
    $this->authmap = $authmap;
    $this->globalConfig = $this->config['global'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $config = $container->get('config.factory');
    $entity_type_manager = $container->get('entity_type.manager');
    $authmap = $container->get('externalauth.authmap');
//    $authmap = $container->get('externalauth.authmap');
    $externalauth = $container->get('externalauth.externalauth');
    return new static($config, $entity_type_manager, $authmap, $externalauth);
  }

  /**
   * Helper function.
   * Create new user.
   */
  public function createUser($name, $email) {
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
  public function updateUser($account, $user_info) {
    foreach ($user_info as $field_name => $field_value) {
      if (!empty($field_value)) {
        try {
          $account->set($field_name, $field_value);
        }
        catch (Exception $ex) {
          return FALSE;
        }
      }
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
    try {
      $account->save();
    }
    catch (Exception $ex) {
      return FALSE;
    }

    return $account;
  }

  public function externalAuthLoginRegister($source, $account) {
    // Check if authmap exist. If not - create it.
    $authmaps = $this->authmap->get($account->id(), 'social_' . $source);
    if (!$authmaps) {
      $this->authmap->save($account, 'social_' . $source, $account->getAccountName());
    }
    // Login or Register user.
    $this->externalAuth->loginRegister($account->getAccountName(), 'social_' . $source);
  }

}
