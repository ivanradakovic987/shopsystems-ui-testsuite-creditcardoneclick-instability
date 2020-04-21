<?php


namespace Helper\Config;


use PHPUnit\Exception;

/**
 * Class Environment
 * @package Helper\Config
 */
class Environment
{

    /**
     * @var
     */
    private $env;

    /**
     * Environment constructor.
     */
    public function __construct()
    {
        // phpcs:disable
        $environment = $_ENV;
        print_r($environment);
        // phpcs:enable
        $this->env['SHOP_SYSTEM'] = $environment['SHOP_SYSTEM'];
    }

    /**
     * @return array
     */
    public function getEnv(): array
    {
        return $this->env;
    }
}
