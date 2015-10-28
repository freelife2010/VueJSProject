<script src="{{ asset('js/main.js') }}"></script>
<div class="modal-header" style="margin-bottom: 10px;">
    <button type="button"
            class="close"
            id="close-modal"
            data-dismiss="modal"
            aria-hidden="true">&times;</button>
    <h4 class="modal-title">@yield('title')</h4>
</div>
<div class="hide preloader" style="text-align: center; margin: 10px;">
    <div class="loader-demo">
        <div class="ball-scale-multiple block-center">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>
</div>
<div class="modal-body" style="overflow: auto">
    @yield('modal_body')
</div>
