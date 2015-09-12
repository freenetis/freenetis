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

use \phpaxrs\http\ResponseBuilder;

/**
 * The "Network_Link_Api" end point class that provides data from links
 * at API path "/network/link".
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @Consumes(application/json)
 * @Produces(application/json)
 */
class Network_Link_Api
{
    /**
     * @GET
     */
    public function get_all()
    {
        return ORM::factory('link')->find_all()->as_arrays();
    }

    /**
     * @GET
     * @Path(/list)
     */
    public function get_list()
    {
        return ORM::factory('link')->select_list();
    }

    /**
     * @GET
     * @Path(/{type}/list)
     */
    public function get_list_of_type($medium)
    {
        $mediums = array();
        switch (strtolower($medium))
        {
            case 'roaming':
                $mediums[] = Link_Model::MEDIUM_ROAMING;
                break;
            case 'air':
                $mediums[] = Link_Model::MEDIUM_AIR;
                break;
            case 'fiber':
                $mediums[] = Link_Model::MEDIUM_SINGLE_FIBER;
                $mediums[] = Link_Model::MEDIUM_MULTI_FIBER;
                break;
            case 'cable':
                $mediums[] = Link_Model::MEDIUM_CABLE;
                break;
            default:
                return ResponseBuilder::not_found();
        }
        return ORM::factory('link')
                ->in('medium', $mediums)
                ->select_list();
    }

    /**
     * @GET
     * @Path(/{id:\d+})
     */
    public function get_item($id)
    {
        $link = ORM::factory('link', $id);
        if ($link->id)
        {
            $alink = $link->as_array();
            // hateoas links
            $alink['_links'] = array
            (
                'app_view' => array
                (
                    'href' => url::base() . '{lang}/links/show/' . $link->id,
                    'parameters' => array('lang')
                )
            );
            return $alink;
        }
        return ResponseBuilder::not_found();
    }

    /**
     * @GET
     * @Path(/subnets)
     */
    public function get_items_subnets()
    {
        $ids = filter_input(INPUT_GET, 'ids');
        if (empty($ids))
        {
            return ResponseBuilder::bad_request('empty ids argument');
        }
        $ids_pars = array_map('intval', explode(',', $ids));
        return ORM::factory('link')->get_subnets_on_link($ids_pars)->as_array();
    }

    /**
     * @GET
     * @Path(/vlans)
     */
    public function get_items_vlans()
    {
        $ids = filter_input(INPUT_GET, 'ids');
        if (empty($ids))
        {
            return ResponseBuilder::bad_request('empty ids argument');
        }
        $ids_pars = array_map('intval', explode(',', $ids));
        return ORM::factory('link')->get_vlans_on_link($ids_pars)->as_array();
    }

    /**
     * @GET
     * @Path(/count)
     */
    public function count()
    {
        return array
        (
            'count' => ORM::factory('link')->count_all()
        );
    }

}
