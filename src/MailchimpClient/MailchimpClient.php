<?php

namespace Drupal\mailchimp_subscribe\MailchimpClient;

use Drupal\mailchimp_subscribe\MailchimpClient\Exception\ApiException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class MailchimpClient {
  public const STATUS_ARCHIVED = 'archived';
  public const STATUS_PENDING = 'pending';
  public const STATUS_SUBSCRIBED = 'subscribed';
  public const STATUS_UNSUBSCRIBED = 'unsubscribed';

  protected GuzzleClient $client;
  protected string $apiKey;
  protected string $apiEndpoint = 'https://%dc%.api.mailchimp.com/3.0/';
  protected array $headers = [];
  protected ?object $lastError = NULL;

  public function __construct($apiKey) {
    $this->apiKey = $apiKey;

    [, $dc] = explode('-', $this->apiKey);
    $this->apiEndpoint = preg_replace('/%dc%/', $dc, $this->apiEndpoint);

    $this->headers = [
      'Accept' => 'application/vnd.api+json',
      'Content-Type' => 'application/vnd.api+json',
      'Authorization' => 'apikey ' . $this->apiKey,
      'User-Agent' => '1up/mailchimp-api-v3 (https://github.com/1up-lab/mailchimp-api-v3)',
    ];

    $this->client = new GuzzleClient([
      'base_uri' => $this->apiEndpoint,
    ]);
  }

  /**
   *
   */
  public function call($type = 'get', $uri = '', $args = [], $timeout = 10): ?ResponseInterface {
    $args['apikey'] = $this->apiKey;

    try {
      switch ($type) {
        case 'post':
          $response = $this->client->request('POST', $uri, [
            'json' => $args,
            'timeout' => $timeout,
            'headers' => $this->headers,
          ]);
          break;

        case 'patch':
          $response = $this->client->request('PATCH', $uri, [
            'body' => json_encode($args, \JSON_THROW_ON_ERROR),
            'timeout' => $timeout,
            'headers' => $this->headers,
          ]);
          break;

        case 'put':
          $response = $this->client->request('PUT', $uri, [
            'body' => json_encode($args, \JSON_THROW_ON_ERROR),
            'timeout' => $timeout,
            'headers' => $this->headers,
          ]);
          break;

        case 'delete':
          $response = $this->client->request('DELETE', $uri, [
            'query' => $args,
            'timeout' => $timeout,
            'headers' => $this->headers,
          ]);
          break;

        case 'get':
        default:
          $response = $this->client->request('GET', $uri, [
            'query' => $args,
            'timeout' => $timeout,
            'headers' => $this->headers,
          ]);
          break;
      }

      $this->lastError = NULL;

      return $response;
    }
    catch (RequestException $e) {
      $response = $e->getResponse();

      if (NULL === $response) {
        throw $e;
      }

      try {
        $this->lastError = json_decode((string) $response->getBody(), FALSE, 512, \JSON_THROW_ON_ERROR);
      }
      catch (\JsonException $e) {
        $this->lastError = $e;
      }

      return $response;
    }
    catch (\JsonException | GuzzleException $e) {
      $this->lastError = $e;
    }

    return NULL;
  }

  /**
   *
   */
  public function get($uri = '', $args = [], $timeout = 10): ?ResponseInterface {
    return $this->call('get', $uri, $args, $timeout);
  }

  /**
   *
   */
  public function post($uri = '', $args = [], $timeout = 10): ?ResponseInterface {
    return $this->call('post', $uri, $args, $timeout);
  }

  /**
   *
   */
  public function patch($uri = '', $args = [], $timeout = 10): ?ResponseInterface {
    return $this->call('patch', $uri, $args, $timeout);
  }

  /**
   *
   */
  public function put($uri = '', $args = [], $timeout = 10): ?ResponseInterface {
    return $this->call('put', $uri, $args, $timeout);
  }

  /**
   *
   */
  public function delete($uri = '', $args = [], $timeout = 10): ?ResponseInterface {
    return $this->call('delete', $uri, $args, $timeout);
  }

  /**
   *
   */
  public function validateApiKey(): bool {
    $response = $this->get();

    return $response && 200 === $response->getStatusCode();
  }

  /**
   * @throws \JsonException
   */
  public function getAccountDetails() {
    $response = $this->get('');

    return $response ? json_decode((string) $response->getBody(), FALSE, 512, \JSON_THROW_ON_ERROR) : NULL;
  }

  /**
   *
   */
  public function isSubscribed($listId, $email): bool {
    return self::STATUS_SUBSCRIBED === $this->getSubscriberStatus($listId, $email);
  }

  /**
   * @throws \Drupal\mailchimp_subscribe\MailchimpClient\Exception\ApiException
   * @throws \JsonException
   */
  public function getSubscriberStatus($listId, $email) {
    $endpoint = sprintf('lists/%s/members/%s', $listId, $this->getSubscriberHash($email));

    $response = $this->get($endpoint);

    if (NULL === $response) {
      throw new ApiException('Could not connect to API. Check your credentials.');
    }

    $body = json_decode((string) $response->getBody(), FALSE, 512, \JSON_THROW_ON_ERROR);

    return $body->status;
  }

  /**
   * @param $listId
   * @param $email
   * @param array $mergeVars
   * @param string $if_new_status
   * @param bool $doubleOptin
   * @param array $tags
   * @param string $lng
   * @param array $interests
   *
   * @return string
   * @throws \Drupal\mailchimp_subscribe\MailchimpClient\Exception\ApiException
   * @throws \JsonException
   */
  public function subscribeToList($listId, $email, string $lng = "", array $mergeVars = [], array $tags = [], array $interests = [], bool $doubleOptin = TRUE) {
    $endpoint = sprintf('lists/%s/members', $listId);

    $current_status = $this->getSubscriberStatus($listId, $email);

    $status = self::STATUS_SUBSCRIBED;
    if ($current_status !== self::STATUS_SUBSCRIBED) {
      if ($doubleOptin) {
        $status = self::STATUS_PENDING;
      }
    }

    /*
    // If attempt to subscribe change current status to pending if current status is archived or unsubscribed.
    if ($if_new_status == self::STATUS_SUBSCRIBED) {
    if ($current_status == 404 || $current_status == self::STATUS_ARCHIVED || $current_status == self::STATUS_UNSUBSCRIBED) {
    $update_status = self::STATUS_PENDING;
    }
    }

    // If attempt to subscribe change new status to pending if double opt-in.
    if ($if_new_status == self::STATUS_SUBSCRIBED) {
    $if_new_status = $doubleOptin ? self::STATUS_PENDING : self::STATUS_SUBSCRIBED;
    }


    // Force pending status to unsubscribe status if adding an already (invisible) existing pending subscription.
    if ($forceStatus != '') {
    $update_status = $forceStatus;
    $if_new_status = $forceStatus;
    }
     */

    // Prepare the data to be sent with the request.
    $requestData = [
      'id' => $listId,
      'email_address' => $email,
      'status_if_new' => $status,
      'status' => $status,
    ];

    if (\count($mergeVars) > 0) {
      $requestData['merge_fields'] = $mergeVars;
    }

    if (\count($interests) > 0) {
      $requestData['interests'] = $interests;
    }

    if (\count($tags) > 0) {
      $requestData['tags'] = $tags;
    }

    if ($lng != '') {
      $requestData['language'] = $lng;
    }

    if ($this->put($endpoint . '/' . $this->getSubscriberHash($email), $requestData)) {
      return $status;
    }

    return FALSE;
  }

  /**
   * @throws \Drupal\mailchimp_subscribe\MailchimpClient\Exception\ApiException
   */
  public function unsubscribeFromList($listId, $email): bool {
    $endpoint = sprintf('lists/%s/members/%s', $listId, $this->getSubscriberHash($email));

    $response = $this->patch($endpoint, [
      'status' => 'unsubscribed',
    ]);

    if (NULL === $response) {
      throw new ApiException('Could not connect to API. Check your credentials.');
    }

    return 200 === $response->getStatusCode();
  }

  /**
   * @throws \Drupal\mailchimp_subscribe\MailchimpClient\Exception\ApiException
   */
  public function updateListMemberTags($listId, $email, $tags): bool {
    $endpoint = sprintf('lists/%s/members/%s/tags', $listId, $this->getSubscriberHash($email));

    $response = $this->post($endpoint, ['tags' => $tags]);

    if (NULL === $response) {
      throw new ApiException('Could not connect to API. Check your credentials.');
    }

    return 204 === $response->getStatusCode();
  }

  /**
   * @throws \Drupal\mailchimp_subscribe\MailchimpClient\Exception\ApiException
   */
  public function removeFromList($listId, $email): bool {
    $endpoint = sprintf('lists/%s/members/%s', $listId, $this->getSubscriberHash($email));

    $response = $this->delete($endpoint);

    if (NULL === $response) {
      throw new ApiException('Could not connect to API. Check your credentials.');
    }

    return 204 === $response->getStatusCode();
  }

  /**
   * @throws \Drupal\mailchimp_subscribe\MailchimpClient\Exception\ApiException
   * @throws \JsonException
   */
  public function getListFields($listId, $offset = 0, $limit = 10) {
    $endpoint = sprintf('lists/%s/merge-fields', $listId);

    $response = $this->get($endpoint, ['offset' => $offset, 'limit' => $limit]);

    if (NULL === $response) {
      throw new ApiException('Could not connect to API. Check your credentials.');
    }

    if (200 !== $response->getStatusCode()) {
      throw new ApiException('Could not fetch merge-fields from API.');
    }

    return json_decode((string) $response->getBody(), FALSE, 512, \JSON_THROW_ON_ERROR);
  }

  /**
   * @throws \Drupal\mailchimp_subscribe\MailchimpClient\Exception\ApiException
   * @throws \JsonException
   */
  public function getListGroupCategories($listId, $offset = 0, $limit = 10) {
    $endpoint = sprintf('lists/%s/interest-categories', $listId);

    $response = $this->get($endpoint, ['offset' => $offset, 'limit' => $limit]);

    if (NULL === $response) {
      throw new ApiException('Could not connect to API. Check your credentials.');
    }

    if (200 !== $response->getStatusCode()) {
      throw new ApiException('Could not fetch interest-categories from API.');
    }

    return json_decode((string) $response->getBody(), FALSE, 512, \JSON_THROW_ON_ERROR);
  }

  /**
   * @throws \Drupal\mailchimp_subscribe\MailchimpClient\Exception\ApiException
   * @throws \JsonException
   */
  public function getListGroup($listId, $groupId, $offset = 0, $limit = 10) {
    $endpoint = sprintf('lists/%s/interest-categories/%s/interests', $listId, $groupId);

    $response = $this->get($endpoint, ['offset' => $offset, 'limit' => $limit]);

    if (NULL === $response) {
      throw new ApiException('Could not connect to API. Check your credentials.');
    }

    if (200 !== $response->getStatusCode()) {
      throw new ApiException('Could not fetch interest group from API.');
    }

    return json_decode((string) $response->getBody(), FALSE, 512, \JSON_THROW_ON_ERROR);
  }

  /**
   *
   */
  public function getSubscriberHash($email): string {
    return md5(strtolower($email));
  }

  /**
   *
   */
  public function getLastError(): ?object {
    return $this->lastError;
  }

}
