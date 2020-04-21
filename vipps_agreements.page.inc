<?php

/**
 * @file
 * Contains vipps_agreements.page.inc.
 *
 * Page callback for Vipps agreements entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Vipps agreements templates.
 *
 * Default template: vipps_agreements.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_vipps_agreements(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
