<?php
session_start();
// TODO: Extract $_POST variables, check they're OK, and attempt to make a bid.
// Notify user of success/failure and redirect/give navigation options.
require_once "config.php";
require_once "send_mail.php";

$itemID = $_SESSION['item_id'];
$userID = $_SESSION['userID'];
$now = new DateTime();
$currentPrice = $_SESSION['currentPrice'];

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $bid = $_POST["bid"];
    if ($bid>$currentPrice) {

        $sql = "UPDATE item SET current_price = '$bid', buyer_id = '$userID',
            num_bids=num_bids+1 WHERE item_id='$itemID'";

        if (mysqli_query($link, $sql)) {
            echo "The new record is successfully inserted.";
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($link);
        }

        $history = "INSERT INTO historical_auction_price (item_id, user_id, bid_price,bid_time)
                    VALUES ('$itemID','$userID','$bid',now())";


        if (mysqli_query($link, $history)) {
            echo "Historical records are inserted successfully.";
            echo('<div class="text-center">Your bid are now placed successfully! You will be redirected shortly.</div>');
            header("refresh:3;url=browse.php");
        } else {
            echo "Error: " . $history . "<br>" . mysqli_error($link);
        }


        // Get item title from item table use item_id
        $titles = "SELECT title FROM item WHERE item_id = '$itemID'";
        $title_result = $link->query($titles);
        while ($row = mysqli_fetch_array($title_result)) {
            $title = $row['title'];


            // Send mail to the bidder.
            $emails = "SELECT email FROM user WHERE user_id = '$userID'";
            $email_result = $link->query($emails);
            while ($row = mysqli_fetch_array($email_result)) {
                $bidder_email = $row['email'];
                $subject = "Bid Successful";
                $body = "Hi there, <br/> <br/> You successfully bid on the " . $title . ".<br/> The curent price of " . title . " is £" . $bid . ".<br/> <br/> Kind regards, <br/> Simple Click Marketing Team <br/>";
                send_email($bidder_email, $subject, $body);
            }


            // Get the email addresses of historical bidders.
            $sql_email = "SELECT DISTINCT u.email
                          FROM historical_auction_price AS h Left JOIN user AS u ON u.user_id = h.user_id
                          WHERE h.item_id = '$itemID'";
            $result = $link->query($sql_email);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // for each user, get their emails, and send this to them
                    $historical_bidder_email = $row["email"];
                    // Email title
                    $subject = "Update on the " . $title . " that you previously bedded on";
                    //Mail body
                    $body = "Hi there, <br/> <br/> The current price of " . $title . " is £" . $bid . ".<br/>If you are still interested, please make a new bid. <br/> <br/> Kind regards, <br/> Simple Click Marketing Team <br/>";
                    // send email to historical bidders.
                    send_email($historical_bidder_email, $subject, $body);

                }
            }


            // Get the user email addresses on watch list.
            $sql_watch_email = "SELECT DISTINCT u.email FROM watch_list AS w
                                LEFT JOIN user AS u ON u.user_id = w.user_id 
                                WHERE w.item_id = '$itemID' AND
                                w.user_id != (SELECT user_id FROM historical_auction_price 
                                WHERE item_id = '$itemID')";
            $result = $link->query($sql_email);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // for each user, get their emails, and send this to them
                    $watchlist_email = $row["email"];
                    // Email title
                    $subject = "Update on the " . $title . " that you are watching";
                    //Mail body
                    $body = "Hi there, <br/> <br/> There is a update on the current price for " . $title . ". <br/> The price currently is £" . $bid . ". <br/> <br/> Kind regards, <br/> Simple Click Marketing Team <br/>";
                    send_email($watchlist_email, $subject, $body);

                }
            }
        }
        mysqli_close($link);
    }
    else{
        echo('<div class="text-center">Your price is lower than current price, try again.</div>');
        header("refresh:5;url=browse.php");
    }

}


?>