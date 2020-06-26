<?php

namespace Drupal\event\Command;

// @codingStandardsIgnoreStart
use Drupal\Console\Annotations\DrupalCommand;
// @codingStandardsIgnoreEND
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\content_page\ContentPageManager;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Class EventDateConversionCommand.
 *
 * @DrupalCommand() (
 *   extension="event",
 *   extensionType="module"
 * )
 */
class EventDateConversionCommand extends Command {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EventDateConversionCommand object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('event:dates:convert')
      ->setDescription('Converts event start/end dates to date paragraphs');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $storage = $this->entityTypeManager->getStorage('node');
    $event_ids = $storage->getQuery()
      ->condition('type', ContentPageManager::CONTENT_TYPE_EVENT)
      ->execute();

    $bar = new ProgressBar($output, count($event_ids));
    $events = Node::loadMultiple($event_ids);

    foreach ($events as $event) {
      $date_from = $event->get('field_date_from')->value;
      $date_to = $event->get('field_date_to')->value;
      $time_from = $event->get('field_time_from')->value;
      $time_to = $event->get('field_time_to')->value;

      if (!$date_from) {
        $bar->advance();
        continue;
      }

      $event->set('field_dates', []);

      // Start date same as end date, or no end date - just one paragraph.
      if (($date_from == $date_to) || !$date_to) {
        $this->createDateParagraph($event, $date_from, (string) $time_from, (string) $time_to);
      }
      // Start date differs from end date - two paragraphs.
      else {
        $start_date = strtotime($date_from . ' 00:00:00');
        $end_date = strtotime($date_to . ' 00:00:00');

        $this->createDateParagraph($event, $date_from, (string) $time_from, (string) $time_to);

        for ($date = $start_date + 24 * 60 * 60; $date <= $end_date; $date += 24 * 60 * 60) {
          $this->createDateParagraph($event, date('Y-m-d', $date));
        }

      }

      $bar->advance();
    }

    $bar->finish();
    $io->info('Conversion complete');
  }

  /**
   * Creates a date paragraph and attaches it to the current event.
   *
   * @param \Drupal\node\Entity\Node $event
   *   The event.
   * @param string $date
   *   The date.
   * @param string $time_from
   *   The time from.
   * @param string $time_to
   *   The time to.
   */
  private function createDateParagraph(Node $event, $date, $time_from = '', $time_to = '') {
    $event_date = Paragraph::create([
      'type' => 'event_date',
      'field_date' => $date,
      'field_time_from' => $time_from,
      'field_time_to' => $time_to,
    ]);
    $event_date->save();

    $event->get('field_dates')->appendItem([
      'target_id' => $event_date->id(),
      'target_revision_id' => $event_date->getRevisionId()
    ]);
    $event->save();
  }

}
