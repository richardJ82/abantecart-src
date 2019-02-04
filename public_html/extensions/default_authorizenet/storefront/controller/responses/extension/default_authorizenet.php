<?php

/**
 * Class ControllerResponsesExtensionAuthorizeNet
 *
 * @property  ModelExtensionDefaultAuthorizeNet $model_extension_default_authorizenet
 */
class ControllerResponsesExtensionDefaultAuthorizeNet extends AController
{

    public function main()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('default_authorizenet/default_authorizenet');

        $data = $this->buildCCForm();
        $this->view->batchAssign($data);

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        //load creditcard input validation
        $this->document->addScriptBottom($this->view->templateResource('/javascript/credit_card_validation.js'));

        $this->processTemplate('responses/default_authorizenet.tpl');
    }

    public function form_verification()
    {

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('default_authorizenet/default_authorizenet');

        $data = $this->buildCCForm();
        $this->view->batchAssign($data);

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        //load creditcard input validation
        $this->document->addScriptBottom($this->view->templateResource('/javascript/credit_card_validation.js'));

        $this->processTemplate('responses/default_authorizenet_verification.tpl');
    }

    public function buildCCForm()
    {
        $data = array();
        //need an order details
        $this->loadModel('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $data['payment_address'] = $order_info['payment_address_1']." ".$order_info['payment_address_2'];
        $data['edit_address'] = $this->html->getSecureURL('checkout/address/payment');

        $data['text_credit_card'] = $this->language->get('text_credit_card');
        $data['text_wait'] = $this->language->get('text_wait');

        $csrftoken = $this->registry->get('csrftoken');
        $data['csrfinstance'] = HtmlElementFactory::create(array(
            'type'  => 'hidden',
            'name'  => 'csrfinstance',
            'value' => $csrftoken->setInstance(),
        ));
        $data['csrftoken'] = HtmlElementFactory::create(array(
            'type'  => 'hidden',
            'name'  => 'csrftoken',
            'value' => $csrftoken->setToken(),
        ));

        $data['entry_cc_owner'] = $this->language->get('entry_cc_owner');
        $data['cc_owner_firstname'] = HtmlElementFactory::create(array(
            'type'        => 'input',
            'name'        => 'cc_owner_firstname',
            'placeholder' => 'First name',
            'value'       => $order_info['payment_firstname'],
        ));

        $data['cc_owner_lastname'] = HtmlElementFactory::create(array(
            'type'        => 'input',
            'name'        => 'cc_owner_lastname',
            'placeholder' => 'Last name',
            'value'       => $order_info['payment_lastname'],
        ));

        $data['entry_cc_number'] = $this->language->get('entry_cc_number');
        $data['cc_number'] = HtmlElementFactory::create(array(
            'type'        => 'input',
            'name'        => 'cc_number',
            'attr'        => 'autocomplete="off"',
            'placeholder' => $this->language->get('entry_cc_number'),
            'value'       => '',
        ));

        $data['entry_cc_expire_date'] = $this->language->get('entry_cc_expire_date');

        $data['entry_cc_cvv2'] = $this->language->get('entry_cc_cvv2');
        $data['entry_cc_cvv2_short'] = $this->language->get('entry_cc_cvv2_short');
        $data['cc_cvv2_help_url'] = $this->html->getURL('r/extension/default_authorizenet/cvv2_help');

        $data['cc_cvv2'] = HtmlElementFactory::create(array(
            'type'  => 'input',
            'name'  => 'cc_cvv2',
            'value' => '',
            'style' => 'short',
            'attr'  => ' autocomplete="off" ',
        ));

        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['button_back'] = $this->language->get('button_back');

        $months = array();

        for ($i = 1; $i <= 12; $i++) {
            $months[sprintf('%02d', $i)] = sprintf('%02d - ', $i).strftime('%B', mktime(0, 0, 0, $i, 1, 2000));
        }
        $data['cc_expire_date_month'] = HtmlElementFactory::create(
            array(
                'type'    => 'selectbox',
                'name'    => 'cc_expire_date_month',
                'value'   => sprintf('%02d', date('m')),
                'options' => $months,
                'style'   => 'input-medium short',
            ));

        $today = getdate();
        $years = array();
        for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
            $years[strftime('%Y', mktime(0, 0, 0, 1, 1, $i))] = strftime('%Y', mktime(0, 0, 0, 1, 1, $i));
        }
        $data['cc_expire_date_year'] = HtmlElementFactory::create(array(
            'type'    => 'selectbox',
            'name'    => 'cc_expire_date_year',
            'value'   => sprintf('%02d', date('Y') + 1),
            'options' => $years,
            'style'   => 'short',
        ));

        $back = $this->request->get['rt'] != 'checkout/guest_step_3'
                ? $this->html->getSecureURL('checkout/payment')
                : $this->html->getSecureURL('checkout/guest_step_2');
        $data['back'] = HtmlElementFactory::create(array(
            'type'  => 'button',
            'name'  => 'back',
            'text'  => $this->language->get('button_back'),
            'style' => 'button',
            'href'  => $back,
            'icon'  => 'icon-arrow-left',
        ));

        $data['submit'] = HtmlElementFactory::create(array(
            'type'  => 'button',
            'name'  => 'authorizenet_button',
            'text'  => $this->language->get('button_confirm'),
            'style' => 'button btn-orange pull-right',
            'icon'  => 'icon-ok icon-white',
        ));

        $this->loadModel('extension/default_authorizenet');
        $cust_ccs = array();
        if ($this->config->get('default_authorizenet_save_cards_limit') != 0) {
            //if customer see if we have authorizenet customer object created for credit card saving
            if ($this->customer->getId() > 0){
                $customer_authorizenet_id = $this
                    ->model_extension_default_authorizenet
                    ->getAuthorizeNetCustomerID($this->customer->getId());

                //load credit cards list
                if ($customer_authorizenet_id) {
                    try {
                        $cc_list = $this->model_extension_default_authorizenet->getAuthorizeNetCustomerCCs(
                            $customer_authorizenet_id,
                            $this->customer->getId(),
                            $order_info
                        );
                        if ($cc_list) {
                            $data['saved_cc_list'] = HtmlElementFactory::create(array(
                                'type'    => 'selectbox',
                                'name'    => 'use_saved_cc',
                                'value'   => '',
                                'options' => $cc_list,
                            ));
                        }
                    }catch(AException $e){

                    }

                }
            }
            //build credit card selector
            //option to save creditcard if limit is not reached
            if ($this->customer->isLogged()
                && count($cust_ccs) < $this->config->get('default_authorizenet_save_cards_limit')
            ) {
                $data['save_cc'] = HtmlElementFactory::create(array(
                    'type'    => 'checkbox',
                    'name'    => 'save_cc',
                    'value'   => '0',
                    'checked' => false,
                ));
            }
        }

        return $data;
    }

    public function cvv2_help()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $this->loadLanguage('default_authorizenet/default_authorizenet');

        $image = '<img src="'.$this->view->templateResource('/image/securitycode.jpg')
                .'" alt="'.$this->language->get('entry_what_cvv2').'" />';

        $this->view->assign('title', '');
        $this->view->assign('description', $image);

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->processTemplate('responses/content/content.tpl');
    }

    public function send()
    {
        if ( ! $this->csrftoken->isTokenValid()) {
            $json['error'] = $this->language->get('error_unknown');
            $this->load->library('json');
            $this->response->setOutput(AJson::encode($json));

            return;
        }

        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('default_authorizenet/default_authorizenet');
        //validate input
        $post = $this->request->post;
        //check if saved cc mode is used
        if ( ! $post['use_saved_cc']) {
            if (empty($post['cc_owner_firstname']) && empty($post['cc_owner_lastname'])) {
                $json['error'] = $this->language->get('error_incorrect_name');
            }
            if (empty($post['dataValue']) || empty($post['dataDescriptor'])) {
                $json['error'] = $this->language->get('error_system');
            }
        }

        if (isset($json['error'])) {
            $csrftoken = $this->registry->get('csrftoken');
            $json['csrfinstance'] = $csrftoken->setInstance();
            $json['csrftoken'] = $csrftoken->setToken();
            $this->load->library('json');
            $this->response->setOutput(AJson::encode($json));

            return null;
        }

        $this->loadModel('checkout/order');
        $this->loadModel('extension/default_authorizenet');
        $this->loadLanguage('default_authorizenet/default_authorizenet');
        $order_id = $this->session->data['order_id'];

        $order_info = $this->model_checkout_order->getOrder($order_id);
        // currency code
        $currency = $this->currency->getCode();
        // order amount without decimal delimiter
        $amount = round($order_info['total'], 2);

        // Card owner name
        $card_firstname = html_entity_decode($post['cc_owner_firstname'], ENT_QUOTES, 'UTF-8');
        $card_lastname = html_entity_decode($post['cc_owner_lastname'], ENT_QUOTES, 'UTF-8');

        ADebug::checkpoint('AuthorizeNet Payment: Order ID '.$order_id);

        $pd = array(
            'amount'             => $amount,
            'currency'           => $currency,
            'order_id'           => $order_id,
            'cc_owner_firstname' => $card_firstname,
            'cc_owner_lastname'  => $card_lastname,
            'save_cc'            => $post['save_cc'],
            'use_saved_cc'       => $post['use_saved_cc'],
            'dataDescriptor'     => $post['dataDescriptor'],
            'dataValue'          => $post['dataValue'],
        );

        $p_result = $this->model_extension_default_authorizenet->processPayment($pd);

        ADebug::variable('Processing payment result: ', $p_result);
        if ($p_result['error']) {
            // transaction failed
            $json['error'] = (string)$p_result['error'];
            if ($p_result['code']) {
                $json['error'] .= ' ('.$p_result['code'].')';
            }

        } else {
            if ($p_result['paid']) {
                $json['success'] = $this->html->getSecureURL('checkout/success');
            } else {
                //Unexpected result
                $json['error'] = $this->language->get('error_system').'(abc)';
            }
        }

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        if (isset($json['error']) && $json['error']) {
            $csrftoken = $this->registry->get('csrftoken');
            $json['csrfinstance'] = $csrftoken->setInstance();
            $json['csrftoken'] = $csrftoken->setToken();
        }
        $this->load->library('json');
        $this->response->setOutput(AJson::encode($json));
    }

    public function delete_card()
    {
        //init controller data
        $this->extensions->hk_InitData($this, __FUNCTION__);
        $this->loadLanguage('default_authorizenet/default_authorizenet');

        //validate input
        $post = $this->request->post;
        $json = array();
        if (empty($post['use_saved_cc'])) {
            $json['error'] = $this->language->get('error_system');
        }
        if ( ! $this->customer->getId()) {
            $json['error'] = $this->language->get('error_system');
        }
        if (isset($json['error'])) {
            $this->load->library('json');
            $this->response->setOutput(AJson::encode($json));

            return null;
        }

        $this->loadModel('extension/default_authorizenet');

        $customer_authorizenet_id = $this
            ->model_extension_default_authorizenet
            ->getAuthorizeNetCustomerID($this->customer->getId());
        $deleted = $this
            ->model_extension_default_authorizenet
            ->deleteCreditCard(
                $post['use_saved_cc'],
                $customer_authorizenet_id
            );

        if ( ! $deleted) {
            // transaction failed
            $json['error'] = $this->language->get('error_system');
        } else {
            //basically reload the page
            $json['success'] = $this->html->getSecureURL('checkout/confirm');
        }

        //init controller data
        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->load->library('json');
        $this->response->setOutput(AJson::encode($json));
    }
}

