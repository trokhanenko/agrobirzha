<?php

namespace Drupal\ulogin\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ulogin\UloginHelper;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserIdentityDelete extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ulogin_user_identity_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->question;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('ulogin.user', ['uid' => \Drupal::currentUser()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete it!');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Nevermind');
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = 0) {
    $form = array();
    $this->id = $id;
    $del_identity = UloginHelper::identity_load_by_id($id);
    $account = \Drupal::currentUser();
    if (!$del_identity || $account->id() != $del_identity['uid']) {
      drupal_set_message(t('You are trying to delete non-existing identity.'), 'error');
      throw new AccessDeniedHttpException();
    }
    $del_identity_data = unserialize($del_identity['data']);
    $this->question = t('Are you sure you want to detach the uLogin identity @identity from @user?',
      array(
        '@identity' => Link::fromTextAndUrl($del_identity_data['identity'], Url::fromUri($del_identity_data['identity'], array(
          'attributes' => array('target' => '_blank'),
          'external' => TRUE
        ))),
        '@user' => $account->getDisplayName()
      ));

    $form['#user'] = $account;
    $form['#del_identity_data'] = $del_identity_data;

    $ulogin = \Drupal::service('user.data')->get('ulogin', $account->id());
    if (!empty($ulogin) && $ulogin['network'] == $del_identity_data['network'] &&
      $ulogin['uid'] == $del_identity_data['uid']
    ) {
      $identities = UloginHelper::identity_load_by_uid($account->id());
      $providers = UloginHelper::providers_list();

      $options = array();
      $last_key = NULL;
      foreach ($identities as $key => $identity) {
        $data = unserialize($identity['data']);
        if ($key != $id) {
          $options[$key] = $providers[$identity['network']] . ' - ' . Link::fromTextAndUrl($data['identity'], Url::fromUri($data['identity'], array(
              'attributes' => array('target' => '_blank'),
              'external' => TRUE
            )))->toString();
        }
        $last_key = $key;
      }

      if (!empty($options)) {
        $form['explanation'] = array(
          '#markup' => t('This identity was used to create your account. Please choose another identity to replace it.'),
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        );
        $form['identity_choice'] = array(
          '#type' => 'radios',
          //'#title' => t('Identities'),
          '#options' => $options,
          '#default_value' => count($options) == 1 ? $last_key : NULL,
          '#required' => TRUE,
        );
      }
      else {
        $form['explanation'] = array(
          '#markup' => t('This identity was used to create your account. To delete it you should first add another identity to your account.'),
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        );
        return $form;
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form['identity_choice']) && $form_state->isValueEmpty('identity_choice')) {
      $form_state->setErrorByName('identity_choice', t('Please choose identity for replacement.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    if (!$form_state->isValueEmpty('identity_choice')) {
      $identity = UloginHelper::identity_load_by_id($form_state->getValue('identity_choice'));
      $data = unserialize($identity['data']);
      $name = UloginHelper::make_username($data);
//      //change name
      $edit = User::load(\Drupal::currentUser()->id());
      $edit->set('name', $name);
      //change ulogin data used for tokens
      foreach ($data as $key => $val) {
        \Drupal::service('user.data')->set('ulogin', $edit->id(), $key, $val);
      }
      $edit->save();
//      change authname in authmap DB table
      \Drupal::service('externalauth.authmap')->save($edit, 'ulogin', $name);
    }

    $deleted = UloginHelper::identity_delete_by_id($build_info['args'][0]);
    if ($deleted) {
      drupal_set_message(t('Identity deleted.'));
      //invoke ulogin_identity_deleted rules event
      if (\Drupal::moduleHandler()->moduleExists('rules')) {
        rules_invoke_event('ulogin_identity_deleted', $form['#user'], $form['#del_identity_data']);
      }
    }

    $form_state->setRedirect('ulogin.user', [
      'uid' => \Drupal::currentUser()
        ->id()
    ]);
  }
}
