<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

/**
 * Admin
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Admin extends EntityRepository
{
    /**
     * Count the number of admins.
     *
     * Return count of all admins.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->getEntityManager()->createQuery(
                "SELECT COUNT( a.id ) FROM \\Entities\\Admin a"
            )
            ->getSingleScalarResult();
    }

    /**
     * Finds all admins who are not assigned with domain.
     * 
     * Finds all admins and iterate through then making an array of 'id' => 'username'
     * If admin inactive username will be append by '(inactive)' then we iterate
     * through domain admins and removing all array elements which id is already in domain admins list.
     *
     * @param \Entities\Domain $domain Domain to look for admins
     * @retun array
     */
    public function getNotAssignedForDomain( $domain )
    {
        $adminNames = [];
        foreach( $this->findBy( [ "super" => false ] ) as $admin )
            $adminNames[ $admin->getId() ] = $admin->getActive() ? $admin->getUsername() : $admin->getUsername() . " (inactive)";

        foreach( $domain->getAdmins() as $admin )
            if( isset( $adminNames[ $admin->getId() ] ) )
                unset( $adminNames[ $admin->getId() ] );
        
        return $adminNames;
    }
}