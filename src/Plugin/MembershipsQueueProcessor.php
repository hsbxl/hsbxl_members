<?php

namespace Drupal\hsbxl_members\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes Statements Tasks.
 *
 * @QueueWorker(
 *   id = "statements_queue_processor",
 *   title = @Translation("Generate Sales or Purchases from bank/cash statements in the queue"),
 *   cron = {"time" = 10}
 * )
 */
class MembershipsQueueProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function processItem($sid) {

  }
}