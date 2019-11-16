<?php declare(strict_types=1);

namespace src\Decorator;

use DateTime;
use Exception;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use src\Integration\DataProvider;
use function is_array;

/**
 * DecoratorManager
 */
class DecoratorManager
{
  /** @var DataProvider */
  private $dataProvider;

  /** @var CacheItemPoolInterface */
  private $cachePool;

  /** @var LoggerInterface */
  private $logger;

  /**
   * DecoratorManager constructor.
   *
   * @param DataProvider           $dataProvider
   * @param CacheItemPoolInterface $cachePool
   * @param LoggerInterface        $logger
   */
  public function __construct(
    DataProvider $dataProvider,
    CacheItemPoolInterface $cachePool,
    LoggerInterface $logger
  ) {
    $this->dataProvider = $dataProvider;
    $this->cachePool = $cachePool;
    $this->logger = $logger;
  }

  /**
   * @param array $input
   *
   * @return array|null
   */
  public function getResponse(array $input): ?array
  {
    $cacheItem = $this->getCacheItem($input);

    if ($cacheItem) {
      $response = $this->getResponseFromCache($cacheItem);

      if (is_array($response)) {
        return $response;
      }
    }

    $response = $this->dataProvider->get($input);

    if ($response === null) {
      $this->logger->critical('Response from data provider is null');

      return null;
    }

    if ($cacheItem) {
      $this->saveResponseToCache($cacheItem, $response, '+1 day');
    }

    return $response;
  }

  /**
   * @param array $input
   *
   * @return CacheItemInterface|null
   */
  private function getCacheItem(array $input): ?CacheItemInterface
  {
    $cacheKey = $this->getCacheKey($input);

    if ($cacheKey === null) {
      return null;
    }

    try {
      $cacheItem = $this->cachePool->getItem($cacheKey);
    } catch (InvalidArgumentException $e) {
      $this->logger->critical($e);

      return null;
    }

    return $cacheItem;
  }

  /**
   * @param array $input
   *
   * @return string|null
   */
  private function getCacheKey(array $input): ?string
  {
    $json = json_encode($input);

    if ($json === false) {
      $this->logger->error('Json encode cache key failure');

      return null;
    }

    return md5($json);
  }

  /**
   * @param CacheItemInterface|null $cacheItem
   *
   * @return array|null
   */
  private function getResponseFromCache(CacheItemInterface $cacheItem): ?array
  {
    if (!$cacheItem->isHit()) {
      return null;
    }

    $response = $cacheItem->get();

    if ($response === null) {
      $this->logger->critical('Cached value is null');

      return null;
    }

    return $response;
  }

  /**
   * @param CacheItemInterface $cacheItem
   * @param array              $data
   *
   * @param string             $period
   *
   * @return bool
   */
  private function saveResponseToCache(CacheItemInterface $cacheItem, array $data, string $period): bool
  {
    try {
      $expiresDate = (new DateTime())->modify($period);
    } catch (Exception $e) {
      $this->logger->critical($e);

      return false;
    }

    $cacheItem
      ->set($data)
      ->expiresAt($expiresDate);

    return $this->cachePool->save($cacheItem);
  }
}
