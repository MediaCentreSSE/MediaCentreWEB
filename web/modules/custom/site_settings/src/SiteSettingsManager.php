<?php

namespace Drupal\site_settings;

use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Class SiteSettingsManager.
 */
class SiteSettingsManager {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new SiteSettingsManager object.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Returns any additional dynamic routes.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  public function routes() {
    $collection = new RouteCollection();
    $site_settings = $this->configFactory->get('system.site');

    if ($site_settings->get('privacy_policy_page')) {
      $privacy_policy_route = new Route($site_settings->get('privacy_policy_page'));
      $privacy_policy_route->setRequirements(['_permission' => 'access content']);
      $collection->add('privacy_policy_page', $privacy_policy_route);
    }

    return $collection;
  }

}
