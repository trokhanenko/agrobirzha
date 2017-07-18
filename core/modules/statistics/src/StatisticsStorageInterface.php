<?php

namespace Drupal\statistics;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface defining Statistics Storage.
 *
 * Stores the views per day, total views and timestamp of last view
 * for entities.
 */
interface StatisticsStorageInterface {

  /**
   * Count a entity view.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $key
   *   The ID key of the entity to count.
   * @param int $id
   *   The ID of the entity to count.
   *
   * @return bool
   *   TRUE if the entity view has been counted.
   */
  public function recordView($entity_type_id, $key, $id);

  /**
   * Returns the number of times a single entity has been viewed.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param int $id
   *   The ID of the entity to fetch the views for.
   *
   * @return \Drupal\statistics\StatisticsViewsResult|null
   *   The StatisticsViewsResult object if the result is present NULL otherwise.
   */
  public function fetchView(EntityTypeInterface $entity_type, $id);

  /**
   * Returns the number of times an entity has been viewed.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $order
   *   The counter name to order by:
   *   - 'totalcount' The total number of views.
   *   - 'daycount' The number of views today.
   *   - 'timestamp' The unix timestamp of the last view.
   * @param int $limit
   *   The number of entity IDs to return.
   *
   * @return array
   *   An ordered array of entity IDs.
   */
  public function fetchAll(EntityTypeInterface $entity_type, $order = 'totalcount', $limit = 5);

  /**
   * Delete counts for a specific entity.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param int $id
   *   The ID of the entity which views to delete.
   *
   * @return bool
   *   TRUE if the entity views have been deleted.
   */
  public function deleteViews(EntityTypeInterface $entity_type, $id);

  /**
   * Returns the highest 'totalcount' value.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return int
   *   The highest 'totalcount' value.
   */
  public function maxTotalCount(EntityTypeInterface $entity_type);

  /**
   * Creates entity counter table.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   */
  public function createTable(EntityTypeInterface $entity_type);

  /**
   * Drops entity counter table.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   */
  public function dropTable(EntityTypeInterface $entity_type);

}
