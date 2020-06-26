<?php

namespace Drupal\content_migration\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\content_migration\ContentMigrationManager;
// @codingStandardsIgnoreStart
use Drupal\Console\Annotations\DrupalCommand;
// @codingStandardsIgnoreEND

/**
 * Class ImportAllCommand.
 *
 * @DrupalCommand (
 *     extension="content_migration",
 *     extensionType="module"
 * )
 */
class ImportAllCommand extends Command {

  /**
   * Drupal\content_migration\ContentMigrationManager definition.
   *
   * @var \Drupal\content_migration\ContentMigrationManager
   */
  protected $migrationManager;

  /**
   * Constructs a new ImportAllCommand object.
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
      ->setName('content_migration:import:all')
      ->setDescription('Deletes everything and imports everything with minimal user input. Ids hardcoded.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $this->migrationManager->loadPageStructure();
    $this->migrationManager->deleteAllNodes($io);

    $page_ids = [20, 300, 68, 85, 89, 93, 172, 108];

    foreach ($page_ids as $menu_weight => $page_id) {
      $this->migrationManager->setRootMenuItemWeight($menu_weight);
      $this->migrationManager->migratePage($io, $page_id);
    }

    $this->migrationManager->migrateStaff();

    $io->info('Migration complete');
  }

}
