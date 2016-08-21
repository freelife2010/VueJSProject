<aside class="aside">
    <!-- START Sidebar (left)-->
    <div class="aside-inner">
        <nav data-sidebar-anyclick-close="" class="sidebar">
            <!-- START sidebar nav-->
            <ul class="nav">
                <!-- START user info-->
                @include('partials.sidebar_user_block')
                <!-- END user info-->
                <!-- Iterates over all sidebar items-->
                <li class="nav-heading ">
                    <span data-localize="sidebar.heading.HEADER">Main menu</span>
                </li>
                <li class=" {{ $helper->isActive('app/list') }}">
                    <a href="{{ url('app/list') }}" title="APP">
                        <em class="icon-list"></em>
                        <span>APP List</span>
                    </a>
                </li>
                <li class=" {{ $helper->isActive('app/dashboard') }}">
                    <a href="#app_list" title="APP Dashboard" data-toggle="collapse">
                        <div class="pull-right label label-info">
                            {{ $helper->getAppCount() }}
                        </div>
                        <em class="icon-speedometer"></em>
                        <span>APP Dashboard</span>
                    </a>
                    <ul id="app_list" class="nav sidebar-subnav collapse">
                        <li class="sidebar-subnav-header">APP Dashboard</li>
                        {!! $helper->generateDashboardAppMenu('app/dashboard') !!}
                    </ul>
                </li>
                <li class=" {{ $helper->isActive('cdr') }}">
                    <a href="{{ url('cdr') }}" title="CDR">
                        <em class="icon-call-out"></em>
                        <span>View CDR</span>
                    </a>
                </li>
                <li class=" {{ $helper->isActive('payments') }}">
                    <a href="{{ url('payments') }}" title="CDR">
                        <em class="fa fa-credit-card"></em>
                        <span>Payment History</span>
                    </a>
                </li>
                <li class=" {{ $helper->isActive('usage-history') }}">
                    <a href="{{ url('usage-history') }}" title="Usage History">
                        <em class="fa fa-history"></em>
                        <span>Usage History</span>
                    </a>
                </li>
		<li class=" {{ $helper->isActive('credit-history') }}">
                    <a href="{{ url('credit-history') }}" title="Credit History">
                        <em class="fa fa-credit-card"></em>
                        <span>Credit History</span>
                    </a>
                </li>
                <li class=" {{ $helper->isActive('app-config') }}">
                    <a href="#app_config" title="APP Config" data-toggle="collapse">
                        <em class="fa fa-cog"></em>
                        <span>Config</span>
                    </a>
                    <ul id="app_config" class="nav sidebar-subnav collapse">
                        <li class="">
                            <a href="{{ url('app-config/mass-call') }}" title="Enable Mass Call API">
                                <span>Enable Mass Call API</span>
                            </a>
                        </li>
                        <li class="">
                            <a href="{{ url('app-config/google-api') }}" title="Manage Google API">
                                <span>Manage API</span>
                            </a>
                        </li>
                    </ul>
                </li>
                {!! $helper->generateManageAppMenu() !!}
            </ul>
            <!-- END sidebar nav-->
        </nav>
    </div>
    <!-- END Sidebar (left)-->
</aside>
