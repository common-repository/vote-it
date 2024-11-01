jQuery(document).ready(function() {
    jQuery("#foo").click(function() {
        alert("Hallo Welt");
    });
    jQuery("#addService").click(function() {
    	jQuery("#voteIt-services").append('<tr>'
												+ '<td style="vertical-align:top;">'
													+ '<b>Service-Name:</b><br />'
													+ '<input name="voteit-name[]" type="text" size="20" maxlength="20" value="" />'
													+ '<div class="submit">'
														+ '<input type="submit" name="button" value="Remove service" />'
													+ '</div>'		
												+ '</td>'
												+ '<td>'
													+ '<b>Service Code:</b><br />'
													+ '<textarea name="voteit-code[]" rows="5" cols="100"></textarea>'
												+ '</td>'
											+ '</tr>');
	});
		
});
/*
''
*/