<?php
error_reporting(0);
//connects to the database and 
$connect = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
if(isset($_POST["id"]))
{//here it queries the db and gets an associative array of the result and checks if recepient is empty
	$query = "SELECT recipient FROM suppliers WHERE id = '".$_POST["id"]."'";
	$qResult=mysqli_query($connect, $query);
	while ($qValues=mysqli_fetch_assoc($qResult)){
		if (is_null($qValues["recipient"])){
			//if empty it opens the file and gets the recipient code out and empties it again so i dont have to loop
			$myfile = "list.json";
			$dat = file_get_contents("$myfile");
			$ya = json_decode($dat);
			$code = $ya->data->recipient_code;
			file_put_contents($myfile, "");
				 
			//then it updates the database with the new data
			$query = "UPDATE suppliers SET recipient ='".$code."' WHERE id = '".$_POST["id"]."'";

			if(mysqli_query($connect, $query)){
			  echo '';
			}
			 //next a new query selects the required parameters for the curl call
			$query2 = "SELECT source, amount, recipient FROM suppliers WHERE id = '".$_POST["id"]."'";


			//converts the result of the query to associative array so php can convert that into json
			$res = mysqli_query($connect, $query2);
			while ($row = mysqli_fetch_assoc($res)) 
			{
				$var= json_encode($row, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_LINE_TERMINATORS);    
			}

			//curl call
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transfer');// where to
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);// get a response as value ch instead of outputting straight
			curl_setopt($ch, CURLOPT_POSTFIELDS, $var);// what im sending
			curl_setopt($ch, CURLOPT_POST, 1);// type of call
			//header
			$headers = array();
			$headers[] = $_ENV['SECRET_KEY'];
			$headers[] = 'Content-Type: application/json';
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			//set the response to variable paid
			$paid = curl_exec($ch);
			curl_close ($ch);

			//show the status of the transfer
			$paid2=json_decode($paid);
			$status = $paid2->status;
			if($status==false){
				die ("<script type='text/javascript'>alert('Insufficient funds');</script>");
			}
			elseif($paid2->message==="Transfer has been queued"){
				echo "Transfer Successful";
			}
			// this block runs if there is already a recepient code in the db
	    }
    	else{
        $query3 = "SELECT source, amount, recipient FROM suppliers WHERE id = '".$_POST["id"]."'";


        //query and encoding the query 
			$res2 = mysqli_query($connect, $query3);
			while ($row1 = mysqli_fetch_assoc($res2)) 
			{
				$var2= json_encode($row1, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_LINE_TERMINATORS);    
			}
			// curl call
			$ch2 = curl_init();

			curl_setopt($ch2, CURLOPT_URL, 'https://api.paystack.co/transfer');
			curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch2, CURLOPT_POSTFIELDS, $var2);
			curl_setopt($ch2, CURLOPT_POST, 1);

			$headerss = array();
			$headerss[] = $_ENV['SECRET_KEY'];
			$headerss[] = 'Content-Type: application/json';
			curl_setopt($ch2, CURLOPT_HTTPHEADER, $headerss);

			$paid3 = curl_exec($ch2);
			$paid4=json_decode($paid3);
			$status2 = $paid4->status;
			if($status2==false){
				die ("<script type='text/javascript'>alert('Insufficient funds');</script>");
			}
				curl_close ($ch2);

				//now if the recepient is invalid and it doesnt pay itll print this, but run the other one otherwise
				if($paid4->message === "Recipient specified is invalid"){
					echo "Payment unsuccessful. Check Recepient details.";
				}
				elseif($paid4->message==="Transfer has been queued"){
					echo "Transfer Successful";
				}
			}
		}
	}

?>



