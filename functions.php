<?php

add_action(
    'template_redirect',
    function () {
        if (isset($_GET['consumer']) && $_GET['consumer'] === 'uddoktapay') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_GET['target'])) {
                    $status = $_POST['status'];
                    $signature = $_POST['signature'];
                    $identifier = $_POST['identifier'];
                    $data = $_POST['data'];
                    $customKey = $data['amount'] . $identifier;
                    $secret = get_option('woocommerce_uddokta_payment_gateway_settings')['uddokta_secret_key'];
                    $mySignature = strtoupper(hash_hmac('sha256', $customKey, $secret));

                    if ($status == "success" && $signature == $mySignature) {
                        $order = wc_get_order($identifier);
                        $order->update_status('completed');
                        $order->send_thankyou_email();
                        WC()->cart->empty_cart();
                        return;
                    }
                }
                exit;
            } else {
                $order = wc_get_order($_GET['order_id']);
                $url = "http://wordpress.mncedu.com/checkout/order-received/" . $order->id . "/?key=" . $order->order_key;
                header("Location: $url");
                exit;
            }
        }
    }
);
