<?php

/*
+---------------------------------------------------------------------------+
| Revive Adserver                                                           |
| http://www.revive-adserver.com                                            |
|                                                                           |
| Copyright: See the COPYRIGHT.txt file.                                    |
| License: GPLv2 or later, see the LICENSE.txt file.                        |
+---------------------------------------------------------------------------+
*/

/**
 * Password recovery DAL for Openads
 *
 * @package OpenXDal
 */

require_once MAX_PATH . '/lib/OA/Dal.php';
require_once MAX_PATH . '/lib/max/Plugin.php';

class OA_Dal_PasswordRecovery extends OA_Dal
{
    /**
     * Search users with a matching email address
     *
     * @param string e-mail
     * @return array matching users
     */
    public function searchMatchingUsers($email)
    {
        $doUsers = OA_Dal::factoryDO('users');
        $doUsers->email_address = $email;
        $doUsers->find();

        $users = [];
        while ($doUsers->fetch()) {
            $users[] = $doUsers->toArray();
        }

        return $users;
    }

    public function searchNewUsersById(int $userId): array
    {
        /** @var DataObjects_Users|false $doUser */
        $doUser = OA_Dal::staticGetDO('users', $userId);

        if (!$doUser) {
            return [];
        }

        if ('' !== $doUser->password) {
            // Not a new user
            return [];
        }

        return [$doUser->toArray()];
    }

    /**
     * Generate and save a recovery ID for a user
     *
     * @param int user ID
     * @return string generated recovery ID
     */
    public function generateRecoveryId($userId)
    {
        $doPwdRecovery = OA_Dal::factoryDO('password_recovery');
        
        // Make sure that recoveryId is unique in password_recovery table
        do {
            $recoveryId = strtoupper(bin2hex(random_bytes(12)));
            $recoveryId = substr(chunk_split($recoveryId, 8, "-"), 0, -1);
            $doPwdRecovery->recovery_id = $recoveryId;
        } while ($doPwdRecovery->find() > 0);

        $doPwdRecovery = OA_Dal::factoryDO('password_recovery');
        $doPwdRecovery->whereAdd('user_id = ' . DBC::makeLiteral($userId));
        $doPwdRecovery->delete(true);

        $doPwdRecovery = OA_Dal::factoryDO('password_recovery');
        $doPwdRecovery->user_type = 'user';
        $doPwdRecovery->user_id = $userId;
        $doPwdRecovery->recovery_id = $recoveryId;
        $doPwdRecovery->updated = OA::getNowUTC();

        $doPwdRecovery->insert();

        return $recoveryId;
    }

    /**
     * Get Users DO for recovery ID
     */
    public function getUserFromRecoveryId(string $recoveryId): ?DataObjects_Users
    {
        if (empty($recoveryId)) {
            return null;
        }

        /** @var DataObjects_Users $doUsers */
        $doUsers = OA_Dal::factoryDO('users');

        $doPwdRecovery = OA_Dal::factoryDO('password_recovery');
        $doPwdRecovery->recovery_id = $recoveryId;

        $doUsers->joinAdd($doPwdRecovery);

        $oneHourBack = (new \DateTimeImmutable())->modify('-1 hour')->getTimestamp();
        $oneWeekBack = (new \DateTimeImmutable())->modify('-1 week')->getTimestamp();

        $doPwdRecovery->whereAdd("updated >= IF(password = '', $oneWeekBack, $oneHourBack)");

        if (!$doUsers->find(true)) {
            return null;
        }

        return $doUsers;
    }

    /**
     * Save the new password in the user properties
     *
     * @param string user ID
     * @param string recovery ID
     * @param string new password
     */
    public function saveNewPasswordAndLogin($userId, $recoveryId, $password)
    {
        $doPlugin = OA_Auth::staticGetAuthPlugin();
        $doPlugin->setNewPassword($userId, $password);

        $doPwdRecovery = OA_Dal::factoryDO('password_recovery');
        $doPwdRecovery->recovery_id = $recoveryId;
        $doPwdRecovery->delete();

        phpAds_SessionStart();
        $doUser = OA_Dal::staticGetDO('users', $userId);
        phpAds_SessionDataRegister(OA_Auth::getSessionData($doUser));
        phpAds_SessionDataStore();
    }
}
