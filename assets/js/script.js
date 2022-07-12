var $ = jQuery;
$(document).ready(function() {
	var name = $("#name").val();
	
	$("div#api_cpanel").hide();
	$("div#api_others").hide();
	$("input#api_identifier").prop('required', true);
	$("input#api_credential").prop('required', true);
 	$("input#confirm_api_credential").prop('required', true);
	
	if(name == "cPanel"){
		$("div#api_identifier").hide();
		$("input#api_identifier").removeAttr('required');
		$("div#api_cpanel").show();
		$("div#api_others").hide();
	 	$("div#api_credential").hide();
	 	$("input#api_credential").removeAttr('required');
	 	$("div#confirm_api_credential").hide();
	 	$("input#confirm_api_credential").removeAttr('required');
	}
	    		
	if(name == "0"){
		$("div#api_identifier").hide();
		$("input#api_identifier").removeAttr('required');
		$("div#api_cpanel").hide();
		$("div#api_others").show();
	 	$("div#api_credential").hide();
	 	$("input#api_credential").removeAttr('required');
	 	$("div#confirm_api_credential").hide();
	 	$("input#confirm_api_credential").removeAttr('required');
	 	$("div#dns_provider_takes_longer_to_propagate").hide();
	}
	            
	$("#name").change(function(){
    	var name = $("#name").val();

    	if(name == "cPanel"){
    		$("div#api_identifier").hide();
    		$("input#api_identifier").removeAttr('required');
    		$("div#api_cpanel").show();
    		$("div#api_others").hide();
    	 	$("div#api_credential").hide();
    	 	$("input#api_credential").removeAttr('required');
    	 	$("div#confirm_api_credential").hide();
    	 	$("input#confirm_api_credential").removeAttr('required');
    	 	$("div#dns_provider_takes_longer_to_propagate").show();        	 	
		}
    	else{
		
    	if(name == "0"){
    		$("div#api_identifier").hide();
    		$("input#api_identifier").removeAttr('required');
    		$("div#api_cpanel").hide();
    		$("div#api_others").show();
    	 	$("div#api_credential").hide();
    	 	$("input#api_credential").removeAttr('required');
    	 	$("div#confirm_api_credential").hide();
    	 	$("input#confirm_api_credential").removeAttr('required');
    	 	$("div#dns_provider_takes_longer_to_propagate").hide();
		}
    	else{
    		$("div#api_identifier").show();
    		$("input#api_identifier").prop('required', true);
    		$("div#api_cpanel").hide();
    		$("div#api_others").hide();
    	 	$("div#api_credential").show();
    	 	$("input#api_credential").prop('required', true);
    	 	$("div#confirm_api_credential").show();
    	 	$("input#confirm_api_credential").prop('required', true);
    	 	$("div#dns_provider_takes_longer_to_propagate").show();
    	}
    	
    	}
	});
	
});