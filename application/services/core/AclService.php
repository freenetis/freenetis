<?php

/*
 *  This file is part of open source system FreenetIS
 *  and it is release under GPLv3 licence.
 *
 *  More info about licence can be found:
 *  http://www.gnu.org/licenses/gpl-3.0.html
 *
 *  More info about project can be found:
 *  http://www.freenetis.org/
 */

namespace freenetis\service\core;

/**
 * Service that handles access security check (ACL).
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.2
 */
class AclService extends \AbstractService
{
    /**
     * @var \Groups_aro_map_Model
     */
    private static $resolver = NULL;

    /**
     * Creates service.
     *
     * @param \ServiceFactory $factory
     */
    public function __construct(\ServiceFactory $factory)
    {
        parent::__construct($factory);
        // singleton resolver instance
        if (empty(self::$resolver))
        {
            self::$resolver = new \Groups_aro_map_Model();
        }
    }

    /**
	 * Checks user's access to system.
	 *
	 * @param type $axo_section AXO section value
	 * @param type $axo_value AXO value
	 * @param type $aco_type ACO type of action (view, new, edit, delete)
	 * @param integer $member_id Member who ask for access
	 * @param boolean $force_own Force to use own rules for not logged user
	 * @return bool
	 */
	private function can($axo_section, $axo_value, $aco_type,
            $member_id = NULL, $force_own = FALSE)
	{
		// check own?
		if (($member_id == $_SESSION['member_id']) || $force_own)
		{
			// check own access
			if (self::$resolver->has_access(
					$_SESSION['user_id'], $aco_type . '_own',
					$axo_section, $axo_value
				))
			{
				// access valid
				return true;
			}
		}

		// check all
		return self::$resolver->has_access(
				$_SESSION['user_id'], $aco_type . '_all',
				$axo_section, $axo_value
		);
	}

	/**
	 * Checks if user is in ARO group.
	 *
	 * @param integer $aro_group_id	ARO group ID
	 * @param integer $aro_id User ID
	 * @return boolean true if exists false otherwise
	 */
	public function is_user_in_group($aro_group_id, $aro_id)
	{
		return self::$resolver->groups_aro_map_exists($aro_group_id, $aro_id);
	}

	/**
	 * Fuction that checks access rights for viewing of objects protected by
     * passed AXOs for current logged user that.
     * <p>
     * There are two types of access:
     * <ul>
     * <li>own - passed member ID is owner of this object and all its user may
     *  have access,
     * <li>all - all users may be accessed.
     * </ul>
	 *
	 * @param $axo_section AXO section name
	 * @param $axo_value ACO value
	 * @param $member_id Object owner ID [optional]
	 * @param boolean $force_own Force to use own rules for not logged user
     *      [optional]
	 */
	public function can_view($axo_section, $axo_value, $member_id = NULL,
            $force_own = FALSE)
	{
		return $this->can($axo_section, $axo_value, 'view', $member_id, $force_own);
	}

	/**
	 * Fuction that checks access rights for editing of objects protected by
     * passed AXOs for current logged user that.
     * <p>
     * There are two types of access:
     * <ul>
     * <li>own - passed member ID is owner of this object and all its user may
     *  have access,
     * <li>all - all users may be accessed.
     * </ul>
	 *
	 * @param $axo_section AXO section name
	 * @param $axo_value ACO value
	 * @param $member_id Object owner ID [optional]
	 * @param boolean $force_own Force to use own rules for not logged user
     *      [optional]
	 */
	public function can_edit($axo_section, $axo_value, $member_id = NULL,
            $force_own = FALSE)
	{
		return $this->can($axo_section, $axo_value, 'edit', $member_id, $force_own);
	}

	/**
	 * Fuction that checks access rights for creating of objects protected by
     * passed AXOs for current logged user that.
     * <p>
     * There are two types of access:
     * <ul>
     * <li>own - passed member ID is owner of this object and all its user may
     *  have access,
     * <li>all - all users may be accessed.
     * </ul>
	 *
	 * @param $axo_section AXO section name
	 * @param $axo_value ACO value
	 * @param $member_id Object owner ID [optional]
	 * @param boolean $force_own Force to use own rules for not logged user
     *      [optional]
	 */
	public function can_create($axo_section, $axo_value, $member_id = NULL,
            $force_own = FALSE)
	{
		return $this->can($axo_section, $axo_value, 'new', $member_id, $force_own);
	}

	/**
	 * Fuction that checks access rights for deletion of objects protected by
     * passed AXOs for current logged user that.
     * <p>
     * There are two types of access:
     * <ul>
     * <li>own - passed member ID is owner of this object and all its user may
     *  have access,
     * <li>all - all users may be accessed.
     * </ul>
	 *
	 * @param $axo_section AXO section name
	 * @param $axo_value ACO value
	 * @param $member_id Object owner ID [optional]
	 * @param boolean $force_own Force to use own rules for not logged user
     *      [optional]
	 */
	public function can_delete($axo_section, $axo_value, $member_id = NULL,
            $force_own = FALSE)
	{
		return $this->can($axo_section, $axo_value, 'delete', $member_id, $force_own);
	}
}
