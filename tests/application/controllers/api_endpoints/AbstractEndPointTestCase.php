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

require_once APPPATH . '/vendors/httpful/httpful.phar';

use \Httpful\Request;

/**
 * Abstract integration test case for easy testing of API endpoints.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 */
abstract class AbstractEndPointTestCase extends AbstractItCase
{
    /**
     * API user account username.
     */
    const API_USERNAME = 'test_NL';

    /**
     * API user account password.
     */
    const API_PASSWORD = '12345678901234567890123456789012';

    /**
     * Base path to FreenetIS API.
     *
     * @var string
     */
    protected $base_path;

    /**
     * API account that is used for logging to API.
     *
     * @var Api_account_Model
     */
    protected $api_account;

    /**
     * Defines authentification type that is used for connecting to API.
     *
     * @var string
     */
    protected $auth_method;

    /**
     * Prepare base path and add API account.
     */
    protected function setUp()
    {
        $this->base_path = Settings::get('protocol') . '://'
            . Settings::get('domain') . Settings::get('suffix') . 'cs'
            . Api_Controller::API_BASE_PATH;

        $this->api_account = new Api_account_Model();
        $this->api_account->allowed_paths = '/**'; // allow all
        $this->api_account->enabled = TRUE;
        $this->api_account->readonly = FALSE;
        $this->api_account->username = self::API_USERNAME;
        $this->api_account->token = self::API_PASSWORD;
        $this->api_account->save_throwable();

        $this->auth_method = 'authenticateWith'
                . ucfirst(Settings::get('api_auth_type'));
    }

    /**
     * Remove API account.
     */
    protected function tearDown()
    {
        $this->api_account->delete();
    }

    /**
     * Gets resource on given path, with given parameters, than test the response
     * code and return the response.
     *
     * @param string $path path relative to base path
     * @params array $params optional request parameters
     * @param integer $expected_code optional expected HTTP response code
     * @return \Httpful\Response
     */
    protected function request_get($path = '', $params = array(),
            $expected_code = 0)
    {
        if (!empty($params))
        {
            $params_str_array = array();
            foreach ($params as $name => $value)
            {
                $params_str_array[] = urlencode($name) . '=' . urlencode($value);
            }
            $path .= '?' . implode('&', $params_str_array);
        }

        $rsp = Request::get($this->base_path . $path)
                ->{$this->auth_method}(self::API_USERNAME, self::API_PASSWORD)
                ->send();

        $this->assertNotNull($rsp);

        if ($expected_code > 0)
        {
            $this->assertEquals($expected_code, $rsp->code, 'GET failed expected');
        }

        return $rsp;
    }

}
