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
        $this->env['SHOP_SYSTEM'] = getenv('SHOP_SYSTEM');
        // phpcs:enable
    }

    /**
     * @return array
     */
    public function getEnv(): array
    {
        return $this->env;
    }
}
