<?php
/**
 * FiltimPay Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category        FiltimPay
 * @package         filtimpay/filtimpay
 * @version         3.0
 * @author          FiltimPay
 * @copyright       Copyright (c) 2020 FiltimPay
 * @license         http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *
 * EXTENSION INFORMATION
 *
 * FiltimPay API       https://www.filtimPay.com/documentation/en
 *
 */

/**
 * Payment method filtimpay process
 *
 * @author      FiltimPay <support@FiltimPay.com>
 */
class FilPay
{
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_USD = 'USD';
    const CURRENCY_KES = 'KES';
    const CURRENCY_RUB = 'RUB';
    const CURRENCY_RUR = 'RUR';

    private $_api_url = 'https://filtimpay.com/api/';
    private $_checkout_url = 'https://filtimpay.com/api/';
    protected $_supportedCurrencies = array(
        self::CURRENCY_EUR,
        self::CURRENCY_USD,
        self::CURRENCY_KES,
        self::CURRENCY_RUB,
        self::CURRENCY_RUR,
    );
    private $_project_id;
    private $_token;
    private $_server_response_code = null;

    /**
     * Constructor.
     *
     * @param string $project_id
     * @param string $token
     * @param string $api_url (optional)
     *
     * @throws InvalidArgumentException
     */
    public function __construct($project_id, $token, $api_url = null)
    {
        if (empty($project_id)) {
            throw new InvalidArgumentException('project_id is empty');
        }

        if (empty($token)) {
            throw new InvalidArgumentException('token is empty');
        }

        $this->_project_id = $project_id;
        $this->_token = $token;
        
        if (null !== $api_url) {
            $this->_api_url = $api_url;
        }
    }

    /**
     * Return last api response http code
     *
     * @return string|null
     */
    public function get_response_code()
    {
        return $this->_server_response_code;
    }

    /**
     * cnb_form
     *
     * @param array $params
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function cnb_link($params)
    {
        $language = 'ru';
        if (isset($params['language']) && $params['language'] == 'en') {
            $language = 'en';
        }

        $params    = $this->cnb_params($params);
        $data      = $this->encode_params($params);
        $signature = $this->cnb_signature($params);

        return $this->_checkout_url . '?' . build_query(array('data' => $data, 'sign' => $signature));
    }
    
    /**
     * cnb_form raw data for custom form
     *
     * @param $params
     * @return array
     */
    public function cnb_form_raw($params)
    {
        $params = $this->cnb_params($params);
        
        return array(
            'url'       => $this->_checkout_url,
            'data'      => $this->encode_params($params),
            'signature' => $this->cnb_signature($params)
        );
    }

    /**
     * cnb_signature
     *
     * @param array $params
     *
     * @return string
     */
    public function cnb_signature($params)
    {
        $params      = $this->cnb_params($params);
        $token = $this->_token;

        $base      = $this->encode_params($params);
        $signature = md5(json_encode($params).$token);
        //$signature = $token;
        
        return $signature;
    }

    /**
     * cnb_params
     *
     * @param array $params
     *
     * @return array $params
     */
    private function cnb_params($params)
    {
        $params['project_id'] = $this->_project_id;

        if (!isset($params['payment_amount'])) {
            throw new InvalidArgumentException('amount is null');
        }
        if (!isset($params['payment_currency'])) {
            throw new InvalidArgumentException('currency is null');
        }
        if (!in_array($params['payment_currency'], $this->_supportedCurrencies)) {
            throw new InvalidArgumentException('currency is not supported');
        }
        if ($params['payment_currency'] == self::CURRENCY_RUR) {
            $params['payment_currency'] = self::CURRENCY_RUB;
        }
        if (!isset($params['payment_desrciption'])) {
            throw new InvalidArgumentException('description is null');
        }

        return $params;
    }

    /**
     * encode_params
     *
     * @param array $params
     * @return string
     */
    private function encode_params($params)
    {
        return base64_encode(json_encode($params));
    }

    /**
     * decode_params
     *
     * @param string $params
     * @return array
     */
    public function decode_params($params)
    {
        return json_decode(base64_decode($params), true);
    }

    /**
     * str_to_sign
     *
     * @param string $str
     *
     * @return string
     */
    public function str_to_sign($str)
    {
        $signature = base64_encode(sha1($str, 1));

        return $signature;
    }
}
