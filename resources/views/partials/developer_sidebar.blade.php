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
                    <a href="/app/list" title="APP">
                        <em class="icon-list"></em>
                        <span>APP List</span>
                    </a>
                </li>
                <li class=" {{ $helper->isActive('app/dashboard') }}">
                    <a href="#app_list" title="APP Dashboards" data-toggle="collapse">
                        <div class="pull-right label label-info">
                            {{ $helper->getAppCount() }}
                        </div>
                        <em class="icon-speedometer"></em>
                        <span>APP Dashboards</span>
                    </a>
                    <ul id="app_list" class="nav sidebar-subnav collapse">
                        <li class="sidebar-subnav-header">APP Dashboards</li>
                        {!! $helper->generateAppMenu('app/dashboard') !!}
                    </ul>
                </li>
                <li class="nav-heading ">
                    <span data-localize="sidebar.heading.HEADER">Manage APP</span>
                </li>
            </ul>
            <!-- END sidebar nav-->
        </nav>
    </div>
    <!-- END Sidebar (left)-->
</aside>