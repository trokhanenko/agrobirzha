<?php

namespace Drupal\statistics;

/**
 * The statistics reset count interface.
 */
interface StatisticsResetCountInterface {

  /**
   * Reset the day counter for all entities once every day.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   */
  public function resetDayCount($entity_type_id);

}
