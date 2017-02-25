<?php

namespace Drupal\br_menu\Plugin\Block;

use Drupal\br_menu\BrMenuIndustrySpecificBase;

/**
 * Overrides Drupal\system\Plugin\Block\SystemMenuBlock
 *
 *  Get "about" menu links.
 *
 * @Block(
 *   id = "br_menu_domain_menu_block_about",
 *   admin_label = @Translation("Menu about"),
 *   category = @Translation("Br Menu"),
 * )
 */
class BrDomainMenuBlockAbout extends BrMenuIndustrySpecificBase {

  /**
   * @inheritDoc
   */
  protected function getIndustrySpecificExpectedAttributeValue() {
    return 'about';
  }

  /**
   * @inheritDoc
   */
  protected function getMenuName() {
    $menu_name =  parent::getMenuName();

    // If menu is not yet defined show the default menu.
    if(empty($menu_name)) {
      $menu_name = 'main-aa';
    }

    return $menu_name;
  }

  /**
   * @inheritDoc
   */
  public function build() {
    return parent::build();
  }


}
