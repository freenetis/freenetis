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
 * The "Export_Member_Api" end point class that provides member data for export
 * at API path "/export/member".
 *
 * @Consumes(application/json)
 * @Produces(application/json)
 */
class Export_Member_Api
{

	/**
	 * @GET
	 * @Path(/{id:\d+})
	 */
	public function get_member($id)
	{
		$member = new Member_Model($id);
		$user_id = $member->get_main_user();
		$user = new User_Model($user_id);

		$phones = $this->get_contacts($user_id, Contact_Model::TYPE_PHONE);
		$emails = $this->get_contacts($user_id, Contact_Model::TYPE_EMAIL);

		return array
		(
			'member' => $member->as_array(),
			'user' => self::user_to_array($user),
			'address_point' => self::ap_to_array($member->address_point),
			'address_point_dom' => self::ap_to_array($member->members_domicile->address_point),
			'emails' => $emails,
			'phones' => $phones
		);
	}
	
	private function get_contacts($user_id, $contact_type) {
		$model = new Contact_Model();
		$contacts = $model->find_all_users_contacts($user_id, $contact_type);
		$values = array();
		foreach ($contacts as $p)
		{
			$values[] = $p->value;
		}
		return $values;
	}
	
	private static function ap_to_array($ap)
	{
		if (!$ap->id)
		{
			return NULL;
		}

		return array
		(
			'country' => $ap->country->country_name,
			'town' => $ap->town->town,
			'quarter' => $ap->town->quarter,
			'zip_code' => $ap->town->zip_code,
			'street' => $ap->street->street,
			'street_number' => $ap->street_number
		);
	}

	private static function user_to_array($user)
	{
		return array
			(
			'id' => $user->id,
			'type' => $user->type,
			'login' => $user->login,
			'member_id' => $user->member_id,
			'pre_title' => $user->pre_title,
			'name' => $user->name,
			'middle_name' => $user->middle_name,
			'surname' => $user->surname,
			'post_title' => $user->post_title,
			'birthday' => $user->birthday,
			'comment' => $user->comment
		);
	}

}
