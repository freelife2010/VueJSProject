
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

        $(document).keyup(function(e) {
            if (e.keyCode == 27) closeModalWindow();
        });
    });

    var $body = $('body');

    $body.on('hidden.bs.modal', '.modal', function () {
        $(this).removeData('bs.modal');
        var html = '<div class="modal-header">' +
            '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' +
            '<h4 class="modal-title">Loading data...</h4>' +
            '</div>' +
            '<div class="modal-body" style="text-align: center">' +
            '<div class="loader-demo">' +
            '<div class="ball-scale-multiple block-center">' +
            '<div></div>' +
            '<div></div>' +
            '<div></div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
            '</div>';
        $(this).find('.modal-content').html(html);
        $(this).find('.modal-dialog').css('width', 600);
    });

    $body.on('show.bs.modal', '.modal', function () {
        $(this).find('.modal-dialog').prop('id', 'modal-dialog');
    });
});

function setModalWidth(width) {
    var modal = $('#myModal');
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
            $('.modal-body').hide();
            $('.preloader').removeClass('hide');
        },
        success: function(data) {
            var type = data.error == 0 ? 'success' : 'danger';
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
                    errors_html += '<br/> - '+val;
                });
                var options = {
                    status: 'danger'
                };
                var text = 'There were some problems with your input: ' + errors_html;
                $.notify(text, options || {});
            } else showErrorMessage(data);
        },
        complete: function() {
            $this.prop('disabled', false);
            $('.modal-body').show();
            $('.preloader').addClass('hide');
        }
    });
}

function showMessage(type, alert) {
    if (type == 'success')
        alert = "<em class='fa fa-check'></em> " + alert;
    var options = {
        status: type
    };
    $.notify(alert, options || {});
}

function showErrorMessage(data) {
    var alert = 'Error occurred: ' + data.responseJSON;
    var options = {
        status: 'danger'
    };
    $.notify(alert, options || {});
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

function reloadPage() {
    window.location.reload();
}

function closeModalWindow() {
    $('#close-modal').click();
}

function getUrlParam(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}
