<?php include_once("header.php")?>
<?php require("utilities.php")?>
<?php
   require_once "config.php";

   $item_id = $_GET['item_id'];
   $_SESSION['item_id']= $item_id;

   $sql = "SELECT * FROM item WHERE (item_id='$item_id')";

   $result = $link->query($sql);
   $row = $result->fetch_assoc();

   $title = $row["title"];
   $description = $row["description"];
   $current_price = $row["current_price"];
   $num_bids = $row["num_bids"];
   $start_price = $row["start_price"];
   $status = $row["status"];

   $_SESSION['currentPrice'] = $current_price;
   $_SESSION['startPrice'] = $start_price;

   $user_id = $_SESSION['userID'];

  try {
    $end_time = new DateTime($row["end_date"]);
  } catch (Exception $e) {
  }

  // TODO: Note: Auctions that have ended may pull a different set of data,
  //       like whether the auction ended in a sale or was cancelled due
  //       to lack of high-enough bids. Or maybe not.
  
  // Calculate time to auction end:
  $now = new DateTime();
  
  if ($now < $end_time) {
    $time_to_end = date_diff($now, $end_time);
    $time_remaining = ' (in ' . display_time_remaining($time_to_end) . ')';
  }
  
  // TODO: If the user has a session, use it to make a query to the database
  //       to determine if the user is already watching this item.
  //       For now, this is hardcoded.

    $watch = "SELECT * FROM watch_list WHERE user_id='$user_id' and item_id='$item_id'";

    $result = mysqli_query($link, $watch);

    if ($result->num_rows > 0) {
                        $watching = true;
    }
    else {
                        $watching = false;
    }

    $his = "SELECT * FROM historical_auction_price WHERE (item_id='$item_id') ORDER BY auction_id DESC";
    $his_result = $link->query($his);

    if ($_SESSION['account_type'] == 'buyer') {
        $has_session = true;
    }
    else{
        $has_session = false;
    }

?>


<div class="container">

<div class="row"> <!-- Row #1 with auction title + watch button -->
  <div class="col-sm-8"> <!-- Left col -->
    <h2 class="my-3"><?php echo($title); ?></h2>
  </div>
  <div class="col-sm-4 align-self-center"> <!-- Right col -->
<?php
  /* The following watchlist functionality uses JavaScript, but could
     just as easily use PHP as in other places in the code */
  if ($now < $end_time and $has_session == true):
?>
    <div id="watch_nowatch" <?php if ($has_session && $watching) echo('style="display: none"');?> >
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addToWatchlist()">+ Add to watchlist</button>
    </div>
    <div id="watch_watching" <?php if (!$has_session || !$watching) echo('style="display: none"');?> >
      <button type="button" class="btn btn-success btn-sm" disabled>Watching</button>
      <button type="button" class="btn btn-danger btn-sm" onclick="removeFromWatchlist()">Remove watch</button>
    </div>
<?php endif /* Print nothing otherwise */ ?>
  </div>
</div>

<div class="row"> <!-- Row #2 with auction description + bidding info -->
  <div class="col-sm-8"> <!-- Left col with item info -->

    <div class="itemDescription">
    <?php echo($description); ?>
    </div>

  </div>

  <div class="col-sm-4"> <!-- Right col with bidding info -->

    <p>
<?php if ($status == "1"): ?>
     This auction ended <?php echo(date_format($end_time, 'j M H:i')) ?>
      <br>
     Final price: ￡<?php echo($current_price) ?>

<?php elseif ($status == "2"): ?>
     This auction ended <?php echo(date_format($end_time, 'j M H:i')) ?>
      <br>
     Fail to sell at auction

<?php elseif ($status == "0" and $_SESSION['account_type'] == 'buyer' and $his_result->num_rows > 0): ?>
     Auction ends <?php echo(date_format($end_time, 'j M H:i') . $time_remaining) ?></p>  
    <p class="lead">Current bid: £<?php echo(number_format($current_price, 0)) ?></p>

    <!-- Bidding form -->
    <form method="POST" action="place_bid.php">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text">£</span>
        </div>
	    <input name="bid" type="number" class="form-control" id="bid">
      </div>
      <button type="submit" class="btn btn-primary form-control">Place bid</button>
    </form>
<?php elseif ($status == "0" and $_SESSION['account_type'] == 'buyer' and $his_result->num_rows == 0): ?>
    Auction ends <?php echo(date_format($end_time, 'j M H:i') . $time_remaining) ?></p>
    <p class="lead">Start price: £<?php echo(number_format($start_price, 0)) ?></p>

    <!-- Bidding form -->
    <form method="POST" action="place_bid.php">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">£</span>
            </div>
            <input name="bid" type="number" class="form-control" id="bid">
        </div>
        <button type="submit" class="btn btn-primary form-control">Place bid</button>
    </form>
<?php endif ?>

  
  </div> <!-- End of right col with bidding info -->

</div> <!-- End of row #2 -->

    <br>

<div class="row"> <!-- Row #3 with pic + historical price -->
        <div class="col-sm-8"> <!-- Left col with pic -->

            <div class="picture">
                <?php
                $id = $item_id;
                $dbms = 'mysql';
                $host = 'localhost';
                $dbName = 'auction_system';
                $user = 'root';
                $pass = 'root';
                $size = ' width="80%"';

                $dsn = "mysql:host = $host;dbname=$dbName";
                $pdo = new PDO($dsn,$user,$pass);
                $query = "select name,path from images where item_id='$id'";

                $result = $pdo->query($query);
                if($result){
                    $result = $result->fetchAll(2);
                    if(empty($result[0]['path'])) $result[0]['path'] = 0;
                    echo "<img src=".$result[0]['path'].$size.">";

                }
                else{
                    echo "Handle errors";
                }?>
            </div>

        </div>

        <div class="col-sm-4"> <!-- Right col with historical price -->

            <div class="historicalPrice">
                <?php if ($his_result->num_rows > 0) {
                    // output data of each row
                    while($his_row = $his_result->fetch_assoc()) {
                        echo "Historical Bid: ￡" . $his_row["bid_price"]. "<br>". "  Bid Time: " . $his_row["bid_time"]. "<br>"."<br>";
                    }
                } else {
                    echo "No historical price";
                } ?>
            </div>

        </div> <!-- End of right col with historical price -->

    </div> <!-- End of row #3 -->




<?php include_once("footer.php")?>


<script> 
// JavaScript functions: addToWatchlist and removeFromWatchlist.

function addToWatchlist(button) {
  console.log("These print statements are helpful for debugging btw");

  // This performs an asynchronous call to a PHP function using POST method.
  // Sends item ID as an argument to that function.
  $.ajax('watchlist_funcs.php', {
    type: "POST",
    data: {functionname: 'add_to_watchlist', arguments: [<?php echo($item_id);?>]},

    success: 
      function (obj, textstatus) {
        // Callback function for when call is successful and returns obj
        console.log("Success");
        var objT = obj.trim();
 
        if (objT == "success") {
          $("#watch_nowatch").hide();
          $("#watch_watching").show();
        }
        else {
          var mydiv = document.getElementById("watch_nowatch");
          mydiv.appendChild(document.createElement("br"));
          mydiv.appendChild(document.createTextNode("Add to watch failed. Try again later."));
        }
      },

    error:
      function (obj, textstatus) {
        console.log("Error");
      }
  }); // End of AJAX call

} // End of addToWatchlist func

function removeFromWatchlist(button) {
  // This performs an asynchronous call to a PHP function using POST method.
  // Sends item ID as an argument to that function.
  $.ajax('watchlist_funcs.php', {
    type: "POST",
    data: {functionname: 'remove_from_watchlist', arguments: [<?php echo($item_id);?>]},

    success: 
      function (obj, textstatus) {
        // Callback function for when call is successful and returns obj
        console.log("Success");
        var objT = obj.trim();
 
        if (objT == "success") {
          $("#watch_watching").hide();
          $("#watch_nowatch").show();
        }
        else {
          var mydiv = document.getElementById("watch_watching");
          mydiv.appendChild(document.createElement("br"));
          mydiv.appendChild(document.createTextNode("Watch removal failed. Try again later."));
        }
      },

    error:
      function (obj, textstatus) {
        console.log("Error");
      }
  }); // End of AJAX call

} // End of addToWatchlist func
</script>

    <?php $link->close();?>