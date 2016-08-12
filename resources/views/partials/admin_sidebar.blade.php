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
                    <span data-localize="sidebar.heading.MORE">Settings</span>
                </li>
                <li class=" {{ $helper->isActive('emails') }}">
                    <a href="#emails" title="Email configuration" data-toggle="collapse">
                        <em class="icon-envelope-open"></em>
                        <span>E-mail requests</span>
                    </a>
                    <ul id="emails" class="nav sidebar-subnav collapse">
                        <li class=" {{ $helper->isActive('emails/auth-content') }}">
                            <a href=" {{ url('emails/auth-content') }}" title="Modify authorization e-mail">
                                <span>Authorization e-mail</span>
                            </a>
                        </li>
                        <li class=" {{ $helper->isActive('emails/confirm-content') }}">
                            <a href=" {{ url('emails/confirm-content') }}" title="Modify confirmation e-mail">
                                <span>Confirmation e-mail</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class=" {{ $helper->isActive('logs') }}">
                    <a href=" {{ url('logs') }}" title="View system logs" target="_blank">
                        <em class="icon-docs"></em>
                        <span>System logs</span>
                    </a>
                </li>
                <li class=" {{ $helper->isActive('revisions') }}">
                    <a href=" {{ url('revisions') }}" title="View modification log">
                        <em class="fa fa-history"></em>
                        <span>Modification log</span>
                    </a>
                </li>
                <li class=" {{ $helper->isActive('users') }}">
                    <a href=" {{ url('users') }}" title="View developers">
                        <em class="icon-users"></em>
                        <span>Developers</span>
                    </a>
                </li>
                <li class=" {{ $helper->isActive('did') }}">
                    <a href=" {{ url('did-admin') }}" title="View DID vendors">
                        <em class="icon-users"></em>
                        <span>DID vendors</span>
                    </a>
                </li>
                <li class=" {{ $helper->isActive('costs') }}">
                    <a href="#costs" title="Manage costs" data-toggle="collapse">
                        <em class="fa fa-money"></em>
                        <span>Costs</span>
                    </a>
                    <ul id="costs" class="nav sidebar-subnav collapse">
                        <li class=" {{ $helper->isActive('costs/did') }}">
                            <a href=" {{ url('costs/did') }}" title="Modify DID costs">
                                <span>DID Cost</span>
                            </a>
                        </li>
                        <li class=" {{ $helper->isActive('costs/sms') }}">
                            <a href=" {{ url('costs/sms') }}" title="Modify SMS Cosrt">
                                <span>SMS Cost</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class=" {{ $helper->isActive('rates') }}">
                    <a href=" {{ url('rates') }}" title="Rates">
                        <em class="fa fa-dollar"></em>
                        <span>Rates</span>
                    </a>
                </li>
                <li class=" {{ $helper->isActive('payments') }}">
                    <a href=" {{ url('payments/admin') }}" title="Payments">
                        <em class="fa fa-credit-card"></em>
                        <span>Payments</span>
                    </a>
                </li>
                <li class=" {{ $helper->isActive('app-config') }}">
                    <a href=" {{ url('app-config') }}" title="Config">
                        <em class="fa fa-cog"></em>
                        <span>Config</span>
                    </a>
                </li>
            </ul>
            <!-- END sidebar nav-->
        </nav>
    </div>
    <!-- END Sidebar (left)-->
</aside>