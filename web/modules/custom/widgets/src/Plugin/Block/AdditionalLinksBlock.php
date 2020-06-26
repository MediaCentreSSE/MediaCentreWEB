<?php

namespace Drupal\widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;

/**
 * Provides a 'AdditionalLinksBlock' block.
 *
 * @Block(
 *  id = "additional_links_block",
 *  admin_label = @Translation("Additional Links block"),
 * )
 */
class AdditionalLinksBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->configuration['content'];

    foreach ($content->get('field_links') as $link) {
      $url = $link->getUrl();
      if ($url->isRouted() && $url->getRouteName() == 'entity.node.canonical') {
        $parameters = $url->getRouteParameters();
        $node = Node::load($parameters['node']);

        $values = $link->getValue();
        if (!$values['title']) {
          $values['title'] = $node->get('title')->value;
        }

        $link->setValue($values);
      }
    }

    return [
      '#theme' => 'additional_links_block',
      '#content' => $content,
    ];
  }

}
