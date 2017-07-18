<?php

namespace Drupal\statistics;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure statistics settings for this site.
 */
class StatisticsSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type repository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * The storage for statistics.
   *
   * @var \Drupal\statistics\StatisticsStorageInterface
   */
  protected $statisticsStorage;

  /**
   * Constructs a \Drupal\user\StatisticsSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository service.
   * @param \Drupal\statistics\StatisticsStorageInterface $statistics_storage
   *   The storage for statistics.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, EntityTypeRepositoryInterface $entity_type_repository, StatisticsStorageInterface $statistics_storage) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeRepository = $entity_type_repository;
    $this->statisticsStorage = $statistics_storage;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.repository'),
      $container->get('statistics.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'statistics_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['statistics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('statistics.settings');
    $labels = $this->entityTypeRepository->getEntityTypeLabels(TRUE);
    $labels = $labels[(string) $this->t('Content', [], ['context' => 'Entity type group'])];
    $options = [];
    foreach ($labels as $entity_type_id => $label) {
      $options[$entity_type_id] = $this->t('Enable statistics for @entity_type', ['@entity_type' => $label]);
    }

    // Content entity counter settings.
    $form['content'] = [
      '#type' => 'details',
      '#title' => $this->t('Content viewing counter settings'),
      '#description' => $this->t('Increment a counter each time content is viewed.'),
      '#open' => TRUE,
    ];

    $form['content']['entity_type_ids'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select content'),
      '#options' => $options,
      '#default_value' => $config->get('entity_type_ids'),

    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type_ids = $form_state->getValue('entity_type_ids');
    $this->config('statistics.settings')
      ->set('entity_type_ids', $entity_type_ids)
      ->save();
    foreach (array_keys($entity_type_ids) as $entity_type_id) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if ($entity_type_ids[$entity_type_id] === $entity_type_id) {
        $this->statisticsStorage->createTable($entity_type);
      }
      else {
        $this->statisticsStorage->dropTable($entity_type);
      }
    }

    // The popular statistics block is dependent on these settings, so clear the
    // block plugin definitions cache.
    if ($this->moduleHandler->moduleExists('block')) {
      \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
    }

    parent::submitForm($form, $form_state);
  }

}
