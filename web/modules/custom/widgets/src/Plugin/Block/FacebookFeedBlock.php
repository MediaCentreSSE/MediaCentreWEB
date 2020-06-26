<?php

namespace Drupal\widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Facebook;

/**
 * Provides a 'FacebookFeedBlock' block.
 *
 * @Block(
 *   id = "facebook_feed_block",
 *   admin_label = @Translation("Facebook feed block"),
 * )
 */
class FacebookFeedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new FacebookFeedBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactory $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = $this->configuration['content'];
    $posts = $this->getPosts($content->field_facebook_page->value, $content->field_post_count->value);

    return [
      '#theme' => 'facebook_feed_block',
      '#content' => $content,
      '#posts' => $posts,
    ];
  }

  /**
   * Gets the posts.
   *
   * @param string $facebook_page
   *   The facebook page.
   * @param int $post_count
   *   The post count.
   *
   * @return array
   *   The posts.
   */
  private function getPosts($facebook_page, $post_count) {
    $config = $this->configFactory->get('social_network.settings');
    if (!$config->get('facebook_app_id') || !$config->get('facebook_app_secret')) {
      return [];
    }

    $fbClient = new Facebook([
      'app_id' => $config->get('facebook_app_id'),
      'app_secret' => $config->get('facebook_app_secret'),
    ]);
    $fbClient->setDefaultAccessToken(
      $config->get('facebook_app_id') . '|' . $config->get('facebook_app_secret')
    );

    $facebook_feed = [];
    $url = parse_url($facebook_page);
    $page = rtrim($url['path'], '/');

    try {
      $response = $fbClient->get($page . '/posts?limit=' . $post_count);
      $responseData = $response->getDecodedBody()['data'] ?? [];
    }
    catch (FacebookResponseException $exception) {
      $responseData = [];
    }
    foreach ($responseData as $post_data) {
      try {
        $post = $fbClient->get('/' . $post_data['id'] . '/attachments')->getDecodedBody()['data'][0];
        $post = array_merge($post, $post_data);
        $facebook_feed[] = $post;
      }
      catch (FacebookResponseException $exception) {
      }
    }

    return $facebook_feed;
  }

}
