document.createElement('slides');
document.createElement('slide');

$(document).ready(function() {
	// When clicking on previous/next or finish buttons
	$('div.SlideControls input[type="submit"]').click(function(obj) {
		// Get the current active slide we are on
		var currentSlide = $(this).closest('slide');
		// Get the current index of the slide
		var currentSlideIndex = $('slides slide').index(currentSlide);
		// If we pressed previous and we're not at the first slide
		if ($(this).attr("value").toLowerCase() == "previous" && (currentSlideIndex - 1) >= 0) {
			$('slides slide:eq(' + (currentSlideIndex - 1) + ')').attr('class', 'active');
			currentSlide.attr('class', '');
		} else {
			//We must have otherwise pressed next or finish
			var serialized = $(this).closest('form').serializeArray();
			serialized.push({name:this.name, value:this.value});
			var ajaxWindow = $('slide.active section.ajax');
			//Show that we're working on processing the information
			ajaxWindow.fadeIn(250, function() {
				//After we show, post the information to the server
				$.ajax({
					type: "POST",
					data: serialized,
					url: window.location,
					error: function(request, status, error) {
						alert(request.responseText);
					},
					success: function(msg) {
						//Once we have a message, fade out
						ajaxWindow.fadeOut(500, function() {
							//After we face, we parse the JSON
							console.log(msg);
							var parse = msg.match(/{.*}/);
							console.log('PARSE?', parse);
							if (!parse) {
								alert('Unable to parse setup response');
								console.error('Unable to match:-', parse);
							}
							var p = JSON.parse(msg);
							if (p["status"] == "ok") {
								if (p["details"] != null) {
									//Ok messages normally don't have messages unless they want to redirect, in this case, do so.
									window.location = p["details"];
								} else {
									//Otherwise we can go to the next slide
									$('slides slide:eq(' + (currentSlideIndex + 1) + ')').attr('class', 'active');
									$('slides slide:eq(' + (currentSlideIndex) + ') *.input.error').slideUp();
									currentSlide.attr('class', 'done');
								}
							} else if (p["status"] == "error") {
								//Otherwise the status is normally an error, in this case we show each error below
								$('*.input.error').each(function(){
									for (x in p["details"]) {
										if ($(this).attr('class').indexOf(p["details"][x]) > 0) {
											$('*.input.error.' + p["details"][x]).slideDown();
											return null;
										}
									}
									$(this).slideUp();
								});
								if (p["details"]["error_message"] !== undefined) {
									alert("Error:" + p["details"]["error_message"]);
								}
							} else {
								alert("Unknown JSON Action: " . p);
							}
						});
					}
				});
			})
		}
		return false;
	});
});
