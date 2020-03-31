<?php


namespace Drupal\vipps_recurring_payments_commerce\Service;


use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;

class CacheManager {

  private $cacheBackend;

  public function __construct(CacheBackendInterface $cacheBackend) {
    $this->cacheBackend = $cacheBackend;
  }

  public function execute(string $cacheId, array $cacheTags, callable $function) {
    if ($cache = $this->cacheBackend->get($cacheId)) {
      return $cache->data;
    }

    $data = call_user_func($function);

    $this->cacheBackend->set($cacheId, $data,Cache::PERMANENT, $cacheTags);

    return $data;
  }

}