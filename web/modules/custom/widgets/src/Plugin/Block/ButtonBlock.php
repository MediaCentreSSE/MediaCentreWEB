<?php

namespace Drupal\widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'ButtonBlock' block.
 *
 * @Block(
 *  id = "button_block",
 *  admin_label = @Translation("Button block"),
 * )
 */
class ButtonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->configuration['content'];

    return [
      '#theme' => 'button_block',
      '#content' => $content,
    ];
  }

}
