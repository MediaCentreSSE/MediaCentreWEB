<?php

namespace Drupal\widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'BlockAsLinkBlock' block.
 *
 * @Block(
 *  id = "block_as_link_block",
 *  admin_label = @Translation("Block as link block"),
 * )
 */
class BlockAsLinkBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->configuration['content'];

    return [
      '#theme' => 'block_as_link_block',
      '#content' => $content,
    ];
  }

}
