<?php

namespace Drupal\dekufinal\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for DekuFinal routes.
 */
class DekufinalController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
