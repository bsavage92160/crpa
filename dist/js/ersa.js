/*
 * Specific Javascript file associated to edit-reservation.php
 */

// Listen for click on toggle checkbox
$('#select-all-mat').click(function(event) {   
    if(this.checked) { $('.chk_mat').each(function() { this.checked = true;});
    } else { $('.chk_mat').each(function() { this.checked = false; }); }
});
$('#select-all-soi').click(function(event) {   
    if(this.checked) { $('.chk_soi').each(function() { this.checked = true;});
    } else { $('.chk_soi').each(function() { this.checked = false; }); }
});
$('#select-all-mid').click(function(event) {   
    if(this.checked) { $('.chk_mid').each(function() { this.checked = true;});
    } else { $('.chk_mid').each(function() { this.checked = false; }); }
});
$('#select-all-rep').click(function(event) {   
    if(this.checked) { $('.chk_rep').each(function() { this.checked = true;});
    } else { $('.chk_rep').each(function() { this.checked = false; }); }
});
$('#select-all-apm').click(function(event) {   
    if(this.checked) { $('.chk_apm').each(function() { this.checked = true;});
    } else { $('.chk_apm').each(function() { this.checked = false; }); }
});
$('select[name=ch]').change(function() {
	$(this).closest('form').submit();
});