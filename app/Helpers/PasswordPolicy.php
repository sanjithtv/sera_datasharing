<?php
// app/Helpers/PasswordPolicy.php
namespace App\Helpers;

use App\Models\SiteConfiguration;

class PasswordPolicy
{
    public static function validate($password): bool
    {
        $policy = SiteConfiguration::getValue('password_policy', 'medium');

        if ($policy === 'low') {
            return strlen($password) >= 6;
        }

        if ($policy === 'medium') {
            return preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $password);
        }

        if ($policy === 'high') {
            return preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{10,}$/', $password);
        }

        return true;
    }
}
