<?php

/**
 * @file
 * Contains event_applicant.page.inc.
 *
 * Page callback for Event applicant entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Event applicant templates.
 *
 * Default template: event_applicant.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_event_applicant(array &$variables) {
  // Fetch EventApplicant Entity Object.
  $event_applicant = $variables['elements']['#event_applicant'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
