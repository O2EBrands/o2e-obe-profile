<?php

namespace Drupal\Tests\o2e_obe_profile\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests for default site or application setting setup (e.g. name).
 */
class DefaultSiteSetupTest extends ExistingSiteBase {

  /**
   * Tests the default configuration's site name.
   *
   * @return void
   */
  public function testSiteName() {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')
      ->get('system.site');
    $this->assertEquals('O2E Online Booking Engine', $config->get('name'));
  }

}
