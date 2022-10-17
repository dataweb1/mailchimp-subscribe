<?php

namespace Drupal\mailchimp_subscribe\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bo\Service\BoSettings;
use Drupal\Core\Cache\Cache;
use Drupal\mailchimp_subscribe\Service\MailchimpSubscribeSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class BoSettingsForm.
 *
 * @package Drupal\bo\Form
 */
class MailchimpSubscribeSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\mailchimp_subscribe\Service\MailchimpSubscribeSettings
   */
  private MailchimpSubscribeSettings $mailchimpSubscribeSettings;

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('mailchimp_subscribe.settings')
    );
  }

  /**
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailchimpSubscribeSettings $mailchimpSubcribeSettings) {
    parent::__construct($config_factory);
    $this->mailchimpSubscribeSettings = $mailchimpSubcribeSettings;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mailchimp_subscribe.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailchimp_subscribe_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['mailchimp_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MailChimp API Key'),
      '#default_value' => $this->mailchimpSubscribeSettings->getMailchimpKey(),
    ];

    $form['mailchimp_list_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MailChimp list ID'),
      '#default_value' => $this->mailchimpSubscribeSettings->getMailchimpListId(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $input = $form_state->getUserInput();
    $settings["mailchimp_key"] = $input["mailchimp_key"];
    $settings["mailchimp_list_id"] = $input["mailchimp_list_id"];

    $this->mailchimpSubscribeSettings->setSettings($settings);

    Cache::invalidateTags(["mailchimp_subscribe:settings"]);
  }

}
