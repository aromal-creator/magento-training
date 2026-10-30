<?php

namespace Codilar\AdminUser\Setup\Patch\Data;

use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\UserFactory;

/**
 * Creates a "demoadmin" admin user with the Administrators role.
 *
 * The password is saved through the User model, so it is hashed like a user
 * created in System > Permissions > All Users.
 */
class CreateDemoAdminUser implements DataPatchInterface, PatchRevertableInterface
{
    private const USERNAME = 'demoadmin';
    private const ROLE_NAME = 'Administrators';

    private UserFactory $userFactory;
    private UserResource $userResource;
    private RoleCollectionFactory $roleCollectionFactory;

    public function __construct(
        UserFactory $userFactory,
        UserResource $userResource,
        RoleCollectionFactory $roleCollectionFactory
    ) {
        $this->userFactory = $userFactory;
        $this->userResource = $userResource;
        $this->roleCollectionFactory = $roleCollectionFactory;
    }

    public function apply()
    {
        $user = $this->userFactory->create();
        $this->userResource->load($user, self::USERNAME, 'username');
        if ($user->getId()) {
            return $this;
        }

        $user->setData([
            'firstname' => 'Demo',
            'lastname' => 'Admin',
            'email' => 'demoadmin@example.com',
            'username' => self::USERNAME,
            'password' => 'Demo@12345',
            'interface_locale' => 'en_US',
            'is_active' => 1,
        ]);
        // Adds the user under the Administrators role (full access)
        $user->setRoleId($this->getAdministratorsRoleId());

        $this->userResource->save($user);

        return $this;
    }

    public function revert()
    {
        $user = $this->userFactory->create();
        $this->userResource->load($user, self::USERNAME, 'username');
        if ($user->getId()) {
            $this->userResource->delete($user);
        }
    }

    private function getAdministratorsRoleId(): int
    {
        $role = $this->roleCollectionFactory->create()
            ->addFieldToFilter('role_name', self::ROLE_NAME)
            ->addFieldToFilter('role_type', 'G')
            ->getFirstItem();

        return (int) $role->getId();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
