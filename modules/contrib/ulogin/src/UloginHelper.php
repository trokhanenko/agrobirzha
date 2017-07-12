<?php

namespace Drupal\ulogin;

use Drupal\Core\Url;
use Drupal\user\Entity\User;

class UloginHelper {
  /**
   * Internal functions.
   */
  public static function user_is_blocked_by_uid($uid) {
    return \Drupal::database()->select('users_field_data', 'u')
      ->fields('u', array('name'))
      ->condition('uid', $uid)
      ->condition('status', 0)
      ->execute()
      ->fetchField();
  }

  /**
   * Internal functions.
   */
  public static function identity_save($data, $uid = NULL) {
    if (!$uid) {
      $uid = \Drupal::currentUser()->id();
    }
    \Drupal::database()->merge('ulogin_identity')
      ->key(array(
        'uid' => $uid,
        'network' => $data['network'],
        'ulogin_uid' => $data['uid']
      ))
      ->fields(array('data' => serialize($data)))
      ->execute();
  }

  /**
   * Internal functions.
   */
  public static function identity_load($data) {
    $result = \Drupal::database()->select('ulogin_identity', 'ul_id')
      ->fields('ul_id')
      ->condition('network', $data['network'])
      ->condition('ulogin_uid', $data['uid'])
      ->execute()
      ->fetchAssoc();
    return $result;
  }

  /**
   * Internal functions.
   */
  public static function identity_load_by_uid($uid) {
    $result = \Drupal::database()->select('ulogin_identity', 'ul_id')
      ->fields('ul_id')
      ->condition('uid', $uid)
      ->execute()
      ->fetchAllAssoc('id', \PDO::FETCH_ASSOC);
    return $result;
  }

  /**
   * Internal functions.
   */
  public static function identity_load_by_id($id) {
    $result = \Drupal::database()->select('ulogin_identity', 'ul_id')
      ->fields('ul_id')
      ->condition('id', $id)
      ->execute()
      ->fetchAssoc();
    return $result;
  }

  /**
   * Internal functions.
   */
  public static function identity_delete_by_uid($uid) {
    $result = \Drupal::database()->delete('ulogin_identity')
      ->condition('uid', $uid)
      ->execute();
    return $result;
  }

  /**
   * Internal functions.
   */
  public static function identity_delete_by_id($id) {
    $result = \Drupal::database()->delete('ulogin_identity')
      ->condition('id', $id)
      ->execute();
    return $result;
  }

  /**
   * Internal functions.
   */
  public static function user_save($data, $uid = NULL) {
    if ($uid) {
      $account = User::load($uid);
    }
    else {
      $account = \Drupal::currentUser();
    }

    UloginHelper::identity_save($data, $uid);

    $user_save_trigger = FALSE;
    //save user picture
    if (\Drupal::config('ulogin.settings')
        ->get('user_pictures') && \Drupal::config('ulogin.settings')
        ->get('pictures')
    ) {
      $photo_url = '';
      if (!empty($data['photo_big']) && $data['photo_big'] != 'http://ulogin.ru/img/photo_big.png') {
        $photo_url = $data['photo_big'];
      }
      elseif (!empty($data['photo']) && $data['photo'] != 'http://ulogin.ru/img/photo.png') {
        $photo_url = $data['photo'];
      }
      if ($photo_url) {
        $photo = \Drupal::httpClient()->get($photo_url);
        $file = file_save_data($photo->getBody()->getContents());
        $file->status = 0; //to make user_save() to process the file and move it
        $account->set('picture', $file);
        $user_save_trigger = TRUE;
      }
    }
    //email_confirm: if email was manually entered - trigger email change confirmation
    if (!empty($data['email']) && !empty($data['manual']) && in_array('email', explode(',', $data['manual'])) &&
      \Drupal::config('ulogin.settings')
        ->get('email_confirm') && \Drupal::moduleHandler()
        ->moduleExists('email_confirm')
    ) {
      $account->set('mail', $data['email']);
      $user_save_trigger = TRUE;
      if ($uid) {
        //backup original user
        $user_backup = \Drupal::currentUser();
        //replace user with fake one so that email_confirm module works without notices
        $user = $account;
      }
    }

    if ($user_save_trigger) {
      //hack to remove one notice from Legal module
      if (\Drupal::moduleHandler()->moduleExists('legal')) {
        $account->set('legal_accept', NULL);
      }
      $account->save();
    }

    //return original user back
    if (isset($user_backup)) {
      \Drupal::currentUser()->setAccount($user_backup);
    }
  }

  /**
   * Internal functions.
   */
  public static function make_username($data) {
    $pattern = \Drupal::config('ulogin.settings')
      ->get('username') ?: '[user:ulogin:network]_[user:ulogin:uid]';
    $ulogin_name = $desired_name = \Drupal::token()
      ->replace($pattern, array('user' => array('ulogin' => $data)), array(
        'clear' => TRUE,
        'sanitize' => FALSE
      ));
    $counter = 0;
    while (user_load_by_name($ulogin_name)) {
      $counter++;
      $ulogin_name = $desired_name . ' ' . $counter;
    }
    $name = $ulogin_name;

    \Drupal::moduleHandler()->alter('ulogin_username', $name, $data);

    // Check that the altered username is unique.
    if ($name == $ulogin_name || user_load_by_name($name)) {
      return $ulogin_name;
    }
    else {
      return $name;
    }
  }


  /**
   * Internal functions.
   */
  public static function providers_list() {
    return array(
      'vkontakte' => t('VKontakte'),
      'twitter' => t('Twitter'),
      'mailru' => t('Mail.ru'),
      'facebook' => t('Facebook'),
      'odnoklassniki' => t('Odnoklassniki'),
      'yandex' => t('Yandex'),
      'google' => t('Google'),
      'steam' => t('Steam'),
      'soundcloud' => t('SoundCloud'),
      'lastfm' => t('Last.fm'),
      'linkedin' => t('LinkedIn'),
      'liveid' => t('Live ID'),
      'flickr' => t('Flickr'),
      'uid' => t('uID'),
      'livejournal' => t('Live Journal'),
      'openid' => t('OpenID'),
      'webmoney' => t('WebMoney'),
      'youtube' => t('YouTube'),
      'foursquare' => t('foursquare'),
      'tumblr' => t('tumblr'),
      'googleplus' => t('Google+'),
      'dudu' => t('dudu'),
      'vimeo' => t('Vimeo'),
      'instagram' => t('Instagram'),
      'wargaming' => t('Wargaming.net'),
    );
  }

  /**
   * Internal functions.
   */
  public static function fields_list() {
    return array(
      'first_name' => t('First name'),
      'last_name' => t('Last name'),
      'email' => t('Email address'),
      'nickname' => t('Nickname'),
      'bdate' => t('Birthday'),
      'sex' => t('Gender'),
      'phone' => t('Phone number'),
      'photo' => t('Photo'),
      'photo_big' => t('Big photo'),
      'city' => t('City'),
      'country' => t('Country'),
    );
  }

  /**
   * Internal functions.
   */
  public static function token_url($destination = NULL) {
    if (empty($destination)) {
      $destination = \Drupal::service('redirect.destination')->getAsArray();
    }
    elseif ($destination == '[HTTP_REFERER]' && isset($_SERVER['HTTP_REFERER'])) {
      $destination = array('destination' => $_SERVER['HTTP_REFERER']);
    }
    else {
      $destination = array('destination' => $destination);
    }
    $token_url = Url::fromRoute('ulogin.callback', array(
      'absolute' => TRUE,
      'query' => $destination
    ))->toString();
    return urlencode($token_url);
  }

}