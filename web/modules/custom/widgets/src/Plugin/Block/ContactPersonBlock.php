<?php

namespace Drupal\widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'ContactPersonBlock' block.
 *
 * @Block(
 *  id = "contact_person_block",
 *  admin_label = @Translation("Contact Person block"),
 * )
 */
class ContactPersonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->configuration['content'];

    return [
      '#theme' => 'contact_person_block',
      '#content' => $content,
    ];
  }

}
