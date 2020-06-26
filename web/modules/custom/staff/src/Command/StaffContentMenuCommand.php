<?php

namespace Drupal\staff\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
// @codingStandardsIgnoreStart
use Drupal\Console\Annotations\DrupalCommand;
// @codingStandardsIgnoreEND
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\content_page\ContentPageManager;
use Drupal\node\Entity\Node;

/**
 * Class StaffContentMenuCommand.
 *
 * @DrupalCommand (
 *   extension="staff",
 *   extensionType="module"
 * )
 */
class StaffContentMenuCommand extends Command {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new StaffContentMenuCommand object.
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
      ->setName('staff:contentmenu:add')
      ->setDescription('Adds the default value for the new field "Content menu"');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $staff_member_ids = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('type', ContentPageManager::CONTENT_TYPE_STAFF)
      ->execute();

    $staff_members = Node::loadMultiple($staff_member_ids);
    foreach ($staff_members as $staff) {
      $staff->set('field_content_menu', 'sibling');
      $staff->save();
    }

    $io->info('Value adding complete');
  }

}
