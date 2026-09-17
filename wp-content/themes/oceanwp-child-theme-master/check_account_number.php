<?php
// Include WordPress load file
include_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

if (!empty($_POST["email"]) && !empty($_POST["account_number"])) {
  $email = $_POST["email"];
  $account_number = $_POST["account_number"];

  // Get the user ID based on the email address
  $user = get_user_by("email", $email);
  if ($user) {
    $user_id = $user->ID;

    // Get the registered account number from custom field
    $registered_account_number = get_user_meta($user_id, 'my_custom_field', true);

    global $wpdb;
    $table_name = $wpdb->prefix . 'wc_points_rewards_user_points';

    // Query the total points for the user
    $total_points = $wpdb->get_var($wpdb->prepare("SELECT SUM(points_balance) FROM $table_name WHERE user_id = %d", $user_id));

    // Query the last update date for the user
    $last_update_date = $wpdb->get_var($wpdb->prepare("SELECT MAX(date) FROM $table_name WHERE user_id = %d", $user_id));

    // Debugging statement to check the value of $total_points and $last_update_date
    error_log("Total points for user ID " . $user_id . ": " . $total_points);
    error_log("Last update date for user ID " . $user_id . ": " . $last_update_date);

    if ($total_points !== null && $account_number == $registered_account_number) {
      $formatted_last_update_date = '';
      if (!empty($last_update_date)) {
        // Convert the date to a DateTime object
        $date_time = new DateTime($last_update_date);
        // Format the date as desired
        $formatted_last_update_date = $date_time->format('Y-m-d'); 
      }
      $response = array(
        "status" => "valid",
        "message" => "Account number is valid.",
        "points" => $total_points,
        "last_update_date" => $formatted_last_update_date
      );
    } else {
      $response = array(
        "status" => "invalid",
        "message" => "Account number is not registered.",
        "points" => 0,
        "last_update_date" => null // Add last_update_date key with null value
      );
    }
  } else {
    $response = array(
      "status" => "error",
      "message" => "Email not found.",
      "points" => 0,
      "last_update_date" => null // Add last_update_date key with null value
    );
  }

  // Return the response as JSON
  echo json_encode($response);
  exit;
}
?>