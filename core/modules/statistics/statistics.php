<?php

/**
 * @file
 * Handles counts of node views via AJAX with minimal bootstrap.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

chdir('../../..');

$autoloader = require_once 'autoload.php';
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$container = $kernel->getContainer();

$entity_types = $container
  ->get('config.factory')
  ->get('statistics.settings')
  ->get('entity_type_ids');
$key = filter_input(INPUT_POST, 'key', FILTER_CALLBACK, ['options' => 'statistics_validate_machine_name']);
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$entity_type = filter_input(INPUT_POST, 'type', FILTER_CALLBACK, ['options' => 'statistics_validate_machine_name']);
if ($key && $id && $entity_type && in_array($entity_type, $entity_types, TRUE)) {
  $container->get('request_stack')->push($request);
  $container->get('statistics.storage')->recordView($entity_type, $key, $id);
}

/**
 * Validate entity type machine name.
 *
 * @param string $machine_name
 *   The machine name.
 *
 * @return string|null
 *   The valid machine name or NULL.
 */
function statistics_validate_machine_name($machine_name) {
  return preg_match('@[^a-z0-9_]+@', $machine_name) === 0 ? $machine_name : NULL;
}
