<!-- Datatables-->
<script src="{{ asset('bower_components/datatables/media/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables-colvis/js/dataTables.colVis.js') }}"></script>
<script src="{{ asset('vendor/datatable-bootstrap/js/dataTables.bootstrap.js') }}"></script>
<script src="{{ asset('vendor/datatable-bootstrap/js/dataTables.bootstrapPagination.js') }}"></script>
<script>
    $.fn.dataTableExt.sErrMode = 'throw';
    $.extend( $.fn.dataTable.defaults, {
        "fnDrawCallback": appendFilter()()
    } );

    function appendFilter() {
        return function() {
            setTimeout(function() {
                var $create_button_div = $('#create-btn');
                $('#create-btn-div').append($create_button_div);
                $create_button_div.removeClass('hide');
            }, 50);
        }
    }
</script>