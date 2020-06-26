<?php

namespace Drupal\content_migration\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\content_migration\ContentMigrationManager;
use Drupal\Console\Core\Style\DrupalStyle;
// @codingStandardsIgnoreStart
use Drupal\Console\Annotations\DrupalCommand;
// @codingStandardsIgnoreEND

/**
 * Class ImportPageTreeCommand.
 *
 * @DrupalCommand (
 *     extension="content_migration",
 *     extensionType="module"
 * )
 */
class ImportStaffCommand extends Command {

  /**
   * Drupal\content_migration\ContentMigrationManager definition.
   *
   * @var \Drupal\content_migration\ContentMigrationManager
   */
  protected $migrationManager;

  /**
   * Constructs a new ImportStaffCommand object.
   */
  public function __construct(ContentMigrationManager $content_migration_manager) {
    $this->migrationManager = $content_migration_manager;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('content_migration:import:staff')
      ->setDescription('Migrates all staff members');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    // Perform migration actions for staff migration.
    $this->migrationManager->migrateStaff();

    $io->info('Migration complete');
  }

}
