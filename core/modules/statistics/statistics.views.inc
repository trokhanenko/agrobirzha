<?php

/**
 * @file
 * Provide views data for statistics.module.
 */

/**
 * Implements hook_views_data().
 */
function statistics_views_data() {
  $data = [];
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_types = \Drupal::configFactory()->get('statistics.settings')->get('entity_type_ids');
  if (!empty($entity_types)) {
    foreach (array_filter($entity_types) as $entity_type_id) {
      if ($entity_type_manager->hasHandler($entity_type_id, 'views_data')) {
        $table = $entity_type_id . '_counter';
        $entity_type = $entity_type_manager->getDefinition($entity_type_id);
        $base_table = $entity_type_manager->getHandler($entity_type_id, 'views_data')
          ->getViewsTableForEntityType($entity_type);
        $data[$table]['table']['group'] = t('@label statistics', ['@label' => $entity_type->getLabel()]);

        $data[$table]['table']['join'] = [
          $base_table => [
            'left_field' => $entity_type->getKey('id'),
            'field' => $entity_type->getKey('id'),
          ],
        ];

        $data[$table]['totalcount'] = [
          'title' => t('Total views'),
          'help' => t('The total number of times the @entity has been viewed.', ['@entity' => $entity_type->id()]),
          'field' => [
            'id' => 'statistics_numeric',
            'click sortable' => TRUE,
          ],
          'filter' => [
            'id' => 'numeric',
          ],
          'argument' => [
            'id' => 'numeric',
          ],
          'sort' => [
            'id' => 'standard',
          ],
        ];

        $data[$table]['daycount'] = [
          'title' => t('Views today'),
          'help' => t('The total number of times the @entity has been viewed today.', ['@entity' => $entity_type->id()]),
          'field' => [
            'id' => 'statistics_numeric',
            'click sortable' => TRUE,
          ],
          'filter' => [
            'id' => 'numeric',
          ],
          'argument' => [
            'id' => 'numeric',
          ],
          'sort' => [
            'id' => 'standard',
          ],
        ];

        $data[$table]['timestamp'] = [
          'title' => t('Most recent view'),
          'help' => t('The most recent time the @entity has been viewed.', ['@entity' => $entity_type->id()]),
          'field' => [
            // @todo replace node_counter_timestamp with entity_counter_timestamp.
            'id' => 'node_counter_timestamp',
            'click sortable' => TRUE,
          ],
          'filter' => [
            'id' => 'date',
          ],
          'argument' => [
            'id' => 'date',
          ],
          'sort' => [
            'id' => 'standard',
          ],
        ];
      }
    }
  }

  return $data;
}
