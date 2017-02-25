<?php

namespace Drupal\br_menu\Plugin\Block;

use Drupal\system\Plugin\Block\SystemMenuBlock;

/**
 * Overrides Drupal\system\Plugin\Block\SystemMenuBlock
 *
 * @Block(
 *   id = "br_menu_menu_block",
 *   admin_label = @Translation("Menu"),
 *   category = @Translation("Br Menu"),
 *   deriver = "Drupal\system\Plugin\Derivative\SystemMenuBlock"
 * )
 */
class BrMenuBlock extends SystemMenuBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    // Add a theme template variant to allow different templates for main and
    // sub-navigation menu. Example: menu__main__level1
    if (isset($build['#theme'])) {
      $level = $this->configuration['level'];
      $build['#theme'] = [$build['#theme'] . "__level$level", $build['#theme']];
    }
    return $build;
  }

}
