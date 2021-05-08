<?php


namespace Richard87\ApiRoute\Tests\resources\src\Entity\Dto;


class ResetPassword
{
    public string $oldPassword;
    public string $newPassword;
    public string $repeatPassword;
}