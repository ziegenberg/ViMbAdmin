<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

/**
 * Alias
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Alias extends EntityRepository
{

	/**
	 * Loads aliases for mailbox.
	 *
	 * Selects aliases where address not equal to goto, and goto equals to 
	 * mailbox username, or all if include mailbox aliases is true. If admin 
	 * is not super then it checks if admin have have linked with domain.
	 *
	 * @param \Entities\Mailbox $mailbox Mailbox for alias filtering.
	 * @param \Entities\Admin   $admin   Admin for checking privileges.
	 * @param bool              $ima     If set to true, then it include and where address equals to goto.
	 * @return \Entities\Alias[]
	 */
	public function loadForMailbox( $mailbox, $admin, $ima = false )
	{
		$qb = $this->getEntityManager()->createQueryBuilder()
                ->select( 'a' )
                ->from( '\\Entities\\Alias', 'a' )
                ->where( 'a.goto = ?1' )
                ->setParameter( 1, $mailbox->getUsername() );

        if( !$ima )
            $qb->andWhere( 'a.address != a.goto' );

        if( !$admin->isSuper() )
        {
            $qb->leftJoin( 'a.Domain', 'd' )
                ->leftJoin( 'd.DomainAdmin', 'da' )
                ->andWhere( 'da.Admin = ?2' )
                ->leftJoin( 'd.Admins', 'd2a' )
                ->andWhere( 'd2a = ?2' )
                ->setParameter( 2, $admin );
        }

        return $qb->getQuery()->getResult();
	}

	/**
	 * Loads aliases with mailbox.
	 *
	 * Selects aliases where address not equal to goto, and goto not equals to 
	 * mailbox username, but goto has mailbox username. If admin is not super 
	 * then it checks if admin have have linked with domain.
	 *
	 * @param \Entities\Mailbox $mailbox Mailbox for alias filtering.
	 * @param \Entities\Admin   $admin   Admin for checking privileges.
	 * @return \Entities\Alias[]
	 */
	public function loadWithMailbox( $mailbox, $admin ) 
	{
		$qb = $this->getEntityManager()->createQueryBuilder()
                ->select( 'a' )
                ->from( '\\Entities\\Alias', 'a' )
                ->where( 'a.address != a.goto' )
                ->andWhere( 'a.goto != ?1' )
                ->andWhere( 'a.goto like ?2' )
                ->setParameter( 1, $mailbox->getUsername() )
                ->setParameter( 2, '%' . $mailbox->getUsername() . '%');

        if( !$admin->isSuper() )
        {
            $qb->leftJoin( 'a.Domain', 'd' )
                ->leftJoin( 'd.DomainAdmin', 'da' )
                ->andWhere( 'da.username = ?3' )
                ->setParameter( 3, $admin->getUsername() );
        }

        return $qb->getQuery()->getResult();
	}

	/**
     * Load aliases for alias list .
     *
     * Loads aliases for alias list, if admin is not super, it will select aliases
     * only from domains which are linked with admin. If domain is set it will select
     * aliases only for given domain, and if ima set to false then it will select all
     * aliases where goto not equal to address that mean non mailbox aliases. 
     *
     * @param \Entities\Admin  $admin  Admin for filtering mailboxes.
     * @param \Entities\Domain $domain Domain for filtering mailboxes.
     * @param bool             $ima    Include mailbox aliases flag.
     * @return array
     */
    public function loadForAliasList( $admin, $domain = null, $ima = false )
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select( 'a.id as id , a.address as address, a.goto as goto, a.active as active, d.domain as domain' )
            ->from( '\\Entities\\Alias', 'a' )
            ->join( 'a.Domain', 'd' );
     
        if( !$admin->isSuper() )
            $qb->join( 'd.Admins', 'd2a' )
                ->where( 'd2a = ?1' )
                ->setParameter( 1, $admin );
        
        if( $domain )
            $qb->andWhere( 'a.Domain = ?2' )
                ->setParameter( 2, $domain );

        if( !$ima )
        	$qb->andWhere( "a.address != a.goto" );
        return $qb->getQuery()->getArrayResult();
    }
    
    /**
     * Return filtered alias data array
     *
     * Use filter to filter aliases by address or goto or domain. If filter
     * starts with * it will be replaced with % to meet sql requirements. At 
     * the end % will be added to all strings. So filter 'man' will bicome
     * 'man%' and will look for man, manual and iffilter '*man' it wil bicome
     * '%man%' and will look for records like human, humanity, man, manual.
     *
     * @param string           $filter Flter for mailboxes 
     * @param \Entities\Admin  $admin  Admin for filtering mailboxes.
     * @param \Entities\Domain $domain Domain for filtering mailboxes.
     * @param bool             $ima    Include mailbox aliases flag.
     * @return array
     */
    public function filterForAliasList( $filter, $admin, $domain = null,  $ima = false  )
    {
        $filter = str_replace ( "'" , "" , $filter );
        
        if( strpos( $filter, "*" ) === 0 )
            $filter = '%' . substr( $filter, 1 );
        
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select( 'a.id as id , a.address as address, a.goto as goto, a.active as active, d.domain as domain' )
            ->from( '\\Entities\\Alias', 'a' )
            ->join( 'a.Domain', 'd' )
            ->where( "( a.goto LIKE '{$filter}%' OR a.address LIKE '{$filter}%' OR d.domain LIKE '{$filter}%')" );
        
        if( !$admin->isSuper() )
            $qb->join( 'd.Admins', 'd2a' )
                ->andWhere( 'd2a = ?1' )
                ->setParameter( 1, $admin );

        if( $domain )
            $qb->andWhere( 'm.Domain = ?2' )
                ->setParameter( 2, $domain );
                
        if( !$ima )
        	$qb->andWhere( "a.address != a.goto" );
        	
        return $qb->getQuery()->getArrayResult();  
    }
}
