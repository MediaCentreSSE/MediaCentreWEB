<?php

namespace Drupal\event_form\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
// @codingStandardsIgnoreStart
use Drupal\Console\Annotations\DrupalCommand;
// @codingStandardsIgnoreEND
use Drupal\event_form\EventFormManager;

/**
 * Class ApplicantNotificationCommand.
 *
 * @DrupalCommand (
 *     extension="event_form",
 *     extensionType="module"
 * )
 */
class ApplicantNotificationCommand extends Command {

  /**
   * Drupal\event_form\EventFormManager definition.
   *
   * @var \Drupal\event_form\EventFormManager
   */
  protected $eventFormManager;

  /**
   * Constructs a new ApplicantNotificationCommand object.
   */
  public function __construct(EventFormManager $event_form_manager) {
    $this->eventFormManager = $event_form_manager;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('event_form:notify:applicants')
      ->setDescription('Checks for any applicants/admins that require an event notifications.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $count = $this->eventFormManager->sendApplicantNotifications();
    $io->info($count . ' event applicants notified.');
  }

}
