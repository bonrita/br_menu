<?php

namespace Drupal\br_menu\Plugin\Block;

use Drupal\br_menu\BrMenuIndustrySpecificBase;

/**
 * Overrides Drupal\system\Plugin\Block\SystemMenuBlock
 *
 *  Get platform menu links.
 *
 * @Block(
 *   id = "br_menu_domain_menu_block_platforms",
 *   admin_label = @Translation("Menu platforms"),
 *   category = @Translation("Br Menu"),
 * )
 */
class BrDomainMenuBlockPlatforms extends BrMenuIndustrySpecificBase {

  /**
   * @inheritDoc
   */
  protected function getIndustrySpecificExpectedAttributeValue() {
    return 'platforms';
  }

}
