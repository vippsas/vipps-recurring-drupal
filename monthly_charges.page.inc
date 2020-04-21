<?php

/**
 * @file
 * Contains periodic_charges.page.inc.
 *
 * Page callback for Periodic charges entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Periodic charges templates.
 *
 * Default template: periodic_charges.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_periodic_charges(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
