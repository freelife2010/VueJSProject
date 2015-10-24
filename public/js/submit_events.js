$(document).ready(function () {
    $('button[data-submit=ajax]').on('click', function(e) {
        var form = $(this).parents('form');
        var url = form.attr('action');
        e.preventDefault();
        if ($(this).data('confirm')) {
            if (confirm($(this).data('confirm')))
                postForm($(this), form, url);
        }
        else postForm($(this), form, url);
    });
});

function setModalWidth(width) {
    var modal = $('#awsModal');
    modal.hide();
    modal.find('.modal-dialog').css('width', width);
    modal.show();
}

function postForm($this, form, url) {
    var param    = '';
    var callback = '';
    if ($this.data('callback')) {
        callback = $this.data('callback');
        param = $this.data('callbackParam');
    }
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: url,
        data: form.serialize(),
        beforeSend: function() {
            $this.prop('disabled', true);
        },
        success: function(data) {
            var type = data.error == 0 ? 'success' : 'error';
            showMessage(type, data.alert);
            if (type == 'success')
                closeModalWindow();

            reloadTables();

            if (callback)
                executeCallback(callback, param);
        },
        error: function(data) {
            var errors = data.responseJSON;
            if (errors) {
                var errors_html = '';
                $.each(errors, function(key, val) {
                    console.log(key);
                    form.find('label[for='+key+']').addClass('label-danger');
                    errors_html += '<br/>'+val;
                });
                noty({
                    type: 'error',
                    layout: 'bottomLeft',
                    text: 'There were some problems with your input: ' + errors_html,
                    timeout: 7000,
                    animation: {
                        open: 'animated shake',
                        close: 'animated fadeOut'
                    }
                });
            } else showErrorMessage(data);
        },
        complete: function() {
            $this.prop('disabled', false);
        }
    });
}

function showMessage(type, alert) {
    noty({
        text: alert,
        type: type,
        layout: 'bottomLeft',
        timeout: 4000,
        animation: {
            open: 'animated pulse',
            close: 'animated fadeOut'
        }
    });
}

function showErrorMessage(data) {
    noty({
        type: 'error',
        layout: 'bottomLeft',
        text: 'Error occurred: ' + data.responseJSON,
        timeout: 4000,
        animation: {
            open: 'animated shake',
            close: 'animated fadeOut'
        }
    });
}

function reloadTables() {
    if (typeof(oTable) != 'undefined')
        oTable.ajax.reload();
    if (typeof(oTable_modal) != 'undefined')
        oTable_modal.ajax.reload();
}

function executeCallback(callback, param) {
    window[callback](param);
}

function goToPage(url) {
    window.location.href = url;
}