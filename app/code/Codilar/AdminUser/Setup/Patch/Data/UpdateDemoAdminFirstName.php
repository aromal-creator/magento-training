<?php

namespace Codilar\AdminUser\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\UserFactory;

/**
 * Changes the first name of the "demoadmin" admin user from Demo to Codilar.
 */
class UpdateDemoAdminFirstName implements DataPatchInterface, PatchRevertableInterface
{
    private const USERNAME = 'demoadmin';
    private const OLD_FIRSTNAME = 'Demo';
    private const NEW_FIRSTNAME = 'Codilar';

    private UserFactory $userFactory;
    private UserResource $userResource;

    public function __construct(
        UserFactory $userFactory,
        UserResource $userResource
    ) {
        $this->userFactory = $userFactory;
        $this->userResource = $userResource;
    }

    public function apply()
    {
        $this->setFirstName(self::NEW_FIRSTNAME);

        return $this;
    }

    public function revert()
    {
        $this->setFirstName(self::OLD_FIRSTNAME);
    }

    private function setFirstName(string $firstName): void
    {
        $user = $this->userFactory->create();
        $this->userResource->load($user, self::USERNAME, 'username');
        if (!$user->getId()) {
            return;
        }

        $user->setFirstname($firstName);
        $this->userResource->save($user);
    }

    /**
     * The user must be created before it can be updated.
     */
    public static function getDependencies()
    {
        return [CreateDemoAdminUser::class];
    }

    public function getAliases()
    {
        return [];
    }
}
