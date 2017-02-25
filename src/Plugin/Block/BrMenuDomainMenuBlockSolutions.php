<?php

namespace Drupal\br_menu\Plugin\Block;

use Drupal\br_menu\BrMenuIndustrySpecificBase;

/**
 * Overrides Drupal\system\Plugin\Block\SystemMenuBlock
 *
 *  Get solution menu links.
 *
 * @Block(
 *   id = "br_menu_domain_menu_block_solutions",
 *   admin_label = @Translation("Menu main solutions"),
 *   category = @Translation("Br Menu"),
 * )
 */
class BrMenuDomainMenuBlockSolutions extends BrMenuIndustrySpecificBase  {

  /**
   * @inheritDoc
   */
  protected function getIndustrySpecificExpectedAttributeValue() {
    return 'solutions';
  }

}
