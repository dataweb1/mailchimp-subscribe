<?php
namespace Drupal\mailchimp_subscribe\Service;

use Drupal\Core\Config\ConfigFactory;
class MailchimpSubscribeSettings {

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * @var array|mixed|null
   */
  private $settings;

  /**
   * @param ConfigFactory $config
   */
  public function __construct(ConfigFactory $config) {
    $this->config = $config->getEditable('mailchimp_subscribe.settings');
    $this->settings = $this->config->get('config');
  }

  /**
   * @param $settings_to_save
   */
  public function setSettings($settings_to_save) {

    foreach ($settings_to_save as $key => $settings) {
      if (is_array($settings)) {
        foreach ($settings as $key1 => $settings1) {
          $this->settings[$key][$key1] = $settings1;
        }
      }
      else {
        $this->settings[$key] = $settings;
      }
    }
    $this->saveSettings();
  }

  /**
   *
   */
  private function saveSettings() {
    $this->config->set('config', $this->settings)->save();
  }


  /**
   * @return mixed
   */
  public function getMailchimpKey() {
    return $this->settings['mailchimp_key'];
  }

  /**
   * @return mixed
   */
  public function getMailchimpListId() {
    return $this->settings['mailchimp_list_id'];
  }

}