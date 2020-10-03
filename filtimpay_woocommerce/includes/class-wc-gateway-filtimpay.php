<?php
/**
 * Class WC_Gateway_FiltimPay file.
 *
 * @package WooCommerce\Gateways
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * WC_Gateway_FiltimPay Class.
 */
class WC_Gateway_FiltimPay extends WC_Payment_Gateway
{

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        // Setup general properties.
        $this->setup_properties(); 	// local

        // Load the settings.
        $this->init_form_fields(); 	// local
        $this->init_settings();		// parent

        // Get settings.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->instructions = $this->get_option('instructions');
        $this->lang = $this->get_option('lang', 'ru');
        $this->enable_for_methods = $this->get_option('enable_for_methods', array());
        $this->enable_for_virtual = $this->get_option('enable_for_virtual', 'yes') === 'yes';

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
    }

    /**
     * Setup general properties for the gateway.
     */
    protected function setup_properties()
    {
        $this->id = 'filtimpay';
        $this->icon = apply_filters('woocommerce_cod_icon', '');
        $this->method_title = 'FiltimPay';
        $this->method_description = 'Payment service that allows you to make instant payments on the Internet and payment cards Visa, MasterCard all over the world.';
        $this->has_fields = false;
    }

    /**
     * Get gateway icon.
     *
     * @return string
     */
    public function get_icon()
    {

        $icon_html = '<img style="width:125px;" src="https://filtimpay.com/wp-content/uploads/2020/05/n.logo_.black_.png" alt="' . esc_attr__('FiltimPay acceptance mark', 'woocommerce') . '" />';

        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'label' => 'Включить',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => 'FiltimPay - Instant payments worldwide',
                'default' => 'FiltimPay - Instant payments worldwide',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'textarea',
                'description' => 'Payment service that allows you to make instant payments on the Internet and payment cards Visa, MasterCard all over the world.',
                'default' => 'Payment service that allows you to make instant payments on the Internet and payment cards Visa, MasterCard all over the world.',
                'desc_tip' => true,
            ),
            'instructions' => array(
                'title' => __('Instructions', 'woocommerce'),
                'type' => 'textarea',
                'description' => '',
                'default' => '',
                'desc_tip' => true,
            ),
            'project_id' => array(
                'title' => __('API Project Id', 'woocommerce'),
                'type' => 'text',
                'description' => '',
                'default' => '',
                'desc_tip' => true,
                'placeholder' => '',
            ),
            'token' => array(
                'title' => __('API token', 'woocommerce'),
                'type' => 'text',
                'description' => '',
                'default' => '',
                'desc_tip' => true,
                'placeholder' => '',
            ),
            'lang' => array(
                'title' => __('Language of FiltimPay interface', 'woocommerce'),
                'type' => 'select',
                'default' => 'ru',
                'options' => array(
                    'en' => 'en',
                    'ru' => 'ru',
                    'uk' => 'uk'
                )
            ),
            'plugin_details' => array(
                'title' => __('About plugin', 'woocommerce'),
                'type' => 'title',
                /* translators: %s: URL */
                'description' => sprintf(__('В этой версии плагина покупатели смогут только оплачивать товары из корзины вашего интернет магазина выбрав способ оплаты FiltimPay.<br />Но в этой версии нет callback на ваш сайт после оплаты. Сallback – это обращения к вашему сайту по API с сервиса FiltimPay для уведомления Вас, что деньги поступили на счет и тем самым изменяет статус в ваших заказах в админ панели на “Обработка”.<br />Более полную версию плагина с callback вы можете заказать, обратившись по эмейлу указанному на <a href="%s">странице плагина</a>.<hr /> Рекоммендуем вам еще один мой плагин <a href="%s">WebPlus-Gallery</a> - это галерея слайдер. Очень красивая и удобная.', 'woocommerce'), 'https://filtimpay.com', 'https://wordpress.org/plugins/filtimpay/'),
            ),
        );
    }

    /**
     * @param $order_id
     * @return string
     */
    private function getDescription($order_id)
    {
        switch ($this->lang) {
            case 'ru' :
                $description = 'Оплата заказа № ' . $order_id;
                break;
            case 'en' :
                $description = 'Order payment # ' . $order_id;
                break;
            case 'uk' :
                $description = 'Оплата замовлення № ' . $order_id;
                break;
            default :
                $description = 'Order payment № ' . $order_id;
                $description = 'Order payment № ' . $order_id;
        }

        return $description;
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
		$order_data = $order->get_data(); // The Order data
		
        if ($order->get_total() > 0) {
            $this->pending_new_order_notification($order->get_id());
        } else {
            $order->payment_complete();
        }

        // Remove cart.
        WC()->cart->empty_cart();

        require_once(__DIR__ . '/classes/FiltimPay.php');
        $FilPay = new FilPay($this->get_option('project_id'), $this->get_option('token'));
        $url = $FilPay->cnb_link(array(
            //'sandbox'=>'1' // и куда же без песочницы,
          
            "project_id" 	=> $this->get_option('project_id'),
            "order_id" 		=> $order->get_id(),
	        "payment_amount" 	=> $order->get_total(),
	        "payment_currency" 	=> $order->get_currency(),	        
	        "customer_addr_country" => $order->get_currency(),
	        "customer_forename" => $order_data['billing']['first_name'], //$order_data['billing']['first_name']
	        "customer_surname" 	=> $order_data['billing']['last_name'],
	        "customer_company" 	=> $order_billing_company = $order_data['billing']['company'],
	        "customer_email" 	=> $order_data['billing']['email'],
	        "customer_addr_line1" 	=> $order_data['billing']['address_1'],
	        "customer_addr_line2" 	=> $order_data['billing']['address_2'],
	        "customer_addr_city" 	=> $order_data['billing']['city'],
	        "customer_addr_postal_code" => $order_data['billing']['postcode'],
	        "customer_addr_state" 	=> $order_data['billing']['state'],
	        "customer_addr_country" => $order_data['billing']['country'],
	        "customer_phone" 	=> $order_data['billing']['phone'],
	        "language" 		=> $this->get_option('lang'),
	        "success_link" 		=> $this->get_return_url($order),
	        "error_link" 		=> $this->get_return_url($order),
	        'language' 		=> $this->get_option('lang'),            
	        "sandbox" 		=> "1",
	        "payment_gate" 	=> "14", // auto forwarding to CyberSource
	        "get_url" 		=> "0", // auto forwarding to payment page
	        "payment_desrciption" => $this->getDescription($order->get_id())	        
        ));
                
        return array(
            'result' => 'success',
            'redirect' => $url,
        );
    }

    /**
     * Output for the order received page.
     */
    public function thankyou_page()
    {
        if ($this->instructions) {
            echo wp_kses_post(wpautop(wptexturize($this->instructions)));
        }
    }

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order Order object.
     * @param bool $sent_to_admin Sent to admin.
     * @param bool $plain_text Email format: plain text or HTML.
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false)
    {
        if ($this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method()) {
            echo wp_kses_post(wpautop(wptexturize($this->instructions)) . PHP_EOL);
        }
    }

    /**
     * @param $order_id
     */
    private function pending_new_order_notification($order_id)
    {
        $order = wc_get_order($order_id);

        // Only for "pending" order status
        if (!$order->has_status('pending')) return;

        // Get an instance of the WC_Email_New_Order object
        $wc_email = WC()->mailer()->get_emails()['WC_Email_New_Order'];


        $wc_email->settings['subject'] = __('{site_title} - Новый заказ ({order_number}) - {order_date}');
        $wc_email->settings['heading'] = __('Новый заказ');
        // $wc_email->settings['recipient'] .= ',name@email.com'; // Add email recipients (coma separated)

        // Send "New Email" notification (to admin)
        $wc_email->trigger($order_id);
    }
}
