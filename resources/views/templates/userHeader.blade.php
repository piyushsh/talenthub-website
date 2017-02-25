<script src="{!! asset('js/header_ajax_request.js') !!}"></script>
<header>
    <div class="container">
        <nav class="navbar navbar-default">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                        <span class="sr-only">Toggle Navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="{!! url('profile') !!}"><span class="brand">Talenthub</span></a>
                </div>

                @if(Auth::user())
                    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                        <ul class="nav navbar-nav">
                        <!--li><a href="{!!  url('/')  !!}">Updates</a></li-->
                            <li class="has-dropdown"><a href="#">Information</a>
                                <ul class="dropdown-menu">
                                    <li><a href="{!! url('profile') !!}"><span class="glyphicon glyphicon-user"></span> {!! Session::get(\talenthub\Repositories\SiteSessions::USER_NAME) !!} Profile</a></li>
                                    <li><a href="{!! url('profile/edit') !!}"><span class="glyphicon glyphicon-pencil"></span> Edit Profile</a></li>
                                    <li><a href="{!! url('request-recommendation') !!}"><span class="glyphicon glyphicon-circle-arrow-right"></span> Request Recommendation</a></li>
                                    @if(Session::get(\talenthub\Repositories\SiteSessions::USER_MANAGEMENT_LEVEL) == \talenthub\Repositories\SiteConstants::USER_TALENT_MANAGEMENT_LEVEL_STUDENT)
                                        <li><a href="{!! url('scholarship-process') !!}"><span class="glyphicon glyphicon-hand-right"></span> Scholarship Process Help</a></li>
                                    @endif
                                    @if(Session::get(\talenthub\Repositories\SiteSessions::USER_MANAGEMENT_LEVEL) == \talenthub\Repositories\SiteConstants::USER_TALENT_MANAGEMENT_LEVEL_ASPIRING_PRO)
                                        <li><a href="{!! url('pro-process-help') !!}"><span class="glyphicon glyphicon-hand-right"></span> Professional Process Help</a></li>
                                    @endif
                                    <li><a href="{!! url('FAQ') !!}"><span class="glyphicon glyphicon-question-sign"></span> Help Centre</a></li>
                                    <li><a href="{!! url('settings/privacy') !!}"><span class="glyphicon glyphicon-lock"></span> Privacy Settings</a></li>
                                    <li><a href="{!! url('settings/general') !!}"><span class="glyphicon glyphicon-cog"></span> General Settings</a></li>
                                <!--li><a href="{!! url('profile/edit') !!}"><span class="glyphicon glyphicon-list-alt"></span> Policies</a></li-->
                                </ul>
                            </li>
                            <li><a href="{!!  url('database')  !!}">Database</a></li>
                            <li class="has-dropdown"><a href="#">Notifications <span class="badge">{!! $unReadNotifications > 0 ? $unReadNotifications : "" !!}</span></a>


                                <ul class="dropdown-menu notifications_container">


                                    @foreach($notifications as $key=>$notification)
                                        <?php
                                        $read_unread_status = $notification->status == \talenthub\Notifications::NOTIFICATION_STATUS_READ ? "read" : "unread";
                                        ?>
                                        <li>
                                            @if($notification->notification_type == \talenthub\Notifications::NOTIFICATION_TYPE_RECOMMENDATION)
                                                <?php
                                                $recommendation = \talenthub\Talent\Recommendations::find($notification->source_id)
                                                ?>
                                                <a href="{!! url('profile/'.Session::get(\talenthub\Repositories\SiteSessions::USER_ID).'/curriculumvitae').'?tab=recommendations' !!}" data-notification-id="{!! $notification->notification_id !!}" class="to_notification_link">
                                                    <div class="notification {!! $read_unread_status !!}">
                                                        <div class="row">
                                                            <div class="col-xs-10">
                                                                <p class="notification_heading">Received Recommendation from <strong>{!! $recommendation->name!!}</strong></br>
                                                                    <strong>{!! $recommendation->position !!}</strong><br>
                                                                    <span class="action">View all recommendations</span>
                                                                </p>
                                                            </div>
                                                            <div class="col-xs-2">
                                                                <span class="date">{!! \Carbon\Carbon::parse($notification->notification_on)->diffForHumans(); !!}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            @endif

                                            @if($notification->notification_type == \talenthub\Notifications::NOTIFICATION_TYPE_ENDORSEMENT)
                                                <a href="{!! url('profile/'.Session::get(\talenthub\Repositories\SiteSessions::USER_ID).'/evangelists') !!}" data-notification-id="{!! $notification->notification_id !!}" class="to_notification_link">
                                                    <div class="notification {!! $read_unread_status !!}">
                                                        <div class="row">
                                                            <div class="col-xs-3 image_container">
                                                                <img src="{!! $notification->profile_small_image_path !!}" class="img img-thumbnail">
                                                            </div>
                                                            <div class="col-xs-7">
                                                                <p class="notification_heading"><strong>{!! $notification->first_name. " ". $notification->last_name !!}</strong>
                                                                    Endoresed you<br>
                                                                    <span class="action">View all Evanglists</span></p>
                                                            </div>
                                                            <div class="col-xs-2">
                                                                <span class="date">{!! \Carbon\Carbon::parse($notification->notification_on)->diffForHumans(); !!}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            @endif

                                            @if($notification->notification_type == \talenthub\Notifications::NOTIFICATION_TYPE_FAVOURITE)
                                                <a href="{!! url('profile/'.Session::get(\talenthub\Repositories\SiteSessions::USER_ID).'/favouritedYou') !!}" data-notification-id="{!! $notification->notification_id !!}" class="to_notification_link">
                                                    <div class="notification {!! $read_unread_status !!}">
                                                        <div class="row">
                                                            <div class="col-xs-3 image_container">
                                                                <img src="{!! $notification->profile_small_image_path !!}" class="img img-thumbnail">
                                                            </div>
                                                            <div class="col-xs-7">
                                                                <p class="notification_heading"><strong>{!! $notification->first_name. " ". $notification->last_name !!}</strong>
                                                                    Faviourited you<br>
                                                                    <span class="action">View all Faviourites</span></p>
                                                            </div>
                                                            <div class="col-xs-2">
                                                                <span class="date">{!! \Carbon\Carbon::parse($notification->notification_on)->diffForHumans(); !!}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            @endif

                                        </li>
                                    @endforeach

                                </ul>
                            </li>
                            <li class="has-dropdown"><a href="{!!  url('messages')  !!}">Messages <span class="badge">{!! $messageCount == 0 ? "" : $messageCount !!}</span></a>
                            </li>
                        <!--li class="has-dropdown"><a href="{!!  url('profile/'.Session::get(\talenthub\Repositories\SiteSessions::USER_ID).'/favourites')  !!}">Favourites</a>
                            <ul class="dropdown-menu">
                                <li><a href="{!!  url('profile/'.Session::get(\talenthub\Repositories\SiteSessions::USER_ID).'/favouritedYou')  !!}"><span class="glyphicon glyphicon-star"></span> Who favourited you?</a></li>
                            </ul>
                        </li-->
                        </ul>
                        <ul class="nav navbar-nav navbar-right">
                        <!--li>
                            <div class="form_container">
                                {!! Form::open(['method'=>'post','url'=>'']) !!}
                                <div>
                                    {!! Form::text('search_users',null,['class'=>'form-control']) !!}
                                </div>
                                {!! Form::close() !!}
                                </div>
                            </li-->
                            <li class="has-dropdown"><a href="{!!  url('auth/logout') !!}">Logout</a>
                            <!--div class="dropdown"><span class="caret" data-toggle="dropdown"></span>
                                <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                                    <li><a href="{!!  url('auth/logout') !!}">Logout</a></li>
                                </ul>
                            </div-->
                            </li>
                        </ul>
                    </div>
                @endif
            </div>
        </nav>
    </div>
</header>