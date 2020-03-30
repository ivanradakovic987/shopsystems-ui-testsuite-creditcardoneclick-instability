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
        // phpcs:enable
        $this->env['SHOP_SYSTEM'] = $environment['SHOP_SYSTEM'];
        $this->env['SHOP_SYSTEM_CONTAINER_NAME'] = '';
        if ($this->env['SHOP_SYSTEM'] === 'magento2')
        {
            $this->env['SHOP_SYSTEM_CONTAINER_NAME'] = $environment['SHOP_SYSTEM_CONTAINER_NAME'];
        }
    }

    /**
     * @return array
     */
    public function getEnv(): array
    {
        return $this->env;
    }
}
