<?php

namespace Drupal\statistics;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;

/**
 * The statistics reset count class.
 */
class StatisticsResetCount implements StatisticsResetCountInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs the statistics reset count.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection for the node view storage.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(Connection $connection, StateInterface $state, TimeInterface $time) {
    $this->connection = $connection;
    $this->state = $state;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function resetDayCount($entity_type_id) {
    $statistics_timestamp = $this->state->get('statistics.day_timestamp') ?: 0;
    $time = $this->time->getRequestTime();
    $table = $entity_type_id . '_counter';
    if (($time - $statistics_timestamp) >= 86400 && $this->connection->schema()->tableExists($table)) {
      $this->state->set('statistics.day_timestamp', $time);
      $this->connection->update($table)
        ->fields(['daycount' => 0])
        ->execute();
    }
  }

}
