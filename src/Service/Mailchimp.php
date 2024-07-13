<?php

namespace Drupal\mailchimp_subscribe\Service;

use Drupal\mailchimp_subscribe\MailchimpClient\MailchimpClient;

/**
 *
 */
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
      return FALSE;
    }
  }

  /**
   * @param $email
   * @param array $merge_fields
   * @param array $tags
   *
   * @return bool
   */
  public function unsubscribe($email): bool {
    $mailchimpClient = new MailchimpClient($this->getMailchimpKey());
    try {
      return $mailchimpClient->removeFromList(
        $this->getMailchimpListId(),
        $email,
      );
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * @param $email
   * @param array $merge_fields
   * @param array $tags
   *
   * @return bool
   */
  public function subscribe($email, string $lng = '', array $merge_fields = [], array $tags = [], array $interests = []): bool {
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
      return FALSE;
    }
  }

  /**
   * @param $email
   * @param array $merge_fields
   * @param array $tags
   *
   * @return bool
   */
  public function updateTags($email, array $tags = []): bool {
    $mailchimpClient = new MailchimpClient($this->getMailchimpKey());
    try {
      return $mailchimpClient->updateListMemberTags(
        $this->getMailchimpListId(),
        $email,
        $tags,
      );
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * @return mixed
   */
  private function getMailchimpKey(): mixed {
    return (string) $this->mailchimSubscribeSettings->getMailchimpKey();
  }

  /**
   * @return mixed
   */
  private function getMailchimpListId(): mixed {
    return (string) $this->mailchimSubscribeSettings->getMailchimpListId();
  }

}
