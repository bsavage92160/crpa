/*
 * Specific Javascript file associated to edit-payment.php
 */
function cancel() {
	$('#stateInput').val('-1');
	$('#paymentForm').find('input[type=submit]').click();
}