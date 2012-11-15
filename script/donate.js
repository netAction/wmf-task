// Convert conversion rate to button labels
// Adjust this function if different pricing needed.
function buttonAmounts(conversion) {
	// set the highes amount (lowermost button) in USD here
	// this will be converted to all other currencies:
	var maxamount=100;

	var conversionInv = Math.round(maxamount*10/conversion);
	var firstDigits = parseInt(conversionInv.toString()[0]+
		conversionInv.toString()[1]);

	// possible button values:
	var possibleButtonTexts = [
		[1,2.5,5,10],
		[1,3,6,12],
		[1,5,10,15],
		[2,5,10,20],
		[3,5,15,30],
		[5,10,20,40],
		[5,10,20,50],
		[5,10,30,60],
		[10,20,40,80],
		[10,25,40,90],
		[10,25,50,100]
	];

	// find button values that fit best:
	var buttonTexts = null;
	$.each(possibleButtonTexts, function() {
		if (buttonTexts == null ||
			Math.abs(this[this.length-1] - firstDigits) <
			Math.abs(buttonTexts[buttonTexts.length-1] - firstDigits)) {
		  buttonTexts = this;
		}
	});

	$.each(buttonTexts,function(index,button) {
		buttonTexts[index] = button * Math.pow(10,
			conversionInv.toString().length-3 // <- exponent
		);
	});
	return buttonTexts;
} // buttonAmounts


// Change the text and behaviour of donate buttons
function changeCurrency() {
	var currency = $('#currency').val();
	var conversion = $('#buttons').data('conversions')[currency];
	var buttonTexts = conversion['buttonTexts'];
	$('#buttons button.directDonation').each(function(index,button) {
		$(this)
			.text(currency+' '+buttonTexts[index])
			.val(buttonTexts[index])
			.attr('title','USD '+Math.round(buttonTexts[index]*conversion['rate']))
			.button('refresh');
	});
} // changeCurrency


// return the url paypal needs
function paypalUrl(currency,amount) {
	var url = 'https://www.paypal.com/cgi-bin/webscr?'+
		'cmd=_donations'+
		'&business=donate%40wikimedia%2eorg'+
		'&currency_code='+currency+
		'&amount='+amount+
		'&item_name=DONATE';
	return url;
} // paypalUrl


// activate functionality for the buttons
function prepareButtons(conversions) {
	$.each(conversions,function(currency,conversion) {
		conversion['buttonTexts']=buttonAmounts(conversion['rate']);
	});
	$('#buttons').data('conversions',conversions); // store conversions
	$('#buttons button.directDonation').click(function() {
		$('#proceedCurrency').html($('#currency').val());
		$('#proceedAmount').html($(this).val());
		$('#proceedDonateButton').attr('href',
			paypalUrl($('#currency').val(),$(this).val())
		);
		$.mobile.changePage('#proceedPage', 'slideup', true, true);
	});
	// "free amount..." button:
	$('#freeAmountButton').click(function() {
		$('#freeAmountCurrency')
			.html($('#currency').val());
		$('#freeAmountAmount')
			.attr('placeholder',$('#currency').val())
			.val('');
		$.mobile.changePage('#freeAmoutPage', 'slideup', true, true);
	});
	// Donate button in free amount popup:
	$('#freeAmountDonateButton').click(function(event) {
		var amount = $('#freeAmountAmount').val();
		amount = parseInt(amount,10);
		if (!amount || amount<1) {
			amount='';
			event.preventDefault();
		} else {
			$('#freeAmountDonateButton').attr('href',
				paypalUrl($('#currency').val(),amount));
		}
		$('#freeAmountAmount').val(amount);
	});
} // prepareButtons


// add currencies to page
function prepareCurrencies(conversions) {
	var sortedCurrencies=[];
	$.each(conversions,function(currency,conversion) {
		sortedCurrencies.push(currency)
	});
	sortedCurrencies.sort();
	$.each(sortedCurrencies,function(index,currency) {
		$('#currency')
			.append('<option>'+currency+'</option>');
	});

	$('#currency')
		.val('USD')
		.selectmenu("refresh")
		.change(function() {
			changeCurrency();
		});
	changeCurrency(); // first run
} // prepareCurrencies


$(function() {
	$.getJSON('api.php', function(conversions) {
		conversions['USD']={rate:1}; // add if missing
		prepareButtons(conversions);
		prepareCurrencies(conversions);
	});
});
