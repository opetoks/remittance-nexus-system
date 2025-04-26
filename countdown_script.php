<h4 style="line-height: 30px; color:#ec7063;">

	<strong>
	<?php
		date_default_timezone_set('Africa/Lagos');
		$now = date('Y-m-d H:i:s');

		$wipe_date = strtotime($now);
		$wipe_date2 = strtotime($now);
		$wipe_date = date('M j, Y', $wipe_date);
		$wipe_date2 = date('jS M Y', $wipe_date2);
	?>

		<!-- Display the countdown timer in an element -->
		<span id="text_notice"></span>
		<span id="counter" class="blinking"></span>

		<script>
		// Set the date we're counting down to
		var countDownDate = new Date("<?php echo $wipe_date; ?> 18:30:00").getTime();

		// Update the count down every 1 second
		var x = setInterval(function() {

		  // Get today's date and time
		  var now = new Date().getTime();

		  // Find the distance between now and the count down date
		  var distance = countDownDate - now;

		  // Time calculations for days, hours, minutes and seconds
		  var days = Math.floor(distance / (1000 * 60 * 60 * 24));
		  var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
		  var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
		  var seconds = Math.floor((distance % (1000 * 60)) / 1000);

		  // Display the result in the element with id="demo"
		  document.getElementById("text_notice").innerHTML = "ATTENTION: Posting will be automatically disabled in: ";
		  document.getElementById("counter").innerHTML = hours + "h " + minutes + "m " + seconds + "s ";

		  // If the count down is finished, write some text
		  if (distance < 0) {
			clearInterval(x);
			document.getElementById("text_notice").innerHTML = "ATTENTION: TIME UP! Posting disabled.";
			document.getElementById("counter").innerHTML = "";
		  }
		}, 1000);
		</script>
	</strong>
</h4>
