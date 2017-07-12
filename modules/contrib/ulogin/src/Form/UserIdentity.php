<?php

namespace Drupal\ulogin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ulogin\UloginHelper;
use Drupal\user\Entity\User;

/**
 * Displays banned IP addresses.
 */
class UserIdentity extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ulogin_user_identity';
  }

  /**
   * {@inheritdoc}
   *
   * @param string $default_ip
   *   (optional) IP address to be passed on to
   *   \Drupal::formBuilder()->getForm() for use as the default value of the IP
   *   address form field.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = 0) {
    $account = User::load($uid);
    if ($route = \Drupal::request()->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
      $route->setDefault('_title', $account->getDisplayName());
    }

    $identities = UloginHelper::identity_load_by_uid($uid);
    $providers = UloginHelper::providers_list();

    $header = array(t('Authentication provider'), t('Identity'), t('Delete'));
    $rows = array();
    $data_array = array();
    foreach ($identities as $identity) {
      $data = unserialize($identity['data']);
      $data_array[] = $data;
      $rows[] = array(
        $providers[$data['network']],
        Link::fromTextAndUrl($data['identity'], Url::fromUri($data['identity'], array(
          'attributes' => array('target' => '_blank'),
          'external' => TRUE
        ))),
        Link::createFromRoute(t('Delete'), 'ulogin.user_delete', [
          'uid' => $uid,
          'id' => $identity['id']
        ]),
      );
    }

    $form = array();

    $form['identity'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('You don\'t have any identities yet.')
    );

    //add more identities
    if (\Drupal::currentUser()->hasPermission('use ulogin')) {
      $form['ulogin_widget'] = array(
        '#type' => 'ulogin_widget',
        '#title' => t('Add more identities'),
        '#weight' => 10,
        '#ulogin_destination' => '',
      );
    }

    //tokens browser for admins
    if (\Drupal::currentUser()
        ->hasPermission('administer site configuration') || \Drupal::currentUser()
        ->hasPermission('administer users')
    ) {
      $form['vtabs'] = array(
        '#type' => 'vertical_tabs',
        '#default_tab' => 'edit-fset-user-tokens',
        '#weight' => 20,
      );

      $header = array(t('Token'), t('Value'));
      //user tokens
      $ulogin = \Drupal::service('user.data')->get('ulogin', $uid);
      if (!empty($ulogin)) {
        $form['fset_user_tokens'] = array(
          '#type' => 'details',
          '#title' => t('User tokens'),
          '#group' => 'vtabs'
        );

        $rows = array();
        foreach ($ulogin as $key => $value) {
          if (!in_array($key, array('manual', 'ulogin'))) {
            $rows[] = array('[user:ulogin:' . $key . ']', $value);
          }
        }
        $form['fset_user_tokens']['tokens'] = array(
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
        );
      }

      //data from auth providers
      foreach ($data_array as $data) {
        $form['fset_' . $data['network'] . '_' . $data['uid']] = array(
          '#type' => 'details',
          '#title' => $providers[$data['network']],
          '#group' => 'vtabs'
        );

        $rows = array();
        foreach ($data as $key => $value) {
          $rows[] = array($key, $value);
        }
        $form['fset_' . $data['network'] . '_' . $data['uid']]['tokens'] = array(
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
        );
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
