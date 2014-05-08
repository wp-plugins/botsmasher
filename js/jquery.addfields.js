jQuery(document).ready(function($) {
	$(document).on('click', '.add_field', function() {
		var num     = $('.clonedInput').length; // how many "duplicatable" input fields we currently have
		var newNum  = new Number(num + 1);      // the numeric ID of the new input field being added
		// create the new element via clone(), and manipulate it's ID using newNum value
		var newElem = $('#field' + num).clone().attr('id', 'field' + newNum);
		// manipulate the name/id values of the input inside the new element		
		// insert the new element after the last "duplicatable" input field
		$('#field' + num).after(newElem);
		// enable the "remove" button
		$('.del_field').removeAttr('disabled');
		// business rule: you can only add 16 occurrences
		if (newNum == 16)
			$('.add_field').attr('disabled','disabled');
	});

	$(document).on('click','.del_field', function() {
		var num = $('.clonedInput').length; // how many "duplicatable" input fields we currently have
		$('#field' + num).remove();     // remove the last element
		// enable the "add" button
		$('.add_field').removeAttr('disabled');
		// if only one element remains, disable the "remove" button
		if (num-1 == 1)
			$('.del_field').attr('disabled','disabled');
	});
	$('.del_field').attr('disabled','disabled');
	
	$(document).on( 'click', '.up,.down', function(e){
		e.preventDefault();
		$('.botsmasher input[type=checkbox]').attr('disabled',true);
		var row = $(this).parents("tr:first");
		if ($(this).is(".up")) {
			row.insertBefore(row.prev());
		} else {
			row.insertAfter(row.next());
		}
	});
});