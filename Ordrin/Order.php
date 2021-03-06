<?php

/* Order API */
class Order extends OrdrinApi {
    function __construct($key,$base_url){
      $this->_key = $key;
      $this->base_url = $base_url;
    }

    /**
     * Order a tray of items 
     *
     * @param int     $rID          Ordr.in's restaurant identifier 
     * @param object  $tray         An object containing a collection of TrayItems to be ordered
     * @param float   $tip          Tip to be added to order
     * @param array   $dateTime     Either "ASAP" or the dateTime for order to be delivered
     * @param string  $email        Email address of customer
     * @param string  $fName        First name of customer
     * @param string  $lName        Last name of customer
     * @param object  $addr         Address object for delivery
     * @param object  $credit_card  Credit card object for delivery
     * @param bool    $useAuth      Whether to use user authentication or not
     *
     * @return object An object containing information about the order
     */
    function submit($rID, $tray, $tip, $date_time, $email, $password='', $fName, $lName, $addr, $credit_card, $useAuth = false) {
        if(strtoupper($date_time) == 'ASAP') {
          $date = 'ASAP';
          $time = '';
        } else {
          $date = $this->format_date($date_time);
          $time = $this->format_time($date_time);
        }
        
        $_errors = array();
        
        if(!preg_match('/^\d+$/',$rID)) {
          $_errors[] = 'Order Submit - Validation - Restaurant ID (invalid, must be integer) (' . $rID . ')';
        }
        
        if(!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i", $email)) {
          $_errors[] = 'Order Submit - Validation - Email (invalid) (' . $email . ')';
        }
        
        if(!preg_match('/^\$?\d*(\.\d{2})?$/', $tip) || $tip == '') {
          $_errors[] = 'Order Submit - Validation - Tip (invalid) (' . $tip . ')';
        }
        
        try {
          $tray->validate();
        } catch (OrdrinExceptionBadValue $ex) {
          $_errors[] = $ex.__toString();
        }
        
        try {
          $addr->validate();
        } catch (OrdrinExceptionBadValue $ex) {
          $_errors[] = $ex.__toString();
        }
        
        try {
          $credit_card->validate();
        } catch (OrdrinExceptionBadValue $ex) {
          $_errors[] = $ex.__toString();
        }
        
        if(!empty($_errors)) {
          throw new OrdrinExceptionBadValue($_errors);
        }

        $params =  array(
                                'restaurant_id' => $rID,
                                'tray' => $tray->_convertForAPI(),
                                'tip' => $tip,
                                'delivery_date' => $date,
                                'delivery_time' => $time,
                                'first_name' => $fName,
                                'last_name' => $lName,
                                'addr' => $addr->street,
                                'city' => $addr->city,
                                'state' => $addr->state,
                                'zip' => $addr->zip,
                                'phone' => $addr->phone,
                                'card_name' => $credit_card->name,
                                'card_number' => $credit_card->number,
                                'card_expiry' => $credit_card->expiration,
                                'card_cvc' => $credit_card->cvc,
                                'card_bill_addr' => $credit_card->address->street,
                                'card_bill_addr2' => $credit_card->address->street2,
                                'card_bill_city' => $credit_card->address->city,
                                'card_bill_state' => $credit_card->address->state,
                                'card_bill_zip' => $credit_card->address->zip,
                                'type' => 'res'
                            );

        if(!$useAuth) {
          $params['em'] = $email;
          if(!empty($password)) {
            $params['pw'] = $password;
          }
        }

        return $this->_call_api('POST',
                                array(
                                    'o',
                                    $rID,
                                ),
                                $params,
                                $useAuth
                        );
    }
}
