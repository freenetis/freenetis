<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is release under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * Vote
 * 
 * @package Model
 * 
 * @property integer $user_id
 * @property User_Model $user
 * @property integer $type
 * @property integer $fk_id
 * @property integer $aro_group_id
 * @property integer $priority
 * @property integer $this
 * @property datetime $time
 * @property string $comment
 */
class Vote_Model extends ORM
{
	// Vote object types
	
	/** Type of vote for a work */
	const WORK	= 1;
	
	/** Type of vote for work report */
	const WORK_REPORT = 2;
	
	/** Type of vote to request */
	const REQUEST	= 3;
	
	// Types of vote
	
	/** abstain */
	const ABSTAIN	= 0;
	
	/** agree */
	const AGREE	= 1;
	
	/** disagree */
	const DISAGREE	= -1;
	
	/** new item, one votes about it, can be edited */
	const STATE_NEW = 0;
	
	/** open item, approval already started, cannot be edited */
	const STATE_OPEN = 1;
	
	/** rejected item */
	const STATE_REJECTED = 2;
	
	/** approved item */
	const STATE_APPROVED = 3;
	
	/**
	 * Vote options
	 *
	 * @var array
	 */
	private static $vote_options = array
	(
		self::AGREE		=> 'Agree',
		self::DISAGREE	=> 'Disagree',
		self::ABSTAIN	=> 'Abstain',
	);
	
	// state names
	private static $states = array
	(
		self::STATE_NEW			=> 'New',
		self::STATE_OPEN		=> 'Open',
		self::STATE_REJECTED	=> 'Rejected',
		self::STATE_APPROVED	=> 'Approved'
	);
	
	// state colors
	private static $state_colors = array
	(
		self::STATE_NEW			=> 'black',
		self::STATE_OPEN		=> 'black',
		self::STATE_REJECTED	=> 'red',
		self::STATE_APPROVED	=> 'green'
	);
	
	protected $belongs_to = array('user');
	
	/**
	 * Returns vote options
	 * 
	 * @author Michal Kliment
	 * @param integer $type Type of item to vote
	 * @param bool $is_own Whether vote about own item (e.g. work)
	 * @param bool $none_item Add NULL 'none' item as first empty vote?
	 * @return array Vote options
	 */
	public static function get_vote_options($type = NULL, $is_own = NULL, $none_item = FALSE)
	{
		$options = array();
		
		// @see Approval_types_Controller
		if ($none_item)
		{
			$options = array(NULL => __('None'));
		}
		
		foreach (self::$vote_options as $key => $value)
		{
			if ($type == self::WORK && $is_own && $key != self::ABSTAIN)
				continue;
			
			$options[$key] = __($value);
		}
		
		return $options;
	}
	
	/**
	 * Returns vote option name
	 * 
	 * @author Michal Kliment
	 * @param integer $option
	 * @param bool $none_item Add NULL 'none' item as first empty vote?
	 * @return string
	 */
	public static function get_vote_option_name ($option, $none_item = FALSE)
	{
		if (array_key_exists($option, self::$vote_options))
		{
			return __(self::$vote_options[$option]);
		}
		else if ($none_item && $option == NULL) // @see Approval_types_Controller
		{
			return __('None');
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Returns all states
	 * 
	 * @author Michal Kliment
	 * @return array
	 */
	public static function get_states()
	{
		$states = array();
		
		foreach (self::$states as $key => $value)
		{
			$states[$key] = __($value);
		}
		
		return $states;
	}
	
	/**
	 * Returns state name
	 * 
	 * @author Michal Kliment
	 * @param integer $option
	 * @return string
	 */
	public static function get_state_name ($state)
	{
		if (array_key_exists($state, self::$states))
		{
			return __(self::$states[$state]);
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Returns state color
	 * 
	 * @author Michal Kliment
	 * @param integer $option
	 * @return string
	 */
	public static function get_state_color ($state)
	{
		if (array_key_exists($state, self::$state_colors))
		{
			return __(self::$state_colors[$state]);
		}
		else
		{
			return '';
		}
	}


	/**
	 * Get all wotes by work
	 * 
	 * @param integer $work_id 
	 * @return Mysql_Result
	 */
	public function get_all_votes_by_work($work_id)
	{
		return $this->db->query("
				SELECT * FROM votes v
				WHERE v.type = ? AND v.fk_id = ?
		", self::WORK, $work_id);
	}
	
	/**
	 * Check if user voted about object
	 *
	 * @param integer $user_id	User's ID
	 * @param integer $fk_id	ID of object
	 * @param integer $type		Type of voted object
	 * @return boolean
	 */
	public function has_user_voted_about($user_id, $fk_id, $type)
	{
		return $this->db->query("
				SELECT COUNT(*) AS count
				FROM votes
				WHERE user_id = ? AND fk_id = ? AND type = ?
		", $user_id, $fk_id, $type)->current()->count > 0;
	}
	
	/**
	 * Function to get state of work/work report/request approval from all votes
	 * by approval template and type.
	 * 
	 * @author Michal Kliment, Ondřej Fibich
	 * @param object $object
	 * @param integer $approval_type_id Check only for a single approval type
	 * @return integer
	 */
	public static function get_state($object, $approval_type_id = NULL)
	{
		// check param
		if (!$object->id)
		{
			throw new Exception('Invalid call of method, object has to be loaded.');
		}
		
		// get type of object
		if ($object instanceof Job_Model || $object instanceof Job_report_Model)
		{
			$type = self::WORK;
		}
		else if ($object instanceof Request_Model)
		{
			$type = self::REQUEST;
		}
		else
		{
			throw new Exception('Invalid loaded object: '.get_class($object));
		}
		
		$approval_template_item_model = new Approval_template_item_Model();
		$groups_aro_map_model = new Groups_aro_map_Model();
		$vote_model = new Vote_Model();
		
		$votes_count = $vote_model->where('type', $type)
				->where('type', $type)
				->where('fk_id', $object->id)
				->count_all();

		// 0 votes - vote not yet started
		if (!$votes_count)
		{
			return self::STATE_NEW;
		}

		// select approval types by priority in order to provide hierarchy of voting
		if (!empty($approval_type_id))
		{
			$approval_template_items = $approval_template_item_model
					->where('approval_template_id', $object->approval_template_id)
					->where('approval_type_id', $approval_type_id)
					->orderby('priority', 'desc')
					->find_all();
		}
		else
		{
			$approval_template_items = $approval_template_item_model
					->where('approval_template_id', $object->approval_template_id)
					->orderby('priority', 'desc')
					->find_all();
		}
		
		// ensures that each user votes only once even if he is in multiple groups
		$arr_users = array();
		// return state of voting (by default voting is set up as approved)
		$state = self::STATE_APPROVED;
		// get suggested amount for selecting only approval types at this limit
		$suggest_amount = $object->get_suggest_amount();

		// go thought votes for each approval type
		foreach ($approval_template_items as $approval_template_item)
		{
			$at = $approval_template_item->approval_type;
			
			// filter unecessery voting under limit
			if (!$suggest_amount || $at->min_suggest_amount <= $suggest_amount)
			{
				// count of all users that are allow to vote without those 
				// who already voted in previous approval types
				$count = 0;
				// count of made votes
				$total_votes = 0;
				// count of agree votes
				$agree = 0;
				// count of abstain votes
				$abstain = 0;
				// count of rejected votes
				$disagree = 0;
				
				// all users of group
				$users = $groups_aro_map_model->get_all_users_by_group_id(
						$at->aro_group_id
				);
				
				// check vote of each user in group
				foreach ($users as $user)
				{
					// user vote was already checked
					if (in_array($user->id, $arr_users))
					{
						continue;
					}
					
					$arr_users[] = $user->id;
					$count++;

					$vote = $vote_model->where('user_id', $user->id)
							->where('type', $type)
							->where('fk_id', $object->id)
							->find();

					// this user has not voted yet
					if (!$vote || !$vote->id)
					{
						// if interval not set up then all users must vote
						// otherwise an auto vote is accepted
						if (!$at->one_vote && !date::hour_diff($at->interval))
						{
							$state = self::STATE_OPEN;
						}
					}
					// user has voted
					else
					{
						$total_votes++;

						if ($at->type == Approval_type_Model::SIMPLE &&
							$vote->vote == self::ABSTAIN)
						{
							$abstain++;
						}

						if ($vote->vote == self::AGREE)
						{
							$agree++;
						}

						if ($vote->vote == self::DISAGREE)
						{
							$disagree++;
						}
					}
				}

				// no votes in this group, skip
				if (!$count)
				{
					continue;
				}

				// all users has voted
				if ($count == $total_votes)
				{
					// we dont care about abstain votes
					$total_votes -= $abstain;

					// calculate ration between agree votes and disagree votes 
					// with agree votes
					$percent = ($total_votes) ? round($agree/$total_votes*100, 2) : 0;

					// rejected?
					if ($percent < $at->majority_percent)
					{
						return self::STATE_REJECTED;
					}
				}
				// not all user has voted
				else
				{
					// if one_vote or interval set up then an auto vote is accepted
					if ($at->one_vote || date::hour_diff($at->interval))
					{
						// we dont care about abstain votes
						$count -= $abstain;

						// calculate ratio between agree votes and count of users
						$percent = ($count) ? round($agree/$count*100, 2) : 0;

						// not approved by majority?
						if ($percent < $at->majority_percent)
						{
							// auto vote - all unmade or abstain votes are
							// used as agreed votes
							$agree_with_abstain = $agree + ($count - $total_votes + $abstain);

							// calculate ratio 
							$percent = ($count) ? round($agree_with_abstain/$count*100, 2) : 0;

							// no approved even with abstain votes?
							if ($percent < $at->majority_percent)
							{
								return self::STATE_REJECTED;
							}
							else
							{
								// if one_vote enabled then voting may be 
								// rejected or accepted by a one vote
								if ($at->one_vote &&
									$total_votes > 0 && $total_votes != $abstain)
								{
									// most voted for rejecting?
									if ($agree < $disagree)
									{
										$state = self::STATE_REJECTED;
									}
									// if equal votes then continue in voting
									else if ($agree == $disagree)
									{
										$state = self::STATE_OPEN;
									}
									// there is no code for accepting because 
									// it is a default state
								}
								// else voting is still open
								else
								{
									$state = self::STATE_OPEN;
								}
							}
						}
					}
					// some users must vote until then voting is open
					else
					{
						$state = self::STATE_OPEN;
					}
				}
			}
		}
		
		return $state;
	}
	
	/**
	 * Returns all items (works, work reports and requests) to which user can vote
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @return array
	 * 
	 * @todo this is not working on hierarchical voting!!!!!!!!!!!!
	 */
	public function get_all_items_user_can_vote($user_id)
	{	
		$items = $this->db->query("
			SELECT i.*
			FROM groups_aro_map gam
			JOIN approval_types at ON at.aro_group_id = gam.group_id
			JOIN approval_template_items ati ON ati.approval_type_id = at.id
			JOIN
			(
					SELECT
						? AS type,
						j.id AS fk_id,
						suggest_amount,
						approval_template_id,
						id, state
					FROM jobs j
					WHERE state = ? OR state = ?
				UNION
					SELECT
						? AS type,
						r.id AS fk_id,
						suggest_amount,
						approval_template_id,
						id, state
					FROM requests r
					WHERE state = ? OR state = ?
			) i
			ON i.approval_template_id = ati.approval_template_id AND
				(i.suggest_amount >= at.min_suggest_amount)
			WHERE gam.aro_id = ?
		", array
		(
			self::WORK,
			self::STATE_NEW,
			self::STATE_OPEN,
			self::REQUEST,
			self::STATE_NEW,
			self::STATE_OPEN,
			$user_id
		));
		
		$ati = new Approval_template_item_Model();
		$result = array();
		
		foreach ($items as $item)
		{
			if (!array_key_exists($item->type, $result))
			{
				$result[$item->type] = array();
			}
			
			// another check (SQL command cannot handle hierarchical approval)
			if ($ati->check_user_vote_rights(
					ORM::factory($item->type == self::WORK ? 'job' : 'request', $item->id),
					$item->type, $user_id,
					$item->suggest_amount
				))
			{
				$result[$item->type][] = $item->fk_id;
			}
		}
		
		return $result;
	}
	
	/**
	 * Inserts new vote
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @param integer $type
	 * @param integer $fk_id
	 * @param integer $vote
	 * @param string $comment
	 * @param integer $aro_group_id
	 * @param datetime $time
	 * @return Vote_Model
	 */
	public static function insert (
		$user_id, $type, $fk_id, $vote, $comment, $aro_group_id,
		$time = NULL)
	{
		if (!$time)
			$time = date('Y-m-d H:i:s');
		
		$vote_model = new Vote_Model();
		$vote_model->user_id		= $user_id;
		$vote_model->type		= $type;
		$vote_model->fk_id		= $fk_id;
		$vote_model->aro_group_id	= $aro_group_id;
		$vote_model->vote		= $vote;
		$vote_model->time		= $time;
		$vote_model->comment		= $comment;
		$vote_model->save_throwable();
		
		return $vote_model;
	}
	
	/**
	 * Removes vote
	 * 
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @param integer $type
	 * @param integer $fk_id
	 * @return type
	 */
	public function remove_vote ($user_id, $type, $fk_id)
	{
		return $this->db->query("
			DELETE FROM votes
			WHERE user_id = ? AND type = ? AND fk_id = ?
		", array($user_id, $type, $fk_id));
	}
}
