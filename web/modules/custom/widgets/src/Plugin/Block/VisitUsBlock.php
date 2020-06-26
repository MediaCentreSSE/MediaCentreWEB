<?php

namespace Drupal\widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'VisitUsBlock' block.
 *
 * @Block(
 *  id = "visit_us_block",
 *  admin_label = @Translation("Visit us block"),
 * )
 */
class VisitUsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->configuration['content'];

    return [
      '#theme' => 'visit_us_block',
      '#content' => $content,
    ];
  }

}
