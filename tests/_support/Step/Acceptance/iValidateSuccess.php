<?php

namespace Step\Acceptance;

/**
 * Interface iValidateSuccess
 * @package Helper
 */
interface iValidateSuccess
{
    /**
     * @return mixed
     */
    public function validateSuccessPage();

    /**
     * @return mixed
     */
    public function validateTransactionInDatabase();
}