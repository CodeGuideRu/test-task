<?php declare(strict_types=1);

namespace src\Integration;

/**
 * DataProvider
 */
class DataProvider
{
  /** @var string */
  private $host;

  /** @var string */
  private $user;

  /** @var string */
  private $password;

  /**
   * DataProvider constructor.
   *
   * @param string $host
   * @param string $user
   * @param string $password
   */
  public function __construct(string $host, string $user, string $password)
  {
    $this->host = $host;
    $this->user = $user;
    $this->password = $password;
  }

  /**
   * @param array $input
   *
   * @return array
   */
  public function get(array $input): ?array
  {
    // TODO: Implement get() method.

    return null;
  }
}
