<?php

namespace Drupal\content_migration\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\content_migration\ContentMigrationManager;
use Symfony\Component\Console\Input\InputArgument;
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
class ImportPageTreeCommand extends Command {

  /**
   * Drupal\content_migration\ContentMigrationManager definition.
   *
   * @var \Drupal\content_migration\ContentMigrationManager
   */
  protected $migrationManager;

  /**
   * Constructs a new ImportPageTreeCommand object.
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
      ->setName('content_migration:import:page:tree')
      ->setDescription('Migrates a page tree - a root page and all pages under it')
      ->addArgument('page_id', InputArgument::REQUIRED);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);
    $page_id = $input->getArgument('page_id');

    // Perform manager initializing/validation for request page.
    $this->migrationManager->initialize($io, $page_id);

    // Perform migration actions for the requested page and all child pages.
    $this->migrationManager->migratePage($io, $page_id);

    $io->info('Migration complete');
  }

}
