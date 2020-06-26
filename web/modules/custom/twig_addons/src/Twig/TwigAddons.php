<?php

namespace Drupal\twig_addons\Twig;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TwigAddons.
 *
 * @package Drupal\twig_addons\Twig
 */
class TwigAddons extends \Twig_Extension {

  protected $languageManager;

  /**
   * TwigAddons constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   */
  public function __construct(
    LanguageManagerInterface $language_manager
  ) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return __CLASS__;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    $filters = [
      new \Twig_SimpleFilter('style',
        [$this, 'imageStyleFromId']
      ),
    ];
    return $filters;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    $functions = [
      new \Twig_SimpleFunction('translateEntity', [$this, 'translateEntity']),
      new \Twig_SimpleFunction('imageStyleFromId', [$this, 'imageStyleFromId']),
      new \Twig_SimpleFunction('dump', [$this, 'dump']),
    ];
    return $functions;
  }

  /**
   * Retrieves entity translated in current languge.
   *
   * @param \Drupal\Core\Entity\Entity $entity
   *   Entity to translate.
   *
   * @return mixed
   *   Translated entity.
   */
  public function translateEntity(Entity $entity) {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    if ($entity->hasTranslation($language)) {
      return $entity->getTranslation($language);
    }
    // Fall back to available translation.
    return $entity;
  }

  /**
   * Returns the URL of this image derivative for an original image ID.
   *
   * @param int $id
   *   The path or URI to the original image.
   * @param string $style
   *   The image style.
   *
   * @return string
   *   The absolute URL where a style image can be downloaded, suitable for use
   *   in an <img> tag. Requesting the URL will cause the image to be created.
   */
  public function imageStyleFromId($id, $style) {
    $file = File::load((int) $id);
    if ($file) {
      $url = ImageStyle::load($style)->buildUrl($file->getFileUri());
      return file_url_transform_relative($url);
    }
    return '';
  }

  /**
   * Dumps the variable.
   *
   * @param mixed $variable
   *   Route name.
   */
  public function dump($variable) {
    dump($variable);
  }

}
