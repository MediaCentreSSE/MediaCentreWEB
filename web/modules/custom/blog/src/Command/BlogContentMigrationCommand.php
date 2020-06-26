<?php

namespace Drupal\blog\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
// @codingStandardsIgnoreStart
use Drupal\Console\Annotations\DrupalCommand;
// @codingStandardsIgnoreEND
use Drupal\content_migration\ContentMigrationManager;

/**
 * Class BlogContentMigrationCommand.
 *
 * @DrupalCommand (
 *   extension="blog",
 *   extensionType="module"
 * )
 */
class BlogContentMigrationCommand extends Command {

  /**
   * Drupal\content_migration\ContentMigrationManager definition.
   *
   * @var \Drupal\content_migration\ContentMigrationManager
   */
  protected $contentMigrationManager;

  /**
   * Constructs a new BlogContentMigrationCommand object.
   */
  public function __construct(ContentMigrationManager $content_migration_manager) {
    $this->contentMigrationManager = $content_migration_manager;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('content_migration:convert:blog')
      ->setDescription('Converts existing blog list news items to blog items');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $this->contentMigrationManager->migrateBlogListItems($io);

    $io->info('Migration complete');
  }

}
