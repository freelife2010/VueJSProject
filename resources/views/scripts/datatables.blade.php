<!-- Datatables-->
<script src="{{ asset('vendor/datatables/media/js/jquery.dataTables.min.js') }}"></script>
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
                var filter_div = $('#table_filter');
                var $filter_input = filter_div.find('input');
                filter_div.text('');
                filter_div.append($filter_input);
                $create_button_div.removeClass('hide');
            }, 10);
        }
    }
</script>