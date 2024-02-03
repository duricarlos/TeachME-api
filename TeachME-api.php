<?php

/**
 * Plugin Name: TeachME API
 * Description: This plugin returns if a user has access to the TeachME App and which type of user they are.
 * Version: 1.0
 * Author: Carlos Duri
 * Author URI: https://github.com/duricarlos
 */

add_action('rest_api_init', function () {
    register_rest_route('teachme/v1', '/user/(?P<user_name>[a-zA-Z0-9-]+)', array(
        'methods' => 'GET',
        'callback' => 'teachme_user'
    ));
});

// Function to return the user type from getting in GET the user_name
// This function will call Ultimate Member to get the user role and return it

function teachme_user($data)
{
    $user = get_user_by('login', $data['user_name']);
    $user_id = $user->ID;
    um_fetch_user($user_id);

    // If the user role is subscriber, the user  has access to the app and is PAID user
    if (um_user('role') == 'subscriber') {
        $user_info = array(
            'user_id' => $user_id,
            'user_type' => 'PAID'
        );
        return $user_info;
    }
    // If not, we have to check if is a TRIAL user, checking the pursaches of the product PEC TRIAL and if they have pursached in the last 30 days
    // Get the user email
    $user_email = $user->user_email;
    // Get the product ID of the PEC TRIAL. SKU: 1490
    $product_id = wc_get_product_id_by_sku('1490');
    // Get the orders of the user 
    $query = new WC_Order_Query(array(
        'limit' => -1,
        'return' => 'ids',
        'customer' => $user_id,
        'status' => 'completed'
    ));
    $orders = $query->get_orders(); // Get the orders numbers
    // Check if the orders numbers correspond to the product ID of the PEC TRIAL
    $purchases = array();
    foreach ($orders as $order) { // Get the date of the orders
        $order = wc_get_order($order); // Get the order
        foreach ($order->get_items() as $item_id => $item) { // Get the items of the order
            if ($item->get_product_id() == $product_id) { // If the product ID is the PEC TRIAL
                $purchases[] = $order->get_date_created()->date('Y-m-d H:i:s'); // Get the date of the order
            }
        }
    }
    // If the user has pursached the product in the last 30 days, the user has access to the app and is a TRIAL user
    $date = new DateTime();
    $date->modify('-30 days');
    $date = $date->format('Y-m-d H:i:s');
    if (count($purchases) > 0 && $purchases[0] > $date) { // If the user has pursached the product in the last 30 days
        $user_info = array( // The user has access to the app and is a TRIAL user
            'user_id' => $user_id,
            'user_type' => 'TRIAL'
        );
        return $user_info;
    }
    // If not, the user has no access to the app
    $user_info = array( 
        'user_id' => $user_id,
        'user_type' => 'NO ACCESS'
    );
    return $user_info;
}