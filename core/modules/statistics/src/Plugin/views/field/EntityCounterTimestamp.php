<?php

namespace Drupal\statistics\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;
use Drupal\Core\Session\AccountInterface;

/**
 * Field handler to display the most recent time the entity has been viewed.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_counter_timestamp")
 */
class EntityCounterTimestamp extends Date {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('view post access counter');
  }

}
