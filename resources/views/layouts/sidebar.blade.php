<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="{{route('index')}}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ URL::asset('build/images/sera_logo.png') }}" alt="" height="auto">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="index" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ URL::asset('build/images/logo-light.png') }}" alt="" height="17">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span>@lang('translation.menu')</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('index') }}" >
                        <i class="ri-dashboard-2-line"></i> <span>@lang('translation.dashboards')</span>
                    </a>
                    
                </li> <!-- end Dashboard Menu -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarApps" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarApps">
                        <i class="ri-apps-2-line"></i> <span>@lang('translation.assessments')</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarApps">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('assessments.index') }}" class="nav-link">@lang('translation.assessments')</a>
                            </li>
                            
                        </ul>
                    </div>
                </li>

                <li class="menu-title"><i class="ri-more-fill"></i> <span>@lang('translation.administration')</span></li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarForms" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarForms">
                        <i class="ri-file-list-3-line"></i> <span>@lang('translation.forms')</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarForms">
                         <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('forms.licensee_templates') }}" class="nav-link"> @lang('translation.list') </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('forms.licensee_templates.create') }}" class="nav-link"> @lang('translation.new_form')</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarPages" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarPages">
                        <i class="ri-pages-line"></i> <span>@lang('translation.licensees')</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarPages">
                         <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('licensees.index') }}" class="nav-link"> @lang('translation.list') </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('licensees.create') }}" class="nav-link"> @lang('translation.new_licensee')</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('licensees.subfolders') }}" class="nav-link">@lang('translation.sub_folders')</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAuth" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarAuth">
                        <i class="ri-account-circle-line"></i> <span>@lang('translation.departments')</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAuth">
                         <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('departments.index') }}" class="nav-link"> @lang('translation.list') </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('departments.create') }}" class="nav-link"> @lang('translation.new_department')</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarLanding" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarLanding">
                        <i class="ri-folder-user-line"></i> <span>@lang('translation.profiles')</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarLanding">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('security.profile_users.index') }}" class="nav-link"> @lang('translation.users') </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('security.roles.index') }}" class="nav-link"> @lang('translation.roles')</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarConfig" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarConfig">
                        <i class="ri-rocket-line"></i> <span>@lang('translation.site_configuration')</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarConfig">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('settings.index') }}" class="nav-link"> @lang('translation.settings') </a>
                            </li>
                        </ul>
                    </div>
                </li>

                
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
