<?php

namespace Drupal\ulogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\externalauth\ExternalAuth;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\ulogin\UloginHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\user\Entity\User;

/**
 * Controller routines for user routes.
 */
class UloginController extends ControllerBase {
  public function uloginReport() {
    $providers = UloginHelper::providers_list();

    $header = array(t('Authentication provider'), t('Users count'));
    $rows = array();
    $query = \Drupal::database()->select('ulogin_identity', 'ul_id');
    $query->addField('ul_id', 'network', 'network');
    $query->addExpression('COUNT(ulogin_uid)', 'count');
    $query->groupBy('network');
    $results = $query->execute()
      ->fetchAllAssoc('network', \PDO::FETCH_ASSOC);
    foreach ($results as $result) {
      $rows[] = array(
        $providers[$result['network']],
        $result['count'],
      );
    }

    $build = array();

    $build['report'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    return $build;
  }

  public function uloginCallback() {
    $get_token = \Drupal::request()->query->get('token');;
    $post_token = \Drupal::request()->request->get('token');;
    if (!empty($post_token) || !empty($get_token)) {
      $token = !empty($post_token) ? $post_token : $get_token;
      $data_raw = \Drupal::httpClient()
        ->get('http://ulogin.ru/token.php?token=' . $token . '&host=' . $_SERVER['HTTP_HOST']);
      $data = $data_raw->getBody()->getContents();
      if (!empty($data_raw->getStatusCode() != 200)) {
        \Drupal::logger('ulogin')->warning($data);
        drupal_set_message($data, 'error');
        throw new AccessDeniedHttpException();
      }

      $data = json_decode($data, TRUE);
      //check for error
      if (!empty($data['error'])) {
        \Drupal::logger('ulogin')->warning($data['error']);
        drupal_set_message($data['error'], 'error');
        throw new AccessDeniedHttpException();
      }
      //validate that returned data contains 'network' and 'uid' keys
      if (empty($data['network']) || empty($data['uid'])) {
        \Drupal::logger('ulogin')
          ->warning('Empty data =>' . json_encode($data));
        drupal_set_message('something is wrong, try again later', 'error');
        throw new AccessDeniedHttpException();
      }
      //remove 'access_token' property
      unset($data['access_token']);
    }
    else {
      drupal_set_message('no token given', 'error');
      throw new AccessDeniedHttpException();
    }

    $user = \Drupal::currentUser();
    //user is already logged in, tries to add new identity
    if ($user->isAuthenticated()) {
      //identity is already registered
      if ($identity = UloginHelper::identity_load($data)) {
        //registered to this user

        if ($user->id() == $identity['uid']) {
          drupal_set_message(t('You have already registered this identity.'));
        }
        //registered to another user
        else {
          drupal_set_message(t('This identity is registered to another user.'), 'error');
        }
        return new RedirectResponse(Url::fromRoute('user.page')->toString());
      }
      //identity is not registered - register it to the logged in user
      else {
        UloginHelper::identity_save($data);
        drupal_set_message(t('New identity added.'));
        //invoke ulogin_identity_added rules event
        if (\Drupal::moduleHandler()->moduleExists('rules')) {
          rules_invoke_event('ulogin_identity_added', $user, $data);
        }
        return new RedirectResponse(Url::fromRoute('user.page')->toString());
      }
    }

    $vars = \Drupal::config('ulogin.settings')->getRawData();

    if ($identity = UloginHelper::identity_load($data)) {
      //check if user is blocked
      if (UloginHelper::user_is_blocked_by_uid($identity['uid'])) {
        drupal_set_message(t('Your account has not been activated or is blocked.'), 'error');
      }
      else {
        $user = User::load($identity['uid']);
        user_login_finalize($user);
      }
    }
    //handle duplicate email addresses
    elseif ((array_key_exists('duplicate_emails', $vars) ? $vars['duplicate_emails'] : 1) && !empty($data['email']) && $account = user_load_by_mail($data['email'])) {
      drupal_set_message(t('You are trying to login with email address of another user.'), 'error');
      $ulogin = \Drupal::service('user.data')->get('ulogin', $account->id());
      if (!empty($ulogin)) {
        $providers = UloginHelper::providers_list();
        drupal_set_message(t('If you are completely sure it is your email address, try to login through %network.',
          array('%network' => $providers[$ulogin['network']])), 'status');
      }
      else {
        drupal_set_message(t('If you are completely sure it is your email address, try to login using your username and password on this site. If you don\'t remember your password - <a href="@password">request new password</a>.',
          array('@password' => Url::fromRoute('user.password')->toString())));
      }
    }
    else {
      global $ulogin_data;
      $ulogin_data = $data;


      \Drupal::service('externalauth.externalauth')
        ->loginRegister(UloginHelper::make_username($data), 'ulogin');
      UloginHelper::user_save($data);
    }

    return new RedirectResponse(Url::fromRoute('user.page')->toString());
  }
}
