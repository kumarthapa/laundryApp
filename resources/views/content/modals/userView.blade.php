<!-- Large Modal -->
<div class="modal fade" id="viewRowDetails" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalCenterTitle"></h4>
                <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card h-100 shadow-none">
                            <div class="card-header d-flex justify-content-between pt-0">
                                <div class="card-title d-flex py-3">
                                    <div class="avatar me-3 flex-shrink-0">
                                        <span class="avatar-initial rounded-circle bg-label-warning"><i
                                                class="bx bx-user"></i></span>
                                    </div>
                                    <div
                                        class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-1">
                                        <div class="me-2">
                                            <h5 class="mb-0" id="row-title">Person Name</h5>
                                            <small class="text-muted" id="row-email">example@123.com</small>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="card-body pb-0" style="position: relative;">
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <ul class="m-0 p-0">
                                            <li class="d-flex mb-4 pb-1">
                                                <div
                                                    class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-2">
                                                    <div class="me-2">
                                                        <h6 class="mb-0">Username</h6>
                                                    </div>
                                                    <div class="">
                                                        <h6 class="text-muted mb-0" id="username"></h6>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <ul class="m-0 p-0">
                                            <li class="d-flex mb-4 pb-1">
                                                <div
                                                    class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-2">
                                                    <div class="me-2">
                                                        <h6 class="mb-0">Mobile Number</h6>
                                                    </div>
                                                    <div class="">
                                                        <h6 class="text-muted mb-0" id="contact"></h6>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <ul class="m-0 p-0">
                                            <li class="d-flex mb-4 pb-1">
                                                <div
                                                    class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-2">
                                                    <div class="me-2">
                                                        <h6 class="mb-0">Email</h6>
                                                    </div>
                                                    <div class="">
                                                        <h6 class="text-muted mb-0" id="email"></h6>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <ul class="m-0 p-0">
                                            <li class="d-flex mb-4 pb-1">
                                                <div
                                                    class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-2">
                                                    <div class="me-2">
                                                        <h6 class="mb-0">User Code</h6>
                                                    </div>
                                                    <div class="">
                                                        <h6 class="text-muted mb-0" id="user_code"></h6>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <ul class="m-0 p-0">
                                            <li class="d-flex mb-4 pb-1">
                                                <div
                                                    class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-2">
                                                    <div class="me-2">
                                                        <h6 class="mb-0">Role Name</h6>
                                                    </div>
                                                    <div class="">
                                                        <h6 class="text-muted mb-0" id="role_name"></h6>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <ul class="m-0 p-0">
                                            <li class="d-flex mb-4 pb-1">
                                                <div
                                                    class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-2">
                                                    <div class="me-2">
                                                        <h6 class="mb-0">Status</h6>
                                                    </div>
                                                    <div class="">
                                                        <h6 class="text-muted mb-0" id="status"></h6>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <ul class="m-0 p-0">
                                            <li class="d-flex mb-4 pb-1">
                                                <div
                                                    class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-2">
                                                    <div class="me-2">
                                                        <h6 class="mb-0">Created Date</h6>
                                                    </div>
                                                    <div class="">
                                                        <h6 class="text-muted mb-0" id="created_at"></h6>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div id="UserActivity"></div>
                    <button type="button" class="btn btn-label-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
