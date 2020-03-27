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

    private $requiredEnvVariables = ['SHOP_SYSTEM', 'SHOP_SYSTEM_CONTAINER_NAME'];

    /**
     * Environment constructor.
     * @param $environment
     */
    public function __construct()
    {
        $environment = $_ENV;
        foreach ( $this->requiredEnvVariables as $var) {
            try {
                $this->env[$var] = $environment[$var];
            } catch (Exception $e) {
                $this->env[$var] = '';
            }
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