<?php
namespace Drupal\mailchimp_subscribe\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\mailchimp_subscribe\MailchimpClient\MailchimpClient;

class Mailchimp {

  /**
   * @var \Drupal\mailchimp_subscribe\Service\MailchimpSubscribeSettings
   */
  private MailchimpSubscribeSettings $mailchimSubscribeSettings;

  public function __construct(MailchimpSubscribeSettings $mailchimpSubcribeSettings) {
    $this->mailchimSubscribeSettings = $mailchimpSubcribeSettings;
  }

  /**
   * @param $email
   * @param array $merge_fields
   * @param array $tags
   *
   * @return bool
   */
  public function add($email, $lng = '', array $merge_fields = [], array $tags = [], array $interests = []) {
    $mailchimpClient = new MailchimpClient($this->getMailchimpKey());
    try {
      return $mailchimpClient->subscribeToList(
        $this->getMailchimpListId(),
        $email,
        $lng,
        $merge_fields,
        $tags,
        $interests,
        FALSE,
      );
    }
    catch (\Exception $e) {
      return false;
    }
  }

  /**
   * @param $email
   * @param array $merge_fields
   * @param array $tags
   *
   * @return bool
   */
  public function subscribe($email, string $lng = '', array $merge_fields = [], array $tags = [], array $interests = []) {
    $mailchimpClient = new MailchimpClient($this->getMailchimpKey());
    try {
      return $mailchimpClient->subscribeToList(
        $this->getMailchimpListId(),
        $email,
        $lng,
        $merge_fields,
        $tags,
        $interests,
        TRUE
      );
    }
    catch (\Exception $e) {
      return false;
    }
  }

  /**
   * @return mixed
   */
  private function getMailchimpKey() {
    return (string) $this->mailchimSubscribeSettings->getMailchimpKey();
  }

  /**
   * @return mixed
   */
  private function getMailchimpListId() {
    return (string) $this->mailchimSubscribeSettings->getMailchimpListId();
  }

}
