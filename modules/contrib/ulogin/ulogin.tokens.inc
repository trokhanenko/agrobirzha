<?php

/**
 * @file
 * Builds placeholder replacement tokens for ulogin-related data.
 */
use \Drupal\Component\Utility\Html;
use \Drupal\ulogin\UloginHelper;

/**
 * Implements hook_token_info().
 */
function ulogin_token_info() {
  $type = array(
    'name' => t('uLogin data'),
    'description' => t('Information about the user from the uLogin service.'),
    'needs-data' => 'user',
  );

  $tokens = [];
  $tokens['user']['ulogin'] = array(
    'name' => t('uLogin information'),
    'description' => t('Information from uLogin service.'),
    'type' => 'ulogin',
  );
  $tokens['ulogin'] = array(
    'network' => array(
      'name' => t('Authentication provider'),
      'description' => t('Network providing authentication.'),
    ),
    'uid' => array(
      'name' => t('UID'),
      'description' => t('UID') . ' ' . t('provided by uLogin.'),
    ),
    'identity' => array(
      'name' => t('Identity'),
      'description' => t('Identity') . ' ' . t('provided by uLogin.'),
    ),
    'photo_processed' => array(
      'name' => t('Checked photo'),
      'description' => t('Big photo by default. If big photo is not available - then photo.'),
    ),
  );

  foreach (UloginHelper::fields_list() as $key => $value) {
    $tokens['ulogin'][$key] = array(
      'name' => $value,
      'description' => $value . ' ' . t('provided by uLogin.'),
    );
  }

  return array(
    'types' => array('ulogin' => $type),
    'tokens' => array('ulogin' => $tokens['ulogin'], 'user' => $tokens['user']),
  );
}

/**
 * Implements hook_tokens().
 */
function ulogin_tokens($type, $tokens, array $data, array $options, Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $sanitize = !empty($options['sanitize']);
  $replacements = array();

  if ($type == 'user' && !empty($data['user'])) {
    if (is_object($data['user'])) {
      $ulogin = \Drupal::service('user.data')
        ->get('ulogin', $data['user']->id());
    }
    else {
      $ulogin = $data['user']['ulogin'];
    }
    if (!empty($ulogin)) {
      $ulogin_tokens = $token_service->findWithPrefix($tokens, 'ulogin');
      $replacements += $token_service->generate('ulogin', $ulogin_tokens, array('ulogin' => $ulogin), $options, $bubbleable_metadata);
    }
  }

  if ($type == 'ulogin' && !empty($data['ulogin'])) {
    foreach ($tokens as $name => $original) {
      if ($name == 'photo_processed') { //computed token
        $photo_url = '';
        if (isset($data['ulogin']['photo_big']) && $data['ulogin']['photo_big'] != 'http://ulogin.ru/img/photo_big.png') {
          $photo_url = $data['ulogin']['photo_big'];
        }
        elseif (isset($data['ulogin']['photo']) && $data['ulogin']['photo'] != 'http://ulogin.ru/img/photo.png') {
          $photo_url = $data['ulogin']['photo'];
        }
        $replacements[$original] = $sanitize ? Html::escape($photo_url) : $photo_url;
      }
      else {
        if (isset($data['ulogin'][$name])) {
          $replacements[$original] = $sanitize ? Html::escape($data['ulogin'][$name]) : $data['ulogin'][$name];
        }
        else {
          $replacements[$original] = '';
        }
      }
    }
  }

  return $replacements;
}