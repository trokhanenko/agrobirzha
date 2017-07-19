<?php

namespace Drupal\statistics;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the default database storage backend for statistics.
 */
class StatisticsDatabaseStorage implements StatisticsStorageInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs the statistics storage.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection for the node view storage.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(Connection $connection, RequestStack $request_stack) {
    $this->connection = $connection;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function recordView($entity_type_id, $key, $id) {
    $table = $entity_type_id . '_counter';
    try {
      return (bool) $this->connection
        ->merge($table)
        ->key($key, $id)
        ->fields([
          'daycount' => 1,
          'totalcount' => 1,
          'timestamp' => $this->requestStack->getCurrentRequest()->server->get('REQUEST_TIME'),
        ])
        ->expression('daycount', 'daycount + 1')
        ->expression('totalcount', 'totalcount + 1')
        ->execute();
    }
    catch (\Exception $e) {
      $database_schema = $this->connection->schema();
      if ($database_schema->tableExists($table)) {
        throw $e;
      }
      else {
        $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
        $this->createTable($entity_type);
        $this->recordView($entity_type_id, $key, $id);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetchView(EntityTypeInterface $entity_type, $id) {
    try {
      $view = $this->connection
        ->select($this->tableName($entity_type), 'c')
        ->fields('c', ['totalcount', 'daycount', 'timestamp'])
        ->condition($entity_type->getKey('id'), $id)
        ->execute()
        ->fetchObject();
      if ($view) {
        return new StatisticsViewsResult($view->totalcount, $view->daycount, $view->timestamp);
      }
    }
    catch (\Exception $e) {
      $this->catchException($entity_type, $e);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll(EntityTypeInterface $entity_type, $order = 'totalcount', $limit = 5) {
    assert(in_array($order, ['totalcount', 'daycount', 'timestamp']), "Invalid order argument.");
    try {
      return $this->connection
        ->select($this->tableName($entity_type), 'nc')
        ->fields('nc', [$entity_type->getKey('id')])
        ->orderBy($order, 'DESC')
        ->range(0, $limit)
        ->execute()
        ->fetchCol();
    }
    catch (\Exception $e) {
      $this->catchException($entity_type, $e);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteViews(EntityTypeInterface $entity_type, $id) {
    try {
      return (bool) $this->connection
        ->delete($this->tableName($entity_type))
        ->condition($entity_type->getKey('id'), $id)
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($entity_type, $e);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function maxTotalCount(EntityTypeInterface $entity_type) {
    try {
      $query = $this->connection->select($this->tableName($entity_type), 'nc');
      $query->addExpression('MAX(totalcount)');
      $max_total_count = (int) $query->execute()->fetchField();
      return $max_total_count;
    }
    catch (\Exception $e) {
      $this->catchException($entity_type, $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createTable(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $idKey = $entity_type->getKey('id');
    $id_definition = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions($entity_type_id)[$idKey];
    if ($id_definition->getType() === 'integer') {
      $id_schema = [
        'description' => "The {{$entity_type_id}}.$idKey for these statistics.",
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0,
      ];
    }
    else {
      $id_schema = [
        'description' => "The {{$entity_type_id}}.$idKey for these statistics.",
        'type' => 'varchar_ascii',
        'length' => 128,
        'not null' => TRUE,
      ];
    }
    $table = $entity_type_id . '_counter';
    if (!$this->connection->schema()->tableExists($table)) {
      $schema = [
        'description' => "Access statistics for {{$entity_type_id}}s.",
        'fields' => [
          $idKey => $id_schema,
          'totalcount' => [
            'description' => "The total number of times the {{$entity_type_id}} has been viewed.",
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
            'size' => 'big',
          ],
          'daycount' => [
            'description' => "The total number of times the {{$entity_type_id}} has been viewed today.",
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
            'size' => 'medium',
          ],
          'timestamp' => [
            'description' => "The most recent time the {{$entity_type_id}} has been viewed.",
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
          ],
        ],
        'primary key' => [$idKey],
      ];
      $this->connection->schema()->createTable($table, $schema);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function dropTable(EntityTypeInterface $entity_type) {
    if ($this->connection->schema()->tableExists($this->tableName($entity_type))) {
      $this->connection->schema()->dropTable($this->tableName($entity_type));
    }
  }

  /**
   * Act on an exception when the table might not have been created.
   *
   * If the table does not yet exist, that's fine, but if the table exists and
   * something else caused the exception, then propagate it.
   *
   * @param \Exception $e
   *   The exception.
   *
   * @throws \Exception
   */
  protected function catchException(EntityTypeInterface $entity_type,\Exception $e) {
    if ($this->connection->schema()->tableExists($this->tableName($entity_type))) {
      throw $e;
    }
  }

  /**
   * Generates the table name from the entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *
   * @return string
   */
  protected function tableName(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    return $entity_type_id . '_counter';
  }

}
