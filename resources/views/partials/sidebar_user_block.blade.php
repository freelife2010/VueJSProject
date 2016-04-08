<li class="has-user-block">
    <div id="user-block" class="collapse">
        <div class="item user-block">
            <!-- User picture-->
            <div class="user-block-picture">
                <a href="{{url('/edit-profile/'.Auth::user()->id)}}"
                    data-target="#myModal"
                    data-toggle="modal">
                    <div class="user-block-status">
                            <img src="{{ asset('img/user/userpic.png') }}"
                                 alt="Avatar"
                                 title="Click to edit"
                                 width="60" height="60" class="img-thumbnail img-circle">
                            <div class="circle circle-success circle-lg"></div>
                    </div>
                </a>
            </div>
            <!-- Name and Job-->
            <div class="user-block-info">
                <span class="user-block-name">Hello, {{ $user->name }}</span>
                    <span class="user-block-role">
                        @if($user->isAdmin())
                            Administrator
                        @endif
                        @if($user->isDeveloper())
                            Developer
                        @endif
                    </span>
            </div>
        </div>
    </div>
</li>